<?php

namespace App\Http\Controllers\Admin;

use App\Http\Filters\ComposeVideoFilter;
use App\Models\ComposeVideo;
use App\Http\Requests\ComposeVideoRequest;
use App\Http\Resources\ComposeVideoResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OSS\OssClient;
use OSS\Core\OssException;
use Illuminate\Support\Facades\Storage;
use ZipStream\ZipStream;
use ZipStream\Option\Archive as ArchiveOptions;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComposeVideoController extends Controller
{
    public function index(ComposeVideoFilter $filter)
    {
        $composeVideos = ComposeVideo::query()
            ->leftjoin('admin_users', 'compose_videos.creator_id', '=', 'admin_users.id')
            ->leftjoin('admin_templates', 'compose_videos.template_id', '=', 'admin_templates.id')
            ->where('compose_videos.status','!=',2)
            ->filter($filter)
            ->select('compose_videos.*','admin_users.name', 'admin_templates.class_rules')
            ->orderByDesc('id')
            ->paginate();

        return $this->ok(ComposeVideoResource::collection($composeVideos));
    }

    public function destroy(ComposeVideo $composeVideo)
    {
        $composeVideo->update(['status'=>2]);
        return $this->noContent();
    }



    public function batchDownload(Request $request)
    {
        $urls = $request->input('urls');
        $zipFileName = 'videos_'.date('Y-m-d').'.zip';

        // 1. 创建流式响应
        $response = new StreamedResponse(function() use ($urls, $zipFileName) {
            // 2. 配置流式压缩选项
            $opt = new ArchiveOptions();
            $opt->setSendHttpHeaders(false); // 禁用自动发送头，由StreamedResponse处理
            $opt->setZeroHeader(true);      // 启用ZIP64支持大文件
            $opt->setEnableZip64(true);
            $opt->setDeflateLevel(0);       // 0=不压缩(最快)
            
            // 3. 创建流式压缩对象
            $zip = new ZipStream($zipFileName, $opt);
            
            // 4. 设置必要的HTTP头（通过StreamedResponse）
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
            header('Pragma: no-cache');
            header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
            
            // 5. 配置OSS客户端
            $ossClient = new OssClient(
                env('OSS_ACCESS_KEY_ID'), 
                env('OSS_ACCESS_KEY_SECRET'), 
                'oss-cn-shenzhen.aliyuncs.com'
            );
            
            // 6. 使用Guzzle客户端进行并行下载
            $client = new Client(['timeout' => 30]);
            
            foreach ($urls as $index => $url) {
                try {
                    $parsed = parse_url($url);
                    $objectKey = basename(urldecode($parsed['path']));
                    $safeName = Str::ascii($objectKey); // 安全文件名
                    
                    // 7. 生成签名URL（避免直接使用OSS客户端）
                    $signedUrl = $ossClient->signUrl(
                        'compose-video', 
                        'video/'.$objectKey, 
                        300, // 5分钟有效期
                        "GET"
                    );
                    
                    // 8. 流式添加到压缩包
                    $response = $client->get($signedUrl, ['stream' => true]);
                    $stream = $response->getBody()->detach();
                    
                    $zip->addFileFromStream($safeName, $stream);
                    
                } catch (\Exception $e) {
                    \Log::error("文件处理失败: {$url} - " . $e->getMessage());
                    // 添加错误占位文件
                    $zip->addFile("ERROR_{$safeName}.txt", "Failed to download: {$url}");
                }
            }
            
            // 9. 完成压缩流输出
            $zip->finish();
        });
        
        // 10. 设置响应头
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $zipFileName . '"');
        
        return $response;
    }

    public function downloadLog(Request $request)
    {
        $ids = $request->all();
        ComposeVideo::query()->whereIn('id', $ids)->update([
             'download_count' => DB::raw('download_count + 1')
         ]);
        return $this->noContent();
    }

    public function updateScore(Request $request)
    {
        $post = $request->all();
        ComposeVideo::query()->where('id', $post['id'])->update([
             'score' => $post['score']
         ]);
        return $this->noContent();
    }
}
