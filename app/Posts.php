<?php

namespace WpRloutHtml;

use WpRloutHtml\Essentials\Curl;
use WpRloutHtml\Modules\Git;
use WpRloutHtml\Modules\S3;
use WpRloutHtml\Modules\Ftp;
use WpRloutHtml\Helpers;
use WpRloutHtml\Terms;

Class Posts {

	public $is_block_editor = false;
	
	public function __construct() {	
		if(!function_exists('get_sample_permalink')) {
			/** Load WordPress Bootstrap */
			require_once ABSPATH . '/wp-load.php';
			/** Load WordPress Administration APIs */
			require_once ABSPATH . 'wp-admin/includes/admin.php';
		}

		// verifica alterações de posts
		$post_types = explode(',', Helpers::getOption('post_types_rlout'));
		foreach ($post_types as $post_type) {
			add_action("publish_{$post_type}", array($this, 'create_folder'));
			add_action("pre_post_update", array($this, 'delete_folder'));
		}

		add_action("future_to_publish", array($this, 'future_callback'));

		if($_GET['post'] && $_GET['action']=='edit'){
			$post = get_post($_GET['post']);
			if($post){
				
				$link = get_sample_permalink($post);
				$link = str_replace(site_url().'/', '', $link);
				$url_del = str_replace('%pagename%',$link[1],$link[0]);
				$url_del = str_replace('%postname%',$link[1],$url_del);
				setcookie('old_slug', $url_del);
			}
		}else{
			setcookie('old_slug', null);
		}
	}

	public function future_callback($post_id=null){

		Helpers::importantfiles_generate();
		Helpers::subfiles_generate('xml');

		$this->publish_folder($post_id);
	}

	public function publish_folder($post_id=null) {

		Helpers::subfiles_generate('xml');

		$post_types = explode(',', Helpers::getOption('post_types_rlout'));

		$post = get_post($post_id);
		
		if(in_array($post->post_type, $post_types)){
				
			// Gerador da archive do post estatizado
			$link_archive = get_post_type_archive_link($post->post_type);
			if($link_archive && $link_archive!=site_url()){
				Curl::generate($link_archive);
			}
			
			// Verificando os terms do post de todas as taxonomies selecionadas
			$taxonomies = explode(",", Helpers::getOption('taxonomies_rlout'));
			$terms = wp_get_post_terms($post->ID, $taxonomies);
			
			$objects = array();
			
			$objects[] = $post;
			
			
			// categorias relacionadas
			foreach ($terms as $key => $term) {

				$objects[] = $term;
				Terms::api($term, true, $post);

				if(!empty($term->parent) && Helpers::getOption('parent_term_rlout')){
					$terms_recursive = array();
					$terms_recursive = $this->recursive_term($term->parent, $terms_recursive);
					foreach($terms_recursive as $term_recursive){
						Terms::api($term_recursive, true, $post);
						$objects[] = $term_recursive;
					}
				}

			}
				
			Curl::list_deploy($objects);
			Posts::api($post);
		}
	}
	
	public function create_folder($post_id=null) {

		add_action('updated_post_meta', function($meta_id, $post_id, $meta_key) {
		
			if($meta_key=='_edit_lock'):
				$static = get_post_meta($post_id, '_static_output_html', true);
				if(!empty($static) || (isset($_POST['static_output_html']) && !empty($_POST['static_output_html']))):
					
					$this->publish_folder($post_id);
				endif;

			endif;
		}, 10, 3);
	}
	
	public function delete_folder($post_id) {

		if(!function_exists('get_sample_permalink')) {
			/** Load WordPress Bootstrap */
			require_once ABSPATH . '/wp-load.php';
			/** Load WordPress Administration APIs */
			require_once ABSPATH . 'wp-admin/includes/admin.php';
		}
		
		$post = get_post($post_id);

		$url_delete = get_sample_permalink($post);
		$url_del = str_replace('%pagename%',$url_delete[1],$url_delete[0]);
		$url_del = str_replace('%postname%',$url_delete[1],$url_del);
		$url_delete = $url_del;
		$dir_base =  str_replace('__trashed', '', $url_delete);
		$dir_base = Helpers::getOption('path_rlout') . str_replace(site_url(), '', $dir_base);
	
		$delete_old = $_COOKIE['old_slug'];

		if(!empty($delete_old)){
			$delete_old = Helpers::getOption('path_rlout').'/'.$delete_old;
			if($delete_old!=$dir_base || (!strpos($dir_base, $_POST['post_name'].'/') && !empty($_POST['post_name']))){
				Helpers::rrmdir($delete_old);
				S3::remove_file($delete_old . 'index.html');
			}
		}

		if($_GET['action']=='trash' || (!empty($_POST['post_status']) && $_POST['post_status'] != 'publish') ) {

			$post_types = explode(',', Helpers::getOption('post_types_rlout'));

			if(in_array($post->post_type, $post_types)){
				
				$taxonomies = explode(",", Helpers::getOption('taxonomies_rlout'));
				$terms = wp_get_post_terms($post->ID, $taxonomies);
				foreach ($terms as $key => $term) {

					Terms::api($term, true, $post);
	
					if(!empty($term->parent) && Helpers::getOption('parent_term_rlout')){
						$terms_recursive = array();
						$terms_recursive = $this->recursive_term($term->parent, $terms_recursive);
						foreach($terms_recursive as $term_recursive){
							Terms::api($term_recursive, true, $post);
						}
					}
	
				}

				if($url_delete){
					
					Helpers::rrmdir($dir_base);
					S3::remove_file($dir_base . 'index.html');
				}
				
			}
			
			Helpers::subfiles_generate('xml');
			Posts::api($post);
		}
	}

	public function recursive_term($term_id, $objects){
		$term = get_term($term_id);
		$objects[] = $term;
		if(!empty($term->parent)){
			$objects = $this->recursive_term($term->parent, $objects);
		}
		return $objects;
	}
	
	static function api($post=null, $upload=true){
		
		$post_types = explode(",", Helpers::getOption('post_types_rlout'));
		
		$gerenate_all = false;
		
		if(empty($post)){
			$gerenate_all = true;
		}
		
		$urls = array();
		foreach($post_types as $post_type){

			$generate = true;
			
			if($gerenate_all==true){
				$post->post_type = $post_type;
			}else{
				if($post_type!=$post->post_type){
					$generate=false;
				}
			}
			
			if($generate==true){
				$replace_url = Helpers::getOption('replace_url_rlout');
				if(empty($replace_url)){
					$replace_url = site_url().'/html';
				}

				$posts_arr = Posts::get_post_json($post, array());
				
				$dir_base =  Helpers::getOption('path_rlout');
				if( realpath($dir_base) === false ){
					wp_mkdir_p($dir_base);
				}
				
				$file_raiz = $dir_base . '/'.$post->post_type.'.json';
				
				$file = fopen($file_raiz, "wa+");

				fwrite($file, '[');
				
				foreach($posts_arr as $key_arr => $post_arr){
					
					$response = json_encode($post_arr , JSON_UNESCAPED_SLASHES);
					
					if(!empty($response)){
						
						if($post_arr!=end($posts_arr)){
							$response = $response.',';
						}
					}

					fwrite($file, $response);
				}

				fwrite($file, ']');
				
				fclose($file);
				
				if($upload==true){
					S3::upload_file($file_raiz, Helpers::getOption('s3_cloudfront_auto_rlout'));
				}
				
				$urls[] = str_replace($dir_base,$replace_url,$file_raiz);
			}
		}
		
		
		return $urls;
	}
	
	static function get_post_json($post=null, $not_in=array(), $term = null){
		
		$replace_url = Helpers::getOption('replace_url_rlout');
		if(empty($replace_url)){
			$replace_url = site_url().'/html';
		}
		
		if(!empty($post)){
			if(!is_array($post->post_type) && !empty($post->ID)){
				$json_exist = Curl::get($replace_url.'/'.$post->post_type.'.json');
				$post_arr = json_decode($json_exist);
				if(is_array($post_arr)){

					$create_post = true;
					
					$new_post = Posts::new_params($post, true);
					
					foreach($post_arr as $arr_key => $arr){
						if($arr->ID==$post->ID){
							$create_post = false;
							if($_POST['post_status']=='publish'){
								$post_arr[$arr_key] = $new_post;
							}else{
								unset($post_arr[$arr_key]);
							}
							break;
						}
					}
					
					if($create_post==true && $post->post_status=='publish'){
						
						array_unshift($post_arr, $new_post);
					}
					
					return $post_arr;
				}
			}
			
			$object = new \StdClass();
			$object->post_type = $post->post_type;

			$range = Helpers::getOption('range_posts_rlout');
			if(empty($range)){
				$range = 50;
			}

			$args = array(
				'post_type'=>$post->post_type,
				'posts_per_page' => $range,
				'order'=>'DESC',
				'orderby'=>'date',
				'post_status' => 'publish',
				'post__not_in'=>$not_in
			);
			
			if(!empty($term)):
				$args['tax_query'][0]['taxonomy'] = $term->taxonomy;
				$args['tax_query'][0]['terms'] = array($term->term_id);
			endif;
			
			$posts = get_posts($args);
			
			$posts_arr = array();
			
			$ignore_json_rlout = explode(',' , Helpers::getOption('ignore_json_rlout'));
			foreach ($posts as $key => $post) {
				
				$url = get_permalink($post);
				if(empty(in_array($url, $ignore_json_rlout))){
					$not_in[] = $post->ID;
					
					$posts_arr[$key] = Posts::new_params($post, true);
				}
				
			}
			
			if(count($posts)==$range){
				sleep(0.1);
				$posts_arr = array_merge($posts_arr, Posts::get_post_json($object, $not_in, $term));
			}
			
			return $posts_arr;
		}
	}
	
	static function new_params($post, $show_terms=false){
		
		$rpl = Helpers::getOption('replace_url_rlout');
		if(empty($rpl)){
			$rpl = site_url().'/html';
		}
		
		$new_post = array();
		
		$new_post['ID'] = $post->ID;
		$new_post['post_title'] = $post->post_title;
		$new_post['post_date'] = $post->post_date;
		$new_post['post_excerpt'] = get_the_excerpt($post);
		$size_thumb = Helpers::getOption('size_thumbnail_rlout');
		
		$thumbnail = get_the_post_thumbnail_url($post, $size_thumb);
		if(empty($thumbnail)){
			$thumbnail = Helpers::getOption('uri_rlout').'/img/default.jpg';
			$thumbnail = str_replace(Helpers::getOption('uri_rlout'), $rpl, $thumbnail);
		}
		$new_post['thumbnail'] = $thumbnail;
		$url = str_replace(site_url(),$rpl,get_permalink($post)).'index.json';
		$new_post['post_json'] = $url;
		
		$taxonomies = explode(",", Helpers::getOption('taxonomies_rlout'));
		if(!empty($taxonomies) && $show_terms==true){
			
			$term = wp_get_post_terms($post->ID, $taxonomies);
			if(!empty($term) && empty($term->errors)){
				foreach($term as $tm_k => $tm){
					$url = str_replace(site_url(),$rpl,get_term_link($tm)).'index.json';
					$new_post[$tm->taxonomy][$tm_k]['term_id'] = $tm->term_id;
					$new_post[$tm->taxonomy][$tm_k]['term_name'] = $tm->name;
					$new_post[$tm->taxonomy][$tm_k]['term_json'] = $url;
				}
			}
		}
		
		$new_post = apply_filters('rel_output_custom_post', $post, $new_post);
		
		return $new_post;
	}
}