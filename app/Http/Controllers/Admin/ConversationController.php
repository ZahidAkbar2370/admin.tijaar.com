<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\ConversationReport;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConversationController extends Controller
{
    public function index(Request $request): View
    {
        $query = Conversation::with(['user:id,name,email', 'seller:id,name,email', 'product:id,name,slug'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qry) use ($q) {
                $qry->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"))
                    ->orWhereHas('seller', fn ($u) => $u->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"));
            });
        }

        $conversations = $query->paginate(20)->withQueryString();

        return view('admin.conversations.index', compact('conversations'));
    }

    public function show(Conversation $conversation): View
    {
        $conversation->load(['user', 'seller', 'product', 'messages.user:id,name']);
        $reported = ConversationReport::where('conversation_id', $conversation->id)->with('user:id,name')->get();
        return view('admin.conversations.show', compact('conversation', 'reported'));
    }

    public function reported(): View
    {
        $reports = ConversationReport::with(['conversation.user', 'conversation.seller', 'user'])
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.conversations.reported', compact('reports'));
    }
}
