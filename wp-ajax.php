<?php 
require "vendor/autoload.php";

use Aws\S3\S3Client;

Class WpAjaxRelOutHtml {
    
    public function __construct() {
        
        // deploy
        add_action('wp_ajax_static_output_deploy', array($this, 'deploy') );
        
        // deploy
        add_action('wp_ajax_static_output_deploy_json', array($this, 'deploy_json') );
        
        // get files
        add_action('wp_ajax_static_output_files', array($this, 'files') );
    }
    
    public function deploy(){
        $file = $_GET['file_url'];
        if(!empty($file)){
            $rlout = new RelOutputHtml;
            die($rlout->curl_generate($file));
        }
    }
    
    public function deploy_json(){
        $rlout = new RelOutputHtml;
        $terms = $rlout->api_terms(true);
        $posts = $rlout->api_posts(true);
        
        $urls = array_merge($terms, $posts);
        die(json_encode($urls));
    }
    
    public function files(){
        
        $rlout = new RelOutputHtml;
        $taxonomy = $_GET['taxonomy'];
        $post_type = $_GET['post_type'];
        $urls = array();

        // Subfiles
        $files = explode(',', get_option("subfiles_rlout"));

		foreach ($files as $key => $file) {

			if(!empty($file)){
                $urls[] = $file;
            }
        }
        
        // Taxonomy
        if($taxonomy=='all'){
            $taxonomy = explode(",", get_option('taxonomies_rlout'));
        }else{
            $taxonomy = array($taxonomy);
        }
        foreach($taxonomy as $tax){
            $terms = get_terms(array("taxonomy"=>$tax, 'hide_empty' => false));
            $ignore_json_rlout = explode(',' ,get_option("ignore_json_rlout"));
            foreach ($terms as $key => $term) {
                $url = get_term_link($term);
                if(array_search($url, $ignore_json_rlout)!='NULL'){
                    $urls[] = $url;
                }
            }
        }
        // Post_type
        $args_posts = array();
        if($post_type=='all'){
            $post_type = explode(",", get_option('post_types_rlout'));
        }else{
            $post_type = array($post_type);
        }
        foreach($post_type as $pt){
            $url = get_post_type_archive_link($pt);
            if($url){
                $urls[] = $url;
            }
        }

        $urls = $this->recursive_post($post_type, $urls);
        
        header("Content-type: application/json");
        die(json_encode($urls));
    }
    public function recursive_post($post_type, $urls=array(), $not_in=array()){
        $args_posts = array();
        $args_posts['post_type'] = $post_type;
        $args_posts['posts_per_page'] = 25;
        $args_posts['order'] = 'DESC';
        $args_posts['orderby'] = 'date';
        $args_posts['post__not_in'] = $not_in;
  
        $posts = get_posts($args_posts);
        $ignore_json_rlout = explode(',' ,get_option("ignore_json_rlout"));
        foreach($posts as $post){
            $url = get_permalink($post);
            if(array_search($url, $ignore_json_rlout)!='NULL'){
                $not_in[] = $post->ID;
                $urls[] = $url;
            }
        }
        if(count($posts)==25){
            sleep(0.5);
            $urls = array_unique(array_merge($urls, $this->recursive_post($post_type, $urls, $not_in)));
        }
        return array_values($urls);
    }
}