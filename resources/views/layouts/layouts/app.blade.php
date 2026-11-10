<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - SCP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --scp-bg: #f4f5f7;
            --scp-surface: #ffffff;
            --scp-border: #e3e5e9;
            --scp-text: #24262b;
            --scp-muted: #767a82;
            --scp-accent: #4b5563;
            --scp-accent-dark: #33363d;
        }
        * { font-family: 'Inter', -apple-system, sans-serif; }
        body {
            background: var(--scp-bg);
            color: var(--scp-text);
            padding-bottom: 76px;
        }
        a { text-decoration: none; }

        .scp-topbar {
            background: var(--scp-surface);
            border-bottom: 1px solid var(--scp-border);
            position: sticky; top: 0; z-index: 20;
        }
        .scp-brand { font-weight: 700; color: var(--scp-text); font-size: 1.05rem; }
        .scp-role-pill {
            background: var(--scp-bg);
            border: 1px solid var(--scp-border);
            color: var(--scp-muted);
            font-size: .72rem;
            padding: .2rem .6rem;
            border-radius: 999px;
        }

        .scp-sidebar {
            background: var(--scp-surface);
            border-right: 1px solid var(--scp-border);
            min-height: calc(100vh - 60px);
        }
        .scp-nav-link {
            color: var(--scp-muted);
            padding: .6rem .9rem;
            border-radius: .5rem;
            display: flex; align-items: center; gap: .6rem;
            font-size: .92rem; font-weight: 500;
            margin-bottom: .15rem;
        }
        .scp-nav-link:hover { background: var(--scp-bg); color: var(--scp-text); }
        .scp-nav-link.active { background: var(--scp-accent); color: #fff; }

        .scp-bottomnav {
            display: none;
            position: fixed; bottom: 0; left: 0; right: 0;
            background: var(--scp-surface);
            border-top: 1px solid var(--scp-border);
            z-index: 30;
        }
        .scp-bottomnav a {
            flex: 1; text-align: center; padding: .55rem 0 .5rem;
            color: var(--scp-muted); font-size: .68rem; font-weight: 500;
        }
        .scp-bottomnav a.active { color: var(--scp-accent-dark); }
        .scp-bottomnav .bi { font-size: 1.15rem; display: block; margin-bottom: .1rem; }

        @media (max-width: 767.98px) {
            .scp-sidebar { display: none; }
            .scp-bottomnav { display: flex; }
            body { padding-bottom: 70px; }
        }

        .scp-card {
            background: var(--scp-surface);
            border: 1px solid var(--scp-border);
            border-radius: .75rem;
        }
        .scp-card-header {
            border-bottom: 1px solid var(--scp-border);
            padding: .9rem 1.1rem;
            font-weight: 600;
        }
        .btn-scp {
            background: var(--scp-accent-dark);
            color: #fff;
            border: none;
        }
        .btn-scp:hover { background: #24262b; color: #fff; }
        .table thead th {
            font-size: .74rem; text-transform: uppercase; letter-spacing: .03em;
            color: var(--scp-muted); border-bottom: 1px solid var(--scp-border);
            font-weight: 600;
        }
        .badge-soft { background: var(--scp-bg); color: var(--scp-text); border: 1px solid var(--scp-border); font-weight: 500; }
    </style>
    @stack('styles')
</head>
<body>
    @php
        $u = auth()->user();
        $role = $u->role_id ?? null;
    @endphp

    <nav class="scp-topbar py-2 px-3 d-flex align-items-center justify-content-between">
        <span class="scp-brand">School Class Progress</span>
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

    <nav class="scp-bottomnav d-flex">
        @include('layouts.nav', ['variant' => 'bottom'])
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
