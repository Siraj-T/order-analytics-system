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

    <!-- Server-Side Pagination Bar (10 per page) -->
    <div class="pagination-container">
        <div class="pagination-info">
            Showing <span style="font-weight: 600; color: var(--color-ink);">{{ number_format($customers->firstItem() ?? 0) }}</span> to 
            <span style="font-weight: 600; color: var(--color-ink);">{{ number_format($customers->lastItem() ?? 0) }}</span> of 
            <span style="font-weight: 600; color: var(--color-ink);">{{ number_format($customers->total()) }}</span> customers
        </div>

        <div style="display: flex; align-items: center; gap: 8px;">
            <div class="pagination-links">
                @if($customers->currentPage() > 2)
                    <a href="{{ $customers->url(1) }}" class="page-btn" title="First Page">&laquo; 1</a>
                @endif

                @if ($customers->onFirstPage())
                    <span class="page-btn disabled">&larr; Previous</span>
                @else
                    <a href="{{ $customers->previousPageUrl() }}" class="page-btn">&larr; Previous</a>
                @endif

                <span class="page-btn active" style="cursor: default;">Page {{ number_format($customers->currentPage()) }} of {{ number_format($customers->lastPage()) }}</span>

                @if ($customers->hasMorePages())
                    <a href="{{ $customers->nextPageUrl() }}" class="page-btn">Next &rarr;</a>
                @else
                    <span class="page-btn disabled">Next &rarr;</span>
                @endif

                @if($customers->currentPage() < $customers->lastPage() - 1)
                    <a href="{{ $customers->url($customers->lastPage()) }}" class="page-btn" title="Last Page">{{ number_format($customers->lastPage()) }} &raquo;</a>
                @endif
            </div>

            <form method="GET" action="{{ route('admin.customers') }}" style="display: flex; align-items: center; gap: 4px; margin-left: 8px;">
                @foreach(request()->except('page') as $key => $val)
                    <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                @endforeach
                <input type="number" name="page" min="1" max="{{ $customers->lastPage() }}" placeholder="Jump" class="filter-input" style="width: 70px; padding: 5px 8px; font-size: 12px;">
                <button type="submit" class="page-btn" style="padding: 5px 10px; font-size: 12px; cursor: pointer;">Go</button>
            </form>
        </div>
    </div>
</div>
@endsection
