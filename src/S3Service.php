<?php 
namespace PhpS3Service;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;


class S3Service
{
  
  public function __construct($config)
  {
    $this->config = array_merge(array(
      'defaultExpiresIn' => '+7 days'    
    ),$config);
    
    $this->s3Client = new S3Client([
        'region' => $config['region'],
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

  public function createYourBucket()
  {
    try 
    {
      $this->s3Client->createBucket([
        'Bucket' => $this->config['bucket'],
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
  
  public function getExternalUri($key)
  {
    return $this->getPresignedUrl($key, $this->config['defaultExpiresIn']);
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
  
  public function getPaginator()
  {
    return $this->s3Client->getIterator('ListObjects', [
        'Bucket' => $this->config['bucket']
    ]);    
  }
	
}