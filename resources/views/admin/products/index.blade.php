@extends('layouts.admin')

@section('title', 'Product Performance Insights | Order Analytics')
@section('page-title', 'Product Insights (3,000,000 Line Items)')

@section('content')
<!-- KPI Metric Cards Grid -->
<div class="metrics-grid">
    <div class="metric-card">
        <span class="metric-label">Total Catalog Units Sold</span>
        <div class="metric-value" style="color: var(--color-primary);">{{ number_format($totalCatalogUnits) }}</div>
        <div class="metric-badge" style="color: var(--color-ink-muted);">
            <span>From 3,000,000 line items</span>
        </div>
    </div>

    <div class="metric-card">
        <span class="metric-label">Top Selling Product</span>
        <div class="metric-value" style="font-size: 22px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
            {{ $products->first()->product_name ?? 'N/A' }}
        </div>
        <div class="metric-badge" style="color: #248a3d;">
            <span>${{ number_format($products->first()->total_gross_revenue ?? 0, 2) }} Gross Revenue</span>
        </div>
    </div>

    <div class="metric-card">
        <span class="metric-label">Total Item Transactions</span>
        <div class="metric-value">{{ number_format($totalItemRows) }}</div>
        <div class="metric-badge" style="color: var(--color-ink-muted);">
            <span>Indexed Covering Scan</span>
        </div>
    </div>
</div>

<!-- Product Leaderboard Table Card -->
<div class="table-card">
    <div style="padding: 20px 24px; border-bottom: 1px solid var(--color-hairline); display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h3 style="font-size: 16px; font-weight: 600;">Top 25 Products by Revenue</h3>
            <p style="font-size: 13px; color: var(--color-ink-muted);">Aggregated and cached using composite index <code>idx_items_product_metrics</code></p>
        </div>
        <span style="font-size: 12px; color: var(--color-ink-muted);">Updated Hourly</span>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 60px;">Rank</th>
                <th>Product Name</th>
                <th>Units Sold</th>
                <th>Total Gross Revenue</th>
                <th>Avg. Unit Price</th>
                <th>Order Appearances</th>
                <th style="width: 200px;">Revenue Share</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $maxRevenue = $products->max('total_gross_revenue') ?: 1;
            @endphp
            @forelse($products as $idx => $prod)
                <tr>
                    <td style="font-weight: 700; color: var(--color-ink-muted);">#{{ $idx + 1 }}</td>
                    <td style="font-weight: 600; font-size: 15px;">{{ $prod->product_name }}</td>
                    <td style="font-weight: 600;">{{ number_format($prod->total_units_sold) }}</td>
                    <td style="font-weight: 700; font-size: 15px; color: #248a3d;">
                        ${{ number_format($prod->total_gross_revenue, 2) }}
                    </td>
                    <td style="color: var(--color-ink-muted);">
                        ${{ number_format($prod->average_price, 2) }}
                    </td>
                    <td>
                        <span style="padding: 2px 8px; border-radius: var(--radius-full); background: rgba(0,0,0,0.05); font-size: 12px; font-weight: 600;">
                            {{ number_format($prod->order_occurrences) }} orders
                        </span>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="flex: 1; height: 6px; background: rgba(0,0,0,0.06); border-radius: 999px; overflow: hidden;">
                                <div style="height: 100%; width: {{ round(($prod->total_gross_revenue / $maxRevenue) * 100) }}%; background: var(--color-primary); border-radius: 999px;"></div>
                            </div>
                            <span style="font-size: 11px; color: var(--color-ink-muted); font-weight: 600; min-width: 30px;">
                                {{ round(($prod->total_gross_revenue / $maxRevenue) * 100) }}%
                            </span>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 48px; color: var(--color-ink-muted);">
                        No product data currently loaded. Run <code>php artisan app:import-dump</code> to populate records.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
