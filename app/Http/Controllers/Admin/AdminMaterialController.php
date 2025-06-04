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
            // dd($request->sort_order);
        if ($request->sort_field === 'created_at') {
            $adminMaterials = $adminMaterials->orderBy('created_at', $request->sort_order ?? 'desc');
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
        try {

            // 获取并清理参数
            $params = $request->all();
            unset($params['page'], $params['per_page']);
            
            
            // 创建导出实例
            $export = new AdminMaterialsExport($params);
            
            // 生成文件名
            $fileName = '素材列表_' . date('Ymd_His') . '.xlsx';
            
            // 直接返回 Excel 下载响应
            return Excel::download($export, $fileName);
            
        } catch (\Exception $e) {
            logger('导出失败: ' . $e->getMessage());
            return response()->json([
                'error' => '导出失败',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
