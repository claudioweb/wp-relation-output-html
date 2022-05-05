<?php

namespace WpRloutHtml;

// Essentials
use WpRloutHtml\Essentials\Enqueue;
use WpRloutHtml\Essentials\Menu;
use WpRloutHtml\Essentials\WpAjax;

// Apps
use WpRloutHtml\Posts;
use WpRloutHtml\Terms;

// Modelules
use WpRloutHtml\Modules\Logs;
use WpRloutHtml\Modules\Auxiliar;
 
Class App {
    
    // nome do plugin
    static $name_plugin = 'Relation Output HTML';
    
    // Evitar repetições de arquivos para a estatização
    static $repeat_files_rlout = array();
    
    public function __construct(){

        // create table
        $this->logs = new Logs;
        $this->logs->createTable();
        
        $this->aux = new Auxiliar;
        $this->aux->createTable();

        // Enquee de Js e Css
        $this->enqueue = new Enqueue;

        // Configurações do menu e dos resources
        $this->menu = new Menu;

        // Iniciando actions em Ajax privada
        $this->wpajax = new WpAjax;

        // Iniciando verificação para as alterações de terms
        $this->terms = new Terms;

        add_action('init', function() {
            
			register_meta('post', '_static_output_html', array(
				'type'		=> 'boolean',
				'single'	=> true,
				'show_in_rest'	=> true,
                'auth_callback' => function() {
                    return current_user_can('edit_posts');
                }
			 ));

            $this->posts = new Posts;

		});

    }
    
}