<?php
namespace WpRloutHtml\Essentials;

use WpRloutHtml\App;
use WpRloutHtml\Helpers;

Class Cdn {
	
	// Envia todos os objetos recebidos para o generate
	static function clear_cdn(){
        $domain=Helpers::getOption('domain_cdn_rlout');
        $token=Helpers::getOption('cred_cdn_rlout');
		$ch_goc = curl_init("https://api.gocache.com.br/v1/cache/".$domain."/all");
		curl_setopt($ch_goc, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($ch_goc, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch_goc, CURLOPT_HTTPHEADER, array("GoCache-Token:".$token));
		$response_goc = curl_exec($ch_goc);
		$statusCode_goc = curl_getinfo($ch_goc, CURLINFO_HTTP_CODE);
		curl_close($ch_goc);
		if( $statusCode_goc == 200 ) {
			$msg= $domain." Limpeza do cache efetuada com sucesso!";
		}else{
			$msg= "CDN ".$domain." Falha na Limpeza do cache!";
		}
		return $msg;
	}
	
	// static function clear_cdn_file($file=null){
	// 	print "teste";
	// 	die();
    //     $domain=Helpers::getOption('domain_cdn_rlout');
    //     $token=Helpers::getOption('cred_cdn_rlout');
	// 	$ch_goc = curl_init("https://api.gocache.com.br/v1/cache/".$domain);
	// 	curl_setopt($ch_goc, CURLOPT_CUSTOMREQUEST, "DELETE");
	// 	curl_setopt($ch_goc, CURLOPT_RETURNTRANSFER, TRUE);
	// 	curl_setopt($ch_goc, CURLOPT_HTTPHEADER, array("GoCache-Token:".$token));
	// 	$urls_goc = array(
	// 				"urls[1]=https://www.".$domain=."/".$file,
	// 				"urls[2]=http://www.".$domain=."/".$file,
	// 				"urls[3]=https://".$domain=."/".$file,
	// 				"urls[4]=http://".$domain=."/".$file
	// 			);
	// 	curl_setopt($ch_goc, CURLOPT_POSTFIELDS, implode("&", $urls_goc));
	// 	$response_goc = curl_exec($ch_goc);
	// 	$statusCode_goc = curl_getinfo($ch_goc, CURLINFO_HTTP_CODE);
	// 	curl_close($ch_goc);
	// }
}