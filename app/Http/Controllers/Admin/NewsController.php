<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class NewsController extends Controller
{
    public function index()
    {
        return view('admin.news.index', ['news' => News::with(['author', 'category'])->latest()->paginate(25)]);
    }

    public function publish(News $news): RedirectResponse
    {
        Gate::authorize('publish', News::class);
        $news->update(['status' => 'published']);
        return back()->with('success', 'News published.');
    }

    public function destroy(News $news): RedirectResponse
    {
        Gate::authorize('delete', $news);
        $news->delete();
        return back()->with('success', 'News deleted.');
    }
}
