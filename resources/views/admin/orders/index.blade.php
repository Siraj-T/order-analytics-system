@extends('layouts.admin')

@section('title', 'Orders Explorer | Order Analytics')
@section('page-title', 'Orders Explorer (1,000,000 Records)')

@section('content')
<!-- Status Tabs Group (Apple Segmented Control) -->
<div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
    <div class="tab-group">
        <a href="{{ route('admin.orders', array_merge(request()->query(), ['status' => 'all', 'page' => 1])) }}" 
           class="tab-btn {{ $status === 'all' || empty($status) ? 'active' : '' }}">
           All Orders ({{ number_format($statusCounts->all_count ?? 0) }})
        </a>
        <a href="{{ route('admin.orders', array_merge(request()->query(), ['status' => 'completed', 'page' => 1])) }}" 
           class="tab-btn {{ $status === 'completed' ? 'active' : '' }}">
           Completed ({{ number_format($statusCounts->completed_count ?? 0) }})
        </a>
        <a href="{{ route('admin.orders', array_merge(request()->query(), ['status' => 'pending', 'page' => 1])) }}" 
           class="tab-btn {{ $status === 'pending' ? 'active' : '' }}">
           Pending ({{ number_format($statusCounts->pending_count ?? 0) }})
        </a>
        <a href="{{ route('admin.orders', array_merge(request()->query(), ['status' => 'refunded', 'page' => 1])) }}" 
           class="tab-btn {{ $status === 'refunded' ? 'active' : '' }}">
           Refunded ({{ number_format($statusCounts->refunded_count ?? 0) }})
        </a>
        <a href="{{ route('admin.orders', array_merge(request()->query(), ['status' => 'cancelled', 'page' => 1])) }}" 
           class="tab-btn {{ $status === 'cancelled' ? 'active' : '' }}">
           Cancelled ({{ number_format($statusCounts->cancelled_count ?? 0) }})
        </a>
    </div>

    <span style="font-size: 13px; color: var(--color-ink-muted);">
        Server-Side 10 items per page (Sub-10ms response)
    </span>
</div>

<!-- Multi-Filter Bar -->
<form method="GET" action="{{ route('admin.orders') }}" class="filter-bar">
    <input type="hidden" name="status" value="{{ $status }}">

    <div class="filter-group">
        <input type="text" name="search" value="{{ $search }}" placeholder="Search by Order # or Customer..." class="filter-input" style="width: 260px;">

        <select name="country" class="filter-select">
            <option value="">All Shipping Countries</option>
            @foreach($countries as $c)
                <option value="{{ $c }}" {{ $country === $c ? 'selected' : '' }}>{{ $c }}</option>
            @endforeach
        </select>

        <div style="display: flex; align-items: center; gap: 6px;">
            <input type="date" name="from" value="{{ $from }}" class="filter-input" title="From date">
            <span style="color: var(--color-ink-muted); font-size: 12px;">to</span>
            <input type="date" name="to" value="{{ $to }}" class="filter-input" title="To date">
        </div>
    </div>

    <div style="display: flex; gap: 10px;">
        <button type="submit" class="page-btn active" style="cursor: pointer; padding: 8px 18px;">Apply Filters</button>
        @if(!empty($search) || !empty($country) || !empty($from) || !empty($to) || ($status !== 'all' && !empty($status)))
            <a href="{{ route('admin.orders') }}" class="page-btn" style="padding: 8px 14px;">Reset</a>
        @endif
    </div>
</form>

<!-- Orders Table Card -->
<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Destination</th>
                <th>Date & Time</th>
                <th>Items Count</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr data-order-id="{{ $order->id }}">
                    <td style="font-weight: 700; color: var(--color-primary);">#{{ $order->id }}</td>
                    <td>
                        <div style="font-weight: 500;">{{ $order->customer->full_name ?? 'Customer #' . $order->customer_id }}</div>
                        <div style="font-size: 12px; color: var(--color-ink-muted);">{{ $order->customer->email ?? '' }}</div>
                    </td>
                    <td>
                        <span style="font-weight: 500;">{{ $order->shipping_country }}</span>
                    </td>
                    <td style="color: var(--color-ink-muted); font-size: 13px;">
                        {{ $order->order_date ? $order->order_date->format('M d, Y H:i') : 'N/A' }}
                    </td>
                    <td>
                        <span style="padding: 2px 8px; border-radius: var(--radius-full); background: rgba(0,0,0,0.05); font-size: 12px; font-weight: 600;">
                            {{ $order->items_count }} items
                        </span>
                    </td>
                    <td style="font-weight: 700; font-size: 15px;">
                        ${{ number_format($order->total_amount, 2) }}
                    </td>
                    <td>
                        <span class="badge badge-{{ $order->status }}">
                            {{ $order->status }}
                        </span>
                    </td>
                    <td>
                        <button type="button" class="page-btn" style="font-size: 12px; padding: 4px 10px;">
                            Inspect &rarr;
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 48px; color: var(--color-ink-muted);">
                        No orders match the specified criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Server-Side Pagination Bar (Strict 10 items per page) -->
    <div class="pagination-container">
        <div class="pagination-info">
            Showing <span style="font-weight: 600; color: var(--color-ink);">{{ number_format($orders->firstItem() ?? 0) }}</span> to 
            <span style="font-weight: 600; color: var(--color-ink);">{{ number_format($orders->lastItem() ?? 0) }}</span> of 
            <span style="font-weight: 600; color: var(--color-ink);">{{ number_format($orders->total()) }}</span> orders
        </div>

        <div style="display: flex; align-items: center; gap: 8px;">
            <div class="pagination-links">
                {{-- First Page --}}
                @if($orders->currentPage() > 2)
                    <a href="{{ $orders->url(1) }}" class="page-btn" title="First Page">&laquo; 1</a>
                @endif

                {{-- Previous Page Link --}}
                @if ($orders->onFirstPage())
                    <span class="page-btn disabled">&larr; Previous</span>
                @else
                    <a href="{{ $orders->previousPageUrl() }}" class="page-btn">&larr; Previous</a>
                @endif

                {{-- Current Page Indicator --}}
                <span class="page-btn active" style="cursor: default;">Page {{ number_format($orders->currentPage()) }} of {{ number_format($orders->lastPage()) }}</span>

                {{-- Next Page Link --}}
                @if ($orders->hasMorePages())
                    <a href="{{ $orders->nextPageUrl() }}" class="page-btn">Next &rarr;</a>
                @else
                    <span class="page-btn disabled">Next &rarr;</span>
                @endif

                {{-- Last Page --}}
                @if($orders->currentPage() < $orders->lastPage() - 1)
                    <a href="{{ $orders->url($orders->lastPage()) }}" class="page-btn" title="Last Page">{{ number_format($orders->lastPage()) }} &raquo;</a>
                @endif
            </div>

            {{-- Instant Jump Form --}}
            <form method="GET" action="{{ route('admin.orders') }}" style="display: flex; align-items: center; gap: 4px; margin-left: 8px;">
                @foreach(request()->except('page') as $key => $val)
                    <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                @endforeach
                <input type="number" name="page" min="1" max="{{ $orders->lastPage() }}" placeholder="Jump" class="filter-input" style="width: 70px; padding: 5px 8px; font-size: 12px;">
                <button type="submit" class="page-btn" style="padding: 5px 10px; font-size: 12px; cursor: pointer;">Go</button>
            </form>
        </div>
    </div>
</div>
@endsection
