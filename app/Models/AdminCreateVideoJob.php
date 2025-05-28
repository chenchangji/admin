<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminCreateVideoJob extends Model
{
     protected $fillable = [
        'admin_template_id',
        'creator_id',
        'video_count',
        'finish_video_count',
        'job_ids',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'job_ids' => 'array', // 读取时自动 json_decode，写入时自动 json_encode
    ];
}
