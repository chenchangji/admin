<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AdminMaterial;
use App\Utils\AliCloudVideoStitcher;
use Illuminate\Support\Facades\File;

class SyncVideoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:video';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '批量上传素材';

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
        $directoryPath = 'D:\24片清血新包装素材分类管理';                  
        // 检查目录是否存在
        if (!File::isDirectory($directoryPath)) {
            throw new \InvalidArgumentException("目录不存在: {$directoryPath}");
        }
            /*{ id: 11, name: 'A1-营销内容' },
            { id: 12, name: 'A2-价格营销' },
            { id: 14, name: 'A4-营销内容-合规' },
            { id: 15, name: 'A5-价格营销-合规' },
            { id: 16, name: 'A6-旧素材混剪' },
            { id: 21, name: 'B1-症状代入' },
            { id: 22, name: 'B2-疾病科普' },
            { id: 23, name: 'B3-病理' },
            { id: 26, name: 'B6-旧素材混剪' },
            { id: 31, name: 'C1-产品相关' },
            { id: 36, name: 'C6-旧素材混剪' },
            { id: 41, name: 'D1-价格优惠' },
            { id: 42, name: 'D2-厂家直发' },
            { id: 43, name: 'D3-厂家活动' },
            { id: 44, name: 'D6-旧素材混剪' }*/
        // 递归获取所有文件（包含子目录）
        $files =  File::allFiles($directoryPath);
        foreach ($files as $key => $file) {
            $path = $file->getRealPath();
            $name = $file->getFileName();
            try {
                //1. 初始化服务
                $videoStitcher = new AliCloudVideoStitcher();
                $data['url']  = $videoStitcher->uploadVideo($path, $name);
                $data['video_cover_url'] = $data['url'].'?x-oss-process=video/snapshot,t_10000,m_fast';
                $data['title'] = $name;
                $data['type'] = 1;
                $data['product_id'] = 2;
                $data['product_format'] = 3;
                $data['user_id'] = 1;
                //根据路径获取相关产品分类信息
                if (strpos($path, "竖屏") !== false) {
                    $data['screen_type'] = 2;
                }else{
                    $data['screen_type'] = 1;
                }
                if (strpos($path, "A1") !== false) {
                    $data['class'] = 1;
                    $data['sub_class'] = 11;
                }elseif(strpos($path, "A2") !== false){
                    $data['class'] = 1;
                    $data['sub_class'] = 12;
                }elseif(strpos($path, "A4") !== false){
                    $data['class'] = 1;
                    $data['sub_class'] = 14;
                }elseif(strpos($path, "A5") !== false){
                    $data['class'] = 1;
                    $data['sub_class'] = 15;
                }elseif(strpos($path, "A6") !== false){
                    $data['class'] = 1;
                    $data['sub_class'] = 16;
                }elseif(strpos($path, "B1") !== false){
                    $data['class'] = 2;
                    $data['sub_class'] = 21;
                }elseif(strpos($path, "B2") !== false){
                    $data['class'] = 2;
                    $data['sub_class'] = 21;
                }elseif(strpos($path, "B3") !== false){
                    $data['class'] = 2;
                    $data['sub_class'] = 23;
                }elseif(strpos($path, "B6") !== false){
                    $data['class'] = 2;
                    $data['sub_class'] = 26;
                }elseif(strpos($path, "C1") !== false){
                    $data['class'] = 3;
                    $data['sub_class'] = 31;
                }elseif(strpos($path, "C6") !== false){
                    $data['class'] = 3;
                    $data['sub_class'] = 36;
                }elseif(strpos($path, "D1") !== false){
                    $data['class'] = 4;
                    $data['sub_class'] = 41;
                }elseif(strpos($path, "D2") !== false){
                    $data['class'] = 4;
                    $data['sub_class'] = 42;
                }elseif(strpos($path, "D3") !== false){
                    $data['class'] = 4;
                    $data['sub_class'] = 43;
                }elseif(strpos($path, "D6") !== false){
                    $data['class'] = 4;
                    $data['sub_class'] = 46;
                }else{
                    $data['class'] = 1;
                    $data['sub_class'] = 11;
                }
                if (strpos($path, "电子音") !== false) {
                    $data['actor_ids'] = [7];
                }elseif(strpos($path, "裴祥民") !== false){
                    $data['actor_ids'] = [11];
                }elseif(strpos($path, "张东瑶") !== false){
                    $data['actor_ids'] = [12];
                }elseif(strpos($path, "商丹") !== false){
                    $data['actor_ids'] = [13];
                }elseif(strpos($path, "李珺") !== false){
                    $data['actor_ids'] = [14];
                }elseif(strpos($path, "数字人") !== false){
                    $data['actor_ids'] = [15];
                }
                AdminMaterial::create($data);
                echo "同步".$name.'成功！'. "\n";
            } catch (Exception $e) {
                logger("上传视频失败: " . $e->getMessage() . "\n");
            }
        }
    }
}
