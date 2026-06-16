<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(): View
    {
        $tenants = Tenant::withCount(['users', 'orders'])
            ->with(['amazonAccounts' => fn ($q) => $q->where('status', 'connected')])
            ->orderBy('name')
            ->paginate(20);

        return view('admin.tenants.index', compact('tenants'));
    }

    public function create(): View
    {
        return view('admin.tenants.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'slug'      => ['required', 'string', 'max:255', 'unique:tenants,slug', 'alpha_dash'],
            'email'     => ['required', 'email', 'unique:tenants,email'],
            'plan'      => ['required', 'in:free,pro,enterprise'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        Tenant::create($data);

        return redirect()->route('admin.tenants.index')
            ->with('success', 'Tenant created successfully.');
    }
}