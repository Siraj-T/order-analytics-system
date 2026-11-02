/**
 * Order Analytics System - Apple Fluid Interface Interaction Script
 * WWDC 2018 Fluid Interface Principles (Interruptibility, Latency Minimization, Physics)
 */

document.addEventListener('DOMContentLoaded', () => {
    initInstantNavigation();
    initLiveSync();
    initOrderDrawer();
    initCustomerModal();
});

/**
 * 0. Instant Fluid Navigation (Hover / Mousedown Link & Tab Prefetcher)
 * Pre-fetches navigation endpoints on pointer hover / touchstart into browser HTTP cache.
 * Makes tab and page switching feel 0ms instantaneous.
 */
function initInstantNavigation() {
    const prefetchedUrls = new Set();

    function prefetch(url) {
        if (!url || prefetchedUrls.has(url) || url.startsWith('#') || url.startsWith('javascript:')) return;
        try {
            const parsed = new URL(url, window.location.origin);
            if (parsed.origin !== window.location.origin) return;
            prefetchedUrls.add(parsed.href);

            const link = document.createElement('link');
            link.rel = 'prefetch';
            link.href = parsed.href;
            link.as = 'document';
            document.head.appendChild(link);
        } catch (e) {
            // Quiet fail
        }
    }

    const selector = '.nav-menu a, .tab-btn, .pagination-links a, a[href^="/admin"]';

    document.addEventListener('mouseover', (e) => {
        const link = e.target.closest(selector);
        if (link && link.href) {
            prefetch(link.href);
        }

        // Prefetch on-demand order drawer on row hover (<0.2ms warm cache pop)
        const orderRow = e.target.closest('[data-order-id]');
        if (orderRow) {
            const orderId = orderRow.getAttribute('data-order-id');
            const drawerUrl = `/admin/orders/${orderId}/items`;
            if (orderId && !prefetchedUrls.has(drawerUrl)) {
                prefetchedUrls.add(drawerUrl);
                fetch(drawerUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).catch(() => {});
            }
        }

        // Prefetch customer profile modal on hover
        const custBtn = e.target.closest('[data-customer-id]');
        if (custBtn) {
            const custId = custBtn.getAttribute('data-customer-id');
            const custUrl = `/admin/customers/${custId}`;
            if (custId && !prefetchedUrls.has(custUrl)) {
                prefetchedUrls.add(custUrl);
                fetch(custUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).catch(() => {});
            }
        }
    }, { passive: true });

    document.addEventListener('touchstart', (e) => {
        const link = e.target.closest(selector);
        if (link && link.href) {
            prefetch(link.href);
        }
    }, { passive: true });
}

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

        // Prevent opening only if clicking inside text input elements
        if (e.target.tagName === 'INPUT') return;

        // Smoothly dismiss order slide-over drawer if open when transitioning to customer modal
        const drawer = document.getElementById('order-drawer');
        const drawerOverlay = document.getElementById('order-drawer-overlay');
        if (drawer && drawer.classList.contains('open')) {
            drawer.classList.remove('open');
            if (drawerOverlay) drawerOverlay.classList.remove('open');
        }

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
