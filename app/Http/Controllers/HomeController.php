<?php
namespace App\Http\Controllers;
use App\Models\News; use Illuminate\View\View;
class HomeController extends Controller { public function index(): View { return view('public.home', ['stories'=>News::with(['category','author'])->where('status','published')->latest()->paginate(12)]); } public function dashboard(): View { return view('dashboard', ['stories'=>request()->user()->news()->latest()->get()]); } }
