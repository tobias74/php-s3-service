<?php 
namespace PhpS3Service;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;


class S3Service
{
  
  public function __construct($config)
  {
    $this->config = $config;
    
    $this->s3Client = new S3Client([
        'region' => 'us-west-2',
        'endpoint' => $config['endpoint'],
        'use_path_style_endpoint' => true,
        'credentials' => [
            'key'    => $config['key'],
            'secret' => $config['secret'],
        ],
        'version' => 'latest'
    ]);

    $this->s3Client->registerStreamWrapper();
  }

  public function createBucket($bucketName)
  {
    try 
    {
      $this->s3Client->createBucket([
        'Bucket' => $bucketName,
      ]);
      echo "Bucket seems to have been created...";
    }
    catch (AwsException $e) 
    {
      echo $e->getMessage();
    }    
  }

  public function storeFile($sourceFilePath, $key)
  {
    $insert = $this->s3Client->putObject([
      'Bucket' => $this->config['bucket'],
      'Key'    => $key,
      'ContentType' => mime_content_type($sourceFilePath),
      'SourceFile'   => $sourceFilePath
    ]);
  }

  public function deleteFile($key)
  {
    $this->s3Client->deleteObject(array(
      'Bucket' => $this->config['bucket'],
      'Key'    => $key
    ));    
  }
  
  public function getStream($key)
  {
    $context = stream_context_create(array(
        's3' => array(
            'seekable' => true
        )
    ));    

    return fopen('s3://' . $this->config['bucket']. '/' . $key, 'r', false, $context);    
  }
  
  public function getFileUri($key)
  {
    return 's3://' . $this->config['bucket']. '/' . $key;    
  }
  
  public function getExternalUrl($key)
  {
    return $this->s3Client->getObjectUrl($this->config['bucket'], $key);
  }
  
  public function getPresignedUrl($key, $expiresIn='+10 minutes')
  {
    $command = $this->s3Client->getCommand('GetObject', [
        'Bucket' => $this->config['bucket'],
        'Key'    => $key
    ]);
    
    $presignedRequest = $this->s3Client->createPresignedRequest($command, $expiresIn);
    
    return (string) $presignedRequest->getUri();    
  }
	
}