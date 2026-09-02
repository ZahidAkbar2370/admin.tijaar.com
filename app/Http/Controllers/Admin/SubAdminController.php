<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SubAdminController extends Controller
{
    public function index(): View
    {
        $subAdmins = User::where('role', 'sub_admin')
            ->with('roles.permissions')
            ->orderByDesc('created_at')
            ->paginate(15);
        return view('admin.sub-admins.index', compact('subAdmins'));
    }

    public function create(): View
    {
        $roles = Role::whereIn('slug', ['sub_admin'])->orWhere('slug', 'admin')->orderBy('name')->get();
        return view('admin.sub-admins.form', ['user' => null, 'roles' => $roles]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'sub_admin',
        ]);
        $user->roles()->sync([$request->role_id]);

        return redirect()->route('admin.sub-admins.index')->with('success', 'Sub-admin created.');
    }

    public function edit(User $user): View
    {
        if ($user->role !== 'sub_admin') {
            abort(404);
        }
        $roles = Role::whereIn('slug', ['sub_admin'])->orWhere('slug', 'admin')->orderBy('name')->get();
        $user->load('roles');
        return view('admin.sub-admins.form', ['user' => $user, 'roles' => $roles]);
    }

    public function update(Request $request, User $user)
    {
        if ($user->role !== 'sub_admin') {
            abort(404);
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
        ]);

        $user->update(['name' => $request->name, 'email' => $request->email]);
        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }
        $user->roles()->sync([$request->role_id]);

        return redirect()->route('admin.sub-admins.index')->with('success', 'Sub-admin updated.');
    }
}
