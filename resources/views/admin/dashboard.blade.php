@extends('layouts.admin')

@section('title', 'Executive Dashboard | Order Analytics')
@section('page-title', 'Executive Analytics Dashboard')

@section('content')
<div data-live-sync data-version="{{ $version }}" data-live-url="{{ route('admin.dashboard.live') }}">
    @if(!empty($dbOffline))
        <div style="background: linear-gradient(135deg, rgba(0, 102, 204, 0.08) 0%, rgba(0, 113, 227, 0.04) 100%); border: 1px solid rgba(0, 102, 204, 0.25); border-radius: var(--radius-lg); padding: 22px 28px; margin-bottom: 28px; display: flex; align-items: center; justify-content: space-between; gap: 20px;">
            <div>
                <h3 style="color: var(--color-primary); font-size: 17px; font-weight: 600; margin-bottom: 6px;">Database Setup Required</h3>
                <p style="color: var(--color-ink); font-size: 14px; margin-bottom: 0;">Start MySQL service (<code>net start MySQL80</code>) and stream-import the 4,025,000 records:</p>
            </div>
            <code style="background: #fff; padding: 10px 18px; border-radius: var(--radius-sm); font-size: 14px; font-weight: 600; border: 1px solid var(--color-border); color: var(--color-primary); white-space: nowrap; box-shadow: var(--shadow-subtle);">
                php artisan app:import-dump
            </code>
        </div>
    @endif

    <!-- KPI Metric Cards Grid -->
    <div class="metrics-grid">
        <div class="metric-card">
            <span class="metric-label">Total Gross Revenue</span>
            <div id="metric-total_revenue" class="metric-value">${{ number_format($totalRevenue, 2) }}</div>
            <div class="metric-badge" style="color: #248a3d;">
                <span>Across {{ number_format($totalOrders) }} Orders</span>
            </div>
        </div>

        <div class="metric-card">
            <span class="metric-label">Completed Orders</span>
            <div id="metric-completed_count" class="metric-value" style="color: #248a3d;">{{ number_format($completedCount) }}</div>
            <div class="metric-badge" style="color: var(--color-ink-muted);">
                <span>{{ $totalOrders > 0 ? round(($completedCount / $totalOrders) * 100, 1) : 0 }}% Fulfillment Rate</span>
            </div>
        </div>

        <div class="metric-card">
            <span class="metric-label">Pending Processing</span>
            <div id="metric-pending_count" class="metric-value" style="color: #b36b00;">{{ number_format($pendingCount) }}</div>
            <div class="metric-badge" style="color: var(--color-ink-muted);">
                <span>Awaiting action</span>
            </div>
        </div>

        <div class="metric-card">
            <span class="metric-label">Refunded Orders</span>
            <div id="metric-refunded_count" class="metric-value" style="color: #4b48b5;">{{ number_format($refundedCount) }}</div>
            <div class="metric-badge" style="color: var(--color-ink-muted);">
                <span>{{ $totalOrders > 0 ? round(($refundedCount / $totalOrders) * 100, 1) : 0 }}% Refund Rate</span>
            </div>
        </div>
    </div>

    <!-- Status Distribution Bar -->
    <div class="metric-card" style="margin-bottom: 28px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <span class="metric-label" style="margin-bottom: 0;">Portfolio Order Distribution</span>
            <span style="font-size: 12px; color: var(--color-ink-muted);">1,000,000 Total Transactions</span>
        </div>
        <div style="height: 10px; width: 100%; border-radius: var(--radius-full); display: flex; overflow: hidden; background: rgba(0,0,0,0.06);">
            <div style="width: {{ $totalOrders > 0 ? ($completedCount / $totalOrders) * 100 : 25 }}%; background-color: var(--color-success);" title="Completed"></div>
            <div style="width: {{ $totalOrders > 0 ? ($pendingCount / $totalOrders) * 100 : 25 }}%; background-color: var(--color-warning);" title="Pending"></div>
            <div style="width: {{ $totalOrders > 0 ? ($refundedCount / $totalOrders) * 100 : 25 }}%; background-color: var(--color-info);" title="Refunded"></div>
            <div style="width: {{ $totalOrders > 0 ? ($cancelledCount / $totalOrders) * 100 : 25 }}%; background-color: var(--color-danger);" title="Cancelled"></div>
        </div>
        <div style="display: flex; gap: 20px; margin-top: 14px; font-size: 12px;">
            <div style="display: flex; align-items: center; gap: 6px;"><span style="width: 8px; height: 8px; border-radius: 50%; background: var(--color-success);"></span> Completed: {{ number_format($completedCount) }}</div>
            <div style="display: flex; align-items: center; gap: 6px;"><span style="width: 8px; height: 8px; border-radius: 50%; background: var(--color-warning);"></span> Pending: {{ number_format($pendingCount) }}</div>
            <div style="display: flex; align-items: center; gap: 6px;"><span style="width: 8px; height: 8px; border-radius: 50%; background: var(--color-info);"></span> Refunded: {{ number_format($refundedCount) }}</div>
            <div style="display: flex; align-items: center; gap: 6px;"><span style="width: 8px; height: 8px; border-radius: 50%; background: var(--color-danger);"></span> Cancelled: {{ number_format($cancelledCount) }}</div>
        </div>
    </div>

    <!-- Live Recent Orders Ledger -->
    <div class="table-card">
        <div style="padding: 20px 24px; border-bottom: 1px solid var(--color-hairline); display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="font-size: 16px; font-weight: 600; letter-spacing: -0.2px;">Recent Orders Stream</h3>
                <p style="font-size: 13px; color: var(--color-ink-muted);">Click any order row to inspect line items in the slide-over drawer</p>
            </div>
            <a href="{{ route('admin.orders') }}" class="page-btn" style="font-size: 12px; font-weight: 600;">View All 1,000,000 Orders &rarr;</a>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Destination</th>
                    <th>Date & Time</th>
                    <th>Items</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="live-orders-table-body">
                @fragment('live-rows')
                    @forelse($recentOrders as $order)
                        <tr data-order-id="{{ $order->id }}">
                            <td style="font-weight: 600; color: var(--color-primary);">#{{ $order->id }}</td>
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
                            <td style="font-weight: 600; font-size: 15px;">
                                ${{ number_format($order->total_amount, 2) }}
                            </td>
                            <td>
                                <span class="badge badge-{{ $order->status }}">
                                    {{ $order->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: var(--color-ink-muted);">
                                No orders currently available in database. Run <code>php artisan app:import-dump</code> to load the 4M records.
                            </td>
                        </tr>
                    @endforelse
                @endfragment
            </tbody>
        </table>
    </div>
</div>
@endsection
