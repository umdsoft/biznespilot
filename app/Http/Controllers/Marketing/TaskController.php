<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\PanelTaskController;

class TaskController extends Controller
{
    use PanelTaskController;

    protected function getViewPrefix(): string
    {
        return 'Marketing';
    }

    protected function getRoutePrefix(): string
    {
        return 'marketing';
    }
}
