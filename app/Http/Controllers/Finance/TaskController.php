<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\PanelTaskController;

class TaskController extends Controller
{
    use PanelTaskController;

    protected function getViewPrefix(): string
    {
        return 'Finance';
    }

    protected function getRoutePrefix(): string
    {
        return 'finance';
    }
}
