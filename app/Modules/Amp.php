<?php

namespace WpRloutHtml\Modules;

use WpRloutHtml\Helpers;

Class Amp {
    
    static function urls(){
        
        $qtd_pages = Helpers::getOption('amp_pagination_rlout');
        
        $urls_all = array();
        
        $urls_pages = array();

        $post_types = explode(',', Helpers::getOption('post_types_rlout'));

        foreach($post_types as $post_type){
            $urls_all[] = get_post_type_archive_link($post_type).'/';
        }
        
        $taxonomies = explode(',', Helpers::getOption('taxonomies_rlout'));
        
        $terms = get_terms(array(
            'taxonomy'=> $taxonomies,
            'hide_empty'=>true
        ));
        
        foreach($terms as $key_term => $term){
            $link = get_term_link($term);
            if($link){
                $urls_all[] = $link;
            }
        }

        foreach($urls_all as $u_key => $url){
            for ($i=1; $i <= intval($qtd_pages); $i++) { 
                $urls_pages[] = $url.'amp/page/'.$i.'/';
            }
        }
        
        return $urls_pages; 
    }
    
    static function remove_pagination($response, $static_url=null){
        
        $static_url = explode('amp/page/', $static_url);

        $qtd_pages = Helpers::getOption('amp_pagination_rlout');

        $static_url = str_replace(site_url(), Helpers::getOption('replace_url_rlout'), $static_url[0]);
   
        $string_pagination = 'amp/page/'.($qtd_pages+1).'/';
        
        if(strpos($response, $string_pagination)){
            $response = str_replace('<div class="right"><a href="'.$static_url.$string_pagination.'" >Ver mais posts</a></div>','',$response);
         }
        
        return $response;
    }
}