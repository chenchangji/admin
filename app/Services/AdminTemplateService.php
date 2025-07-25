<?php

namespace App\Services;

use App\Models\AdminCreateVideoJob;
use App\Models\AdminTemplate;
use App\Models\AdminMaterial;
use App\Models\ComposeVideo;
use App\Utils\AliCloudVideoStitcher;
use Exception;

class AdminTemplateService
{
    public function generateVideo(array $data)
    {
        $template_id = $insert['admin_template_id'] = $data['template_id'];
        $count = $insert['video_count'] = $data['count'];
        AdminCreateVideoJob::create($insert);
        //获取创建模板配置
        $template = AdminTemplate::find($data['template_id'])->toArray();
        //小类规则，类似：A1+B2+C3+D4
        $rules   = json_decode($template['class_rules']);
        $sets = [];
        foreach ($rules as  $k => $rule) {
            $sets[$k] = $this->getMaterials($template, $rule);
        }
        // 生成$count个随机不重复的组合
        $combinations = $this->generateRandomCombinations($sets, $rules, $count);
        if (count($combinations) < $count) {
            throw new Exception("符合条件素材不足，请添加素材！", -1);
        }
        //将筛选出的素材进行拼接
        $job_ids = $this->mediaDel($combinations, $template);
        if (auth()->check()) {
            $user = auth()->user();
            $insert['creator_id'] = $user->id;
        }else{
            throw new Exception("用户信息获取失败！", 1);
            
        }
        $insert['job_ids'] = $job_ids;
        AdminCreateVideoJob::create($insert);

    }

    public function getMaterials($template, $rule)
    {
        $class                = data_get($rule, 'class');
        $sub_class            = data_get($rule, 'sub_class');
        $actor_ids            = data_get($rule, 'actor_ids');
        $product_id           = data_get($template, 'product_id');
        $product_format       = data_get($template, 'product_format');
        $product_type         = data_get($template, 'product_type');
        $product_tag          = data_get($template, 'product_tag');
        $screen_type          = data_get($template, 'screen_type');
        $range                = data_get($template, 'range');
        $exclude_sub_class    = data_get($template, 'exclude_sub_class');
        $exclude_actor_ids    = data_get($template, 'exclude_actor_ids');
        //找到符合规则的视频
        $query = AdminMaterial::query()->where('product_id', $product_id)
                                       ->where('status', 1)
                                       ->where('screen_type', $screen_type);
        if (!empty($class)) {
            $query->where('class', $class);
        }
        if (!empty($sub_class)) {
            $query->where('sub_class', $sub_class);
        }
        if (!empty($product_format)) {
            $query->where('product_format', $product_format);
        }
        if (!empty($product_type)) {
            $query->where('type', $product_type);
        }
        //指定演员
        if (!empty($actor_ids)) {
            $query->where(function ($q) use ($actor_ids) {
                foreach ($actor_ids as $id) {
                    $q->orWhereRaw("JSON_CONTAINS(actor_ids, ?)", [json_encode($id)]);
                }
            });
        }
        //剔除规则演员
        if (!empty($exclude_actor_ids)) {
            $query->where(function ($q) use ($exclude_actor_ids) {
                foreach ($exclude_actor_ids as $id) {
                    $q->whereRaw("NOT JSON_CONTAINS(actor_ids, ?)", [json_encode($id)]);
                }
            });
        }
        //剔除子类
        if (!empty($exclude_sub_class)) {
            $query->whereNotIn('sub_class',$exclude_sub_class);
        }
        //D类要剔除投放类型
        if (!empty($product_tag) && strpos($sub_class, "D") !== false) {
            $query->where('tag', $product_tag);
        }
        if ($query->count() == 0) {
            return [];
        }
        $range_query = clone $query;
        if (!empty($range)) {
            switch ($range) {
                case '1':
                    $date = date('Y-m-d H:i:s', strtotime('-7 days'));
                    break;

                case '2':
                    $date = date('Y-m-d H:i:s', strtotime('-30 days'));
                    break;

                case '3':
                    $date = date('Y-m-d H:i:s', strtotime('-90 days'));
                    break;

                default:
                    $date = date('Y-m-d H:i:s', strtotime('-30 days'));
                    break;
            }
            $range_query->where('updated_at', '>', $date);
        }
        //如果在优先选择的时间范围内有素材，则优先使用时间范围内素材，否则去掉优先时间筛选
        if ($range_query->count() > 0) {
            return $range_query->get()->toArray();
        }
        return $query->get()->toArray();
    }

    public function generateRandomCombinations($sets, $rules, $requiredCount) 
    {
        $result = [];
        $attempts = 0;
        $maxAttempts = $requiredCount * 10; // 增加尝试次数，避免无限循环
        $collection = $sets; // 复制原始集合，避免修改原数据
        while (count($result) < $requiredCount && $attempts < $maxAttempts) {
            $attempts++;
            $materials = [];
            $ids = [];
            $actor_ids = []; // 初始化

            // 按规则选择元素
            foreach ($rules as $k => $rule) {
                if (!isset($collection[$k]) || empty($collection[$k])) {
                    continue 2; // 跳过本次尝试，进入下一次循环
                }

                $randomKey = array_rand($collection[$k]);
                $material = $collection[$k][$randomKey];
                
                unset($collection[$k][$randomKey]); // 避免重复选择
                //如果可选的素材变为0，则重新填充
                if (count($collection[$k]) <= 0) {
                    $collection[$k] = $sets[$k];
                }
                $materials[] = $material;
                $ids[] = $material['id'];
                if (!empty($material['actor_ids'])) {
                    $actor_ids = array_merge($actor_ids, $material['actor_ids']);
                }
                
            }

            $actor_ids = array_unique($actor_ids);
            $key = implode('-', $ids);

            // 检查组合是否有效且不重复
            if (!empty($materials)  && !isset($result[$key])) {
                $result[$key] = $materials;
            }
        }

        return $result; 
    }

    public function mediaDel($combinations, $template)
    {
        $job_ids = [];
        $video_data = [];
        $date = date('Y-m-d');
        $i = 1;
        if (auth()->check()) {
            $user = auth()->user();
            $creator_id = $user->id;
        }else{
            $creator_id = 1;
        }
        foreach ($combinations as $key => $combination) {
            try {
                //1. 初始化服务
                $videoStitcher = new AliCloudVideoStitcher();
                // 2. 准备要拼接的视频列表（必须是同地域同Bucket下的文件）
                $video_urls = array_column($combination, 'url');
                // 3. 设置输出位置
                $outputBucket = env('OSS_BUCKET'); // 输出Bucket名称
                $outputObject = 'output/stitched-video.mp4'; // 输出文件路径

                // 5. 提交拼接作业 
                $response = $videoStitcher->submitStitchingJob($video_urls, $date.'-'.$template['title'].'-'.$i, $template);
                // 6. 获取作业ID
                $job_id = data_get($response, 'body.jobResultList.jobResult.0.job.jobId');
                if (!empty($job_id)) {
                    $job_ids[]  =$job_id;
                }
                $actor_ids=[];
                $material_titles = [];
                foreach ($combination as  $v) {
                    if (!empty($v['actor_ids'])) {
                        $actor_ids = array_merge($actor_ids, $v['actor_ids']);
                    }
                    
                    $material_titles[] = $v['title'];
                }
                $video_data[] = [
                    'title'          => $date.'-'.$template['title'].'-'.$i,
                    'template_id'    => $template['id'],
                    'job_id'         => $job_id,
                    'product_id'     => $template['product_id'],
                    'product_format' => $template['product_format'],
                    'screen_type'    => $template['screen_type'],
                    'actor_ids'      => json_encode($actor_ids),
                    'material_ids'   => json_encode(explode('-', $key)),
                    'material_titles'   => implode('+',$material_titles),
                    'status'         => 0,
                    'creator_id'     => $creator_id,
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s'),
                ];
                $i++;
                logger( "拼接作业已提交，作业ID: {$job_id}\n");
            } catch (Exception $e) {
                logger("视频拼接失败: " . $e->getMessage() . "\n");
            }
        }
        if (!empty($video_data)) {
            ComposeVideo::insert($video_data);
        }
        
        return $job_ids;
        
    }
}