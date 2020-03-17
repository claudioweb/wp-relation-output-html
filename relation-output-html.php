<?php
/***************************************************************************
Plugin Name:  Relation Output HTML
Plugin URI:   http://www.claudioweb.com.br/
Description:  Este plugin transforma todos os conteúdos salvos e relaxionados em páginas staticas HTML para servidores como FTP e S3
Version:      1.0
Author:       Claudio Web (claudioweb)
Author URI:   http://www.claudioweb.com.br/
Text Domain:  relation-output-html
**************************************************************************/
require "vendor/autoload.php";

use Aws\S3\S3Client;

Class RelOutputHtml {

	private $name_plugin;

	private $repeat_files_rlout;

	public function __construct() {

		$this->name_plugin = 'Relation Output HTML';

		$this->repeat_files_rlout = array();

		add_action('init', array($this, 'blog_public') );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ));

		// verifica alterações de POST
		$post_types = explode(',', get_option('post_types_rlout'));
		foreach ($post_types as $key => $post_type) {
			add_action( 'publish_'.$post_type, array($this, 'post_auto_deploy'));
			add_action( 'pre_'.$post_type.'_update', array($this, 'post_delete_folder'));
			add_action( 'trash_'.$post_type,  array($this, 'post_delete_folder'));
		}

		// verifica alterações de TERMS
		add_action( 'create_term', array($this, 'term_create_folder'), 10, 3);
		add_action( 'edit_term', array($this, 'term_create_folder'), 10, 3);
		add_action( 'delete_term', array($this, 'term_delete_folder'), 10, 3);

		add_action('wp_ajax_posts', array($this, 'api_posts') );
   		// Definindo action para acesso público
		add_action('wp_ajax_nopriv_posts', array($this, 'api_posts') );

		add_action('wp_ajax_terms', array($this, 'api_terms') );
   		// Definindo action para acesso público
		add_action('wp_ajax_nopriv_terms', array($this, 'api_terms') );

		//removendo Infos Header
		remove_action('wp_head', 'print_emoji_detection_script', 7);
		remove_action('wp_print_styles', 'print_emoji_styles');
		remove_action('wp_head', 'rsd_link');
		remove_action('wp_head', 'wlwmanifest_link');
		remove_action('wp_head', 'wp_generator');

		// add_action("init", array($this, 'json_generate'));

		//Schedule an action if it's not already scheduled
		//wp_schedule_event( strtotime(get_option('horario_cron_rlout')), 'daily', 'gen_html_cron_hook' );

		///Hook into that action that'll fire every six hours
		//add_action( 'gen_html_cron_hook', array($this, 'gen_html_cron_function') );

		if(!empty($_POST['salvar'])){

			unset($_POST['salvar']);
			foreach ($_POST as $key_field => $value_field) {

				if(is_array($value_field)){
					update_option( $key_field, implode(',', $value_field) );
				}else{
					update_option( $key_field, $value_field );
				}
			}

			$redirect_param = sanitize_title($this->name_plugin) . '-config';

			header('Location:'.admin_url('admin.php?page='.$redirect_param));
			exit;
		}

		if(!empty($_POST['deploy_all_static'])){

			if ( ! function_exists( 'get_home_path' ) || ! function_exists( 'wp_get_current_user' ) ) {
				include_once(ABSPATH . '/wp-admin/includes/file.php');
				include_once(ABSPATH . '/wp-includes/pluggable.php');
			}

			$user = wp_get_current_user();

			if(in_array('administrator', $user->roles)){

				add_action("init", array($this, 'post_auto_deploy'), 9999);
			}

			$redirect_param = sanitize_title($this->name_plugin) . '-config';

			header('Location:'.admin_url('admin.php?&loading_deploy=true&page='.$redirect_param));
		}

		if(!empty($_POST['json_generate'])){

			if ( ! function_exists( 'get_home_path' ) || ! function_exists( 'wp_get_current_user' ) ) {
				include_once(ABSPATH . '/wp-admin/includes/file.php');
				include_once(ABSPATH . '/wp-includes/pluggable.php');
			}

			$user = wp_get_current_user();

			if(in_array('administrator', $user->roles)){

				add_action("init", array($this, 'json_generate'), 9999);
			}

			$redirect_param = sanitize_title($this->name_plugin) . '-config';

			header('Location:'.admin_url('admin.php?&loading_deploy=true&page='.$redirect_param));
		}
	}

	public function blog_public(){

		$robots = get_option('robots_rlout');

		if($robots){

			update_option('blog_public', '0');
		}else{

			update_option('blog_public', '1');
		}


		$path = get_option("path_rlout");
		if(empty($path)){
			if ( ! function_exists( 'get_home_path' ) || ! function_exists( 'wp_get_current_user' ) ) {
				include_once(ABSPATH . '/wp-admin/includes/file.php');
				include_once(ABSPATH . '/wp-includes/pluggable.php');
			}
			update_option('path_rlout', get_home_path() . "html");
		}
	}

	public function term_create_folder($term_id, $tt_id, $taxonomy, $deleted_term=null){

		$term = get_term($term_id);

		$taxonomies = explode(',', get_option('taxonomies_rlout'));

		if(in_array($term->taxonomy, $taxonomies)){

			$slug_old = $term->slug;
			$slug_new = $_POST['slug'];

			$url = str_replace(site_url(), '', get_term_link($term));

			if($slug_old!=$slug_new){

				$term->slug = $slug_new;
			}

			$dir_base = get_option("path_rlout") . $url;

			$objects = array($term);

			$this->deploy($objects);
		}
	}

	public function term_delete_folder($term_id, $tt_id, $taxonomy, $deleted_term=null){

		$term = get_term($term_id);

		$taxonomies = explode(',', get_option('taxonomies_rlout'));

		if(in_array($term->taxonomy, $taxonomies)){

			$slug_old = $term->slug;
			$slug_new = $_POST['slug'];

			$url = str_replace(site_url(), '', get_term_link($term));

			if($slug_old!=$slug_new){

				$term->slug = $slug_new;
			}

			$dir_base = get_option("path_rlout") . $url;

			unlink($dir_base . '/index.html');
			rmdir($dir_base);

			$this->ftp_remove_file($dir_base . '/index.html');
			$this->s3_remove_file($dir_base . '/index.html');

			if(empty($deleted_term)){

				$objects = array($term);

				$this->deploy($objects);
			}
		}
	}

	public function post_delete_folder($post_id){

		$post = get_post($post_id);

		$post_types = explode(',', get_option('post_types_rlout'));

		if(in_array($post->post_type, $post_types)){

			if($post->post_status=='publish' && $_POST['post_status']=='publish'){

				$slug_old = $post->post_name;

				$slug_new = $_POST['post_name'];

				if($slug_old==$slug_new){

					return false;
				}
			}

			$dir_base =  str_replace('__trashed', '', get_option("path_rlout") . $post->post_name);

			unlink($dir_base . '/index.html');
			rmdir($dir_base);

			$this->ftp_remove_file($dir_base . '/index.html');
			$this->s3_remove_file($dir_base . '/index.html');

		}

	}

	//create your function, that runs on cron
	public function gen_html_cron_function() {

		$hora_marcada = strtotime(get_option('horario_cron_rlout'));

		if($hora_marcada==strtotime(date('H:i'))){

			$dir_base =  get_option("path_rlout");

			if( is_dir($dir_base) === true ){

				// rmdir($dir_base);
				$this->ftp_remove_file($dir_base);
				$this->s3_remove_file($dir_base);
			}

			$this->post_auto_deploy();
		}
	}

	public function post_auto_deploy($post_id=null){

		$post_types = explode(',', get_option('post_types_rlout'));
		foreach ($post_types as $key => $pt) {
			$link = get_post_type_archive_link($pt);
			if($link){
				$this->curl_generate(get_post_type_archive_link($pt));
			}
		}
		sleep(1);

		if(empty($post_id)){

			$objects = get_posts(array('post_type'=>$post_types, 'posts_per_page'=>-1));

			$this->deploy($objects);
			foreach ($taxonomies as $key => $tax) {
				$objects = get_terms( array('taxonomy' => $tax, 'hide_empty' => false) );
				$this->deploy($objects);
			}

		}else{

			$post =  get_post($post_id);

			if(in_array($post->post_type, $post_types)){

				$terms = wp_get_post_terms($post->ID, $taxonomies);

				$objects = array();

				$objects[] = $post;

				// categorias relacionadas
				foreach ($terms as $key => $term) {
					$objects[] = $term;
				}

				$this->deploy($objects);
			}
		}

		sleep(1);

		$this->git_upload_file('Atualização de object');
	}

	public function deploy($objs=null){

		update_option('robots_rlout', '0');
		update_option('blog_public', '1');
		sleep(1);
		
		if(!empty($objs)){

			foreach ($objs as $key => $obj) {

				$this->curl_generate($obj);
				sleep(1);
			}
		}

		sleep(1);
		$this->subfiles_generate();
		sleep(1);
		$this->curl_generate(null, true);
		sleep(1);
		update_option('robots_rlout', '1');
	}


	public function subfiles_generate(){

		// Generate FILE 1
		$files = explode(',', get_option("subfiles_rlout"));

		foreach ($files as $key => $file) {

			if(!empty($file)){

				$this->deploy_upload($file);
				$this->repeat_files_rlout[] = $file;
			}
		}
	}

	public function json_generate(){

		$this->api_posts(true);
		$this->api_terms(true);
		sleep(1);
		// Generate JSON 1
		$jsons = explode(',', get_option("api_1_rlout"));
		$json = array();
		foreach ($jsons as $key => $json) {

			if(!empty($json)){

				$json_name = explode("action=", $json);
				$json_name = explode("&", $json_name[1]);
				$json_name = $json_name[0];

				$curl = curl_init();

				curl_setopt_array($curl, array(
					CURLOPT_URL => $json,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING => "",
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_TIMEOUT => 120,
					CURLOPT_FOLLOWLOCATION => true,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST => "GET",
					CURLOPT_HTTPHEADER => array(),
				));

				$response = curl_exec($curl);
				$err = curl_error($curl);

				curl_close($curl);

				if ($err) {
					echo "cURL Error #:" . $err;
				} else {

					$dir_base =  get_option("path_rlout");
					if( is_dir($dir_base) === false ){
						mkdir($dir_base);
					}

					$file_path = $dir_base . '/' . $json_name . '.json';

					$file = fopen($file_path, "w");

					fwrite($file, $response);

					$this->ftp_upload_file($file_path);
					$this->s3_upload_file($file_path);
				}
			}
		}

	}

	public function curl_generate($object, $home=null){

		if(!empty($object->ID)){

			$url = get_permalink( $object );
			$slug = $object->post_name;

			$thumbnails = get_intermediate_image_sizes();
			foreach ($thumbnails as $key => $t) {
				$url_thumb = get_the_post_thumbnail_url($object, $t);
				$this->deploy_upload($url_thumb, '/uploads');
			}
		} else if(!empty($object->term_id)){
			
			$url = get_term_link( $object );
			$slug = $object->slug;
		}else{

			if($home){
				$url = site_url('/');
			}else{
				$url = $object;
			}
		}

		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 120,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => array(
				"cache-control: no-cache"
			),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
			echo "cURL Error #:" . $err;
		} else {

			$response = $this->replace_json($response);

			$dir_base =  get_option("path_rlout");
			if( is_dir($dir_base) === false ){
				mkdir($dir_base);
			}

			$replace_path = str_replace(site_url(), '', $url);
			$dir_base = $dir_base . $replace_path; 
			$explode_path = explode("/", $dir_base);

			foreach ($explode_path as $keyp => $path) {
				$wp_path = $wp_path . $path . '/';
				if( is_dir($wp_path) === false ){
					mkdir($wp_path);
				}
			}

			$file = fopen( $dir_base . '/index.html',"w");

			$file_json = fopen( $dir_base . '/index.json',"w");

			$replace_uploads = get_option('uploads_rlout');

			$uploads_url_rlout = get_option('uploads_url_rlout'); 

			if($replace_uploads){
				$upload_url = wp_upload_dir();

				$response = $this->replace_reponse($upload_url['baseurl'], $response, '/uploads');
				
				if($uploads_url_rlout){
					$response = $this->replace_reponse($uploads_url_rlout, $response, '/uploads');
				}

			}

			$response = $this->replace_reponse(get_template_directory_uri(), $response);

			$jsons = array();
			
			fwrite($file, $response);

			$this->ftp_upload_file($dir_base . '/index.html');
			$this->s3_upload_file($dir_base . '/index.html');

			if(term_exists($object->term_id)){
				$this->object_term($object);
			}else{
				$this->object_post($object);
			}

			$response_json = $this->replace_reponse(get_template_directory_uri(), json_encode($object));

			fwrite($file_json,  $response_json);

			$this->ftp_upload_file($dir_base . '/index.json');
			$this->s3_upload_file($dir_base . '/index.json');
		}

	}

	public function url_json_obj($object){

		$dir_base =  get_option("path_rlout");
		$rpl = get_option('replace_url_rlout');
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

	public function object_post($object, $show_terms=true){

		unset($object->post_author);
		unset($object->comment_status);
		unset($object->ping_status);
		unset($object->post_password);
		unset($object->to_ping);
		unset($object->pinged);
		unset($object->post_content_filtered);
		unset($object->post_parent);
		unset($object->guid);
		unset($object->post_mime_type);
		unset($object->comment_count);
		unset($object->filter);

		$object = $this->url_json_obj($object);

		$object->post_type = $object->post_type;

		$object->thumbnails = array();
		$object->thumbnails['thumbnail'] = get_the_post_thumbnail_url($object, 'thumbnail');
		if(empty($object->thumbnails['thumbnail'])){
			$object->thumbnails['thumbnail'] = get_template_directory_uri().'/img/default.jpg';
		}
		$object->thumbnails['medium'] = get_the_post_thumbnail_url($object, 'medium');
		$object->thumbnails['large'] = get_the_post_thumbnail_url($object, 'large');
		$object->thumbnails['full'] = get_the_post_thumbnail_url($object, 'full');
		
		if($show_terms){
			$terms = wp_get_post_terms($object->ID, explode(",", get_option('taxonomies_rlout')) );
			$object->terms = array();
			foreach ($terms as $keyterm => $term) {
				$object->terms[] = $this->object_term($term, false);
			}
		}

		$metas =  get_post_meta($object->ID);

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

	public function object_term($object, $show_posts=true){

		unset($object->term_group);
		unset($object->term_taxonomy_id);
		unset($object->parent);
		unset($object->filter);

		$object = $this->url_json_obj($object);

		$args_posts = array();
		$args_posts['post_type'] = explode(",", get_option('post_types_rlout'));
		$args_posts['posts_per_page'] = -1;
		$args_posts['order'] = 'DESC';
		$args_posts['orderby'] = 'post_modified';
		$args_posts['tax_query'][0]['taxonomy'] = $object->taxonomy;
		$args_posts['tax_query'][0]['terms'] = array($object->term_id);

		if($show_posts){
			$posts = get_posts($args_posts);
			$object->posts = array();
			foreach ($posts as $key_p => $post) {

				$post = $this->object_post($post);

				$object->posts[$key_p]['ID'] = $post->ID;
				$object->posts[$key_p]['post_title'] = $post->post_title;
				$object->posts[$key_p]['thumbnail'] = $post->thumbnails['thumbnail'];
				$object->posts[$key_p]['post_json'] = $post->post_json;
			}
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

	public function api_posts($generate){

		header( "Content-type: application/json");
		$post_types = explode(",", get_option('post_types_rlout'));
		foreach ($post_types as $key => $post_type) {
			$posts = get_posts(
				array(
					'post_type'=>$post_type,
					'posts_per_page' => -1,
					'order'=>'DESC',
					'orderby'=>'post_modified'
				)
			);

			$posts_arr = array();
			$i=0;
			$rpl = get_option('replace_url_rlout');
			if(empty($rpl)){
				$rpl = site_url().'/html';
			}
			foreach ($posts as $key => $post) {

				$posts_arr[$key]['post_title'] = $post->post_title;
				$thumbnail = get_the_post_thumbnail_url($post, "thumbnail");
				if(empty($thumbnail)){
					$thumbnail = get_template_directory_uri().'/img/default.jpg';
					$thumbnail = str_replace(get_template_directory_uri(), $rpl, $thumbnail);
				}
				$posts_arr[$key]['thumbnail'] = $thumbnail;
				$url = str_replace(site_url(),$rpl,get_permalink($post)).'index.json';
				$posts_arr[$key]['post_json'] = $url;

				$taxonomies = explode(",", get_option('taxonomies_rlout'));

				if(!empty($taxonomies)){

					$term = wp_get_post_terms($post->ID,  $taxonomies);

					if(!empty($term) && empty($term->errors)){
						$url = str_replace(site_url(),$rpl,get_term_link($term[0])).'index.json';

						$posts_arr[$key]['term_id'] = $term[0]->term_id;
						$posts_arr[$key]['term_name'] = $term[0]->name;
						$posts_arr[$key]['term_json'] = $url;
					}
				}
				$i++;
				if($i==1000){
					$i=0;
					sleep(1);
				}
			}

			$response = json_encode($posts_arr , JSON_UNESCAPED_SLASHES);

			if($generate==true){

				sleep(1);
				$replace_uploads = get_option('uploads_rlout');

				$uploads_url_rlout = get_option('uploads_url_rlout'); 

				if($replace_uploads){

					$upload_url = wp_upload_dir();						

					$response = str_replace($upload_url['baseurl'], $rpl.'/uploads', $response);
					if($uploads_url_rlout){
						sleep(1);
						$response = str_replace($uploads_url_rlout, $rpl.'/uploads', $response);
					}

				}

				$dir_base =  get_option("path_rlout");
				if( is_dir($dir_base) === false ){
					mkdir($dir_base);
				}

				$file_path = $dir_base . '/'.$post_type.'.json';

				$file = fopen($file_path, "w");

				fwrite($file, $response);

				$this->ftp_upload_file($file_path);
				$this->s3_upload_file($file_path);
			}else{

				die($response);
			}
		}
	}

	public function api_terms($generate){

		header( "Content-type: application/json");

		$taxonomies = explode(",", get_option('taxonomies_rlout'));

		foreach($taxonomies as $tax){

			$terms = get_terms(array("taxonomy"=>$tax, 'hide_empty' => false));

			$rpl = get_option('replace_url_rlout');
			if(empty($rpl)){
				$rpl = site_url().'/html';
			}

			foreach ($terms as $key => $term) {
				$term = $this->object_term($term, false);
			}

			$response = json_encode($terms , JSON_UNESCAPED_SLASHES);

			if($generate==true){

				sleep(1);

				$replace_uploads = get_option('uploads_rlout');

				$uploads_url_rlout = get_option('uploads_url_rlout'); 

				if($replace_uploads){

					$upload_url = wp_upload_dir();						

					$response = str_replace($upload_url['baseurl'], $rpl.'/uploads', $response);

					sleep(1);

					if($uploads_url_rlout){
						$response = str_replace($uploads_url_rlout, $rpl.'/uploads', $response);
					}
				}

				$dir_base =  get_option("path_rlout");
				if( is_dir($dir_base) === false ){
					mkdir($dir_base);
				}

				$file_path = $dir_base . '/'.$tax.'.json';

				$file = fopen($file_path, "w");

				fwrite($file, $response);

				$this->ftp_upload_file($file_path);
				$this->s3_upload_file($file_path);
			}else{

				die($response);
			}
		}
	}

	public function replace_reponse($url_replace, $response, $media=null, $debug=false){

			// pegando itens 
		$itens_theme = explode($url_replace, $response);

		unset($itens_theme[0]);
		foreach($itens_theme as $keyj => $item){

			$item = explode('"', $item);
			$item = explode("'", $item[0]);
			$item = explode(")", $item[0]);
			$item = $url_replace . $item[0];

			if(!empty($item)){
				$this->deploy_upload($item, $media);
				$this->repeat_files_rlout[] = $item;
			}
		}


		//replace url
		$rpl = get_option('replace_url_rlout');
		if(empty($rpl)){
			$rpl = site_url();
		}
		$rpl = $rpl . $media;
		if(!empty($rpl) && $rpl!=site_url() && $rpl!=$url_replace){

			$response = str_replace($url_replace, $rpl, $response);
			if(!$media){
				$response = str_replace(site_url(), $rpl, $response);
			}
		}else{

			$rpl_theme = explode(site_url(), $url_replace);
			$rpl = site_url('html'.$media);

			$response = str_replace($rpl_theme[1], '', $response);
			$response = str_replace(site_url(), $rpl, $response);
		}

		return $response;
	}

	public function deploy_upload($url, $media=null){

		if(!in_array($url, $this->repeat_files_rlout)){

			$curl = curl_init();

			$url = explode('?', $url);

			$url = $url[0];

			$url_point = explode(".", $url);

			$url_space = explode(" ", $url_point[count($url_point)-1]);

			$url_point[count($url_point)-1] = $url_space[0];

			$url = implode(".", $url_point);

			curl_setopt_array($curl, array(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 120,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "GET",
				CURLOPT_HTTPHEADER => array(
					"cache-control: no-cache"
				),
			));

			$response = curl_exec($curl);
			$err = curl_error($curl);

			curl_close($curl);

			if ($err) {
				echo "cURL Error #:" . $err;
			} else {

				$response = $this->replace_json($response);

				$dir_base =  get_option("path_rlout");
				if( is_dir($dir_base) === false ){
					mkdir($dir_base);
				}

				if($media){
					$dir_base =  get_option("path_rlout") . $media;
					if( is_dir($dir_base) === false ){
						mkdir($dir_base);
					}
				}
				
				$url = urldecode($url);

				if($media){
					$upload_url = wp_upload_dir();
					$uploads_url_rlout = get_option('uploads_url_rlout'); 
					$file_name = str_replace($upload_url['baseurl'], '', $url);

					if($uploads_url_rlout){
						$file_name = str_replace($uploads_url_rlout, '', $file_name);
					}
				}else{
					$file_name = str_replace(get_template_directory_uri(), '', $url);
					$file_name = str_replace(site_url(), '', $file_name);
				}

				$folders = explode("/", $file_name);
				foreach ($folders as $key => $folder) {
					if($key+1<count($folders)){
						$dir_base = $dir_base . '/' . $folder;
						if( is_dir($dir_base) === false ){
							mkdir($dir_base);
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

								$attr = str_replace(get_option("path_rlout"), '', $attr);

								$attr = get_template_directory_uri() . $attr;

								$svg = explode("data:image", $attr);

								if(!$svg[1]){
									$this->deploy_upload($attr);
									$this->repeat_files_rlout[] = $attr;
								}
							}
						}
					}
				}

				$folders_point = explode(".", end($folders));

				$folders_space = explode(" ", $folders_point[count($folders_point)-1]);

				$folders_point[count($folders_point)-1] = $folders_space[0];

				$folders = implode(".", $folders_point);

				$file = fopen( $dir_base . '/' . $folders,"w");

				fwrite($file, $response);

				$this->ftp_upload_file($dir_base . '/' . $folders);
				$this->s3_upload_file($dir_base . '/' . $folders);
			}
		}
	}

	public function replace_json($response){

		$jsons = explode(",", get_option("api_1_rlout"));

		foreach ($jsons as $key => $json) {

			$json_name = explode("action=", $json);
			$json_name = explode("&", $json_name[1]);
			$json_name = get_option("path_rlout") . $json_name[0] . '.json';

			$response = str_replace($json, $json_name, $response);
		}

		return $response;
	}

	public function s3_upload_file($file_dir){

		if($file_dir){
			$access_key = get_option('s3_key_rlout');
			$secret_key = get_option('s3_secret_rlout');

			// echo $secret_key;
			if(!empty($secret_key)){

				session_start();

        		// creates a client object, informing AWS credentials
				$clientS3 = S3Client::factory(array(
					'key'    => $access_key,
					'secret' => $secret_key
				));
        		// putObject method sends data to the chosen bucket (in our case, teste-marcelo)

        		$file_dir = str_replace("//", "/", $file_dir);
        		
        		$key_file_s3 = str_replace(get_option("path_rlout").'/','', $file_dir);
        		$key_file_s3 = str_replace(get_option("path_rlout"),'', $key_file_s3);

        		if(!empty($key_file_s3)){
					$response = $clientS3->putObject(array(
						'Bucket' => get_option('s3_bucket_rlout'),
						'Key'    => $key_file_s3,
						'SourceFile' => $file_dir,
						'ACL'    => 'public-read'
					));
				}

			}
		}
	}

	public function s3_remove_file($file_dir){

		$access_key = get_option('s3_key_rlout');
		$secret_key = get_option('s3_secret_rlout');

		if(!empty($secret_key)){

			session_start();

        		// creates a client object, informing AWS credentials
			$clientS3 = S3Client::factory(array(
				'key'    => $access_key,
				'secret' => $secret_key
			));

			$response = $clientS3->deleteObject(array(
				'Bucket' => get_option('s3_bucket_rlout'),
				'Key' => str_replace(get_option("path_rlout"),'', $file_dir)
			));


			return $response;

		}
	}

	public function ftp_upload_file($file_dir){

		 $ftp_server = get_option('ftp_host_rlout');//serverip

		 if(!empty($ftp_server)){

		 	$conn_id = ftp_connect($ftp_server);

    		// login with username and password
		 	$user = get_option('ftp_user_rlout');

		 	$passwd = get_option('ftp_passwd_rlout');

		 	$folder = get_option('ftp_folder_rlout');
		 	
		 	$login_result = ftp_login($conn_id, $user, $passwd);

		 	$destination_file = $folder . str_replace(get_option("path_rlout"), '', $file_dir);

			// upload the file
		 	$upload = ftp_put($conn_id, $destination_file, $file_dir, FTP_BINARY);

			// close the FTP stream
		 	ftp_close($conn_id);
		 }

		}

		public function ftp_remove_file($file_dir){

		 $ftp_server = get_option('ftp_host_rlout');//serverip

		 if(!empty($ftp_server)){

		 	$conn_id = ftp_connect($ftp_server);

    		// login with username and password
		 	$user = get_option('ftp_user_rlout');

		 	$passwd = get_option('ftp_passwd_rlout');

		 	$folder = get_option('ftp_folder_rlout');
		 	
		 	$login_result = ftp_login($conn_id, $user, $passwd);

		 	$destination_file = $folder . str_replace(get_option("path_rlout"), '', $file_dir);

			// upload the file
		 	$delete = ftp_delete($conn_id, $destination_file);

			// close the FTP stream
		 	ftp_close($conn_id);
		 }

		}

		public function git_upload_file($commit){

			$repository = get_option('git_repository_rlout');

			if(!empty($repository)){

				$commands = array();

				$commands[] = 'cd ' . get_option("path_rlout");

				$commands[] = 'git init';

				$commands[] = 'git remote add origin ' . $repository;

				$commands[] = 'git add .';

				$commands[] = 'git commit -m "'. $commit .'" ';

				$commands[] = 'git push origin master -f';

				$command = implode(" && ", $commands);

				$process = proc_open(
					$command,
					array(
      				// STDIN.``
						0 => array("pipe", "r"),
      				// STDOUT.
						1 => array("pipe", "w"),
      				// STDERR.
						2 => array("pipe", "w"),
					),
					$pipes
				);
				if ($process === FALSE) {
					die();
				}
				$stdout = stream_get_contents($pipes[1]);
				$stderr = stream_get_contents($pipes[2]);
				fclose($pipes[1]);
				fclose($pipes[2]);
				proc_close($process);
				// var_dump($stderr);
				// var_dump($stdout);

				// die();
			}
		}

		public function add_admin_menu(){

			add_menu_page(
				$this->name_plugin,
				$this->name_plugin,
				'manage_options', 
				sanitize_title($this->name_plugin), 
				array($this,'reloutputhtml_home'), 
    			'', //URL ICON
    			93.1110 // Ordem menu
    		);

			add_submenu_page( 
				sanitize_title($this->name_plugin), 
				'Configurações', 
				'Configurações', 
				'manage_options', 
				sanitize_title($this->name_plugin).'-config', 
				array($this,'reloutputhtml_settings')
			);
		}

		public function reloutputhtml_home(){

			$fields = array('primeira_config'=>'Primeira Configuração');

			include "templates/home.php";
		}

		public function reloutputhtml_settings(){

			if ( ! function_exists( 'get_home_path' ) || ! function_exists( 'wp_get_current_user' ) ) {
				include_once(ABSPATH . '/wp-admin/includes/file.php');
				include_once(ABSPATH . '/wp-includes/pluggable.php');
			}

			$fields = array();
			$fields['replace_url_rlout'] = array('type'=>'text','label'=>'Substituir a URL <br>
				<small>Default: ('.site_url().')</small>');

			$fields['post_types_rlout'] = array('type'=>'select', 'label'=>'Post Type para deploy', 'multiple'=>'multiple');
			$fields['post_types_rlout']['options'] = get_post_types();


			$fields['taxonomies_rlout'] = array('type'=>'select', 'label'=>'Taxonomy para deploy', 'multiple'=>'multiple');
			$fields['taxonomies_rlout']['options'] = get_taxonomies();

			$fields['uploads_rlout'] = array('type'=>'checkbox', 'label'=>"<small> Todas as imagens em: <br>
				(<b>".wp_upload_dir()['baseurl']."</b>) serão TRANSFERIDAS</small>");

			$fields['uploads_url_rlout'] = array('type'=>'text', 'label'=>"<small> URL de imagens para transferi-las");

			$fields['path_rlout'] = array('type'=>'text', 'label'=>"Path:<br><small> ".get_home_path() . 'html/</small>');

			$fields['robots_rlout'] = array('type'=>'checkbox', 'label'=>'Evitar mecanismos de pesquisa em: '.site_url());

			//$fields['horario_cron_rlout'] = array('type'=>'time', 'label'=>'Horário para sincronização diária');

			$fields['api_1_rlout'] = array('type'=>'repeater','label'=>'URL API AJAX STATIC<br>
				<small>Default: ('.site_url().'/wp-admin/admin-ajax.php?action=<u>EXEMPLO</u>)</small>');

			$fields['subfiles_rlout'] = array('type'=>'repeater','label'=>'Arquivos ignorados<br>
				<small>insira a URL de todos os arquivos que foram ignorados pelo sistema.</small>');

			$fields['s3_rlout'] = array('type'=>'label','label'=>'Storage AWS S3');

			$fields['s3_key_rlout'] = array('type'=>'text', 'label'=>'S3 Key');

			$fields['s3_secret_rlout'] = array('type'=>'text', 'label'=>'S3 Secret');

			$fields['s3_region_rlout'] = array('type'=>'select', 'label'=>'S3 Region');
			$fields['s3_region_rlout']['options'][] = 'us-east-1';
			$fields['s3_region_rlout']['options'][] = 'us-east-2';
			$fields['s3_region_rlout']['options'][] = 'us-west-1';
			$fields['s3_region_rlout']['options'][] = 'us-west-2';
			$fields['s3_region_rlout']['options'][] = 'ca-central-1';
			$fields['s3_region_rlout']['options'][] = 'ap-south-1';
			$fields['s3_region_rlout']['options'][] = 'ap-northeast-2';
			$fields['s3_region_rlout']['options'][] = 'ap-southeast-1';
			$fields['s3_region_rlout']['options'][] = 'ap-southeast-2';
			$fields['s3_region_rlout']['options'][] = 'ap-northeast-1';
			$fields['s3_region_rlout']['options'][] = 'eu-central-1';
			$fields['s3_region_rlout']['options'][] = 'eu-west-1';
			$fields['s3_region_rlout']['options'][] = 'eu-west-2';
			$fields['s3_region_rlout']['options'][] = 'sa-east-1';

			$fields['s3_bucket_rlout'] = array('type'=>'text', 'label'=>'S3 Bucket');

			$fields['ftp_rlout'] = array('type'=>'label','label'=>'FTP SERVER');
			$fields['ftp_host_rlout'] = array('type'=>'text','label'=>'FTP Host');
			$fields['ftp_user_rlout'] = array('type'=>'text','label'=>'FTP User');
			$fields['ftp_passwd_rlout'] = array('type'=>'text','label'=>'FTP Password');
			$fields['ftp_folder_rlout'] = array('type'=>'text','label'=>'FTP Pasta 
				<br> <small>Sempre inserir <u>/</u> (barra) no final</small>');

			$fields['git_rlout'] = array('type'=>'label','label'=>'GITHUB PAGES');
			$fields['git_repository_rlout'] = array('type'=>'text', 'label'=>'URL Repository github');


			include "templates/configuracoes.php";
		}

	}

	$init_plugin = new RelOutputHtml;

	?>