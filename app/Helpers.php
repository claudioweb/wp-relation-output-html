<?php

namespace WpRloutHtml;

use WpRloutHtml\Essentials\Curl;
use WpRloutHtml\Modules\S3;
use WpRloutHtml\Modules\Ftp;

Class Helpers {

	private static $options = array();

	static function rrmdir($src) {
		if (file_exists($src)) {
			$dir = opendir($src);
			while (false !== ($file = readdir($dir))) {
				if (($file != '.') && ($file != '..')) {
					$full = $src . '/' . $file;
					if (is_dir($full)) {
						Helpers::rrmdir($full);
					} else {
						unlink($full);
					}
				}
			}
			closedir($dir);
			rmdir($src);
			return true;
		}else{
			return true;
		}
	}

	static function subfiles_generate($format=null){
		
		$files = explode(',', self::getOption('subfiles_rlout'));
		
		foreach ($files as $key => $file) {
			
			if(!empty($file)){
				$format_url = explode(".", $file);
				if(empty($format) || (!empty($format_url) && end($format_url)==$format)){
					Curl::deploy_upload($file);
					App::$repeat_files_rlout[] = $file;
				}
			}
		}
		return $files;
	}

    static function gen_html_cron_function() {
		
		$hora_marcada = strtotime(self::getOption('horario_cron_rlout'));
		
		if($hora_marcada==strtotime(date('H:i'))){
			
			$dir_base =  self::getOption('path_rlout');
			
			if( is_dir($dir_base) === true ){
				
				Helpers::rrmdir($dir_base);
				
				S3::remove_file($dir_base);
			}
			
			$this->post_auto_deploy();
		}
	}

    static function importantfiles_generate(){
		
		// Generate FILE 1
		$files = explode(',', self::getOption('pages_important_rlout'));
		
		foreach ($files as $key => $file) {
			
			if(!empty($file)){
				Curl::generate($file);
				App::$repeat_files_rlout[] = $file;
			}
		}
		return $files;
	}

    static function url_json_obj($object){
		
		$dir_base =  self::getOption('path_rlout');
		$rpl = self::getOption('replace_url_rlout');
		if(empty($rpl)){
			$rpl = site_url().'/html';
		}
		
		if(term_exists($object->term_id)){
			
			$object->term_json = str_replace(site_url(), $rpl, get_term_link($object)) . 'index.json';
		}else{
			
			$object->post_json = str_replace(site_url(), $rpl, get_permalink($object)) . 'index.json';
		}
		
		return $object;
	}

    static function replace_reponse($url_replace, $response, $media=null, $items=true){
        
		if($items==true){
			// pegando itens 
			$itens_theme = explode($url_replace, $response);
			
			unset($itens_theme[0]);

			foreach($itens_theme as $keyj => $item){
				
				$item = explode('"', $item);
				$item = explode("'", $item[0]);
				$item = explode(")", $item[0]);
				$item = $url_replace . $item[0];
				
				if(!empty($item)){
					Curl::deploy_upload($item, $media);
					App::$repeat_files_rlout[] = $item;
				}
			}
		}
        
        //replace url
        $rpl = self::getOption('replace_url_rlout');
        if(empty($rpl)){
            $rpl = site_url().'/html';
        }
        $rpl_original = $rpl;
        $rpl = $rpl . $media;
        if($rpl!=site_url() && $rpl!=$url_replace){
            $response = str_replace($url_replace, $rpl, $response);
            if(!$media){

				
                $response = str_replace(site_url(), $rpl, $response);

				$site_url_concat = str_replace("https://","",site_url());
				$site_url_concat = str_replace("http://","",$site_url_concat);
				$site_url_concat = explode("/", $site_url_concat);
				$site_url_concat = $site_url_concat[0];

				$rpl_url_concat = str_replace("https://","",$rpl);
				$rpl_url_concat = str_replace("http://","",$rpl_url_concat);
				$rpl_url_concat = explode("/", $rpl_url_concat);
				$rpl_url_concat = $rpl_url_concat[0];

                $response = str_replace($site_url_concat, $rpl_url_concat, $response);

				// url simples ajax
				$url_ajax = $rpl_url_concat.'/wp-admin/admin-ajax.php';
				$url_ajax_original = $site_url_concat.'/wp-admin/admin-ajax.php';
                $response = str_replace($url_ajax, $url_ajax_original, $response);

				// url concatenada ajax
				$url_ajax = $rpl_url_concat.'\/wp-admin\/admin-ajax.php';
				$url_ajax_original = $site_url_concat.'\/wp-admin\/admin-ajax.php';
                $response = str_replace($url_ajax, $url_ajax_original, $response);

            }
        }
        $rpl_dir = str_replace(site_url(), '', $rpl_original);
        if(!empty($rpl_dir)){
            $response = str_replace($rpl_dir.$rpl_dir,$rpl_dir, $response);
        }
		
        return $response;
    }
    
    static function replace_json($response){

        $jsons = explode(",", self::getOption('api_1_rlout'));
        
        foreach ($jsons as $key => $json) {
            
            $json_name = explode("action=", $json);
            $json_name = explode("&", $json_name[1]);
            $json_name = self::getOption('path_rlout') . $json_name[0] . '.json';
            
            $response = str_replace($json, $json_name, $response);
        }
        
        return $response;
    }

	public static function getOption($option) {
        return !isset(self::$options[$option]) ? self::$options[$option] = get_option($option) : self::$options[$option];
    }

}