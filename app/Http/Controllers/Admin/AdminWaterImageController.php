<?php

namespace App\Http\Controllers\Admin;

use App\Http\Filters\WaterImageFilter;
use App\Models\AdminWaterImage;
use App\Http\Requests\WaterImageRequest;
use App\Http\Resources\AdminWaterImageResource;
use Illuminate\Http\Request;

class AdminWaterImageController extends Controller
{
    public function index(WaterImageFilter $filter)
    {
        $waterImages = AdminWaterImage::query()
            ->filter($filter)
            ->paginate();

        return $this->ok(AdminWaterImageResource::collection($waterImages));
    }

    public function create()
    {
        return $this->ok();
    }

    public function store(WaterImageRequest $request)
    {
        $inputs = $request->validated();
        $waterImage = AdminWaterImage::create($inputs);

        return $this->created(AdminWaterImageResource::make($waterImage));
    }

    public function edit(Request $request, AdminWaterImage $adminWaterImage)
    {
        return $this->ok(AdminWaterImageResource::make($adminWaterImage));
    }

    public function update(WaterImageRequest $request, AdminWaterImage $adminWaterImage)
    {
        $inputs = $request->validated();
        $adminWaterImage->update($inputs);

        return $this->created(AdminWaterImageResource::make($adminWaterImage));
    }

    public function destroy(AdminWaterImage $adminWaterImage)
    {
        $adminWaterImage->delete();
        return $this->noContent();
    }

    public function getList(WaterImageFilter $filter)
    {
        $adminActors = AdminWaterImage::query()
            ->filter($filter)->get()->toArray();

        return $this->ok(AdminWaterImageResource::collection($adminActors));
    }
}
