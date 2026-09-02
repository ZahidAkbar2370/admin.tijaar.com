<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsappTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsappTemplateController extends Controller
{
    public function index(): View
    {
        $templates = WhatsappTemplate::orderBy('name')->get();

        return view('admin.whatsapp-templates.index', compact('templates'));
    }

    public function edit(WhatsappTemplate $whatsappTemplate): View
    {
        return view('admin.whatsapp-templates.form', compact('whatsappTemplate'));
    }

    public function update(Request $request, WhatsappTemplate $whatsappTemplate): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'body' => 'required|string|max:4000',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $whatsappTemplate->update($data);

        return redirect()->route('admin.whatsapp-templates.index')
            ->with('success', 'WhatsApp template "'.$whatsappTemplate->name.'" updated.');
    }
}
