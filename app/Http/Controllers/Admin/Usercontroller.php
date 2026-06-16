<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    // ─── List ─────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $users = User::with('tenant')
            ->when($request->filled('search'), fn ($q) =>
                $q->where(function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%")
                      ->orWhere('email', 'like', "%{$request->search}%");
                })
            )
            ->when($request->filled('tenant_id'), fn ($q) =>
                $q->where('tenant_id', $request->tenant_id)
            )
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $tenants = Tenant::orderBy('name')->get(['id', 'name']);

        return view('admin.users.index', compact('users', 'tenants'));
    }

    // ─── Create ───────────────────────────────────────────────────

    public function create(): View
    {
        $tenants = Tenant::orderBy('name')->get(['id', 'name']);

        return view('admin.users.create', compact('tenants'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tenant_id'      => ['required', 'exists:tenants,id'],
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', 'unique:users,email'],
            'password'       => ['required', 'string', 'min:8'],
            'role'           => ['required', 'in:owner,admin,member'],
            'is_super_admin' => ['boolean'],
            'is_active'      => ['boolean'],
        ]);

        $data['is_super_admin'] = $request->boolean('is_super_admin');
        $data['is_active']      = $request->boolean('is_active', true);

        User::create($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    // ─── Edit ─────────────────────────────────────────────────────

    public function edit(User $user): View
    {
        $tenants = Tenant::orderBy('name')->get(['id', 'name']);

        return view('admin.users.edit', compact('user', 'tenants'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'tenant_id'      => ['required', 'exists:tenants,id'],
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password'       => ['nullable', 'string', 'min:8'],
            'role'           => ['required', 'in:owner,admin,member'],
            'is_super_admin' => ['boolean'],
            'is_active'      => ['boolean'],
        ]);

        $data['is_super_admin'] = $request->boolean('is_super_admin');
        $data['is_active']      = $request->boolean('is_active');

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    // ─── Delete (soft) ────────────────────────────────────────────

    public function destroy(User $user): RedirectResponse
    {
        // Prevent self-deletion to avoid locking yourself out
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted.');
    }

    // ─── Toggle active status ─────────────────────────────────────

    public function toggleActive(User $user): RedirectResponse
    {
        $user->update(['is_active' => ! $user->is_active]);

        return redirect()->route('admin.users.index')
            ->with('success', $user->is_active ? 'User activated.' : 'User deactivated.');
    }
}