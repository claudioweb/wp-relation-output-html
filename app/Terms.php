<?php

namespace WpRloutHtml;

use stdClass;
use WpRloutHtml\Helpers;
use WpRloutHtml\Posts;
use WpRloutHtml\Modules\S3;
use WpRloutHtml\Modules\Ftp;
use WpRloutHtml\Modules\Git;
use WpRloutHtml\Essentials\Curl;

Class Terms Extends App {
	
	public function __construct(){
		
		// verifica alterações de terms
		add_action( 'pre_delete_term', array($this, 'delete_folder'), 1, 1);

		$taxonomies = explode(',', Helpers::getOption('taxonomies_rlout'));
		foreach($taxonomies as $tax){
			add_action( 'created_'.$tax, array($this, 'create_folder'), 1, 1);
			add_action( 'edited_'.$tax, array($this, 'create_folder'), 1, 1);
		}
	}
	
	public function create_folder($term_id){
		
		$term = get_term($term_id);
		if($term->slug!=$_POST['slug']){
			$this->delete_folder($term_id);
		}
		
		if($_POST['static_output_html']){
			
			$taxonomies = explode(',', Helpers::getOption('taxonomies_rlout'));
			
			if(in_array($term->taxonomy, $taxonomies)){
				
				$slug_old = $term->slug;
				$slug_new = $_POST['slug'];
				
				if($slug_old!=$slug_new){
					
					$term->slug = $slug_new;
				}
				Curl::generate($term);
				Helpers::subfiles_generate('xml');
				Terms::api($term);
			}

		}
	}
	
	public function delete_folder($term_id){
		
		$term = get_term($term_id);
		
		$taxonomies = explode(',', Helpers::getOption('taxonomies_rlout'));
		
		if(in_array($term->taxonomy, $taxonomies)){
			
			$url = str_replace(site_url(), '', get_term_link($term));
			
			$dir_base = Helpers::getOption('path_rlout') . $url;
			
			Helpers::rrmdir($dir_base);
			
			S3::remove_file($dir_base . '/index.html');
			Helpers::subfiles_generate('xml');
		}
	}

	static function api($term_update=null, $upload=true, $last_post=null){
		$taxonomies = explode(",", Helpers::getOption('taxonomies_rlout'));
		if(!empty($term_update->taxonomy)){
			if(in_array($term_update->taxonomy,$taxonomies)){
				$taxonomies = array($term_update->taxonomy);
			}else{
				$taxonomies = array();
			}
		}

		$urls = array();
		
		foreach($taxonomies as $tax){
			
			$terms = get_terms(array("taxonomy"=>$tax, 'hide_empty' => false));

			$dir_base = Helpers::getOption('path_rlout');

			if( realpath($dir_base) === false ){
				wp_mkdir_p($dir_base);
			}
			
			$replace_url = Helpers::getOption('replace_url_rlout');
			if(empty($replace_url)){
				$replace_url = site_url().'/html';
			}
			
			foreach ($terms as $key => $term) {

				if(empty($term_update) || $term->term_id==$term_update->term_id) {
					
					$term_link = get_term_link($term);
					
					if($last_post){
						$url_link_static = str_replace(site_url(), $replace_url, $term_link);

						$term_json_exist = Curl::get($url_link_static.'index.json');

						$posts = array();

						$decode_json = json_decode($term_json_exist);
						if(property_exists($decode_json, 'posts')){
							if(is_array($decode_json->posts)){
								
								$new_post = Posts::new_params($last_post, true);
								$posts = $decode_json->posts;
								$create_post = true;
								foreach($posts as $key_post => $post){
									if($post->ID==$last_post->ID){
										$posts[$key_post] = $new_post;
										$create_post = false;
										if($last_post->post_status!='publish'){
											unset($posts[$key_post]);
										}
										break;
									}
								}

								if($create_post==true && $last_post->post_status=='publish'){
									array_unshift($posts, $new_post);
								}
							}
						}
					}

					$term = Terms::object_term($term, true, $posts);
					
					$term_link = str_replace(site_url(), $dir_base, $term_link);

					$new_folder = str_replace($dir_base, '', $term_link);
					$new_folder_explode = explode('/', $new_folder);
					$folder_create = '';
					foreach($new_folder_explode as $new_folder){
						$folder_create = $folder_create.'/'.$new_folder;
						if(realpath($dir_base . $folder_create) === false){
							wp_mkdir_p($dir_base . $folder_create);
						}
					}

					$file_raiz = $term_link.'index.json';
					$file = fopen($file_raiz, "wa+");
					
					$response = json_encode($term , JSON_UNESCAPED_SLASHES);

					fwrite($file, $response);
					fclose($file);

					if($upload==true){
						S3::upload_file($file_raiz, Helpers::getOption('s3_cloudfront_auto_rlout'));
					}
					
					if($term){
						$urls[] = str_replace($dir_base,$replace_url,$file_raiz);
					}

					unset($term->posts);
				}
			}
			
			$response = json_encode($terms , JSON_UNESCAPED_SLASHES);
			
			$file_raiz = $dir_base . '/'.$tax.'.json';
			
			$file = fopen($file_raiz, "wa+");
			
			fwrite($file, $response);
			fclose($file);

			S3::upload_file($file_raiz, true);
			
			$urls[] = str_replace($dir_base,$replace_url,$file_raiz);
		}
		
		return $urls;
	}

	static function object_term($object, $show_posts=true, $posts = array()){
		
		$url = get_term_link($object);
		$ignore_json_rlout = explode(',', Helpers::getOption('ignore_json_rlout'));
		if(empty(in_array($url, $ignore_json_rlout))){
			
			unset($object->term_group);
			unset($object->term_taxonomy_id);
			unset($object->parent);
			unset($object->filter);
			
			$object = Helpers::url_json_obj($object);
			
			if($show_posts){

				if(empty($posts)){
					$post_types = explode(",", Helpers::getOption('post_types_rlout'));
					$posts = array();

					// foreach($post_types as $post_type):
						$post = new stdClass;
						$post->post_type = $post_types;
						$posts = Posts::get_post_json($post, array(), $object);
					// endforeach;

					// if(!empty($posts))
					// 	die(var_dump($posts));
				}
				
				$object->posts = $posts;
			}
			
			$metas = get_term_meta($object->term_id);
			$metas_arr = array();
			foreach ($metas as $key_mm => $meta) {
				$thumb = wp_get_attachment_image_src($meta[0], 'full');
				if(!empty($thumb)){
					$sizes = get_intermediate_image_sizes();
					foreach ($sizes as $key_sz => $size) {
						$metas_arr[$key_mm][] = wp_get_attachment_image_src($meta[0], $size);
					}
				}else{
					$metas_arr[$key_mm] = $meta;
				}
			}
			
			$object->metas = $metas_arr;
			
			return $object;
		}
	}
}