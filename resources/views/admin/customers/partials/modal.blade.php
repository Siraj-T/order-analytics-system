<div>
    <div style="background: var(--color-surface-pearl); border-radius: var(--radius-md); padding: 18px; border: 1px solid var(--color-hairline); margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <div>
                <h3 style="font-size: 18px; font-weight: 700;">{{ $customer->full_name }}</h3>
                <div style="font-size: 13px; color: var(--color-ink-muted);">{{ $customer->email }}</div>
            </div>
            <div>
                @if($customer->is_active)
                    <span class="badge badge-completed">Active Customer</span>
                @else
                    <span class="badge badge-cancelled">Inactive</span>
                @endif
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; padding-top: 12px; border-top: 1px solid var(--color-hairline); font-size: 13px;">
            <div>
                <span style="color: var(--color-ink-muted);">Country:</span>
                <div style="font-weight: 600;">{{ $customer->country }}</div>
            </div>
            <div>
                <span style="color: var(--color-ink-muted);">Total Orders:</span>
                <div style="font-weight: 600;">{{ number_format($customer->orders_count) }}</div>
            </div>
            <div>
                <span style="color: var(--color-ink-muted);">Total Spend (LTV):</span>
                <div style="font-weight: 700; color: #248a3d;">${{ number_format((float) $customer->orders_sum_total_amount, 2) }}</div>
            </div>
        </div>
    </div>

    <div>
        <h4 style="font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; color: var(--color-ink-muted); margin-bottom: 12px;">
            Recent Orders History (Last {{ $customer->orders->count() }})
        </h4>

        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <thead>
                <tr style="border-bottom: 1px solid var(--color-hairline); color: var(--color-ink-muted); text-align: left;">
                    <th style="padding: 10px 6px;">Order ID</th>
                    <th style="padding: 10px 6px;">Date</th>
                    <th style="padding: 10px 6px;">Destination</th>
                    <th style="padding: 10px 6px;">Status</th>
                    <th style="padding: 10px 6px; text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customer->orders as $order)
                    <tr style="border-bottom: 1px solid var(--color-hairline);">
                        <td style="padding: 10px 6px; font-weight: 600; color: var(--color-primary);">
                            #{{ $order->id }}
                        </td>
                        <td style="padding: 10px 6px; color: var(--color-ink-muted);">
                            {{ $order->order_date ? $order->order_date->format('M d, Y') : 'N/A' }}
                        </td>
                        <td style="padding: 10px 6px;">
                            {{ $order->shipping_country }}
                        </td>
                        <td style="padding: 10px 6px;">
                            <span class="badge badge-{{ $order->status }}">
                                {{ $order->status }}
                            </span>
                        </td>
                        <td style="padding: 10px 6px; text-align: right; font-weight: 600;">
                            ${{ number_format($order->total_amount, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 20px; color: var(--color-ink-muted);">
                            No orders on record.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
