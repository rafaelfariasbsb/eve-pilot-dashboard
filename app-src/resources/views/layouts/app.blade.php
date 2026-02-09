<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - @yield('title', 'Home')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0d1117; color: #c9d1d9; }
        .navbar { background-color: #161b22 !important; border-bottom: 1px solid #30363d; }
        .card { background-color: #161b22; border: 1px solid #30363d; color: #c9d1d9; }
        .table { color: #c9d1d9; }
        .table-dark { background-color: #161b22; }
        .btn-eve { background-color: #2ea6ff; color: #fff; border: none; }
        .btn-eve:hover { background-color: #1a8cd8; color: #fff; }
        .text-isk { color: #ffd700; }
        .text-eve-green { color: #3fb950; }
        .text-eve-red { color: #f85149; }
        .portrait { border-radius: 8px; border: 2px solid #30363d; }
        a { color: #58a6ff; }
        .badge-bpo { background-color: #1f6feb; }
        .badge-bpc { background-color: #8b949e; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">
                <strong>EVE Pilot Dashboard</strong>
            </a>
            @auth
            <div class="navbar-nav me-auto">
                <a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a>
                <a class="nav-link" href="{{ route('blueprints.index') }}">Blueprints</a>
            </div>
            <div class="d-flex align-items-center">
                @if(auth()->user()->mainCharacter())
                    <img src="{{ auth()->user()->mainCharacter()->portrait_url }}" width="32" height="32" class="portrait me-2">
                    <span class="text-light me-3">{{ auth()->user()->mainCharacter()->name }}</span>
                @endif
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Logout</button>
                </form>
            </div>
            @endauth
        </div>
    </nav>

    <main class="container py-4">
        @if(session('status'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="text-center py-3 text-secondary">
        <small>EVE Pilot Dashboard - Pilot Project | ESI API + Laravel 12</small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
