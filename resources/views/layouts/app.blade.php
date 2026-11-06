<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff">
    <title>@yield('title', 'Dashboard') - SCP</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/scp.css') }}?v={{ @filemtime(public_path('css/scp.css')) }}">
    @stack('styles')
</head>
<body>
    @php
        $u = auth()->user();
        $role = $u->role_id ?? null;
    @endphp

    <nav class="scp-topbar py-2 px-3 d-flex align-items-center justify-content-between">
        <span class="scp-brand d-inline-flex align-items-center gap-2"><img src="{{ asset('img/logo-smkn1bantul.png') }}" alt="Logo SMK Negeri 1 Bantul" class="scp-logo">School Class Progress</span>
        <div class="d-flex align-items-center gap-2">
            <span class="scp-role-pill d-none d-sm-inline">{{ $u?->roleLabel() }}</span>
            <a href="{{ route('profile.edit') }}" class="scp-user small fw-medium">{{ $u?->fullname }}</a>
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
                    <div class="alert alert-success py-2" role="alert">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger py-2" role="alert">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger py-2" role="alert">
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
