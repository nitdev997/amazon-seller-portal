<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::allTenants()
            ->with(['items', 'tenant', 'amazonAccount'])
            ->when($request->filled('tenant_id'), fn ($q) =>
                $q->where('tenant_id', $request->tenant_id)
            )
            ->when($request->filled('search'), fn ($q) =>
                $q->where(function ($q) use ($request) {
                    $q->where('amazon_order_id', 'like', "%{$request->search}%")
                      ->orWhere('buyer_name', 'like', "%{$request->search}%")
                      ->orWhere('buyer_email', 'like', "%{$request->search}%");
                })
            )
            ->when($request->filled('status'), fn ($q) =>
                $q->where('order_status', $request->status)
            )
            ->orderByDesc('purchase_date')
            ->paginate(25)
            ->withQueryString();

        $tenants = Tenant::orderBy('name')->get(['id', 'name']);

        return view('admin.orders.index', compact('orders', 'tenants'));
    }

    public function show(string $amazonOrderId): View
    {
        $order = Order::allTenants()
            ->with(['items', 'tenant', 'amazonAccount'])
            ->where('amazon_order_id', $amazonOrderId)
            ->firstOrFail();

        return view('admin.orders.show', compact('order'));
    }
}