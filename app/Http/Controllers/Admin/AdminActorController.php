<?php

namespace App\Http\Controllers\Admin;

use App\Http\Filters\AdminActorFilter;
use App\Models\AdminActor;
use App\Http\Requests\AdminActorRequest;
use App\Http\Resources\AdminActorResource;
use Illuminate\Http\Request;

class AdminActorController extends Controller
{
    public function index(AdminActorFilter $filter)
    {
        $adminActors = AdminActor::query()
            ->filter($filter)
            ->paginate();

        return $this->ok(AdminActorResource::collection($adminActors));
    }

    public function create()
    {
        return $this->ok();
    }

    public function store(AdminActorRequest $request)
    {
        $inputs = $request->validated();
        $adminActor = AdminActor::create($inputs);

        return $this->created(AdminActorResource::make($adminActor));
    }

    public function edit(Request $request, AdminActor $adminActor)
    {
        return $this->ok(AdminActorResource::make($adminActor));
    }

    public function update(AdminActorRequest $request, AdminActor $adminActor)
    {
        $inputs = $request->validated();
        $adminActor->update($inputs);

        return $this->created(AdminActorResource::make($adminActor));
    }

    public function destroy(AdminActor $adminActor)
    {
        $adminActor->delete();
        return $this->noContent();
    }

    public function getList(AdminActorFilter $filter)
    {
        $adminActors = AdminActor::query()
            ->filter($filter)->get()->toArray();

        return $this->ok(AdminActorResource::collection($adminActors));
    }
}
