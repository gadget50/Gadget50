<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller; use App\Models\News;
class DashboardController extends Controller { public function index(){return view('admin.dashboard',['stats'=>['users'=>\App\Models\User::count(),'news'=>News::count(),'pending'=>News::where('status','pending')->count(),'published'=>News::where('status','published')->count(),'views'=>News::sum('views')],'recentNews'=>News::with(['author','category'])->latest()->limit(10)->get()]);} }
