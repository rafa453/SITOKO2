<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>sitoko – @yield('title', 'Dashboard')</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link rel="stylesheet" href="{{ asset('css/sitoko.css') }}">

    @stack('styles')
    
    <style>
        .notification-dropdown-wrapper {
            position: relative;
            display: inline-block;
        }

        .notification-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: var(--red-500);
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            border-radius: 9999px;
            min-width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
            border: 2px solid var(--surface);
            pointer-events: none;
        }

        .notification-dropdown {
            position: absolute;
            right: 0;
            top: calc(100% + 8px);
            width: 320px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            z-index: 1000;
            overflow: hidden;
        }

        .notification-header {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-header-title {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 14px;
        }

        .btn-mark-all-read {
            font-size: 12px;
            color: var(--blue-600);
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
        }

        .btn-mark-all-read:hover {
            text-decoration: underline;
        }

        .notification-list {
            max-height: 280px;
            overflow-y: auto;
        }

        .notification-item {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            flex-direction: column;
            gap: 4px;
            transition: background 0.15s ease;
        }

        .notification-item:hover {
            background: var(--border-light);
        }

        .notification-item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 8px;
        }

        .notification-item-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .notification-item-time {
            font-size: 11px;
            color: var(--text-muted);
            white-space: nowrap;
        }

        .notification-item-body {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .notification-item-actions {
            display: flex;
            gap: 12px;
            margin-top: 6px;
        }

        .notification-action-link {
            font-size: 12px;
            color: var(--blue-600);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .notification-action-link:hover {
            text-decoration: underline;
        }

        .btn-mark-single-read {
            font-size: 12px;
            color: var(--text-muted);
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
            padding: 0;
        }

        .btn-mark-single-read:hover {
            color: var(--text-secondary);
            text-decoration: underline;
        }

        .notification-empty {
            padding: 32px 16px;
            text-align: center;
            color: var(--text-muted);
            font-size: 13px;
        }

        .notification-loading {
            padding: 16px;
            text-align: center;
            color: var(--text-muted);
            font-size: 13px;
        }
    </style>
</head>
<body>

<div class="app-shell">

    {{-- ======================== SIDEBAR ======================== --}}
    <aside class="sidebar">
        <!-- Logo -->
        <div class="sidebar-logo">
            <div class="logo-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                    <rect x="2" y="3" width="9" height="9" rx="1.5" fill="white"/>
                    <rect x="13" y="3" width="9" height="9" rx="1.5" fill="white" opacity="0.6"/>
                    <rect x="2" y="14" width="9" height="9" rx="1.5" fill="white" opacity="0.6"/>
                    <rect x="13" y="14" width="9" height="9" rx="1.5" fill="white" opacity="0.3"/>
                </svg>
            </div>
            <span class="logo-text">sitoko</span>
        </div>

       <!-- Navigation -->
            <nav class="sidebar-nav">
                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7" rx="1"/>
                        <rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/>
                        <rect x="14" y="14" width="7" height="7" rx="1"/>
                    </svg>
                    Dashboard
                </a>

                <a href="{{ route('inventory.index') }}" class="nav-item {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                        <line x1="12" y1="22.08" x2="12" y2="12"/>
                    </svg>
                    Inventory
                </a>

                <a href="{{ route('transactions.index') }}" class="nav-item {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="5" width="20" height="14" rx="2"/>
                        <line x1="2" y1="10" x2="22" y2="10"/>
                    </svg>
                    Transactions
                </a>

                @if(auth()->user()?->isAdmin())

                <a href="{{ route('staff.index') }}" class="nav-item {{ request()->routeIs('staff.*') ? 'active' : '' }}">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    Staff
                </a>

                <a href="{{ route('reports.index') }}" class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"/>
                        <line x1="12" y1="20" x2="12" y2="4"/>
                        <line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                    Reports
                </a>

                <a href="{{ route('suppliers.index') }}" class="nav-item {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    Suppliers
                </a>

                <a href="{{ route('purchase-orders.index') }}" class="nav-item {{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                    Purchase Orders
                </a>

                <a href="{{ route('supplier-returns.index') }}" class="nav-item {{ request()->routeIs('supplier-returns.*') ? 'active' : '' }}">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="1 4 1 10 7 10"/>
                        <path d="M3.51 15a9 9 0 1 0 .49-3.51"/>
                    </svg>
                    Retur Supplier
                </a>

                <a href="{{ route('settings.index') }}" class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/>
                        <path d="M12 2v2M12 20v2M2 12h2M20 12h2"/>
                    </svg>
                    Settings
                </a>

                @endif

                <a href="{{ route('transactions.create') }}" class="btn-new-transaction" style="margin-top:20px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    New Transaction
                </a>

            </nav>

        <!-- Bottom actions -->
        <div class="sidebar-bottom">
            <div class="sidebar-footer-links">
                <form method="POST" action="{{ route('logout') }}" class="logout-form">
                    @csrf
                    <button type="submit" class="footer-link footer-link--btn" style="color: var(--red-500);">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ======================== MAIN CONTENT ======================== --}}
    <main class="main-content">

        <!-- Page Header -->
        <header class="page-header">
            <div class="page-header-left">
                <h1 class="page-title">@yield('page-title')</h1>
                <p class="page-subtitle">@yield('page-subtitle')</p>
            </div>
            <div class="page-header-right">
                @yield('header-actions')

                @if(auth()->user()?->isAdmin())
                <!-- Notification Bell -->
                <div class="notification-dropdown-wrapper" x-data="notificationDropdown()" x-init="init()">
                    <button class="btn-icon" @click="toggleDropdown()" title="Notifications">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>
                        <span x-show="unreadCount > 0" class="notification-badge" x-text="unreadCount" x-cloak></span>
                    </button>

                    <div class="notification-dropdown" x-show="isOpen" @click.outside="isOpen = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform scale-95"
                         x-transition:enter-end="opacity-100 transform scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 transform scale-100"
                         x-transition:leave-end="opacity-0 transform scale-95"
                         style="display: none;"
                         x-cloak>
                        <div class="notification-header">
                            <span class="notification-header-title">Notifikasi Baru</span>
                            <button x-show="unreadCount > 0" @click="markAllAsRead()" class="btn-mark-all-read">
                                Tandai semua dibaca
                            </button>
                        </div>

                        <div class="notification-list">
                            <div x-show="isLoading" class="notification-loading">Memuat...</div>
                            <div x-show="!isLoading && notifications.length === 0" class="notification-empty">
                                Tidak ada notifikasi baru.
                            </div>
                            <template x-for="item in notifications" :key="item.id">
                                <div class="notification-item">
                                    <div class="notification-item-header">
                                        <span class="notification-item-title" x-text="'Transaksi Baru: ' + item.data.code"></span>
                                        <span class="notification-item-time" x-text="item.created_at"></span>
                                    </div>
                                    <div class="notification-item-body">
                                        Total: <strong style="color: var(--blue-600);">Rp <span x-text="formatNumber(item.data.total)"></span></strong> oleh <span x-text="item.data.cashier_name"></span>
                                    </div>
                                    <div class="notification-item-actions">
                                        <a :href="'/transactions/' + item.data.transaction_id" class="notification-action-link">Detail</a>
                                        <button @click="markAsRead(item.id)" class="btn-mark-single-read">Tandai dibaca</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                @endif

                <!-- User Avatar -->
                <!-- User Avatar -->
                @if(auth()->user()?->isAdmin())
                    <a href="{{ route('profile.edit') }}" style="text-decoration:none">
                        @if(auth()->user()->photo)
                            <img src="{{ Storage::url(auth()->user()->photo) }}"
                                style="width:36px; height:36px; border-radius:50%; object-fit:cover; border:2px solid var(--border)"
                                title="{{ auth()->user()->name }}">
                        @else
                            <div class="user-avatar" title="{{ auth()->user()->name ?? 'Admin' }}">
                                {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                            </div>
                        @endif
                    </a>
                @else
                    <div style="display:flex; align-items:center; gap:8px">
                        <span style="font-size:13px; font-weight:600; color:var(--text-secondary)">
                            Halo, {{ auth()->user()->name }}
                        </span>
                        @if(auth()->user()->photo)
                            <img src="{{ Storage::url(auth()->user()->photo) }}"
                                style="width:32px; height:32px; border-radius:50%; object-fit:cover; border:2px solid var(--border)"
                                title="{{ auth()->user()->name }}">
                        @else
                            <div class="avatar avatar--green" style="width:32px; height:32px; font-size:11px">
                                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </header>

        <!-- Page Body -->
        <div class="page-body">

            @if(session('error'))
                <div class="alert alert--error">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </div>
    </main>

</div>

@if(auth()->user()?->isAdmin())
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('notificationDropdown', () => ({
            isOpen: false,
            isLoading: false,
            unreadCount: 0,
            notifications: [],
            init() {
                this.fetchNotifications();
                setInterval(() => {
                    this.fetchNotifications();
                }, 5000);
            },
            toggleDropdown() {
                this.isOpen = !this.isOpen;
                if (this.isOpen) {
                    this.fetchNotifications();
                }
            },
            fetchNotifications() {
                fetch('{{ route("notifications.index") }}')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.notifications = data.notifications;
                            this.unreadCount = data.unread_count;
                        }
                    })
                    .catch(err => console.error('Gagal mengambil notifikasi:', err));
            },
            markAsRead(id) {
                fetch(`/api/notifications/${id}/read`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.notifications = this.notifications.filter(n => n.id !== id);
                        this.unreadCount = Math.max(0, this.unreadCount - 1);
                    }
                })
                .catch(err => console.error('Gagal menandai dibaca:', err));
            },
            markAllAsRead() {
                fetch('{{ route("notifications.read-all") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.notifications = [];
                        this.unreadCount = 0;
                    }
                })
                .catch(err => console.error('Gagal menandai semua dibaca:', err));
            },
            formatNumber(num) {
                return new Intl.NumberFormat('id-ID').format(num);
            }
        }));
    });
</script>
@endif

@stack('scripts')
</body>
</html>