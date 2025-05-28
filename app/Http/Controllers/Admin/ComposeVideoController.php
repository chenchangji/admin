<?php

namespace App\Http\Controllers\Admin;

use App\Http\Filters\ComposeVideoFilter;
use App\Models\ComposeVideo;
use App\Http\Requests\ComposeVideoRequest;
use App\Http\Resources\ComposeVideoResource;
use Illuminate\Http\Request;

class ComposeVideoController extends Controller
{
    public function index(ComposeVideoFilter $filter)
    {
        $composeVideos = ComposeVideo::query()
            ->filter($filter)
            ->orderByDesc('id')
            ->paginate();

        return $this->ok(ComposeVideoResource::collection($composeVideos));
    }

    public function destroy(ComposeVideo $composeVideo)
    {
        $composeVideo->delete();
        return $this->noContent();
    }
}
