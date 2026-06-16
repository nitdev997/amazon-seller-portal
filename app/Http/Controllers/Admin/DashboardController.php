<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_tenants'   => Tenant::count(),
            'active_tenants'  => Tenant::where('is_active', true)->count(),
            'total_users'     => User::count(),
            'total_orders'    => Order::allTenants()->count(),
            'total_revenue'   => Order::allTenants()->whereNotNull('order_total')->sum('order_total'),
            'connected_amazon' => Tenant::query()
                ->whereHas('amazonAccounts', fn ($q) => $q->where('status', 'connected'))
                ->count(),
        ];

        $recentTenants = Tenant::withCount(['users', 'orders'])
            ->latest()
            ->take(5)
            ->get();

        $recentOrders = Order::allTenants()
            ->with('tenant')
            ->latest('purchase_date')
            ->take(8)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentTenants', 'recentOrders'));
    }
}