<?php

namespace App\Http\Controllers\SalesHead;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class MessageController extends Controller
{
    public function index()
    {
        return Inertia::render('SalesHead/Messages/Index');
    }
}
