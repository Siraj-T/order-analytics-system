<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Order Analytics System')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <div class="app-container">
        <!-- Translucent Apple Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="brand-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                        <polyline points="2 17 12 22 22 17"></polyline>
                        <polyline points="2 12 12 17 22 12"></polyline>
                    </svg>
                </div>
                <div>
                    <h1 class="brand-title">Order Analytics</h1>
                    <span class="brand-subtitle">4,025,000 Records System</span>
                </div>
            </div>

            <ul class="nav-menu">
                <li class="nav-label">Analytics</li>
                <li class="nav-item {{ request()->routeIs('admin.dashboard*') ? 'active' : '' }}">
                    <a href="{{ route('admin.dashboard') }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="7" height="9" x="3" y="3" rx="1" />
                            <rect width="7" height="5" x="14" y="3" rx="1" />
                            <rect width="7" height="9" x="14" y="12" rx="1" />
                            <rect width="7" height="5" x="3" y="16" rx="1" />
                        </svg>
                        <span>Executive Dashboard</span>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('admin.products*') ? 'active' : '' }}">
                    <a href="{{ route('admin.products') }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 20v-6M6 20V10M18 20V4" />
                        </svg>
                        <span>Product Insights</span>
                    </a>
                </li>

                <li class="nav-label">Operations</li>
                <li class="nav-item {{ request()->routeIs('admin.orders*') ? 'active' : '' }}">
                    <a href="{{ route('admin.orders') }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="8" cy="21" r="1" />
                            <circle cx="19" cy="21" r="1" />
                            <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" />
                        </svg>
                        <span>Orders Explorer</span>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('admin.customers*') ? 'active' : '' }}">
                    <a href="{{ route('admin.customers') }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                        <span>Customers Directory</span>
                    </a>
                </li>
            </ul>

            <div style="padding: 16px 8px; border-top: 1px solid var(--color-hairline); font-size: 11px; color: var(--color-ink-muted);">
                <span>High-Performance Keyset Engine</span>
            </div>
        </aside>

        <!-- Main Workspace -->
        <main class="main-content">
            <header class="topbar">
                <h2 class="page-title">@yield('page-title', 'Overview')</h2>
                <div class="topbar-right">
                    <span style="font-size: 13px; font-weight: 600; color: var(--color-ink); background: var(--color-surface-pearl); padding: 6px 14px; border-radius: var(--radius-full); border: 1px solid var(--color-border); display: inline-flex; align-items: center; gap: 6px;">
                        <span style="width: 7px; height: 7px; border-radius: 50%; background-color: var(--color-primary);"></span>
                        1,000,000+ Records
                    </span>
                </div>
            </header>

            <div class="content-body">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Apple Slide-Over Line Item Drawer -->
    <div id="order-drawer-overlay" class="drawer-overlay"></div>
    <div id="order-drawer" class="drawer">
        <div class="drawer-header">
            <h3 id="order-drawer-title" class="drawer-title">Order Details</h3>
            <button id="order-drawer-close" class="close-btn" aria-label="Close Drawer">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div id="order-drawer-body" class="drawer-body"></div>
    </div>

    <!-- Apple Customer Profile Modal -->
    <div id="customer-modal-overlay" class="modal-overlay">
        <div class="modal-content">
            <div class="drawer-header" style="border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
                <h3 class="drawer-title">Customer Profile</h3>
                <button id="customer-modal-close" class="close-btn" aria-label="Close Modal">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div id="customer-modal-body" style="padding: 24px; overflow-y: auto;"></div>
        </div>
    </div>

    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
