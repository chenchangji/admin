<?php

namespace App\Http\Controllers\Admin;

use App\Http\Filters\AdminTemplateFilter;
use App\Models\AdminTemplate;
use App\Http\Requests\AdminTemplateRequest;
use App\Http\Resources\AdminTemplateResource;
use Illuminate\Http\Request;
use App\Services\AdminTemplateService;

class AdminTemplateController extends Controller
{
    protected $service;

    public function __construct(AdminTemplateService $adminTemplateService)
    {
        $this->service = $adminTemplateService;
    }

    public function index(AdminTemplateFilter $filter)
    {
        $adminTemplates = AdminTemplate::query()
            ->where('status', 1)
            ->filter($filter)
            ->paginate();

        return $this->ok(AdminTemplateResource::collection($adminTemplates));
    }

    public function create()
    {
        return $this->ok();
    }

    public function store(AdminTemplateRequest $request)
    {
        $inputs = $request->validated();
        if (auth()->check()) {
            $user = auth()->user();
            $inputs['user_id'] = $user->id;
        }else{
            throw new Exception("用户信息获取失败！", 1);
            
        }
        $adminTemplate = AdminTemplate::create($inputs);

        return $this->created(AdminTemplateResource::make($adminTemplate));
    }

    public function edit(Request $request, AdminTemplate $adminTemplate)
    {
        return $this->ok(AdminTemplateResource::make($adminTemplate));
    }

    public function update(AdminTemplateRequest $request, AdminTemplate $adminTemplate)
    {
        $inputs = $request->validated();
        $adminTemplate->update($inputs);

        return $this->created(AdminTemplateResource::make($adminTemplate));
    }

     public function destroy(AdminTemplate $adminTemplate)
    {
        $adminTemplate->update(['status'=>2]);
        return $this->noContent();
    }

    public function generateVideo(Request $request)
    {
        $post = $request->all();
        $this->service->generateVideo($post);
        return $this->ok();
    }
}
