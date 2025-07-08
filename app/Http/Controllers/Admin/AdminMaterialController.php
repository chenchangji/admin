<?php

namespace App\Http\Controllers\Admin;

use App\Http\Filters\AdminMaterialFilter;
use App\Models\AdminMaterial;
use App\Models\ComposeVideo;
use App\Http\Requests\AdminMaterialRequest;
use App\Http\Resources\AdminMaterialResource;
use App\Exports\AdminMaterialsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class AdminMaterialController extends Controller
{
    public function index(AdminMaterialFilter $filter, Request $request)
    {
        $adminMaterials = AdminMaterial::query()
            ->leftjoin('admin_users', 'admin_materials.user_id', '=', 'admin_users.id')
            ->where('admin_materials.status',1)
            ->filter($filter)
            ->select('admin_materials.*','admin_users.name');
        if ($request->sort_field === 'updated_at') {
            $adminMaterials = $adminMaterials->orderBy('updated_at', $request->sort_order ?? 'desc');
        }else{
            $adminMaterials = $adminMaterials->orderBy('id', 'desc');
        }
        $adminMaterials = $adminMaterials->paginate();

        return $this->ok(AdminMaterialResource::collection($adminMaterials));
    }

    public function create()
    {
        return $this->ok();
    }

    public function store(AdminMaterialRequest $request)
    {
        $inputs = $request->validated();
        if (auth()->check()) {
            $user = auth()->user();
            $inputs['user_id'] = $user->id;
        }else{
            throw new Exception("用户信息获取失败！", 1);
            
        }
        $inputs['video_cover_url'] = $inputs['url'].'?x-oss-process=video/snapshot,t_10000,m_fast';
        $adminMaterial = AdminMaterial::create($inputs);

        return $this->created(AdminMaterialResource::make($adminMaterial));
    }

    public function edit(Request $request, AdminMaterial $adminMaterial)
    {
        return $this->ok(AdminMaterialResource::make($adminMaterial));
    }

    public function update(AdminMaterialRequest $request, AdminMaterial $adminMaterial)
    {
        $inputs = $request->validated();
        $adminMaterial->update($inputs);

        return $this->created(AdminMaterialResource::make($adminMaterial));
    }

    public function destroy(AdminMaterial $adminMaterial)
    {
        $adminMaterial->update(['status'=>2]);
        return $this->noContent();
    }

    public function export(Request $request)
    {
        // 清理缓冲区
        ob_end_clean();
        ob_start();
        try {
            $params = $request->all();
            unset($params['page'], $params['per_page']);
            
            \Log::info('导出参数', $params);
            
            // 添加内存限制
            ini_set('memory_limit', '512M');
            set_time_limit(300);

            $export = new AdminMaterialsExport($params);
            
            // 使用可中断的导出方式
            return Excel::download($export, '素材列表' . date('Ymd_His') . '.csv', \Maatwebsite\Excel\Excel::CSV, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
            
        } catch (\Exception $e) {
            \Log::error('导出失败: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            
            // 返回错误页面而不是JSON
            return back()->withErrors('导出失败: ' . $e->getMessage());
        }
    }

    public function updateScore(Request $request)
    {
        $post = $request->all();
        AdminMaterial::query()->where('id', $post['id'])->update([
             'score' => $post['score']
         ]);
        return $this->noContent();
    }

    public function getCountByClass()
    {
        $data = AdminMaterial::select( \DB::raw('COUNT(*) as total'))
                    ->where('status',1)
                    ->groupBy('class')
                    ->orderBy('class') // 可选：按分类排序
                    ->pluck('total') // 转换为关联数组
                    ->toArray();
        return $this->ok($data);
    }

    public function getCountByProduct()
    {
        $data = AdminMaterial::select( \DB::raw('COUNT(*) as total'), 'product_id')
                    ->where('status',1)
                    ->where('product_id', '!=', 3)
                    ->groupBy('product_id')
                    ->orderBy('product_id') // 可选：按分类排序
                    ->pluck('total', 'product_id') // 转换为关联数组
                    ->toArray();
        $res = [];
        foreach ($data as $key => $value) {
            if ($key == 1) {
                $res[] = [
                            'value' => $value,
                            'name'  => '舒筋健腰丸'
                        ];
            }
            if ($key == 2) {
                $res[] = [
                            'value' => $value,
                            'name'  => '清血八味片'
                        ];
            }
            if ($key == 3) {
                $res[] = [
                            'value' => $value,
                            'name'  => '咽康'
                        ];
            }
        }
        return $this->ok($res);
    }

    public function getVideoCount(Request $request)
    {
        $params = $request->all();
        $time_dimension = data_get($params, 'timeDimension', 'week');
        $data = [];
        //统计前6周，或前15天， 或者 前6个月的数据
        if ($time_dimension == 'week') {
            // 1. 定义时间范围（过去6周，包含当前周）
            $start_time = Carbon::now()->startOfWeek()->subWeeks(5); // 确保包含当前周
            // 2. 执行聚合查询（按周分组）
            $results = ComposeVideo::select(
                    DB::raw('DATE_FORMAT(created_at, "%x-%v") as dimension'), // %x 年份（4位） %v 周数（1-53）
                    DB::raw('SUM(download_count) as total_downloads'),
                    DB::raw('COUNT(*) as total')
                )
                ->where('created_at', '>=', $start_time)
                ->groupBy('dimension')
                ->orderBy('dimension')
                ->get()
                ->keyBy('dimension')
                ->toArray();
        }elseif($time_dimension == 'month') {
            // 1. 定义时间范围（过去6月，包含当前月）
            $start_time = Carbon::now()->startOfMonth()->subMonths(5); 
            // 2. 执行聚合查询（按周分组）
            $results = ComposeVideo::select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as dimension'), 
                    DB::raw('SUM(download_count) as total_downloads'),
                    DB::raw('COUNT(*) as total')
                )
                ->where('created_at', '>=', $start_time)
                ->groupBy('dimension')
                ->orderBy('dimension')
                ->get()
                ->keyBy('dimension')
                ->toArray();
        }else{
            // 1. 定义时间范围（过去15天，包含当前周）
            $start_time = Carbon::now()->startOfDay()->subDays(14); 
            // 2. 执行聚合查询（按周分组）
            $results = ComposeVideo::select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d") as dimension'), 
                    DB::raw('SUM(download_count) as total_downloads'),
                    DB::raw('COUNT(*) as total')
                )
                ->where('created_at', '>=', $start_time)
                ->groupBy('dimension')
                ->orderBy('dimension')
                ->get()
                ->keyBy('dimension')
                ->toArray();
        }
        
        $data['timeLabels'] = array_column($results, 'dimension');
        $data['total'] = array_column($results, 'total');
        $data['total_downloads'] = array_column($results, 'total_downloads');

        return $this->ok($data);
    }


}
