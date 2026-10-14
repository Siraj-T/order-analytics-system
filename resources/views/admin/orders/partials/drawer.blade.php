<div>
    <!-- Order Summary Card -->
    <div style="background: var(--color-surface-pearl); border-radius: var(--radius-md); padding: 18px; border: 1px solid var(--color-hairline); margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
            <div>
                <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--color-ink-muted); font-weight: 600;">Status</span>
                <div>
                    <span class="badge badge-{{ $order->status }}" style="margin-top: 4px; font-size: 13px;">
                        {{ $order->status }}
                    </span>
                </div>
            </div>
            <div style="text-align: right;">
                <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--color-ink-muted); font-weight: 600;">Gross Total</span>
                <div style="font-size: 22px; font-weight: 700; color: var(--color-ink);">
                    ${{ number_format($order->total_amount, 2) }}
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding-top: 12px; border-top: 1px solid var(--color-hairline); font-size: 13px;">
            <div>
                <span style="color: var(--color-ink-muted);">Order Date:</span>
                <div style="font-weight: 500;">{{ $order->order_date ? $order->order_date->format('M d, Y - H:i:s') : 'N/A' }}</div>
            </div>
            <div>
                <span style="color: var(--color-ink-muted);">Destination:</span>
                <div style="font-weight: 500;">{{ $order->shipping_country }}</div>
            </div>
        </div>
    </div>

    <!-- Customer Details -->
    <div style="margin-bottom: 24px;">
        <h4 style="font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; color: var(--color-ink-muted); margin-bottom: 10px;">
            Customer Information
        </h4>
        <div style="border: 1px solid var(--color-hairline); border-radius: var(--radius-md); padding: 14px 18px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="font-weight: 600; font-size: 15px;">{{ $order->customer->full_name ?? 'Guest / Unknown' }}</div>
                <div style="font-size: 13px; color: var(--color-ink-muted);">{{ $order->customer->email ?? '' }}</div>
            </div>
            @if($order->customer)
                <button type="button" class="page-btn" data-customer-id="{{ $order->customer->id }}" style="font-size: 12px; padding: 4px 10px;">
                    View Profile &rarr;
                </button>
            @endif
        </div>
    </div>

    <!-- Line Items Table -->
    <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <h4 style="font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; color: var(--color-ink-muted);">
                Purchased Items ({{ $order->items->count() }})
            </h4>
        </div>

        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <thead>
                <tr style="border-bottom: 1px solid var(--color-hairline); color: var(--color-ink-muted); text-align: left;">
                    <th style="padding: 10px 4px;">Product</th>
                    <th style="padding: 10px 4px; text-align: center;">Qty</th>
                    <th style="padding: 10px 4px; text-align: right;">Unit Price</th>
                    <th style="padding: 10px 4px; text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr style="border-bottom: 1px solid var(--color-hairline);">
                        <td style="padding: 12px 4px; font-weight: 500;">
                            {{ $item->product_name }}
                        </td>
                        <td style="padding: 12px 4px; text-align: center; color: var(--color-ink-muted); font-weight: 600;">
                            {{ $item->quantity }}
                        </td>
                        <td style="padding: 12px 4px; text-align: right; color: var(--color-ink-muted);">
                            ${{ number_format($item->unit_price, 2) }}
                        </td>
                        <td style="padding: 12px 4px; text-align: right; font-weight: 600;">
                            ${{ number_format($item->quantity * $item->unit_price, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="padding: 16px 4px; font-weight: 600; text-align: right;">Order Total:</td>
                    <td style="padding: 16px 4px; font-weight: 700; font-size: 16px; text-align: right; color: var(--color-primary);">
                        ${{ number_format($order->total_amount, 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
