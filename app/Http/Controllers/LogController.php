<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;

class LogController extends Controller
{
    public function index()
    {
        return ActivityLog::with('user')->get();
    }
}