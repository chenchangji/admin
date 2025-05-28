<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ComposeVideo;
use App\Utils\AliCloudVideoStitcher;

class SyncOssCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:oss';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步视频拼接任务结果';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //找到需要同步状态的job
        echo  '开始同步拼接的OSS视频状态！';
        $videos = ComposeVideo::query()->where('status', 0)->get()->toArray();
        foreach ($videos as $video) {
            $job_id = data_get($video, 'job_id');
            try {
                //1. 初始化服务
                $videoStitcher = new AliCloudVideoStitcher();

                $response = $videoStitcher->queryJobStatus($job_id);
                //拼接成功则更新视频信息
                if (data_get($response, 'body.jobList.job.0.state') ==='TranscodeSuccess' 
                    && !empty(data_get($response, 'body.jobList.job.0.output.outputFile.object'))) {
                        $url = 'https://compose-video.oss-cn-shenzhen.aliyuncs.com/'.data_get($response, 'body.jobList.job.0.output.outputFile.object');
                        $cover_url = $url .'?x-oss-process=video/snapshot,t_10000,m_fast';
                        ComposeVideo::find($video['id'])->update([
                            'url'       => $url,
                            'video_cover_url' => $cover_url,
                            'status'       => 1,
                        ]);
                }
            } catch (Exception $e) {
                logger("获取视频拼接结果失败: " . $e->getMessage() . "\n");
            }
        }                               

    }
}
