<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\PanelTodoController;

class TodoController extends Controller
{
    use PanelTodoController;

    protected function getViewPrefix(): string
    {
        return 'Finance';
    }

    protected function getRoutePrefix(): string
    {
        return 'finance';
    }
}
