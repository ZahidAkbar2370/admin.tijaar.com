<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Blog;
use App\Models\Testimonial;
use App\Models\ContactSubmission;
use App\Models\Faq;
use App\Models\NewsletterSubscriber;
use App\Models\HomeSection;
use App\Models\Page;
use App\Support\RichTextHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CmsController extends Controller
{
    public function page(string $slug): JsonResponse
    {
        $page = Page::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $payload = [
            'title' => $page->title,
            'slug' => $page->slug,
            'content' => RichTextHelper::cleanHtml($page->content),
            'banner_title' => $page->banner_title,
            'banner_subtitle' => $page->banner_subtitle,
            'meta_title' => $page->meta_title,
            'meta_description' => $page->meta_description,
            'meta_keywords' => $page->meta_keywords,
        ];
        if ($page->isSectionBased()) {
            $payload['sections'] = $page->sectionsPayload();
        }
        return response()->json(['success' => true, 'page' => $payload]);
    }

    public function banners(Request $request): JsonResponse
    {
        $position = $request->get('position', 'home_hero');
        $banners = Banner::where('position', $position)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'title' => $b->title,
                'image' => $b->image_path ? \App\Support\UploadHelper::url($b->image_path) : null,
                'image_alt' => $b->image_alt,
                'link_url' => $b->link_url,
                'description' => $b->description,
            ]);

        return response()->json(['success' => true, 'banners' => $banners]);
    }

    public function faqs(): JsonResponse
    {
        $faqs = Faq::where('is_active', true)->orderBy('sort_order')->get(['id', 'question', 'answer', 'category'])
            ->map(fn ($f) => [
                'id' => $f->id,
                'question' => $f->question,
                'answer' => RichTextHelper::cleanHtml($f->answer),
                'category' => $f->category,
            ]);
        return response()->json(['success' => true, 'faqs' => $faqs]);
    }

    public function blogs(Request $request): JsonResponse
    {
        $query = Blog::with('author:id,name')->where('is_published', true);

        if ($request->get('sort') === 'popular') {
            $query->orderByDesc('views_count')->orderByDesc('published_at');
        } else {
            $query->orderByDesc('published_at');
        }

        if ($request->filled('search')) {
            $q = \Illuminate\Support\Str::limit($request->search, 255, '');
            $query->where(fn ($qry) => $qry->where('title', 'like', "%{$q}%")->orWhere('excerpt', 'like', "%{$q}%"));
        }

        $blogs = $query->paginate($request->get('per_page', 12))->withQueryString();

        $items = $blogs->getCollection()->map(fn ($b) => [
            'id' => $b->id,
            'title' => $b->title,
            'slug' => $b->slug,
            'excerpt' => $b->excerpt,
            'featured_image' => $b->featured_image ? \App\Support\UploadHelper::url($b->featured_image) : null,
            'featured_image_alt' => $b->featured_image_alt,
            'author' => $b->author?->name,
            'published_at' => $b->published_at?->toIso8601String(),
        ]);
        $blogs->setCollection($items);

        return response()->json([
            'success' => true,
            'blogs' => $blogs->items(),
            'pagination' => [
                'current_page' => $blogs->currentPage(),
                'last_page' => $blogs->lastPage(),
                'per_page' => $blogs->perPage(),
                'total' => $blogs->total(),
            ],
        ]);
    }

    public function blog(string $slug): JsonResponse
    {
        $blog = Blog::with('author:id,name')->where('slug', $slug)->where('is_published', true)->firstOrFail();
        $blog->increment('views_count');

        return response()->json([
            'success' => true,
            'blog' => [
                'id' => $blog->id,
                'title' => $blog->title,
                'slug' => $blog->slug,
                'excerpt' => $blog->excerpt,
                'content' => RichTextHelper::cleanHtml($blog->content),
                'featured_image' => $blog->featured_image ? \App\Support\UploadHelper::url($blog->featured_image) : null,
                'author' => $blog->author?->name,
                'published_at' => $blog->published_at?->toIso8601String(),
                'meta_title' => $blog->meta_title,
                'meta_description' => $blog->meta_description,
                'meta_keywords' => $blog->meta_keywords,
            ],
        ]);
    }

    public function newsletter(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        NewsletterSubscriber::firstOrCreate(
            ['email' => $request->email],
            ['name' => $request->name, 'is_active' => true, 'subscribed_at' => now()]
        );

        return response()->json(['success' => true, 'message' => 'Subscribed successfully']);
    }

    public function contact(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        ContactSubmission::create([
            'name' => $request->name,
            'email' => $request->email,
            'subject' => $request->subject,
            'message' => $request->message,
            'user_id' => $request->user()?->id,
        ]);

        return response()->json(['success' => true, 'message' => 'Message sent. We will get back to you soon.']);
    }

    public function testimonials(): JsonResponse
    {
        $items = Testimonial::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'role' => $t->role,
                'company' => $t->company,
                'avatar' => $t->avatar ? \App\Support\UploadHelper::url($t->avatar) : null,
                'avatar_alt' => $t->avatar_alt,
                'content' => $t->content,
                'rating' => $t->rating,
            ]);

        return response()->json(['success' => true, 'testimonials' => $items]);
    }

    public function homeSections(): JsonResponse
    {
        $sections = HomeSection::where('is_active', true)->orderBy('sort_order')->get(['key', 'title', 'config']);
        $config = $sections->pluck('config', 'key')->toArray();
        $list = $sections->map(fn ($s) => ['key' => $s->key, 'title' => $s->title, 'config' => $s->config])->values()->all();
        return response()->json(['success' => true, 'sections' => $config, 'sections_list' => $list]);
    }
}
