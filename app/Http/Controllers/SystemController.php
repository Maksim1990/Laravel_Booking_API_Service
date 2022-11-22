<?php

namespace App\Http\Controllers;

class SystemController extends Controller
{
    public function version()
    {
        return response()->json(['version' => config('system.app_version')]);
    }
}
