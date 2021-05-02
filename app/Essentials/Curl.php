<?php

namespace WpRloutHtml\Essentials;

use WpRloutHtml\App;
use WpRloutHtml\Posts;
use WpRloutHtml\Terms;
use WpRloutHtml\Helpers;
use WpRloutHtml\Modules\S3;
use WpRloutHtml\Modules\Ftp;
use WpRloutHtml\Modules\Git;
use WpRloutHtml\Modules\Amp;

Class Curl {
	
	// Envia todos os objetos recebidos para o generate
	static function list_deploy($objs=null){

		if(!empty($objs)){
			$terms_repeat = array();
			foreach ($objs as $key => $obj) {
				if($obj->term_id){
					if(!in_array($obj->term_id, $terms_repeat)){
						$terms_repeat[] = $obj->term_id;
						Curl::generate($obj);
					}
				}else{
				
					Curl::generate($obj);
				}
			}
		}
		
	}
	
	// Recebe o Objeto (post ou term) e descobre a Url para enviar a função deploy_upload();
	static function generate($object, $home=null, $items=true, $upload=true, $return=true){
		update_option('robots_rlout', '0');
		update_option('blog_public', '1');
		
		$url_post = url_to_postid($object);
		$search_amp = strpos($object, '/amp/');
		if($search_amp===false){
			if(!empty($url_post)){
				$object = get_post($url_post);
			}else{
				$taxonomy = explode(",", Helpers::getOption('taxonomies_rlout'));
				foreach($taxonomy as $tax){
					$slug_term = explode("/",$object);
					foreach($slug_term as $key_b => $barra){
						$term_exist = get_term_by('slug',$slug_term[$key_b], $tax);
						if($term_exist){
							$link_term = get_term_link($term_exist);
							if($link_term==$object){
								$object = $term_exist;
							}
						}
					}
				}
			}
		}
		
		if(!empty($object->ID)){
			
			$url = get_permalink( $object );
			$slug = $object->post_name;
		}else if(!empty($object->term_id)){
			
			$url = get_term_link( $object );
			$slug = $object->slug;
		}else{
			
			if($home){
				$url = site_url('/');
			}else{
				$url = $object;
			}
		}
		
		if(filter_var($url, FILTER_VALIDATE_URL)==false){
			return $url.' - URL COM ERRO DE SINTAX';
		}
		
		$response = Curl::get($url, $return);
		
		$original_response = $response;
		
		if ($response) {
			
			$response = Helpers::replace_json($response);
			$dir_base = Helpers::getOption('path_rlout');
			if( is_dir($dir_base) === false ){
				wp_mkdir_p($dir_base);
			}
			
			$uri = Helpers::getOption('uri_rlout');
			
			$replace_raiz = str_replace($uri, '', $url);
			$replace_raiz = str_replace(site_url(), '', $replace_raiz);
			$dir_base = $dir_base . $replace_raiz;
			
			$verify_files_point = explode('.',$replace_raiz);
			
			$file_default = 'index.html';
			$json_default = 'index.json';
			
			if(count($verify_files_point)>1){
				$file_default = '';
				$json_default = '';
				
				if($verify_files_point[1]=='xml'){
					
					$htt = str_replace('https:', '', site_url());
					$htt = str_replace('http:', '', $htt);
					$original_response = str_replace(site_url(), Helpers::getOption('replace_url_rlout'), $original_response);
					$original_response = str_replace('href="'.$htt, 'href="'. Helpers::getOption('replace_url_rlout'), $original_response);
					$xml = simplexml_load_string($response);
					foreach($xml->sitemap as $sitemap){
						if(isset($sitemap->loc)){
							$url_map = (array) $sitemap->loc;
							if(!empty($url_map)){
								Curl::generate($url_map[0], null, false, false, false);
							}
						}
					}
					$response=$original_response;
				}
			}
			
			$explode_raiz = explode("/", $dir_base);
			foreach ($explode_raiz as $keyp => $raiz) {
				$wp_raiz = $wp_raiz . $raiz . '/';
				if( realpath($wp_raiz) === false && $keyp+1<count($explode_raiz)){
					wp_mkdir_p($wp_raiz);
				}
			}
			
			$file = fopen($dir_base . $file_default,"wa+");
			
			$response = Helpers::replace_reponse(Helpers::getOption('uri_rlout'), $response, null, $items);
			$amp = Helpers::getOption('amp_rlout');
			if(!empty($amp)){
				$response = Amp::remove_pagination($response, $url);
			}
			
			$jsons = array();
			
			$ignore_files_rlout = explode(',', Helpers::getOption('ignore_files_rlout'));
			if(empty(in_array($url, $ignore_files_rlout))){
				
				fwrite($file, $response);
				fclose($file);
				
				if($upload==true){
					S3::upload_file($dir_base . $file_default, false);
				}
				
				if(!empty($amp) && !empty($file_default) && !$search_amp){
					
					$verify_amp = Curl::get($url.'amp/', false);
					
					if($verify_amp){
						
						Curl::generate($url.'amp/', false, false, $upload, false);
						
						$urls_pagination = Amp::urls();
						foreach($urls_pagination as $url_pg){
							$page_compare = explode('amp/', $url_pg);
							if($url==$page_compare[0]){
								Curl::generate($url_pg, false, false, $upload, false);
							}
						}
					}
				}
			}
			
			if($json_default!='' && is_object($object) && $upload==false || $json_default!='' && is_object($object) && $object->ID){
				
				$file_json = fopen($dir_base . $json_default,"wa+");
				
				if(term_exists($object->term_id)){
					$object = Terms::object_term($object, true);
				}else if($object->ID){
					$object = Posts::new_params($object, true);
				}
				
				
				$response_json = Helpers::replace_reponse(Helpers::getOption('uri_rlout'), json_encode($object), null, $items);
				
				$ignore_json_rlout = explode(',' , Helpers::getOption('ignore_json_rlout'));
				if(empty(in_array($url, $ignore_json_rlout))){
					
					fwrite($file_json,  $response_json);
					fclose($file_json);
					
					if($upload==true){
						S3::upload_file($dir_base . $json_default, true);
					}
				}
			}
			
			update_option('robots_rlout', '1');
			return $url;
		}
	}
	
	// Recebe a URl da página ou media e gera o HTML, em seguida faz upload no S3 e FTP
	static function deploy_upload($url, $media=null){
		
		if(empty(in_array($url, App::$repeat_files_rlout)) && !empty($url)){
			
			$url = explode('?', $url);
			
			$url = $url[0];
			
			$url_point = explode(".", $url);
			
			$url_space = explode(" ", $url_point[count($url_point)-1]);
			
			$url_point[count($url_point)-1] = $url_space[0];
			
			$url = implode(".", $url_point);
			
			$response = Curl::get($url);
			
			if ($response) {
				
				$response = Helpers::replace_json($response);
				
				$dir_base = Helpers::getOption('path_rlout');
				if( is_dir($dir_base) === false ){
					wp_mkdir_p($dir_base);
				}
				
				if($media){
					$dir_base = Helpers::getOption('path_rlout') . $media;
					if( is_dir($dir_base) === false ){
						wp_mkdir_p($dir_base);
					}
				}
				
				$url = urldecode($url);
				
				$file_name = str_replace(Helpers::getOption('uri_rlout'), '', $url);
				$file_name = str_replace(site_url(), '', $file_name);
				
				$folders = explode("/", $file_name);
				foreach ($folders as $key => $folder) {
					if($key+1<count($folders)){
						$dir_base = $dir_base . '/' . $folder;
						if( is_dir($dir_base) === false ){
							wp_mkdir_p($dir_base);
						}
					}
				}
				
				$css = explode(".css", end($folders));
				if(!empty($css[1])){
					$attrs = explode("url(", $response);
					if(empty($attrs)){
						$attrs = explode("url (", $response);
					}						
					
					if(!empty($attrs)){
						unset($attrs[0]);
						foreach ($attrs as $key_att => $attr) {
							$http = explode("http", $attr);
							if(!$http[1]){
								$attr = explode(")", $attr);
								$attr = str_replace('"', '', $attr[0]);
								$attr = str_replace("'", "", $attr);
								
								$attr = $dir_base  . '/' . $attr;
								
								$attr = str_replace(Helpers::getOption('path_rlout'), '', $attr);
								
								$attr = Helpers::getOption('uri_rlout') . $attr;
								
								$svg = explode("data:image", $attr);
								
								if(!$svg[1]){
									Curl::deploy_upload($attr);
									App::$repeat_files_rlout[] = $attr;
								}
							}
						}
					}
				}
				
				$folders_point = explode(".", end($folders));
				
				$folders_space = explode(" ", $folders_point[count($folders_point)-1]);
				
				$folders_point[count($folders_point)-1] = $folders_space[0];
				
				$folders = implode(".", $folders_point);
				
				$file = fopen( $dir_base . '/' . $folders,"wa+");
				
				fwrite($file, $response);
				fclose($file);
				
				S3::upload_file($dir_base . '/' . $folders);
			}
		}
		return $url;
	}
	
	static function generate_json($json_url){
		
		$type = explode('.json', $json_url);
		if(count($type)>1){
			$type = explode('/', $type[0]);
			if(count($type)>1){
				$type = end($type);
			}
		}
		
		$object = new \StdClass();
		$object->post_type = $type;
		$post_type = Posts::api($object);
		if(!empty($post_type)){
			wp_die($post_type[0]);
		}
		
		$object = new \StdClass();
		$object->taxonomy = $type;
		$taxonomy = Terms::api($object);
		if(!empty($taxonomy)){
			wp_die($taxonomy[0]);
		}
		
		$json_url = explode('index.json', $json_url);

		$rpl = Helpers::getOption('replace_url_rlout');
		if(empty($rpl)){
			$rpl = site_url().'/html';
		}

		$json_url = str_replace($rpl,site_url(),$json_url[0]);

		$taxonomies = explode(",", Helpers::getOption('taxonomies_rlout'));
		foreach($taxonomies as $tax){
			$terms = get_terms(array("taxonomy"=>$tax, 'hide_empty' => false));
			foreach($terms as $term){
				$term_link = get_term_link($term);
				if($term_link==$json_url){
					$term = Terms::api($term);
					if(!empty($term)){
						wp_die($term[0]);
					}
				}
			}
		}
	}
	
	static function get($url, $return_status=true){

		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $url,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSLVERSION => 'all',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 120,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_TCP_FASTOPEN => 1,
			CURLOPT_FRESH_CONNECT => false,
			CURLOPT_HTTPHEADER => array(
				"cache-control: no-cache",
				"Authorization: Basic ".base64_encode(Helpers::getOption('userpwd_rlout') . ":" . Helpers::getOption('passpwd_rlout'))
			),
		));
		
		$response = curl_exec($curl);
		$err = curl_error($curl);
		
		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		
		if ($httpCode!=200 && $_GET['file_url'] && $return_status==true) {
			header('HTTP/1.0 404 not found');
			wp_die();
		} else if($httpCode!=200 && $return_status==false) {
			return false;
		}else{
			return $response;
		}
	}
}