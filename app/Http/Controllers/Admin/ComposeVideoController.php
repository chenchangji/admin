<?php

namespace App\Http\Controllers\Admin;

use App\Http\Filters\ComposeVideoFilter;
use App\Models\ComposeVideo;
use App\Http\Requests\ComposeVideoRequest;
use App\Http\Resources\ComposeVideoResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
