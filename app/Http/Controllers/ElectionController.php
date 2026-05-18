<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ElectionController extends Controller
{
    public function login()
    {
        return Inertia::render('Election/login');
    }
    public function portal()
    {
        return Inertia::render('Election/portal');    
    }
}
