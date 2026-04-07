<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistema de Inventario')</title>

    <!-- Google Font removed to avoid external timeouts; use system font stack -->
    <style>
        html, body, .content-wrapper, .brand-text {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', 'Liberation Sans', sans-serif;
        }
    </style>
    <style>
        /* Ensure consistent icon sizing when third-party CSS is missing or overridden */
        .nav-icon {
            font-size: 1.05rem;
            width: 1.25rem;
            text-align: center;
            line-height: 1;
        }
        .brand-image { font-size: 1.2rem; }
        .nav-svg { width: 18px; height: 18px; vertical-align: middle; margin-right: 8px; }
        .brand-svg { width: 20px; height: 20px; vertical-align: middle; }
    </style>
    <!-- Font Awesome (local) -->
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

    <style>
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .stock-input {
            width: 80px;
            text-align: center;
        }
        .saving-indicator {
            display: none;
            font-size: 12px;
            color: #28a745;
        }
        @media (max-width: 768px) {
            .content-wrapper {
                padding: 10px;
            }
            .card-body {
                padding: 10px;
            }
            table {
                font-size: 0.85rem;
            }
            .stock-input {
                width: 60px;
            }
        }
    </style>

    @yield('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="Toggle sidebar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M3 6h18v2H3V6zm0 5h18v2H3v-2zm0 5h18v2H3v-2z"/>
                    </svg>
                </a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center" href="{{ route('logout') }}"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="mr-1">
                        <path d="M16 13v-2H7V8l-5 4 5 4v-3zM20 3h-8v2h8v14h-8v2h8a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z"/>
                    </svg>
                    Salir
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </li>
        </ul>
    </nav>

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="{{ route('products.index') }}" class="brand-link d-flex align-items-center">
            <svg class="brand-svg mr-2" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M21 16V8a1 1 0 0 0-.55-.89l-8-4a1 1 0 0 0-.9 0l-8 4A1 1 0 0 0 3 8v8a1 1 0 0 0 .55.89l8 4a1 1 0 0 0 .9 0l8-4A1 1 0 0 0 21 16zM12 3.27L18.74 7 12 10.73 5.26 7 12 3.27zM5 9.2l6 3v7.6L5 16.8V9.2zm14 7.6l-6 3V12.2l6-3v7.6z"/>
            </svg>
            <span class="brand-text font-weight-light">Inventario Sekai</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user panel -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
                <div class="image mr-2">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v2h20v-2c0-3.3-6.7-5-10-5z"/>
                    </svg>
                </div>
                <div class="info">
                    <a href="#" class="d-block">{{ Auth::user()->name }}</a>
                </div>
            </div>

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                    <li class="nav-item">
                        <a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.index') ? 'active' : '' }}">
                            <svg class="nav-svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M21 16V8a1 1 0 0 0-.55-.89l-8-4a1 1 0 0 0-.9 0l-8 4A1 1 0 0 0 3 8v8a1 1 0 0 0 .55.89l8 4a1 1 0 0 0 .9 0l8-4A1 1 0 0 0 21 16zM12 3.27L18.74 7 12 10.73 5.26 7 12 3.27zM5 9.2l6 3v7.6L5 16.8V9.2zm14 7.6l-6 3V12.2l6-3v7.6z"/>
                            </svg>
                            <p>Inventario</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('patrimonio.index') }}" class="nav-link {{ request()->routeIs('patrimonio.*') ? 'active' : '' }}">
                            <svg class="nav-svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M3 22h18V2H3v20zm2-2v-8h14v8H5zm0-10V4h14v6H5z"/>
                            </svg>
                            <p>Patrimonio</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('discounts.index') }}" class="nav-link {{ request()->routeIs('discounts.*') ? 'active' : '' }}">
                            <svg class="nav-svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M21.41 11.58l-9-9A2 2 0 0 0 10.17 2H5a2 2 0 0 0-2 2v5.17c0 .53.21 1.04.59 1.41l9 9c.37.38.88.59 1.41.59s1.04-.21 1.41-.59l4.59-4.59a2 2 0 0 0 0-2.83zM7 7a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                            </svg>
                            <p>Descuentos</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <!-- Content Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">@yield('page-title', 'Dashboard')</h1>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show">
                        {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show">
                        <strong>Errores de validación:</strong>
                        <ul class="mb-0">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                @endif

                @yield('content')
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <strong>Copyright &copy; 2026 Sistema de Inventario Sekai.</strong>
        Todos los derechos reservados.
    </footer>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<!-- Anime.js -->
<script src="https://cdn.jsdelivr.net/npm/animejs@3.2.1/lib/anime.min.js"></script>

@yield('scripts')

</body>
</html>
