<?php

namespace WpRloutHtml\Modules;

use Aws\CloudFront\CloudFrontClient;
use WpRloutHtml\Helpers;

Class Cloudfront {

	// Verifica se recebeu atalho para limpar cloudfront em /*
	public function __construct(){
		
		if(isset($_GET['cloudfront_rlout'])){
			
			$response_cloudfront = Cloudfront::invalid('/*');
			if($response_cloudfront){
				echo '<script>alert("Cloudfront Atualizado!");</script>';
			}
		}
	}

    static function invalid($response){
        // debug_print_backtrace();
        $DistributionId = Helpers::getOption('s3_distributionid_rlout');

		if(!empty($DistributionId)){
			$CallerReference = (string) rand(100000,9999999).strtotime(date('Y-m-dH:i:s'));
			$raiz = str_replace(site_url(), '', $response);
			$raiz = str_replace(Helpers::getOption('path_rlout'), '', $raiz);
			
			$access_key = Helpers::getOption('s3_key_rlout');
			$secret_key = Helpers::getOption('s3_secret_rlout');
			$acl_key = Helpers::getOption('s3_acl_rlout');
			$region = Helpers::getOption('s3_region_rlout');
			
			$cloudFrontClient = new CloudFrontClient([
				'region' => $region,
				'version' => 'latest',
				'credentials' => [
					'key'    => $access_key,
					'secret' => $secret_key,
				]
			]);

			$args = [
				'DistributionId' => $DistributionId,
				'InvalidationBatch' => [
					'CallerReference' => $CallerReference,
					'Paths' => [
						'Items' => [$raiz],
						'Quantity' => 1,
					],
				]
			];
			
			$cloudFrontClient->createInvalidation($args);
			
		}
    }
}