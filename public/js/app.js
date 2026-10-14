/**
 * Order Analytics System - Apple Fluid Interface Interaction Script
 * WWDC 2018 Fluid Interface Principles (Interruptibility, Latency Minimization, Physics)
 */

document.addEventListener('DOMContentLoaded', () => {
    initLiveSync();
    initOrderDrawer();
    initCustomerModal();
});

/**
 * 1. Live Background Auto-Sync
 * Periodically polls the server with version fingerprint. Zero latency DOM update via Blade fragments.
 */
function initLiveSync() {
    const liveContainer = document.querySelector('[data-live-sync]');
    if (!liveContainer) return;

    let currentVersion = liveContainer.getAttribute('data-version') || '';
    const liveUrl = liveContainer.getAttribute('data-live-url') || '/admin/dashboard/live';

    setInterval(async () => {
        try {
            const response = await fetch(`${liveUrl}?v=${encodeURIComponent(currentVersion)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!response.ok) return;
            const data = await response.json();

            if (data.changed) {
                currentVersion = data.version;
                liveContainer.setAttribute('data-version', currentVersion);

                // Update metric counters
                if (data.metrics) {
                    for (const [key, val] of Object.entries(data.metrics)) {
                        const el = document.getElementById(`metric-${key}`);
                        if (el) el.textContent = val;
                    }
                }

                // Update rows fragment
                if (data.rowsHtml) {
                    const tableBody = document.getElementById('live-orders-table-body');
                    if (tableBody) tableBody.innerHTML = data.rowsHtml;
                }

                // Flash pulse indicator
                const pulse = document.querySelector('.live-pulse');
                if (pulse) {
                    pulse.style.transform = 'scale(1.5)';
                    setTimeout(() => pulse.style.transform = 'scale(1)', 400);
                }
            }
        } catch (err) {
            // Quiet network degradation
        }
    }, 8000);
}

/**
 * 2. Slide-Over Order Items Inspector Drawer
 * Interruptible physics-based drawer loading 3M line items strictly on-demand.
 */
function initOrderDrawer() {
    const overlay = document.getElementById('order-drawer-overlay');
    const drawer = document.getElementById('order-drawer');
    const body = document.getElementById('order-drawer-body');
    const title = document.getElementById('order-drawer-title');
    const closeBtn = document.getElementById('order-drawer-close');

    if (!drawer) return;

    function closeDrawer() {
        if (overlay) overlay.classList.remove('open');
        drawer.classList.remove('open');
    }

    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    if (overlay) overlay.addEventListener('click', closeDrawer);

    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && drawer.classList.contains('open')) {
            closeDrawer();
        }
    });

    // Delegate row click on data-order-row
    document.addEventListener('click', async (e) => {
        const row = e.target.closest('[data-order-id]');
        if (!row) return;

        const orderId = row.getAttribute('data-order-id');
        if (!orderId) return;

        if (title) title.textContent = `Order #${orderId} Details`;
        if (body) {
            body.innerHTML = `
                <div style="display:flex; justify-content:center; align-items:center; height:200px; color:var(--color-ink-muted);">
                    <div class="live-pulse" style="margin-right:8px;"></div> Loading line items...
                </div>
            `;
        }

        if (overlay) overlay.classList.add('open');
        drawer.classList.add('open');

        try {
            const res = await fetch(`/admin/orders/${orderId}/items`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (res.ok) {
                const html = await res.text();
                if (body) body.innerHTML = html;
            } else {
                if (body) body.innerHTML = `<p style="color:var(--color-danger);">Failed to load order items.</p>`;
            }
        } catch (err) {
            if (body) body.innerHTML = `<p style="color:var(--color-danger);">Network error loading items.</p>`;
        }
    });
}

/**
 * 3. Customer Profile & Order History Modal
 */
function initCustomerModal() {
    const overlay = document.getElementById('customer-modal-overlay');
    const body = document.getElementById('customer-modal-body');
    const closeBtn = document.getElementById('customer-modal-close');

    if (!overlay) return;

    function closeModal() {
        overlay.classList.remove('open');
    }

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) closeModal();
    });

    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && overlay.classList.contains('open')) {
            closeModal();
        }
    });

    document.addEventListener('click', async (e) => {
        const trigger = e.target.closest('[data-customer-id]');
        if (!trigger) return;

        // Prevent opening if clicking inside an input or button
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'BUTTON') return;

        const customerId = trigger.getAttribute('data-customer-id');
        if (!customerId) return;

        if (body) {
            body.innerHTML = `
                <div style="display:flex; justify-content:center; align-items:center; height:180px; color:var(--color-ink-muted);">
                    <div class="live-pulse" style="margin-right:8px;"></div> Loading customer profile...
                </div>
            `;
        }

        overlay.classList.add('open');

        try {
            const res = await fetch(`/admin/customers/${customerId}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (res.ok) {
                const html = await res.text();
                if (body) body.innerHTML = html;
            }
        } catch (err) {
            if (body) body.innerHTML = `<p style="color:var(--color-danger); padding:20px;">Unable to load customer history.</p>`;
        }
    });
}
