<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $tenant  = auth()->user()->tenant;
        $account = $tenant->activeAmazonAccount();

        $stats = [
            'total_orders'   => Order::count(),
            'shipped_today'  => Order::where('order_status', 'Shipped')
                                     ->whereDate('last_update_date', today())
                                     ->count(),
            'pending_orders' => Order::whereIn('order_status', ['Pending', 'Unshipped'])->count(),
            'revenue_30d'    => Order::whereDate('purchase_date', '>=', now()->subDays(30))
                                     ->whereNotNull('order_total')
                                     ->sum('order_total'),
        ];

        return view('dashboard.index', compact('stats', 'account'));
    }

    public function orders(): View
    {
        return view('dashboard.orders');
    }
}
