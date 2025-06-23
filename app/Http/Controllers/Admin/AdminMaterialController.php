<?php

namespace App\Http\Controllers\Admin;

use App\Http\Filters\AdminMaterialFilter;
use App\Models\AdminMaterial;
use App\Http\Requests\AdminMaterialRequest;
use App\Http\Resources\AdminMaterialResource;
use App\Exports\AdminMaterialsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

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


}
