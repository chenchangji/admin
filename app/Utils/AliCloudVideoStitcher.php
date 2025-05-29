<?php

namespace App\Utils;

use AlibabaCloud\SDK\Mts\V20140618\Mts;
use AlibabaCloud\Darabonba\Env\Env;
use AlibabaCloud\Tea\Tea;
use AlibabaCloud\Tea\Utils\Utils;
use AlibabaCloud\Tea\Console\Console;
use Darabonba\OpenApi\Models\Config;
use AlibabaCloud\SDK\Mts\V20140618\Models\SubmitJobsRequest;
use AlibabaCloud\SDK\Mts\V20140618\Models\QueryJobListRequest;
use OSS\OssClient;
use OSS\Core\OssException;
use Exception;

class AliCloudVideoStitcher {
    private $pipelineId = "5a322426de424047acb5d30352e03ee3";
    private $templateId = "S00000002-200060"; #转码模板ID，按需配置
    private $ossLocation = "oss-cn-shenzhen";
    private $input_bucket = 'gzps-video';
    private $output_bucket = 'compose-video';
    private $oss_output_object  = "video/stitched-video.mp4";
    
     /**
     * @param string $accessKeyId
     * @param string $accessKeySecret
     * @param string $regionId
     * @return Mts
     * 正式环境下 建议 protocol = "HTTPS";
     */
    public static function createClient($accessKeyId, $accessKeySecret, $regionId){
        $config = new Config([]);
        $config->accessKeyId = $accessKeyId;
        $config->accessKeySecret = $accessKeySecret;
        $config->regionId = $regionId;
        $config->protocol = "HTTP";
        return new Mts($config);
    }
    
    /**
     * @return void
     */
    public  function submitStitchingJob($video_urls , $key){
        $client = self::createClient(env('OSS_ACCESS_KEY_ID'), env('OSS_ACCESS_KEY_SECRET'), 'cn-shenzhen');
        $input_object = ltrim($video_urls[0], '/'); 
        $request = new SubmitJobsRequest([
            "input" => json_encode(array(
                    'Location' => $this->ossLocation,
                    'Bucket' => $this->input_bucket,
                    'Object' => ltrim(parse_url($video_urls[0], PHP_URL_PATH), '/')
                )
            ),
            "outputBucket" => $this->output_bucket,
            "outputLocation" => $this->ossLocation,
            "pipelineId" => $this->pipelineId,
            "outputs" => $this->outputs($video_urls, $key),
        ]);
        $response = $client->submitJobs($request);
        return $response;
    }

    public function outputs($video_urls, $key) {
        $merge_config_url = $this->generateMergeConfig($video_urls);
        $output = [
            'OutputObject' => urlencode("video/".$key.".mp4"),
            'TemplateId' => $this->templateId,
            'MergeConfigUrl' => $merge_config_url
        ];
        return json_encode([$output]);
    }

     // 生成配置文件并返回OSS路径
    public function generateMergeConfig($video_urls) {
        // 构建配置文件内容
        $config = [
            'MergeList' => array_map(function($url) {
                return ['MergeURL' => $url];
            }, $video_urls)
        ];

        // 本地保存临时文件
        $localConfigPath = '../storage/tmp/concat-config-' . time() . '.json';
        file_put_contents($localConfigPath, json_encode($config, JSON_PRETTY_PRINT));

        // 上传到OSS
        $ossConfigPath = 'mps/configs/' . basename($localConfigPath);
        return $this->uploadToOSS($localConfigPath, $ossConfigPath);
    }

    // OSS上传方法
    private function uploadToOSS($localConfigPath, $ossConfigPath) {
        $result = '';
        try {
            // 创建OSS客户端实例
            $ossClient = new OssClient(env('OSS_ACCESS_KEY_ID'), env('OSS_ACCESS_KEY_SECRET'), 'oss-cn-shenzhen.aliyuncs.com');

            // 上传文件
            $result = $ossClient->uploadFile($this->output_bucket, $ossConfigPath, $localConfigPath);
            logger("上传成功，文件URL: " . $result['info']['url']);
            
            
        } catch (OssException $e) {
            logger("上传失败: " . $e->getMessage());
        }
        
        unlink($localConfigPath); // 删除本地临时文件
        return $result['info']['url'];
    }
            
    /**
     * 查询作业状态
     * @param string $jobId 作业ID
     * @return array
     */
    public function queryJobStatus(string $job_id) {
        $client = self::createClient(env('OSS_ACCESS_KEY_ID'), env('OSS_ACCESS_KEY_SECRET'), 'cn-shenzhen');
        $request = new QueryJobListRequest([
            "jobIds" => $job_id
        ]);
        $response = $client->queryJobList($request);
        return $response;
    }
}
?>