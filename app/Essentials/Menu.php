<?php

namespace WpRloutHtml\Essentials;

use WpRloutHtml\App;

use WpRloutHtml\Modules\Cloudfront;
use WpRloutHtml\Modules\Logs;
use WpRloutHtml\Modules\Auxiliar;
use WpRloutHtml\Helpers;
use WpRloutHtml\Posts;
use WpRloutHtml\Terms;

Class Menu {

    public function __construct(){

        $this->logs = new Logs;
        $this->aux = new Auxiliar;
        
        // Inserindo as opções no menu do wp-admin
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ));

        // Inserindo as opções de atalho no header toolbar do wp-admin
        add_action('admin_bar_menu', array($this, 'add_toolbar_items'), 100);

        // Retirando o ver mais ou read more do excerpt padrão
		add_filter('excerpt_more', array($this, 'custom_excerpt_more') );

        // Incia a estatização de arquivos importantes
		if(isset($_GET['importants_rlout'])){
			
			add_action('init', function(){
                
				$response_essenciais = Helpers::importantfiles_generate();
				
				if($response_essenciais){
					
					echo '<script>alert("Páginas importantes Atualizadas!");</script>';
				}
			});
		}

        // Incia a estatização de páginas essenciais
		if(isset($_GET['essenciais_rlout'])){
			
			$response_essenciais = Helpers::subfiles_generate();
			
			if($response_essenciais){
				echo '<script>alert("Arquivos ignorados atualizados!");</script>';
			}
		}

        // Incia a limpeza de cache
		if(isset($_GET['cache_rlout'])){
			
			$response_cache = Cdn::clear_cdn();
			
			if($response_cache){
				echo '<script>alert("Go-Cache limpado com sucesso!");</script>';
			}
		}


        if(!empty($_POST['salvar_rlout'])){
			
			unset($_POST['salvar_rlout']);
			$key_fields = explode(',', $_POST['keys_fields']);
			foreach ($key_fields as $key_field) {
				$value_field = $_POST[$key_field];
				if(is_array($value_field)){
                    foreach($value_field as $value_key =>$value){
                        if(empty($value)){
                            unset($value_field[$value_key]);
                        }
                    }
					update_option( $key_field, implode(',', $value_field) );
				}else{
					update_option( $key_field, $value_field );
				}
			}
			
			$redirect_param = sanitize_title(App::$name_plugin) . '-config';

			header('Location:'.admin_url('admin.php?page='.$redirect_param));
			exit;
		}
    }

    public function custom_excerpt_more( $more ) {
		return '';
	}

    public function add_toolbar_items($admin_bar){
        
        $admin_bar->add_menu(array(
            'id'    => 'relation-output-html-rlout',
            'title' => 'Relation Output HTML',
            'parent' => null,
            'href'  => '',
            'meta' => ['title' => 'Limpeza e estatização dos principais arquivos e arquios ignorados']
        ));
        
        $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        
        $actual_link = str_replace('?cloudfront_rlout=true', '', $actual_link);
        $actual_link = str_replace('&cloudfront_rlout=true', '', $actual_link);
        
        $actual_link = str_replace('?essenciais_rlout=true', '', $actual_link);
        $actual_link = str_replace('&essenciais_rlout=true', '', $actual_link);
        
        $actual_link = str_replace('?importants_rlout=true', '', $actual_link);
        $actual_link = str_replace('&importants_rlout=true', '', $actual_link);
        
        $get_param = explode('?', $actual_link);
        
        if(count($get_param)>1){
            $cloudfront_link = $actual_link.'&cloudfront_rlout=true';
            $essenciais_link = $actual_link.'&essenciais_rlout=true';
            $cache_link = $actual_link.'&cache_rlout=true';
            $importants_link = $actual_link.'&importants_rlout=true';
        }else{
            $cloudfront_link = $actual_link.'?cloudfront_rlout=true';
            $essenciais_link = $actual_link.'?essenciais_rlout=true';
            $cache_link = $actual_link.'?cache_rlout=true';
            $importants_link = $actual_link.'?importants_rlout=true';
        }
        
        $DistributionId = Helpers::getOption('s3_distributionid_rlout');
        if(!empty($DistributionId)){

            $cloudfront = new Cloudfront;

            $admin_bar->add_menu( array(
                'id'    => 'cloudfront-html-rlout',
                'title' => 'Limpar Cloudfront',
                'parent' => 'relation-output-html-rlout',
                'href'  => $cloudfront_link
            ));
        }

        $GocacheToken = Helpers::getOption('cred_cdn_rlout');
        if(!empty($GocacheToken)){
            $admin_bar->add_menu( array(
                'id'    => 'cache-html-rlout',
                'title' => 'Limpar Go-Cache',
                'parent' => 'relation-output-html-rlout',
                'href'  => $cache_link
            ));
        }
        
        $admin_bar->add_menu( array(
            'id'    => 'importants-html-rlout',
            'title' => 'Atualizar páginas importantes',
            'parent' => 'relation-output-html-rlout',
            'href'  => $importants_link
        ));
        
        $admin_bar->add_menu( array(
            'id'    => 'essenciais-html-rlout',
            'title' => 'Gerar aquivos ignorados',
            'parent' => 'relation-output-html-rlout',
            'href'  => $essenciais_link
        ));

    }
    
    public function add_admin_menu(){

        // é necessário get_home_path() para continuar
        if ( ! function_exists( 'get_home_path' ) || ! function_exists( 'wp_get_current_user' ) ) {
            include_once(ABSPATH . '/wp-admin/includes/file.php');
            include_once(ABSPATH . '/wp-includes/pluggable.php');
        }
        
        $user = wp_get_current_user();
        
        if(in_array('administrator', $user->roles)){
            
            // Cria o item Home no menu
            add_menu_page(
                App::$name_plugin,
                App::$name_plugin,
                'manage_options', 
                sanitize_title(App::$name_plugin), 
                array($this,'reloutputhtml_home'), // função que cria os campos HTML
                '', //URL ICON
                93.1110 // Ordem menu
            );
            
            // Cria o item de Configurações no menu
            add_submenu_page( 
                sanitize_title(App::$name_plugin), 
                'Configurações', 
                'Configurações', 
                'manage_options', 
                sanitize_title(App::$name_plugin).'-config', 
                array($this,'reloutputhtml_settings') // função que cria os campos HTML
            );
        }
    }

    public function reloutputhtml_home(){
        
        $fields = array('primeira_config'=>'Primeira Configuração');
        $this->name_plugin = App::$name_plugin;

        include WP_PLUGIN_DIR . "/wp-relation-output-html/resources/home.php";
    }
    
    public function reloutputhtml_settings(){
        
        if ( ! function_exists( 'get_home_path' ) || ! function_exists( 'wp_get_current_user' ) ) {
            include_once(ABSPATH . '/wp-admin/includes/file.php');
            include_once(ABSPATH . '/wp-includes/pluggable.php');
        }
        
        $fields = array();

        $fields['replace_url_rlout'] = array('type'=>'text','label'=>'Substituir a URL <br>
        <small>Default: ('.site_url().'/html)</small>');
        
        $fields['post_types_rlout'] = array('type'=>'select2', 'label'=>'Post Type para deploy', 'multiple'=>'multiple');
        $fields['post_types_rlout']['options'] = get_post_types();
        
        $fields['taxonomies_rlout'] = array('type'=>'select2', 'label'=>'Taxonomy para deploy', 'multiple'=>'multiple');
        $fields['taxonomies_rlout']['options'] = get_taxonomies();

        $fields['range_posts_rlout'] = array('type'=>'number', 'label'=>'<small> Range de estatização</small>', 'default'=>50);
        $fields['range_posts_get_rlout'] = array('type'=>'number', 'label'=>'<small> Quantidade de requisições por vez</small>','default'=>1);

        $fields['parent_term_rlout'] = array('type'=>'checkbox', 'label'=>'Verificar se os terms possui um parent');
        
        $fields['size_thumbnail_rlout'] = array('type'=>'select', 'label'=>'Tamanho padrão (thumbnail)');
        $sizes = get_intermediate_image_sizes();
        foreach($sizes as $size){
            $fields['size_thumbnail_rlout']['options'][] = $size;
        }
        
        $fields['path_rlout'] = array('type'=>'text','label'=>"Path HTML: <br><small>".get_home_path() . "html</small>",'default'=>get_home_path() . 'html');
        
        $fields['uri_rlout'] = array('type'=>'text', 'label'=>"Directory_uri():<br><small>Caminho do template</small>");
        
        $fields['ignore_json_rlout'] = array( 'multiple'=>'multiple','type'=>'select2','action_ajax'=>'all_search_posts','label'=>'Ignorar páginas no JSON<br>
        <small>insira a URL de todos os arquivos que devem ser ignorados no JSON. </small>');
        
        $fields['ignore_files_rlout'] = array( 'multiple'=>'multiple','type'=>'select2','action_ajax'=>'all_search_posts','label'=>'Ignorar páginas<br>
        <small>insira a URL de todos os arquivos que devem ser ignorados. </small>');
        
        $fields['pages_important_rlout'] = array( 'multiple'=>'multiple','action_ajax'=>'all_search_posts','type'=>'select2','label'=>'Páginas importantes (URL)<br>
        <small>Páginas importantes para serem atualizadas ao atualizar os posts</small>');
        
        $fields['subfiles_rlout'] = array('type'=>'repeater','label'=>'Arquivos ignorados<br>
        <small>insira a URL de todos os arquivos que foram ignorados pelo sistema.</small>');

        $fields['amp_label_rlout'] = array('type'=>'label','label'=>'Configurações de AMP');
        
        $fields['amp_rlout'] = array('type'=>'checkbox', 'label'=>'<small> Estatizar Páginas AMP</small>');
        $fields['amp_pagination_rlout'] = array('type'=>'number', 'label'=>'<small> Quantidade de paginação em AMP</small>');

        $fields['s3_rlout'] = array('type'=>'label','label'=>'Storage AWS S3');
        
        $fields['s3_distributionid_rlout'] = array('type'=>'text','label'=>'Distribution ID (Cloudfront)');
        $fields['s3_cloudfront_auto_rlout'] = array('type'=>'checkbox', 'label'=>'Desativar limpeza automática (Cloudfront)');
        
        $fields['s3_key_rlout'] = array('type'=>'text', 'label'=>'S3 Key');
        
        $fields['s3_secret_rlout'] = array('type'=>'text', 'label'=>'S3 Secret');
        
        $fields['s3_cachecontrol_rlout'] = array('type'=>'number', 'label'=>'S3 Cache Control');
        
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
        
        $fields['s3_acl_rlout'] = array('type'=>'select', 'label'=>'S3 ACL');
        $fields['s3_acl_rlout']['options'][] = 'private';
        $fields['s3_acl_rlout']['options'][] = 'public-read';
        $fields['s3_acl_rlout']['options'][] = 'public-read-write';
        $fields['s3_acl_rlout']['options'][] = 'authenticated-read';
        $fields['s3_acl_rlout']['options'][] = 'aws-exec-read';
        $fields['s3_acl_rlout']['options'][] = 'bucket-owner-read';
        $fields['s3_acl_rlout']['options'][] = 'bucket-owner-full-control';
        
        $fields['s3_bucket_rlout'] = array('type'=>'text', 'label'=>'S3 Bucket');
        
        $fields['pwd_rlout'] = array('type'=>'label','label'=>'PWD ACESSO');
        $fields['userpwd_rlout'] = array('type'=>'text','label'=>'USUÁRIO PWD');
        $fields['passpwd_rlout'] = array('type'=>'text','label'=>'SENHA PWD');

        $fields['cdn_rlout'] = array('type'=>'label','label'=>'CDN Gocache');
        $fields['cred_cdn_rlout'] = array('type'=>'text','label'=>'Token');
        $fields['domain_cdn_rlout'] = array('type'=>'text','label'=>'Dóminio cadastrado');
        
        $this->name_plugin = App::$name_plugin;
        include WP_PLUGIN_DIR . "/wp-relation-output-html/resources/configuracoes.php";
    }
}