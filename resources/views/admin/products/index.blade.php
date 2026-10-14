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
            {{ data_get($products, '0.product_name', 'N/A') }}
        </div>
        <div class="metric-badge" style="color: #248a3d;">
            <span>${{ number_format((float) data_get($products, '0.total_gross_revenue', 0), 2) }} Gross Revenue</span>
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
        <span style="font-size: 12px; color: var(--color-ink-muted);">Updated Daily</span>
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
                $maxRevenue = !empty($products) ? max(array_column($products, 'total_gross_revenue') ?: [1]) : 1;
            @endphp
            @forelse($products as $idx => $prod)
                @php
                    $prodName = data_get($prod, 'product_name');
                    $unitsSold = data_get($prod, 'total_units_sold', 0);
                    $grossRev = (float) data_get($prod, 'total_gross_revenue', 0);
                    $avgPrice = (float) data_get($prod, 'average_price', 0);
                    $occurrences = data_get($prod, 'order_occurrences', 0);
                    $sharePercent = $maxRevenue > 0 ? round(($grossRev / $maxRevenue) * 100) : 0;
                @endphp
                <tr>
                    <td style="font-weight: 700; color: var(--color-ink-muted);">#{{ $idx + 1 }}</td>
                    <td style="font-weight: 600; font-size: 15px;">{{ $prodName }}</td>
                    <td style="font-weight: 600;">{{ number_format($unitsSold) }}</td>
                    <td style="font-weight: 700; font-size: 15px; color: #248a3d;">
                        ${{ number_format($grossRev, 2) }}
                    </td>
                    <td style="color: var(--color-ink-muted);">
                        ${{ number_format($avgPrice, 2) }}
                    </td>
                    <td>
                        <span style="padding: 2px 8px; border-radius: var(--radius-full); background: rgba(0,0,0,0.05); font-size: 12px; font-weight: 600;">
                            {{ number_format($occurrences) }} orders
                        </span>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="flex: 1; height: 6px; background: rgba(0,0,0,0.06); border-radius: 999px; overflow: hidden;">
                                <div style="height: 100%; width: {{ $sharePercent }}%; background: var(--color-primary); border-radius: 999px;"></div>
                            </div>
                            <span style="font-size: 11px; color: var(--color-ink-muted); font-weight: 600; min-width: 30px;">
                                {{ $sharePercent }}%
                            </span>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 48px; color: var(--color-ink-muted);">
                        No product data currently loaded.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
