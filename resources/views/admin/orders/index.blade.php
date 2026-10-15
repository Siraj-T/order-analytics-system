@extends('layouts.admin')

@section('title', 'Orders Explorer | Order Analytics')
@section('page-title', 'Orders Explorer (1,000,000 Records)')

@section('content')
<!-- Status Tabs Group (Apple Segmented Control) -->
<div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
    <div class="tab-group">
        <a href="{{ route('admin.orders', array_merge(request()->query(), ['status' => 'all', 'page' => 1])) }}" 
           class="tab-btn {{ $status === 'all' || empty($status) ? 'active' : '' }}">
           All Orders ({{ number_format(data_get($statusCounts, 'all_count', 0)) }})
        </a>
        <a href="{{ route('admin.orders', array_merge(request()->query(), ['status' => 'completed', 'page' => 1])) }}" 
           class="tab-btn {{ $status === 'completed' ? 'active' : '' }}">
           Completed ({{ number_format(data_get($statusCounts, 'completed_count', 0)) }})
        </a>
        <a href="{{ route('admin.orders', array_merge(request()->query(), ['status' => 'pending', 'page' => 1])) }}" 
           class="tab-btn {{ $status === 'pending' ? 'active' : '' }}">
           Pending ({{ number_format(data_get($statusCounts, 'pending_count', 0)) }})
        </a>
        <a href="{{ route('admin.orders', array_merge(request()->query(), ['status' => 'refunded', 'page' => 1])) }}" 
           class="tab-btn {{ $status === 'refunded' ? 'active' : '' }}">
           Refunded ({{ number_format(data_get($statusCounts, 'refunded_count', 0)) }})
        </a>
        <a href="{{ route('admin.orders', array_merge(request()->query(), ['status' => 'cancelled', 'page' => 1])) }}" 
           class="tab-btn {{ $status === 'cancelled' ? 'active' : '' }}">
           Cancelled ({{ number_format(data_get($statusCounts, 'cancelled_count', 0)) }})
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
        @php
            $first = $orders->firstItem() ?? 0;
            $last = $orders->lastItem() ?? 0;
            $total = $orders->total();
            $remaining = max(0, $total - $last);
            $cp = $orders->currentPage();
            $lp = $orders->lastPage();
            $startRange = max(1, $cp - 2);
            $endRange = min($lp, $cp + 2);
        @endphp

        <div class="pagination-info">
            Showing <span style="font-weight: 600; color: var(--color-ink);">{{ number_format($first) }}</span> to 
            <span style="font-weight: 600; color: var(--color-ink);">{{ number_format($last) }}</span> of 
            <span style="font-weight: 600; color: var(--color-ink);">{{ number_format($total) }}</span> orders 
            <span style="color: var(--color-hairline); margin: 0 6px;">|</span>
            <span style="color: var(--color-ink-muted); font-weight: 500;">{{ number_format($remaining) }} remaining</span>
        </div>

        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <div class="pagination-links" style="display: flex; align-items: center; gap: 4px;">
                {{-- Previous Arrow Link (Keyset Cursor Seek) --}}
                @if ($orders->onFirstPage())
                    <span class="page-btn disabled" aria-disabled="true" title="First page reached">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px; margin-right: 2px;"><polyline points="15 18 9 12 15 6"></polyline></svg> Previous
                    </span>
                @else
                    <a href="{{ route('admin.orders', array_merge(request()->query(), ['page' => max(1, $cp - 1), 'cursor_before' => optional($orders->first())->id, 'cursor_after' => null])) }}" class="page-btn" rel="prev" title="Go to previous 10 orders via Keyset Seek">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px; margin-right: 2px;"><polyline points="15 18 9 12 15 6"></polyline></svg> Previous
                    </a>
                @endif

                {{-- Page 1 Pill (if window shifted) --}}
                @if($startRange > 1)
                    <a href="{{ route('admin.orders', array_merge(request()->except(['cursor_after', 'cursor_before']), ['page' => 1])) }}" class="page-btn">1</a>
                    @if($startRange > 2)
                        <span class="page-btn" style="border: none; background: transparent; cursor: default; padding: 6px 4px; color: var(--color-ink-muted);">&hellip;</span>
                    @endif
                @endif

                {{-- Active Window Page Pills (Deferred Join) --}}
                @for($pageNumber = $startRange; $pageNumber <= $endRange; $pageNumber++)
                    @if($pageNumber == $cp)
                        <span class="page-btn active" style="cursor: default;">{{ number_format($pageNumber) }}</span>
                    @else
                        <a href="{{ route('admin.orders', array_merge(request()->except(['cursor_after', 'cursor_before']), ['page' => $pageNumber])) }}" class="page-btn">{{ number_format($pageNumber) }}</a>
                    @endif
                @endfor

                {{-- Last Page Pill (if window shifted) --}}
                @if($endRange < $lp)
                    @if($endRange < $lp - 1)
                        <span class="page-btn" style="border: none; background: transparent; cursor: default; padding: 6px 4px; color: var(--color-ink-muted);">&hellip;</span>
                    @endif
                    <a href="{{ route('admin.orders', array_merge(request()->except(['cursor_after', 'cursor_before']), ['page' => $lp])) }}" class="page-btn" title="Last Page">{{ number_format($lp) }}</a>
                @endif

                {{-- Next Arrow Link (Keyset Cursor Seek) --}}
                @if ($orders->hasMorePages())
                    <a href="{{ route('admin.orders', array_merge(request()->query(), ['page' => min($lp, $cp + 1), 'cursor_after' => optional($orders->last())->id, 'cursor_before' => null])) }}" class="page-btn" rel="next" title="Go to next 10 orders via Keyset Seek">
                        Next <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px; margin-left: 2px;"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </a>
                @else
                    <span class="page-btn disabled" aria-disabled="true" title="Last page reached">
                        Next <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px; margin-left: 2px;"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </span>
                @endif
            </div>

            {{-- Instant Jump Form --}}
            <form method="GET" action="{{ route('admin.orders') }}" style="display: flex; align-items: center; gap: 4px; margin-left: 8px;">
                @foreach(request()->except('page') as $key => $val)
                    @if(is_string($val))
                        <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                    @endif
                @endforeach
                <input type="number" name="page" min="1" max="{{ $orders->lastPage() }}" placeholder="Page" class="filter-input" style="width: 72px; padding: 5px 8px; font-size: 12px; text-align: center;">
                <button type="submit" class="page-btn" style="padding: 5px 10px; font-size: 12px; cursor: pointer;">Go</button>
            </form>
        </div>
    </div>
</div>
@endsection
