<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->query('category');

        return view('pages.posts.index', [
            'posts' => Post::published()
                ->when($category, fn ($q) => $q->where('category', $category))
                ->latestFirst()
                ->paginate(9)
                ->withQueryString(),
            'categories' => Post::published()->distinct()->orderBy('category')->pluck('category'),
            'category' => $category,
        ]);
    }

    public function show(Post $post)
    {
        abort_unless($post->is_active && $post->published_at && $post->published_at->isPast(), 404);

        return view('pages.posts.show', [
            'post' => $post,
            'related' => Post::published()
                ->where('category', $post->category)
                ->whereKeyNot($post->id)
                ->latestFirst()
                ->take(3)
                ->get(),
        ]);
    }
}
