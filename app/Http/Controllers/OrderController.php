<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function show(string $amazonOrderId): View
    {
        $order = Order::with(['items', 'amazonAccount'])
            ->where('amazon_order_id', $amazonOrderId)
            ->firstOrFail();

        return view('orders.show', compact('order'));
    }
}