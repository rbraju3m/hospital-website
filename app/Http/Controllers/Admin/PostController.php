<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesTranslatableContent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostRequest;
use App\Models\Post;
use App\Services\MediaLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class PostController extends Controller
{
    use HandlesTranslatableContent;

    public function __construct(private readonly MediaLibrary $media) {}

    public function index(Request $request): View
    {
        $query = Post::query()
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where('title', 'like', "%{$term}%"))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        return view('admin.posts.index', ['posts' => $this->paginateContent($query, $request)]);
    }

    public function create(): View
    {
        return view('admin.posts.form', [
            'post' => new Post([
                'category' => 'health-tips',
                'is_active' => true,
                'read_minutes' => 4,
                'author' => setting('site_name', 'RBR Hospital'),
            ]),
        ]);
    }

    public function store(PostRequest $request): RedirectResponse
    {
        $post = $this->persist(new Post, $request);

        return redirect()->route('admin.posts.edit', $post)
            ->with('status', __('admin.posts.created', ['title' => $post->untranslated('title')]));
    }

    public function show(Post $post): RedirectResponse
    {
        return redirect()->route('admin.posts.edit', $post);
    }

    public function edit(Post $post): View
    {
        return view('admin.posts.form', ['post' => $post]);
    }

    public function update(PostRequest $request, Post $post): RedirectResponse
    {
        $this->persist($post, $request);

        return back()->with('status', __('admin.posts.updated', ['title' => $post->untranslated('title')]));
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->media->delete($post->untranslated('image'));
        $post->delete();

        return redirect()->route('admin.posts.index')->with('status', __('admin.posts.deleted'));
    }

    private function persist(Post $post, PostRequest $request): Post
    {
        $data = $request->validated();

        $data['slug'] = $this->uniqueSlug('posts', $data['slug'] ?? null, $data['title'], $post->id);

        $image = $this->media->replace(
            $request->file('image'),
            'posts',
            $post->untranslated('image'),
            $request->boolean('image_remove'),
        );

        $this->fillTranslatable($post, Arr::except($data, ['image', 'image_remove']));
        $post->image = $image;
        $post->save();

        return $post;
    }
}
