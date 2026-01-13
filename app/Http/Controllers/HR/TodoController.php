<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\PanelTodoController;

class TodoController extends Controller
{
    use PanelTodoController;

    protected function getViewPrefix(): string
    {
        return 'HR';
    }

    protected function getRoutePrefix(): string
    {
        return 'hr';
    }

    protected function getTodoTypes(): array
    {
        return [
            'personal' => 'Shaxsiy',
            'team' => 'Jamoa',
            'process' => 'Jarayon',
        ];
    }

    protected function getTodoPriorities(): array
    {
        return [
            'urgent' => 'Shoshilinch',
            'high' => 'Yuqori',
            'medium' => 'O\'rta',
            'low' => 'Past',
        ];
    }

    protected function getTodoStatuses(): array
    {
        return [
            'pending' => 'Kutilmoqda',
            'in_progress' => 'Jarayonda',
            'completed' => 'Bajarildi',
            'cancelled' => 'Bekor qilindi',
        ];
    }
}
