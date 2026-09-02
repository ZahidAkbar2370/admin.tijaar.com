<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    public function index(Request $request): View
    {
        $query = NewsletterSubscriber::orderByDesc('subscribed_at');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($qry) => $qry->where('email', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%"));
        }

        $subscribers = $query->paginate(50)->withQueryString();
        return view('admin.newsletter.index', compact('subscribers'));
    }
}
