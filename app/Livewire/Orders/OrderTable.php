<?php

namespace App\Livewire\Orders;

use App\Models\Order;
use Livewire\Component;
use Livewire\WithPagination;

class OrderTable extends Component
{
    use WithPagination;

    // ─── Filters ──────────────────────────────────────────────────

    public string $search       = '';
    public string $statusFilter = '';
    public string $channelFilter = '';
    public string $dateFrom     = '';
    public string $dateTo       = '';
    public string $sortField    = 'purchase_date';
    public string $sortDir      = 'desc';

    // ─── Per-page ─────────────────────────────────────────────────

    public int $perPage = 20;

    // ─── Lifecycle ────────────────────────────────────────────────

    protected $queryString = [
        'search'        => ['except' => ''],
        'statusFilter'  => ['except' => '', 'as' => 'status'],
        'channelFilter' => ['except' => '', 'as' => 'channel'],
        'dateFrom'      => ['except' => '', 'as' => 'from'],
        'dateTo'        => ['except' => '', 'as' => 'to'],
        'sortField'     => ['except' => 'purchase_date'],
        'sortDir'       => ['except' => 'desc'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    // ─── Sorting ──────────────────────────────────────────────────

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDir   = 'desc';
        }
    }

    // ─── Reset ────────────────────────────────────────────────────

    public function resetFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'channelFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    // ─── Query ────────────────────────────────────────────────────

    public function getOrdersProperty()
    {
        return Order::with(['items', 'amazonAccount'])
            ->when($this->search, fn($q) =>
                $q->where(function ($q) {
                    $q->where('amazon_order_id', 'like', "%{$this->search}%")
                      ->orWhere('buyer_name',    'like', "%{$this->search}%")
                      ->orWhere('buyer_email',   'like', "%{$this->search}%")
                      ->orWhereHas('items', fn($q) =>
                          $q->where('seller_sku', 'like', "%{$this->search}%")
                            ->orWhere('asin',     'like', "%{$this->search}%")
                      );
                })
            )
            ->when($this->statusFilter, fn($q) =>
                $q->where('order_status', $this->statusFilter)
            )
            ->when($this->channelFilter, fn($q) =>
                $q->where('fulfillment_channel', $this->channelFilter)
            )
            ->when($this->dateFrom, fn($q) =>
                $q->whereDate('purchase_date', '>=', $this->dateFrom)
            )
            ->when($this->dateTo, fn($q) =>
                $q->whereDate('purchase_date', '<=', $this->dateTo)
            )
            ->orderBy($this->sortField, $this->sortDir)
            ->paginate($this->perPage);
    }

    // ─── Stats for header cards ───────────────────────────────────

    public function getStatsProperty(): array
    {
        $base = Order::query();

        return [
            'total'     => (clone $base)->count(),
            'shipped'   => (clone $base)->where('order_status', 'Shipped')->count(),
            'pending'   => (clone $base)->where('order_status', 'Pending')->count(),
            'revenue'   => (clone $base)->whereNotNull('order_total')->sum('order_total'),
        ];
    }

    // ─── Render ───────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.orders.order-table', [
            'orders' => $this->orders,
            'stats'  => $this->stats,
        ]);
    }
}
