@extends('layouts.admin')

@section('title', 'Customers Directory | Order Analytics')
@section('page-title', 'Customers Directory (25,000 Customers)')

@section('content')
<!-- Filter Bar -->
<form method="GET" action="{{ route('admin.customers') }}" class="filter-bar">
    <div class="filter-group">
        <input type="text" name="search" value="{{ $search }}" placeholder="Search customer name or email..." class="filter-input" style="width: 280px;">

        <select name="country" class="filter-select">
            <option value="">All Countries</option>
            @foreach($countries as $c)
                <option value="{{ $c }}" {{ $country === $c ? 'selected' : '' }}>{{ $c }}</option>
            @endforeach
        </select>
    </div>

    <div style="display: flex; gap: 10px;">
        <button type="submit" class="page-btn active" style="cursor: pointer; padding: 8px 18px;">Search</button>
        @if(!empty($search) || !empty($country))
            <a href="{{ route('admin.customers') }}" class="page-btn" style="padding: 8px 14px;">Reset</a>
        @endif
    </div>
</form>

<!-- Customers Table Card -->
<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Customer ID</th>
                <th>Full Name</th>
                <th>Email Address</th>
                <th>Country</th>
                <th>Member Since</th>
                <th>Total Orders</th>
                <th>Lifetime Spend (LTV)</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers as $customer)
                <tr data-customer-id="{{ $customer->id }}">
                    <td style="font-weight: 700; color: var(--color-primary);">#{{ $customer->id }}</td>
                    <td style="font-weight: 600;">{{ $customer->full_name }}</td>
                    <td style="color: var(--color-ink-muted);">{{ $customer->email }}</td>
                    <td><span style="font-weight: 500;">{{ $customer->country }}</span></td>
                    <td style="color: var(--color-ink-muted); font-size: 13px;">
                        {{ $customer->signup_date ? $customer->signup_date->format('M d, Y') : 'N/A' }}
                    </td>
                    <td>
                        <span style="padding: 2px 8px; border-radius: var(--radius-full); background: rgba(0,0,0,0.05); font-size: 12px; font-weight: 600;">
                            {{ number_format($customer->orders_count ?? 0) }} orders
                        </span>
                    </td>
                    <td style="font-weight: 700; font-size: 15px; color: #248a3d;">
                        ${{ number_format((float) ($customer->orders_sum_total_amount ?? 0), 2) }}
                    </td>
                    <td>
                        @if($customer->is_active)
                            <span class="badge badge-completed">Active</span>
                        @else
                            <span class="badge badge-cancelled">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <button type="button" class="page-btn" style="font-size: 12px; padding: 4px 10px;">
                            Profile &rarr;
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; padding: 48px; color: var(--color-ink-muted);">
                        No customers found matching the search criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Server-Side Pagination Bar (Strict 10 items per page) -->
    <div class="pagination-container">
        @php
            $first = $customers->firstItem() ?? 0;
            $last = $customers->lastItem() ?? 0;
            $total = $customers->total();
            $remaining = max(0, $total - $last);
            $cp = $customers->currentPage();
            $lp = $customers->lastPage();
            $startRange = max(1, $cp - 2);
            $endRange = min($lp, $cp + 2);
        @endphp

        <div class="pagination-info">
            Showing <span style="font-weight: 600; color: var(--color-ink);">{{ number_format($first) }}</span> to 
            <span style="font-weight: 600; color: var(--color-ink);">{{ number_format($last) }}</span> of 
            <span style="font-weight: 600; color: var(--color-ink);">{{ number_format($total) }}</span> customers 
            <span style="color: var(--color-hairline); margin: 0 6px;">|</span>
            <span style="color: var(--color-ink-muted); font-weight: 500;">{{ number_format($remaining) }} remaining</span>
        </div>

        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <div class="pagination-links" style="display: flex; align-items: center; gap: 4px;">
                {{-- Previous Arrow Link --}}
                @if ($customers->onFirstPage())
                    <span class="page-btn disabled" aria-disabled="true" title="First page reached">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px; margin-right: 2px;"><polyline points="15 18 9 12 15 6"></polyline></svg> Previous
                    </span>
                @else
                    <a href="{{ $customers->previousPageUrl() }}" class="page-btn" rel="prev" title="Go to previous 10 customers">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px; margin-right: 2px;"><polyline points="15 18 9 12 15 6"></polyline></svg> Previous
                    </a>
                @endif

                {{-- Page 1 Pill (if window shifted) --}}
                @if($startRange > 1)
                    <a href="{{ $customers->url(1) }}" class="page-btn">1</a>
                    @if($startRange > 2)
                        <span class="page-btn" style="border: none; background: transparent; cursor: default; padding: 6px 4px; color: var(--color-ink-muted);">&hellip;</span>
                    @endif
                @endif

                {{-- Active Window Page Pills --}}
                @for($pageNumber = $startRange; $pageNumber <= $endRange; $pageNumber++)
                    @if($pageNumber == $cp)
                        <span class="page-btn active" style="cursor: default;">{{ number_format($pageNumber) }}</span>
                    @else
                        <a href="{{ $customers->url($pageNumber) }}" class="page-btn">{{ number_format($pageNumber) }}</a>
                    @endif
                @endfor

                {{-- Last Page Pill (if window shifted) --}}
                @if($endRange < $lp)
                    @if($endRange < $lp - 1)
                        <span class="page-btn" style="border: none; background: transparent; cursor: default; padding: 6px 4px; color: var(--color-ink-muted);">&hellip;</span>
                    @endif
                    <a href="{{ $customers->url($lp) }}" class="page-btn" title="Last Page">{{ number_format($lp) }}</a>
                @endif

                {{-- Next Arrow Link --}}
                @if ($customers->hasMorePages())
                    <a href="{{ $customers->nextPageUrl() }}" class="page-btn" rel="next" title="Go to next 10 customers">
                        Next <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px; margin-left: 2px;"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </a>
                @else
                    <span class="page-btn disabled" aria-disabled="true" title="Last page reached">
                        Next <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px; margin-left: 2px;"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </span>
                @endif
            </div>

            {{-- Instant Jump Form --}}
            <form method="GET" action="{{ route('admin.customers') }}" style="display: flex; align-items: center; gap: 4px; margin-left: 8px;">
                @foreach(request()->except('page') as $key => $val)
                    @if(is_string($val))
                        <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                    @endif
                @endforeach
                <input type="number" name="page" min="1" max="{{ $customers->lastPage() }}" placeholder="Page" class="filter-input" style="width: 72px; padding: 5px 8px; font-size: 12px; text-align: center;">
                <button type="submit" class="page-btn" style="padding: 5px 10px; font-size: 12px; cursor: pointer;">Go</button>
            </form>
        </div>
    </div>
</div>
@endsection
