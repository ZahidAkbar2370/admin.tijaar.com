<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactSubmissionController extends Controller
{
    public function index(Request $request): View
    {
        $query = ContactSubmission::with('user:id,name,email')->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($qry) => $qry->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")->orWhere('message', 'like', "%{$q}%"));
        }

        $submissions = $query->paginate(20)->withQueryString();
        return view('admin.contact-submissions.index', compact('submissions'));
    }

    public function show(ContactSubmission $contactSubmission): View
    {
        $contactSubmission->update(['status' => 'read']);
        return view('admin.contact-submissions.show', compact('contactSubmission'));
    }
}
