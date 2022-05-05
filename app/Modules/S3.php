<?php

namespace WpRloutHtml\Modules;

use Aws\S3\S3Client;
use Aws\S3\Transfer;
use WpRloutHtml\Modules\Cloudfront;
use WpRloutHtml\Helpers;

Class S3 {
    
    static function upload_file($file_dir, $ignore_cloud=true){
        
        if($file_dir){
            
            $access_key = Helpers::getOption('s3_key_rlout');
            $secret_key = Helpers::getOption('s3_secret_rlout');
            $acl_key = Helpers::getOption('s3_acl_rlout');
            $region = Helpers::getOption('s3_region_rlout');
            
            if(!empty($secret_key)){
                
                // creates a client object, informing AWS credentials
                $clientS3 = new S3Client([
                    'credentials' => [
                        'key'    => $access_key,
                        'secret' => $secret_key,
                    ],
                    'version' => 'latest',
                    'region' => $region,
                    ]
                );
                
                // putObject method sends data to the chosen bucket
                $file_dir = str_replace("//", "/", $file_dir);
                $file_dir = str_replace("./", "/", $file_dir);
                
                $key_file_s3 = str_replace(Helpers::getOption('path_rlout').'/','', $file_dir);
                $key_file_s3 = str_replace(Helpers::getOption('path_rlout'),'', $key_file_s3);
                
                $directory_empty = explode('/', $key_file_s3);
                
                if(!empty($key_file_s3) && !empty(end($directory_empty)) ){
                    
                    $response = $clientS3->putObject(array(
                        'Bucket' => Helpers::getOption('s3_bucket_rlout'),
                        'Key'    => $key_file_s3,
                        'SourceFile' => $file_dir,
                        'ACL'    => $acl_key,
                        'CacheControl' => 'max-age='.Helpers::getOption('s3_cachecontrol_rlout')
                    ));
                    
                    try {
                        $result = $clientS3->getObject([
                            'Bucket' => Helpers::getOption('s3_bucket_rlout'),
                            'Key' => $key_file_s3
                        ]);

                        if($result["@metadata"]["statusCode"]==200){
                            if($ignore_cloud==false){
                                
                                $key_file_s3_dir = str_replace('/index.html', '', $key_file_s3);
                                Cloudfront::invalid('/'.$key_file_s3_dir.'/');
                                Cloudfront::invalid('/'.$key_file_s3_dir.'*');
                            }
                            return true;
                        }

                        return false;
                        
                    }catch (Exception $e){
                        
                        return false;
                    }

                }else if(empty(end($directory_empty))){
                    
                    $verify_files = scandir($file_dir);
                    
                    if(count($verify_files)!=0){
                        
                        $construct = array(
                            'concurrency'=>30,
                            'before' => function (\Aws\CommandInterface $command) {
                                if (in_array($command->getName(), ['PutObject', 'CreateMultipartUpload'])) {
                                    $command['ACL'] = Helpers::getOption('s3_acl_rlout');
                                    $command['CacheControl'] = 'max-age='.Helpers::getOption('s3_cachecontrol_rlout');
                                }
                            }
                        );
                        $S3Transfer = new Transfer($clientS3, $file_dir, 's3://'.Helpers::getOption('s3_bucket_rlout').'/'.$key_file_s3, $construct);
                        
                        $S3Transfer->transfer();
                        $response = $S3Transfer->promise();
                        
                        if($response->getState()=="fulfilled"){
                            if($S3Transfer && $ignore_cloud==false){
                                Cloudfront::invalid($key_file_s3);
                            }
                            return true;
                        }else{
                            return false;
                        }

                    }
                    
                }
                return false;
            }
        }
    }
    
    static function remove_file($file_dir){
        
        $file_dir = str_replace("//", "/", $file_dir);
        $file_dir = str_replace("./", "/", $file_dir);
        
        $access_key = Helpers::getOption('s3_key_rlout');
        $secret_key = Helpers::getOption('s3_secret_rlout');
        $acl_key = Helpers::getOption('s3_acl_rlout');
        $region = Helpers::getOption('s3_region_rlout');
        
        if(!empty($secret_key)){
            
            // creates a client object, informing AWS credentials
            $clientS3 = new S3Client([
                'credentials' => [  
                    'key'    => $access_key,
                    'secret' => $secret_key,
                ],
                'version' => 'latest',
                'region' => $region,
                ]
            );
            
            $key_file_s3 = str_replace(Helpers::getOption('path_rlout').'/','', $file_dir);
            $key_file_s3 = str_replace(Helpers::getOption('path_rlout'),'', $key_file_s3);
            
            $directory_empty = explode('/', $key_file_s3);
            
            if(!empty($key_file_s3) && !empty(end($directory_empty))){
                
                $response = $clientS3->deleteObject(array(
                    'Bucket' => Helpers::getOption('s3_bucket_rlout'),
                    'Key' => $key_file_s3
                ));
                
                if($response){
                    $key_file_s3 = str_replace('/index.html', '', $key_file_s3);
                    Cloudfront::invalid('/'.$key_file_s3.'*');
                }
                
                return $response;
            }
            
        }
    }
}