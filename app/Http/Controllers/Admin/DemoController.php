<?php

namespace App\Admin\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class DemoController extends Controller
{
    public function resetSystem()
    {
        Artisan::call('admin:init', [
            '--force' => true,
        ]);
        return $this->ok();
    }
}
