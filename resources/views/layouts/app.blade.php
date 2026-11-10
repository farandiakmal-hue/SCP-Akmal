<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff">
    <title>@yield('title', 'Dashboard') - SCP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
                :root {
            --scp-bg: #f4f5f7;
            --scp-surface: #ffffff;
            --scp-border: #e3e5e9;
            --scp-text: #24262b;
            --scp-muted: #767a82;
            --scp-accent: #4b5563;
            --scp-accent-dark: #33363d;
            --scp-soft: #eef0f3;
            --scp-radius: .7rem;
            --scp-shadow: 0 1px 2px rgba(0,0,0,.04), 0 4px 14px rgba(0,0,0,.05);
        }
        * { font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif; }
        body {
            background: var(--scp-bg);
            color: var(--scp-text);
            padding-bottom: 1.5rem;
            -webkit-font-smoothing: antialiased;
        }
        a { text-decoration: none; }
        h5, .h5 { font-weight: 700; letter-spacing: -.01em; }

        /* ===== Topbar ===== */
        .scp-topbar {
            background: var(--scp-surface);
            border-bottom: 1px solid var(--scp-border);
            position: sticky; top: 0; z-index: 20;
            box-shadow: 0 1px 6px rgba(0,0,0,.05);
        }
        .scp-brand { font-weight: 700; color: var(--scp-text); font-size: 1.05rem; letter-spacing: -.01em; }
        .scp-brand:hover { color: var(--scp-text); }
        .scp-role-pill {
            background: var(--scp-soft);
            border: 1px solid var(--scp-border);
            color: var(--scp-text);
            font-size: .72rem;
            font-weight: 600;
            padding: .22rem .65rem;
            border-radius: 999px;
        }

        /* ===== Sidebar ===== */
        .scp-sidebar {
            background: var(--scp-surface);
            border-right: 1px solid var(--scp-border);
            min-height: calc(100vh - 60px);
        }
        .scp-nav-link {
            color: var(--scp-muted);
            padding: .6rem .9rem;
            border-radius: .55rem;
            display: flex; align-items: center; gap: .7rem;
            font-size: .92rem; font-weight: 500;
            margin-bottom: .2rem;
            transition: background .15s, color .15s;
        }
        .scp-nav-link .bi { font-size: 1.05rem; }
        .scp-nav-link:hover { background: var(--scp-bg); color: var(--scp-text); }
        .scp-nav-link.active {
            background: var(--scp-soft);
            color: var(--scp-text);
            font-weight: 600;
            box-shadow: inset 3px 0 0 var(--scp-accent-dark);
        }

        /* ===== Bottom navigation (hanya tampil di HP) ===== */
        .scp-bottomnav { display: none; }

        /* ===== Kartu ===== */
        .scp-card {
            background: var(--scp-surface);
            border: 1px solid var(--scp-border);
            border-radius: var(--scp-radius);
            box-shadow: var(--scp-shadow);
            overflow: hidden;
        }
        .scp-card-header {
            background: #fafbfc;
            border-bottom: 1px solid var(--scp-border);
            padding: .9rem 1.15rem;
            font-weight: 700;
            font-size: .95rem;
        }

        /* ===== Tombol ===== */
        .btn { border-radius: .5rem; transition: background .15s, box-shadow .15s, transform .05s; }
        .btn:active { transform: translateY(1px); }
        .btn-scp {
            background: var(--scp-accent-dark);
            color: #fff;
            border: none;
            box-shadow: 0 1px 2px rgba(0,0,0,.2);
        }
        .btn-scp:hover { background: #24262b; color: #fff; }

        /* ===== Form ===== */
        .form-control, .form-select { border-radius: .5rem; border-color: #d5d8de; }
        .form-control:focus, .form-select:focus {
            border-color: var(--scp-accent-dark);
            box-shadow: 0 0 0 .2rem rgba(51,54,61,.12);
        }

        /* ===== Tabel ===== */
        .table { margin-bottom: 0; }
        .table > :not(caption) > * > * { padding: .8rem 1rem; border-color: #eceef1; }
        .table thead th {
            background: #fafbfc;
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--scp-text);
            border-bottom: 1px solid var(--scp-border);
            font-weight: 700;
            white-space: nowrap;
        }
        .table tbody tr { transition: background .12s; }
        .table tbody tr:hover { --bs-table-bg: #f7f8fa; }

        /* ===== Badge ===== */
        .badge { border-radius: 999px; }
        .badge-soft {
            background: var(--scp-soft);
            color: var(--scp-text);
            border: 1px solid #d5d8de;
            font-weight: 600;
            border-radius: 999px;
        }

        /* ===== Alert ===== */
        .alert { border-radius: .6rem; }

        /* ===== Tab (Manajemen Data) ===== */
        .nav-tabs { border-bottom: 1px solid var(--scp-border); }
        .nav-tabs .nav-link { color: var(--scp-muted); border: 0; border-bottom: 3px solid transparent; font-weight: 600; padding: .6rem 1rem; }
        .nav-tabs .nav-link:hover { color: var(--scp-text); border-bottom-color: #cfd3d9; }
        .nav-tabs .nav-link.active { color: var(--scp-text); background: transparent; border-bottom-color: var(--scp-accent-dark); }

        /* ===== Pagination ===== */
        .pagination {
            --bs-pagination-color: var(--scp-accent-dark);
            --bs-pagination-hover-color: var(--scp-text);
            --bs-pagination-active-bg: var(--scp-accent-dark);
            --bs-pagination-active-border-color: var(--scp-accent-dark);
            --bs-pagination-focus-box-shadow: 0 0 0 .2rem rgba(51,54,61,.12);
        }

        /* ===== Mobile-first: hanya berlaku < 768px, desktop tidak berubah ===== */
        @media (max-width: 767.98px) {
            body { padding-bottom: calc(68px + env(safe-area-inset-bottom)); }

            .scp-bottomnav {
                display: flex;
                position: fixed; bottom: 0; left: 0; right: 0; z-index: 30;
                background: var(--scp-surface);
                border-top: 1px solid var(--scp-border);
                box-shadow: 0 -2px 10px rgba(0,0,0,.05);
                padding-bottom: env(safe-area-inset-bottom);
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
            }
            .scp-bottomnav::-webkit-scrollbar { display: none; }
            .scp-bottomnav a {
                flex: 1 0 auto; min-width: 66px;
                text-align: center;
                padding: .5rem .4rem .45rem;
                color: var(--scp-muted);
                font-size: .68rem; font-weight: 500;
                border-top: 3px solid transparent;
            }
            .scp-bottomnav a .bi { display: block; font-size: 1.25rem; margin-bottom: .1rem; }
            .scp-bottomnav a.active {
                color: var(--scp-text); font-weight: 700;
                border-top-color: var(--scp-accent-dark);
                background: var(--scp-bg);
            }

            /* Topbar ringkas */
            .scp-topbar { padding-left: .85rem !important; padding-right: .85rem !important; }
            .scp-brand { font-size: .95rem; }
            .scp-topbar a.fw-medium {
                max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
            }

            main { padding: .85rem !important; min-width: 0; }
            h5, .h5 { font-size: 1.05rem; }

            .scp-card-header {
                padding: .7rem .9rem;
                display: flex; flex-wrap: wrap; align-items: center; gap: .4rem;
            }

            /* Target sentuh >= 44px & font 16px agar iOS tidak auto-zoom saat fokus */
            .form-control, .form-control-sm,
            .form-select, .form-select-sm {
                font-size: 16px; min-height: 44px;
            }
            textarea.form-control { min-height: 96px; }
            .btn, .btn-sm { min-height: 44px; padding-left: 1rem; padding-right: 1rem; }
            .form-label { font-size: .85rem !important; margin-bottom: .25rem; }

            /* Tabel lebih padat, tetap bisa digeser horizontal */
            .table > :not(caption) > * > * { padding: .6rem .65rem; font-size: .85rem; }
            .table-responsive { -webkit-overflow-scrolling: touch; }
            .table th[style*="width"] { width: auto !important; min-width: 130px; }
            td .btn, td form { white-space: nowrap; }
            td .btn-sm { min-height: 36px; padding: .25rem .65rem; }

            .alert { font-size: .88rem; }
            .badge, .badge-soft { white-space: normal; }
        }
    </style>
    @stack('styles')
</head>
<body>
    @php
        $u = auth()->user();
        $role = $u->role_id ?? null;
    @endphp

    <nav class="scp-topbar py-2 px-3 d-flex align-items-center justify-content-between">
        <a href="{{ route('dashboard') }}" class="scp-brand d-flex align-items-center gap-2">
    <img src="{{ asset('img/logo-smk.png') }}" alt="Logo SMK Negeri 1 Bantul"
         style="width:44px; height:44px; object-fit:contain;">
    <span>School Class Progress</span>
</a>
        <div class="d-flex align-items-center gap-2">
            <span class="scp-role-pill d-none d-sm-inline">{{ $u?->roleLabel() }}</span>
            <a href="{{ route('profile.edit') }}" class="text-decoration-none text-dark small fw-medium">{{ $u?->fullname }}</a>
            <form action="{{ route('logout') }}" method="POST" class="m-0">
                @csrf
                <button class="btn btn-sm btn-outline-secondary" type="submit">Keluar</button>
            </form>
        </div>
    </nav>

    <div class="d-flex">
        <aside class="scp-sidebar p-3 d-none d-md-block" style="width:230px;">
            @include('layouts.nav', ['variant' => 'sidebar'])
        </aside>

        <main class="flex-grow-1 p-3 p-md-4">
            <div class="container-fluid px-0" style="max-width: 1100px;">
                @if(session('success'))
                    <div class="alert alert-success py-2">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger py-2">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger py-2">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <nav class="scp-bottomnav" aria-label="Navigasi utama">
        @include('layouts.nav', ['variant' => 'bottom'])
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
