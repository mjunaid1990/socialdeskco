<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = Auth::user();
        
        if ($user && $user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        } elseif ($user && $user->isTenant()) {
            // For now, tenants can also access the same dashboard
            // In a multi-tenant system, you'd customize this
            return redirect()->route('admin.dashboard');
        } else {
            // Default fallback
            return redirect()->route('admin.dashboard');
        }
    }
}