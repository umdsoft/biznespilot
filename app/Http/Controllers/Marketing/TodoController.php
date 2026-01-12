<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\PanelTodoController;

class TodoController extends Controller
{
    use PanelTodoController;

    protected function getViewPrefix(): string
    {
        return 'Marketing';
    }

    protected function getRoutePrefix(): string
    {
        return 'marketing';
    }
}
