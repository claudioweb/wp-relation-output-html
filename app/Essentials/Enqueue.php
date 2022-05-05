<?php

namespace WpRloutHtml\Essentials;

use WpRloutHtml\Helpers;

Class Enqueue {

    public function __construct(){
            
        // Removendo algumas Informações default do wordpress no header
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'wp_generator');
        
        // Action para incorporar via wp enquee o resources/js/myscript.js
        add_action('admin_enqueue_scripts', array($this, 'my_enqueue') );

        // Action para incorporar via wp enquee a lib de js resources/js/lib/select2
        add_action( 'admin_enqueue_scripts', array($this,'rudr_select2_enqueue') );

        // Action que incorpora funções inline no footer de HTML/CSS/JS
		add_filter( 'update_footer', array($this, 'config_admin_var') );

        add_action('enqueue_block_editor_assets', function() {
            wp_enqueue_script(
                'relation-output-gutenberg',
                trailingslashit(plugins_url()) . 'wp-relation-output-html/resources/inc/js/gutenberg.js',
                array( 'wp-i18n', 'wp-blocks', 'wp-edit-post', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-plugins', 'wp-edit-post' )
            );
        } );
    }

    public function my_enqueue($hook) {

        if ('post.php' == $hook || 'edit-tags.php' == $hook || 'edit.php' == $hook || 'term.php' == $hook) {
            
            global $post;
            
            $post_types = explode(',', Helpers::getOption('post_types_rlout'));

            $post_type = null;
            if(!empty($_GET['post_type'])){
                $post_type = $_GET['post_type'];
            }
            
            if(empty($post_type)){
                $post_type = $post->post_type;
            }
            
            $taxonomies = explode(',', Helpers::getOption('taxonomies_rlout'));
            $taxonomy = null;
            if(!empty($_GET['taxonomy'])){
                $taxonomy = $_GET['taxonomy'];
            }

            if(in_array($taxonomy, $taxonomies) || in_array($post_type, $post_types)){
                
                wp_enqueue_script('my_custom_script_relation_output', site_url() . '/wp-content/plugins/wp-relation-output-html/resources/inc/js/myscript.js');
            }
        }
    }
    
    public function rudr_select2_enqueue(){
        
        if(!empty($_GET['page'])){
            if($_GET['page']=='relation-output-html-config'){
                wp_enqueue_style('select2', site_url() . '/wp-content/plugins/wp-relation-output-html/resources/inc/css/lib/select2.min.css' );
                wp_enqueue_script('select2', site_url() . '/wp-content/plugins/wp-relation-output-html/resources/inc/js/lib/select2.min.js', array('jquery') );
                
                wp_enqueue_script('my_custom_script_relation_output', site_url() . '/wp-content/plugins/wp-relation-output-html/resources/inc/js/select2.js');
            }
        }
    }
    
    public function config_admin_var(){
        $rpl = Helpers::getOption('replace_url_rlout');
		if(empty($rpl)){
			$rpl = site_url().'/html';
		}
        echo '<input type="hidden" name="static_url_rlout" value="'.$rpl.'/" />';
        echo '<style>#loading_rlout h2{text-align:center;} #loading_rlout{display:none;position:fixed;left:0;top:0;width:100%;height:100%;z-index: 99999;background:rgba(255,255,255,0.9);} #loading_rlout .loader_rlout{position: relative;margin: 60px auto;display: block;top: 33%;border:16px solid #f3f3f3;border-radius:50%;border-top:16px solid #3498db;width:120px;height:120px;-webkit-animation:spin 2s linear infinite;animation:spin 2s linear infinite}@-webkit-keyframes spin{0%{-webkit-transform:rotate(0)}100%{-webkit-transform:rotate(360deg)}}@keyframes spin{0%{transform:rotate(0)}100%{transform:rotate(360deg)}}</style>';
        echo '<div id="loading_rlout"><div class="loader_rlout"></div><h2>Por favor aguarde um instante, estamos processando o HTML.</h2></div>';
        echo '<script>jQuery(function(){ jQuery("#wp-admin-bar-relation-output-html-rlout li a").click(function(){jQuery("#loading_rlout").fadeIn();}); });</script>';
    }
}