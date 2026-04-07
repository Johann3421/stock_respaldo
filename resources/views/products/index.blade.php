@extends('layouts.admin')

@section('title', 'Inventario - Sistema de Inventario')
@section('page-title', 'Gestión de Inventario')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Lista de Productos</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#createModal">
                        <i class="fas fa-plus"></i> Agregar Producto
                    </button>
                    <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#importModal">
                        <i class="fas fa-file-import"></i> Importar Excel
                    </button>
                    <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#updateNamesModal">
                        <i class="fas fa-edit"></i> Actualizar Nombres
                    </button>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-file-export"></i> Exportar Excel
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="{{ route('products.export') }}">
                                <i class="fas fa-file-excel text-success"></i> Exportar Todo
                            </a>
                            <a class="dropdown-item" href="{{ route('products.export-verified') }}">
                                <i class="fas fa-check-circle text-primary"></i> Solo Verificados (excl. Pendientes)
                            </a>
                        </div>
                    </div>
                    <div class="btn-group btn-group-sm ml-1">
                        <button type="button" class="btn btn-secondary" onclick="exportCurrentPage()" title="Exportar nombres de esta página">
                            <i class="fas fa-download"></i> Exportar Página
                        </button>
                        <button type="button" class="btn btn-dark" data-toggle="modal" data-target="#importPageModal" title="Importar nombres de esta página">
                            <i class="fas fa-upload"></i> Importar Página
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <!-- Barra de búsqueda y filtros -->
                <form method="GET" action="{{ route('products.index') }}" id="filterForm">
                    <div class="row mb-3">
                        <div class="col-md-6 col-12 mb-2 mb-md-0">
                            <div class="input-group">
                                <input type="text" name="search" id="searchInput" class="form-control"
                                       placeholder="Buscar por código, producto o marca..."
                                       value="{{ request('search') }}">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i><span class="d-none d-md-inline ml-1">Buscar</span>
                                    </button>
                                    @if(request('search') || request('color'))
                                        <a href="{{ route('products.index') }}" class="btn btn-secondary">
                                            <i class="fas fa-times"></i><span class="d-none d-md-inline ml-1">Limpiar</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-12 mt-2 mt-md-0">
                            <div class="btn-group-wrapper d-flex flex-wrap gap-1" style="gap: 0.25rem;">
                                <button type="button" class="btn btn-sm {{ !request('color') ? 'btn-dark' : 'btn-outline-dark' }} mb-1"
                                        onclick="filterByColor('')">
                                    <i class="fas fa-list d-md-none"></i> <span class="d-none d-md-inline">Todos</span> <span class="badge badge-light ml-1">{{ $totalProducts ?? 0 }}</span>
                                </button>
                                <button type="button" class="btn btn-sm {{ request('color') == 'discrepancy' ? 'btn-info' : 'btn-outline-info' }} mb-1"
                                        onclick="filterByColor('discrepancy')" title="Verificación 1 diferente de Verificación 2">
                                    <i class="fas fa-not-equal"></i> <span class="d-none d-md-inline">Discrepancia</span><span class="d-md-none">Disc.</span> <span class="badge badge-light ml-1">{{ $colorCounts->discrepancy ?? 0 }}</span>
                                </button>
                                <button type="button" class="btn btn-sm {{ request('color') == 'danger' ? 'btn-danger' : 'btn-outline-danger' }} mb-1"
                                        onclick="filterByColor('danger')" title="V1 = V2, pero (V2 + V3) < Stock">
                                    <i class="fas fa-exclamation-triangle"></i> <span class="d-none d-md-inline">Inferiores</span><span class="d-md-none">Inf.</span> <span class="badge badge-light ml-1">{{ $colorCounts->danger ?? 0 }}</span>
                                </button>
                                <button type="button" class="btn btn-sm {{ request('color') == 'success' ? 'btn-success' : 'btn-outline-success' }} mb-1"
                                        onclick="filterByColor('success')" title="V1 = V2 y (V2 + V3) = Stock">
                                    <i class="fas fa-check-circle"></i> <span class="d-none d-md-inline">Completos</span><span class="d-md-none">Comp.</span> <span class="badge badge-light ml-1">{{ $colorCounts->success ?? 0 }}</span>
                                </button>
                                <button type="button" class="btn btn-sm {{ request('color') == 'warning' ? 'btn-warning' : 'btn-outline-warning' }} mb-1"
                                        onclick="filterByColor('warning')" title="V1 = V2, pero (V2 + V3) > Stock">
                                    <i class="fas fa-arrow-up"></i> <span class="d-none d-md-inline">Superiores</span><span class="d-md-none">Sup.</span> <span class="badge badge-light ml-1">{{ $colorCounts->warning ?? 0 }}</span>
                                </button>
                                <button type="button" class="btn btn-sm {{ request('color') == 'unverified' ? 'btn-secondary' : 'btn-outline-secondary' }} mb-1"
                                        onclick="filterByColor('unverified')" title="Sin ninguna verificación">
                                    <i class="fas fa-question-circle"></i> <span class="d-none d-md-inline">Pendientes</span><span class="d-md-none">Pend.</span> <span class="badge badge-light ml-1">{{ $colorCounts->unverified ?? 0 }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="color" id="colorFilter" value="{{ request('color') }}">
                </form>

                <!-- Vista Desktop (Tabla) -->
                <div class="d-none d-lg-block">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm">
                            <thead class="thead-dark">
                                <tr>
                                    <th rowspan="2">Código</th>
                                    <th rowspan="2">Producto</th>
                                    <th rowspan="2">Marca</th>
                                    <th rowspan="2">Costo</th>
                                    <th rowspan="2">P. Cliente</th>
                                    <th rowspan="2">Stock</th>
                                    <th colspan="3" class="text-center bg-primary text-white">Verificación 1</th>
                                    <th colspan="3" class="text-center bg-info text-white">Verificación 2</th>
                                    <th colspan="3" class="text-center bg-success text-white">Verificación Tienda</th>
                                    <th rowspan="2">Acciones</th>
                                </tr>
                                <tr>
                                    <th class="bg-primary text-white">Stock</th>
                                    <th class="bg-primary text-white">Por</th>
                                    <th class="bg-primary text-white">Fecha</th>
                                    <th class="bg-info text-white">Stock</th>
                                    <th class="bg-info text-white">Por</th>
                                    <th class="bg-info text-white">Fecha</th>
                                    <th class="bg-success text-white">Stock</th>
                                    <th class="bg-success text-white">Por</th>
                                    <th class="bg-success text-white">Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $product)
                                    <tr id="product-row-{{ $product->id }}" class="row-exportable" data-product-id="{{ $product->id }}">
                                        <td>
                                            <div>{{ $product->codigo }}</div>
                                            <div class="mt-1">
                                                @if($product->has_discrepancy)
                                                    <span class="badge badge-info badge-sm" title="V1 ≠ V2: Discrepancia en verificaciones">
                                                        <i class="fas fa-not-equal"></i> V1 ≠ V2
                                                    </span>
                                                @else
                                                    <span class="badge badge-{{ $product->stock_color_general }} badge-sm" title="V2 + V3 = {{ $product->total_verificado }}">
                                                        <i class="fas fa-calculator"></i> {{ $product->total_verificado }}
                                                    </span>
                                                @endif
                                                <small class="text-muted ml-1">(V1:{{ $product->stock_verificado ?? 0 }} | V2:{{ $product->stock_verificado_2 ?? 0 }} + V3:{{ $product->stock_verificado_3 ?? 0 }})</small>
                                            </div>
                                        </td>
                                        <td>{{ $product->producto }}</td>
                                        <td>{{ $product->marca }}</td>
                                        <td>S/ {{ number_format($product->costo, 2) }}</td>
                                        <td>S/ {{ number_format($product->precio_cliente, 2) }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-info">{{ $product->stock }}</span>
                                        </td>

                                        <!-- Verificación 1 -->
                                        <td class="text-center">
                                            <div class="input-group input-group-sm" style="width: 90px; margin: 0 auto;">
                                                <input type="number"
                                                       class="form-control stock-input"
                                                       data-product-id="{{ $product->id }}"
                                                       value="{{ $product->stock_verificado ?? 0 }}"
                                                       min="0"
                                                       placeholder="0">
                                                <div class="input-group-append">
                                                    <span class="input-group-text bg-{{ $product->stock_color }}" id="indicator-{{ $product->id }}">
                                                        <i class="fas fa-circle text-white"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center small" id="verified-by-{{ $product->id }}">
                                            {{ $product->verificado_por ?? '-' }}
                                        </td>
                                        <td class="text-center small" id="verified-at-{{ $product->id }}">
                                            {{ $product->ultima_verificacion ? $product->ultima_verificacion->format('d/m/Y H:i') : '-' }}
                                        </td>

                                        <!-- Verificación 2 -->
                                        <td class="text-center">
                                            <div class="input-group input-group-sm" style="width: 90px; margin: 0 auto;">
                                                <input type="number"
                                                       class="form-control stock-input-2"
                                                       data-product-id="{{ $product->id }}"
                                                       value="{{ $product->stock_verificado_2 ?? 0 }}"
                                                       min="0"
                                                       placeholder="0">
                                                <div class="input-group-append">
                                                    <span class="input-group-text bg-{{ $product->stock_color_2 }}" id="indicator-2-{{ $product->id }}">
                                                        <i class="fas fa-circle text-white"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center small" id="verified-by-2-{{ $product->id }}">
                                            {{ $product->verificado_por_2 ?? '-' }}
                                        </td>
                                        <td class="text-center small" id="verified-at-2-{{ $product->id }}">
                                            {{ $product->ultima_verificacion_2 ? $product->ultima_verificacion_2->format('d/m/Y H:i') : '-' }}
                                        </td>

                                        <!-- Verificación 3 (Tienda) -->
                                        <td class="text-center">
                                            <div class="input-group input-group-sm" style="width: 90px; margin: 0 auto;">
                                                <input type="number"
                                                       class="form-control stock-input-3"
                                                       data-product-id="{{ $product->id }}"
                                                       value="{{ $product->stock_verificado_3 ?? 0 }}"
                                                       min="0"
                                                       placeholder="0">
                                                <div class="input-group-append">
                                                    <span class="input-group-text bg-{{ $product->stock_color_3 }}" id="indicator-3-{{ $product->id }}">
                                                        <i class="fas fa-circle text-white"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center small" id="verified-by-3-{{ $product->id }}">
                                            {{ $product->verificado_por_3 ?? '-' }}
                                        </td>
                                        <td class="text-center small" id="verified-at-3-{{ $product->id }}">
                                            {{ $product->ultima_verificacion_3 ? $product->ultima_verificacion_3->format('d/m/Y H:i') : '-' }}
                                        </td>

                                        <td class="text-center text-nowrap">
                                                <button type="button" class="btn btn-sm btn-warning"
                                                    onclick='editProduct(@json($product->id), @json($product->codigo), @json($product->producto), @json($product->marca ?? ""), @json($product->costo), @json($product->precio_cliente), @json($product->stock))'
                                                    title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger"
                                                    onclick='deleteProduct(@json($product->id), @json($product->producto))'
                                                    title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="16" class="text-center">
                                            <p class="mt-3">No hay productos en el inventario. Importa un archivo Excel para comenzar.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Vista Móvil (Cards) -->
                <div class="d-lg-none">
                    @forelse($products as $product)
                        <div class="card mb-3 product-card-mobile" id="mobile-card-{{ $product->id }}">
                            <div class="card-header bg-light p-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1" style="min-width: 0;">
                                        <div class="mb-1"><strong class="text-truncate d-inline-block" style="max-width: 100%;">{{ $product->codigo }}</strong></div>
                                        <div class="d-flex flex-wrap" style="gap: 0.25rem;">
                                            <span class="badge badge-info">Stock: {{ $product->stock }}</span>
                                            @if($product->has_discrepancy)
                                                <span class="badge badge-info" title="V1 ≠ V2">
                                                    <i class="fas fa-not-equal"></i> Disc.
                                                </span>
                                            @else
                                                <span class="badge badge-{{ $product->stock_color_general }}" title="V2 + V3 = {{ $product->total_verificado }}">
                                                    V2+V3: {{ $product->total_verificado }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="d-flex flex-column ml-2" style="gap: 0.25rem;">
                                        <button type="button" class="btn btn-sm btn-warning"
                                            onclick='editProduct(@json($product->id), @json($product->codigo), @json($product->producto), @json($product->marca ?? ""), @json($product->costo), @json($product->precio_cliente), @json($product->stock))'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger"
                                                onclick='deleteProduct(@json($product->id), @json($product->producto))'>
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-2">
                                <h6 class="mb-2 text-break"><strong>{{ $product->producto }}</strong></h6>
                                <div class="row mb-2">
                                    <div class="col-6"><small><strong>Marca:</strong> {{ $product->marca }}</small></div>
                                    <div class="col-6"><small><strong>Costo:</strong> S/ {{ number_format($product->costo, 2) }}</small></div>
                                </div>
                                <div class="mb-2"><small><strong>P. Cliente:</strong> S/ {{ number_format($product->precio_cliente, 2) }}</small></div>

                                <!-- Accordion para verificaciones -->
                                <div class="accordion" id="accordion{{ $product->id }}">
                                    <!-- Verificación 1 -->
                                    <div class="card mb-1">
                                        <div class="card-header p-2 bg-primary text-white" id="heading1-{{ $product->id }}">
                                            <button class="btn btn-link text-white p-0 w-100 text-left" type="button" data-toggle="collapse" data-target="#collapse1-{{ $product->id }}">
                                                <i class="fas fa-chevron-down"></i> <strong>Verificación 1</strong>
                                                <span class="badge badge-light float-right">{{ $product->stock_verificado ?? 0 }}</span>
                                            </button>
                                        </div>
                                        <div id="collapse1-{{ $product->id }}" class="collapse" data-parent="#accordion{{ $product->id }}">
                                            <div class="card-body p-2">
                                                <div class="input-group input-group-sm mb-2">
                                                    <input type="number"
                                                           class="form-control stock-input"
                                                           data-product-id="{{ $product->id }}"
                                                           value="{{ $product->stock_verificado ?? 0 }}"
                                                           min="0"
                                                           placeholder="Stock verificado">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text bg-{{ $product->stock_color }}" id="mobile-indicator-{{ $product->id }}">
                                                            <i class="fas fa-circle text-white"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                                <small><strong>Por:</strong> <span id="mobile-verified-by-{{ $product->id }}">{{ $product->verificado_por ?? '-' }}</span></small><br>
                                                <small><strong>Fecha:</strong> <span id="mobile-verified-at-{{ $product->id }}">{{ $product->ultima_verificacion ? $product->ultima_verificacion->format('d/m/Y H:i') : '-' }}</span></small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Verificación 2 -->
                                    <div class="card mb-1">
                                        <div class="card-header p-2 bg-info text-white" id="heading2-{{ $product->id }}">
                                            <button class="btn btn-link text-white p-0 w-100 text-left" type="button" data-toggle="collapse" data-target="#collapse2-{{ $product->id }}">
                                                <i class="fas fa-chevron-down"></i> <strong>Verificación 2</strong>
                                                <span class="badge badge-light float-right">{{ $product->stock_verificado_2 ?? 0 }}</span>
                                            </button>
                                        </div>
                                        <div id="collapse2-{{ $product->id }}" class="collapse" data-parent="#accordion{{ $product->id }}">
                                            <div class="card-body p-2">
                                                <div class="input-group input-group-sm mb-2">
                                                    <input type="number"
                                                           class="form-control stock-input-2"
                                                           data-product-id="{{ $product->id }}"
                                                           value="{{ $product->stock_verificado_2 ?? 0 }}"
                                                           min="0"
                                                           placeholder="Stock verificado">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text bg-{{ $product->stock_color_2 }}" id="mobile-indicator-2-{{ $product->id }}">
                                                            <i class="fas fa-circle text-white"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                                <small><strong>Por:</strong> <span id="mobile-verified-by-2-{{ $product->id }}">{{ $product->verificado_por_2 ?? '-' }}</span></small><br>
                                                <small><strong>Fecha:</strong> <span id="mobile-verified-at-2-{{ $product->id }}">{{ $product->ultima_verificacion_2 ? $product->ultima_verificacion_2->format('d/m/Y H:i') : '-' }}</span></small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Verificación 3 (Tienda) -->
                                    <div class="card mb-1">
                                        <div class="card-header p-2 bg-success text-white" id="heading3-{{ $product->id }}">
                                            <button class="btn btn-link text-white p-0 w-100 text-left" type="button" data-toggle="collapse" data-target="#collapse3-{{ $product->id }}">
                                                <i class="fas fa-chevron-down"></i> <strong>Verificación Tienda</strong>
                                                <span class="badge badge-light float-right">{{ $product->stock_verificado_3 ?? 0 }}</span>
                                            </button>
                                        </div>
                                        <div id="collapse3-{{ $product->id }}" class="collapse" data-parent="#accordion{{ $product->id }}">
                                            <div class="card-body p-2">
                                                <div class="input-group input-group-sm mb-2">
                                                    <input type="number"
                                                           class="form-control stock-input-3"
                                                           data-product-id="{{ $product->id }}"
                                                           value="{{ $product->stock_verificado_3 ?? 0 }}"
                                                           min="0"
                                                           placeholder="Stock verificado">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text bg-{{ $product->stock_color_3 }}" id="mobile-indicator-3-{{ $product->id }}">
                                                            <i class="fas fa-circle text-white"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                                <small><strong>Por:</strong> <span id="mobile-verified-by-3-{{ $product->id }}">{{ $product->verificado_por_3 ?? '-' }}</span></small><br>
                                                <small><strong>Fecha:</strong> <span id="mobile-verified-at-3-{{ $product->id }}">{{ $product->ultima_verificacion_3 ? $product->ultima_verificacion_3->format('d/m/Y H:i') : '-' }}</span></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-info text-center">
                            <p class="mb-0">No hay productos en el inventario. Importa un archivo Excel para comenzar.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            @if($products->hasPages())
                <div class="card-footer clearfix">
                    {{ $products->appends(request()->query())->links('vendor.pagination.custom') }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Crear Producto -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white">Agregar Nuevo Producto</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('products.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Código <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="codigo" id="codigo_input" class="form-control" placeholder="Escanea o escribe el código" required>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" id="scannerBtn" title="Activar escáner de código de barras">
                                    <i class="fas fa-barcode"></i> Escanear
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted" id="scannerStatus" style="display: none;">
                            <i class="fas fa-circle text-success blink"></i> Escáner activo - escanea tu código de barras
                        </small>
                    </div>
                    <div class="form-group">
                        <label>Producto <span class="text-danger">*</span></label>
                        <input type="text" name="producto" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Marca</label>
                        <input type="text" name="marca" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Costo <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="costo" class="form-control" required min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>P. Cliente <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="precio_cliente" class="form-control" required min="0">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Stock <span class="text-danger">*</span></label>
                        <input type="number" name="stock" class="form-control" required min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Producto -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-white">Editar Producto</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label>Código <span class="text-danger">*</span></label>
                        <input type="text" name="codigo" id="edit_codigo" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Producto <span class="text-danger">*</span></label>
                        <input type="text" name="producto" id="edit_producto" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Marca</label>
                        <input type="text" name="marca" id="edit_marca" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Costo <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="costo" id="edit_costo" class="form-control" required min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>P. Cliente <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="precio_cliente" id="edit_precio_cliente" class="form-control" required min="0">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Stock <span class="text-danger">*</span></label>
                        <input type="number" name="stock" id="edit_stock" class="form-control" required min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Actualizar Producto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Importación -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title text-white">Importar Productos desde Excel</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('products.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Seleccionar archivo Excel</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" name="file" id="fileInput" required accept=".xlsx,.xls,.csv">
                            <label class="custom-file-label" for="fileInput">Elegir archivo...</label>
                        </div>
                        <small class="form-text text-muted">
                            El archivo debe contener las columnas: CODIGO, PRODUCTO, MARCA, COSTO, P. CLIENTE, STOCK
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info">Importar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Actualizar Nombres -->
<div class="modal fade" id="updateNamesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-white">
                    <i class="fas fa-edit"></i> Actualizar Nombres de Productos
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('products.update-names') }}" method="POST" enctype="multipart/form-data" id="updateNamesForm">
                @csrf
                <div class="modal-body">
                    <!-- Mensaje destacado para el archivo TXT -->
                    <div class="alert alert-success border-left-success shadow-sm">
                        <div class="d-flex align-items-center">
                            <div class="mr-3">
                                <i class="fas fa-file-alt fa-3x text-success"></i>
                            </div>
                            <div>
                                <h5 class="alert-heading mb-1"><i class="fas fa-check-circle"></i> ¡Archivo Listo para Usar!</h5>
                                <p class="mb-0">
                                    Puedes subir directamente tu archivo <strong>nombres_productos.txt</strong><br>
                                    El sistema detectará automáticamente el formato y actualizará los nombres.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <h6 class="alert-heading"><i class="fas fa-info-circle"></i> Información Importante</h6>
                        <ul class="mb-0">
                            <li>Este proceso <strong>solo actualiza los nombres</strong> de los productos</li>
                            <li>Los campos <strong>Stock, Costo y Precios NO se modificarán</strong></li>
                            <li>La actualización se basa en el <strong>código del producto</strong></li>
                            <li>Acepta archivos <strong>TXT (formato pipe), Excel (.xlsx, .xls) o CSV</strong></li>
                            <li><strong>Puedes subir directamente tu archivo "nombres_productos.txt"</strong></li>
                        </ul>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Seleccionar archivo</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" name="file" id="fileInputUpdateNames" required accept=".xlsx,.xls,.csv,.txt">
                            <label class="custom-file-label" for="fileInputUpdateNames">Elegir archivo...</label>
                        </div>
                        <small class="form-text text-muted">
                            <i class="fas fa-file-alt"></i> Acepta archivos <strong>TXT</strong> (formato pipe |), <strong>Excel</strong> (.xlsx, .xls) o <strong>CSV</strong>
                            <br>
                            <a href="{{ asset('PLANTILLA_NOMBRES.csv') }}" class="text-primary" download>
                                <i class="fas fa-download"></i> Descargar plantilla CSV de ejemplo
                            </a>
                        </small>
                    </div>

                    <div class="card bg-light mt-3">
                        <div class="card-body">
                            <h6 class="card-title"><i class="fas fa-file-alt text-info"></i> Formatos Aceptados:</h6>

                            <!-- Formato TXT -->
                            <div class="mb-3">
                                <h6 class="text-primary"><i class="fas fa-file-code"></i> Archivo TXT (nombres_productos.txt):</h6>
                                <div class="bg-dark text-light p-2 rounded" style="font-family: monospace; font-size: 0.85rem;">
                                    |3901|469|ACCESORIO DE ALMACENAMIENTO - BANDEJA HDD/SSD|2026-01-08 11:46:54<br>
                                    |3902|537|ACCESORIO DE AUDIO - TARJETA DE SONIDO|2026-01-08 11:29:15<br>
                                    <span class="text-muted">...</span>
                                </div>
                                <small class="text-muted">Formato: <strong>|ID|CODIGO|NOMBRE|FECHA|</strong></small>
                            </div>

                            <!-- Formato CSV/Excel -->
                            <div>
                                <h6 class="text-success"><i class="fas fa-file-excel"></i> Archivo Excel/CSV:</h6>
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>CODIGO</th>
                                            <th>NOMBRE</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>469</td>
                                            <td>ACCESORIO DE ALMACENAMIENTO - BANDEJA HDD/SSD</td>
                                        </tr>
                                        <tr>
                                            <td>537</td>
                                            <td>ACCESORIO DE AUDIO - TARJETA DE SONIDO EXTERNA</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">...</td>
                                            <td class="text-muted">...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-warning" id="btnUpdateNames">
                        <i class="fas fa-edit"></i> Actualizar Nombres
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Importar Página Actual -->
<div class="modal fade" id="importPageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h5 class="modal-title text-white">
                    <i class="fas fa-upload"></i> Importar Nombres - Página Actual
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Este proceso actualizará <strong>solo los nombres</strong> de los productos en esta página.
                </div>
                <div class="form-group">
                    <label>Seleccionar archivo CSV</label>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" id="fileInputPage" accept=".csv,.txt">
                        <label class="custom-file-label" for="fileInputPage">Elegir archivo CSV...</label>
                    </div>
                    <small class="form-text text-muted">
                        El archivo debe contener <strong>2 columnas</strong>: <strong>CODIGO</strong> y <strong>NOMBRE</strong><br>
                        <em>Use el botón "Exportar Página" para obtener el formato correcto.</em>
                    </small>
                </div>
                <div id="importResults" class="mt-3" style="display: none;">
                    <div class="alert alert-success">
                        <h6><i class="fas fa-check-circle"></i> Cambios detectados:</h6>
                        <div id="changesList"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-dark" onclick="importCurrentPage(event)">
                    <i class="fas fa-upload"></i> Importar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Form para eliminar (oculto) -->
<form id="deleteForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<!-- Modal de Escáner de Cámara -->
<div class="modal fade" id="cameraScannerModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">
                    <i class="fas fa-camera"></i> Escanear Código de Barras
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" id="closeCameraBtn">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body p-0 bg-dark position-relative">
                <div id="reader" style="width: 100%; height: calc(100vh - 56px);"></div>

                <!-- Marco Redimensionable para Área de Escaneo -->
                <div id="resizableFrame" class="resizable-scan-frame">
                    <div class="scan-corner corner-tl"></div>
                    <div class="scan-corner corner-tr"></div>
                    <div class="scan-corner corner-bl"></div>
                    <div class="scan-corner corner-br"></div>
                    <div class="scan-edge edge-t"></div>
                    <div class="scan-edge edge-r"></div>
                    <div class="scan-edge edge-b"></div>
                    <div class="scan-edge edge-l"></div>
                    <div class="scan-frame-label">Arrastra las esquinas para ajustar</div>
                </div>

                <div class="position-absolute bottom-0 start-0 end-0 text-white text-center p-3" style="pointer-events: none; background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);">
                    <p class="mb-0" style="text-shadow: 2px 2px 4px rgba(0,0,0,0.8);">Ajusta el marco con tus dedos y coloca el código dentro</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Librería para escaneo de códigos de barras con cámara -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
$(document).ready(function() {
    // Update file input label
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
    });

    // Validación y feedback para el formulario de actualizar nombres
    $('#updateNamesForm').on('submit', function(e) {
        const fileInput = $('#fileInputUpdateNames')[0];

        if (!fileInput.files.length) {
            e.preventDefault();
            alert('⚠️ Por favor selecciona un archivo');
            return false;
        }

        const file = fileInput.files[0];
        const validExtensions = ['xlsx', 'xls', 'csv', 'txt'];
        const fileExtension = file.name.split('.').pop().toLowerCase();

        if (!validExtensions.includes(fileExtension)) {
            e.preventDefault();
            alert('❌ Formato de archivo no válido\\n\\nPor favor selecciona un archivo TXT, Excel (.xlsx, .xls) o CSV');
            return false;
        }

        // Validar tamaño del archivo (máx 10MB)
        const maxSize = 10 * 1024 * 1024; // 10MB en bytes
        if (file.size > maxSize) {
            e.preventDefault();
            alert('❌ Archivo demasiado grande\\n\\nEl tamaño máximo permitido es 10MB');
            return false;
        }

        // Mostrar mensaje de procesamiento
        const $btn = $('#btnUpdateNames');
        $btn.prop('disabled', true);

        // Mensaje personalizado según el tipo de archivo
        if (fileExtension === 'txt') {
            $btn.html('<i class="fas fa-spinner fa-spin"></i> Procesando archivo TXT...');
        } else {
            $btn.html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
        }

        // No prevenir el submit, dejar que continúe
        return true;
    });

    // Auto-save stock verification 1 with debounce
    let typingTimer;
    const doneTypingInterval = 1000; // 1 segundo después de dejar de escribir

    $('.stock-input').on('input', function() {
        clearTimeout(typingTimer);
        const input = $(this);
        const productId = input.data('product-id');

        typingTimer = setTimeout(function() {
            // Si está vacío, poner 0 automáticamente
            let value = input.val();
            if (value === '' || value === null) {
                value = 0;
                input.val(0);
            }
            updateStock(productId, value);
        }, doneTypingInterval);
    });

    // Manejar cuando se borra el contenido (blur) - Verificación 1
    $('.stock-input').on('blur', function() {
        if ($(this).val() === '' || $(this).val() === null) {
            $(this).val(0);
        }
    });

    // Seleccionar todo el contenido cuando el valor es 0 - Verificación 1
    $('.stock-input').on('focus', function() {
        if ($(this).val() == '0') {
            $(this).select();
        }
    });

    // Auto-save stock verification 2 with debounce
    let typingTimer2;

    $('.stock-input-2').on('input', function() {
        clearTimeout(typingTimer2);
        const input = $(this);
        const productId = input.data('product-id');

        typingTimer2 = setTimeout(function() {
            // Si está vacío, poner 0 automáticamente
            let value = input.val();
            if (value === '' || value === null) {
                value = 0;
                input.val(0);
            }
            updateStock2(productId, value);
        }, doneTypingInterval);
    });

    // Manejar cuando se borra el contenido (blur) - Verificación 2
    $('.stock-input-2').on('blur', function() {
        if ($(this).val() === '' || $(this).val() === null) {
            $(this).val(0);
        }
    });

    // Seleccionar todo el contenido cuando el valor es 0 - Verificación 2
    $('.stock-input-2').on('focus', function() {
        if ($(this).val() == '0') {
            $(this).select();
        }
    });

    // Auto-save stock verification 3 (Tienda) with debounce
    let typingTimer3;

    $('.stock-input-3').on('input', function() {
        clearTimeout(typingTimer3);
        const input = $(this);
        const productId = input.data('product-id');

        typingTimer3 = setTimeout(function() {
            // Si está vacío, poner 0 automáticamente
            let value = input.val();
            if (value === '' || value === null) {
                value = 0;
                input.val(0);
            }
            updateStock3(productId, value);
        }, doneTypingInterval);
    });

    // Manejar cuando se borra el contenido (blur) - Verificación 3
    $('.stock-input-3').on('blur', function() {
        if ($(this).val() === '' || $(this).val() === null) {
            $(this).val(0);
        }
    });

    // Seleccionar todo el contenido cuando el valor es 0 - Verificación 3 (Tienda)
    $('.stock-input-3').on('focus', function() {
        if ($(this).val() == '0') {
            $(this).select();
        }
    });

    function updateStock(productId, stockVerificado) {
        $.ajax({
            url: '/products/' + productId + '/update-stock',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                stock_verificado: stockVerificado
            },
            success: function(response) {
                if (response.success) {
                    // Update color indicator
                    const indicator = $('#indicator-' + productId);
                    indicator.removeClass('bg-danger bg-success bg-warning bg-secondary');
                    indicator.addClass('bg-' + response.stock_color);

                    // Update color indicator en vista móvil
                    const mobileIndicator = $('#mobile-indicator-' + productId);
                    mobileIndicator.removeClass('bg-danger bg-success bg-warning bg-secondary');
                    mobileIndicator.addClass('bg-' + response.stock_color);

                    // Update verified by
                    $('#verified-by-' + productId).text(response.verificado_por);
                    $('#verified-at-' + productId).text(response.ultima_verificacion);

                    // Update en vista móvil
                    $('#mobile-verified-by-' + productId).text(response.verificado_por);
                    $('#mobile-verified-at-' + productId).text(response.ultima_verificacion);
                }
            },
            error: function(xhr) {
                if (xhr.status === 401) {
                    alert('Tu sesión ha expirado. Serás redirigido al inicio de sesión.');
                    window.location.href = '/login';
                } else if (xhr.status === 500) {
                    alert('Error del servidor. Por favor, recarga la página e intenta nuevamente.');
                } else {
                    alert('Error al guardar. Por favor, intenta nuevamente.');
                }
            }
        });
    }

    function updateStock2(productId, stockVerificado2) {
        $.ajax({
            url: '/products/' + productId + '/update-stock-2',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                stock_verificado_2: stockVerificado2
            },
            success: function(response) {
                if (response.success) {
                    // Update color indicator
                    const indicator = $('#indicator-2-' + productId);
                    indicator.removeClass('bg-danger bg-success bg-warning bg-secondary');
                    indicator.addClass('bg-' + response.stock_color_2);

                    // Update color indicator en vista móvil
                    const mobileIndicator = $('#mobile-indicator-2-' + productId);
                    mobileIndicator.removeClass('bg-danger bg-success bg-warning bg-secondary');
                    mobileIndicator.addClass('bg-' + response.stock_color_2);

                    // Update verified by
                    $('#verified-by-2-' + productId).text(response.verificado_por_2);
                    $('#verified-at-2-' + productId).text(response.ultima_verificacion_2);

                    // Update en vista móvil
                    $('#mobile-verified-by-2-' + productId).text(response.verificado_por_2);
                    $('#mobile-verified-at-2-' + productId).text(response.ultima_verificacion_2);
                }
            },
            error: function(xhr) {
                if (xhr.status === 401) {
                    alert('Tu sesión ha expirado. Serás redirigido al inicio de sesión.');
                    window.location.href = '/login';
                } else if (xhr.status === 500) {
                    alert('Error del servidor. Por favor, recarga la página e intenta nuevamente.');
                } else {
                    alert('Error al guardar. Por favor, intenta nuevamente.');
                }
            }
        });
    }

    function updateStock3(productId, stockVerificado3) {
        $.ajax({
            url: '/products/' + productId + '/update-stock-3',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                stock_verificado_3: stockVerificado3
            },
            success: function(response) {
                if (response.success) {
                    // Update color indicator
                    const indicator = $('#indicator-3-' + productId);
                    indicator.removeClass('bg-danger bg-success bg-warning bg-secondary');
                    indicator.addClass('bg-' + response.stock_color_3);

                    // Update color indicator en vista móvil
                    const mobileIndicator = $('#mobile-indicator-3-' + productId);
                    mobileIndicator.removeClass('bg-danger bg-success bg-warning bg-secondary');
                    mobileIndicator.addClass('bg-' + response.stock_color_3);

                    // Update verified by
                    $('#verified-by-3-' + productId).text(response.verificado_por_3);
                    $('#verified-at-3-' + productId).text(response.ultima_verificacion_3);

                    // Update en vista móvil
                    $('#mobile-verified-by-3-' + productId).text(response.verificado_por_3);
                    $('#mobile-verified-at-3-' + productId).text(response.ultima_verificacion_3);
                }
            },
            error: function(xhr) {
                if (xhr.status === 401) {
                    alert('Tu sesión ha expirado. Serás redirigido al inicio de sesión.');
                    window.location.href = '/login';
                } else if (xhr.status === 500) {
                    alert('Error del servidor. Por favor, recarga la página e intenta nuevamente.');
                } else {
                    alert('Error al guardar. Por favor, intenta nuevamente.');
                }
            }
        });
    }
});

// Filtrar por color
function filterByColor(color) {
    $('#colorFilter').val(color);
    $('#filterForm').submit();
}

// Editar producto
function editProduct(id, codigo, producto, marca, costo, precio_cliente, stock) {
    $('#editForm').attr('action', '/products/' + id);
    $('#edit_codigo').val(codigo);
    $('#edit_producto').val(producto);
    $('#edit_marca').val(marca);
    $('#edit_costo').val(costo);
    $('#edit_precio_cliente').val(precio_cliente);
    $('#edit_stock').val(stock);
    $('#editModal').modal('show');
}

// Eliminar producto
function deleteProduct(id, nombre) {
    if (confirm('¿Estás seguro de eliminar el producto "' + nombre + '"?\n\nEsta acción no se puede deshacer.')) {
        $('#deleteForm').attr('action', '/products/' + id);
        $('#deleteForm').submit();
    }
}

// Sistema de escáner de código de barras
let barcodeBuffer = '';
let barcodeTimeout = null;
let scannerActive = false;

// Detectar entrada del escáner (entrada rápida + Enter)
document.addEventListener('keypress', function(e) {
    // Solo capturar cuando el modal de crear esté visible y escáner activo
    if (!$('#createModal').hasClass('show') || !scannerActive) return;

    // Evitar captura si hay otro input con foco (excepto el campo código)
    const focusedElement = document.activeElement;
    if (focusedElement && focusedElement.tagName === 'INPUT' && focusedElement.id !== 'codigo_input') {
        return;
    }

    // Limpiar timeout anterior
    clearTimeout(barcodeTimeout);

    // Enter indica fin del escaneo
    if (e.key === 'Enter' && barcodeBuffer.length > 0) {
        e.preventDefault();
        $('#codigo_input').val(barcodeBuffer.trim());
        $('#codigo_input').focus();
        barcodeBuffer = '';
        deactivateScanner();
        return;
    }

    // Acumular caracteres
    if (e.key.length === 1) {
        barcodeBuffer += e.key;

        // Auto-reset después de 100ms sin entrada (típico del escáner)
        barcodeTimeout = setTimeout(function() {
            if (barcodeBuffer.length > 0 && barcodeBuffer.length < 5) {
                // Si es entrada muy corta, probablemente no es del escáner
                barcodeBuffer = '';
            }
        }, 100);
    }
});

// Detectar si es dispositivo móvil
function isMobileDevice() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
           ('ontouchstart' in window) ||
           (navigator.maxTouchPoints > 0);
}

// Variables para escáner de cámara
let html5QrcodeScanner = null;

// Activar modo escáner (detecta tipo de dispositivo)
$('#scannerBtn').click(function(e) {
    e.preventDefault();

    if (isMobileDevice()) {
        // En móviles: abrir cámara
        openCameraScanner();
    } else {
        // En PC: activar escáner físico
        if (scannerActive) {
            deactivateScanner();
        } else {
            activateScanner();
        }
    }
});

function activateScanner() {
    scannerActive = true;
    barcodeBuffer = '';
    $('#scannerBtn').removeClass('btn-outline-secondary').addClass('btn-success');
    $('#scannerBtn').html('<i class="fas fa-barcode"></i> Escaneando...');
    $('#scannerStatus').show();
    $('#codigo_input').attr('placeholder', 'Esperando escaneo...').focus();
}

function deactivateScanner() {
    scannerActive = false;
    barcodeBuffer = '';
    $('#scannerBtn').removeClass('btn-success').addClass('btn-outline-secondary');
    $('#scannerBtn').html('<i class="fas fa-barcode"></i> Escanear');
    $('#scannerStatus').hide();
    $('#codigo_input').attr('placeholder', 'Escanea o escribe el código');
}

// Abrir escáner de cámara
function openCameraScanner() {
    $('#cameraScannerModal').modal('show');

    // Pequeño delay para que el modal se muestre completamente
    setTimeout(function() {
        startCameraScanner();
    }, 300);
}

// Variables para el marco redimensionable
let frameWidth = 280;
let frameHeight = 180;

// Iniciar escáner de cámara
function startCameraScanner() {
    // Inicializar marco redimensionable
    initResizableFrame();

    // Configuración optimizada para móviles - área grande y flexible
    const config = {
        fps: 30,
        qrbox: function(viewfinderWidth, viewfinderHeight) {
            // Usar dimensiones del marco redimensionable
            return {
                width: Math.min(frameWidth, viewfinderWidth - 20),
                height: Math.min(frameHeight, viewfinderHeight - 20)
            };
        },
        aspectRatio: 1.777,
        disableFlip: false,
        formatsToSupport: [
            Html5QrcodeSupportedFormats.QR_CODE,
            Html5QrcodeSupportedFormats.AZTEC,
            Html5QrcodeSupportedFormats.DATA_MATRIX,
            Html5QrcodeSupportedFormats.PDF_417,
            Html5QrcodeSupportedFormats.EAN_13,
            Html5QrcodeSupportedFormats.EAN_8,
            Html5QrcodeSupportedFormats.UPC_A,
            Html5QrcodeSupportedFormats.UPC_E,
            Html5QrcodeSupportedFormats.CODE_128,
            Html5QrcodeSupportedFormats.CODE_39,
            Html5QrcodeSupportedFormats.CODE_93,
            Html5QrcodeSupportedFormats.ITF,
            Html5QrcodeSupportedFormats.CODABAR,
            Html5QrcodeSupportedFormats.RSS_14,
            Html5QrcodeSupportedFormats.RSS_EXPANDED
        ],
        experimentalFeatures: {
            useBarCodeDetectorIfSupported: true
        },
        rememberLastUsedCamera: true,
        showTorchButtonIfSupported: true
    };

    html5QrcodeScanner = new Html5Qrcode("reader");

    // Callback de éxito
    const onScanSuccess = (decodedText, decodedResult) => {
        console.log('Código detectado:', decodedText);
        $('#codigo_input').val(decodedText);
        playSuccessBeep();
        stopCameraScanner();
        $('#cameraScannerModal').modal('hide');
        $('input[name="producto"]').focus();
    };

    // Callback de error (no hace nada, sigue escaneando)
    const onScanError = (errorMessage) => {
        // No mostrar errores continuos
    };

    // Iniciar cámara con configuración simple
    html5QrcodeScanner.start(
        { facingMode: "environment" }, // Solo facingMode, sin otras propiedades
        config,
        onScanSuccess,
        onScanError
    ).catch((err) => {
        console.error('Error al iniciar la cámara:', err);
        alert('No se pudo acceder a la cámara.\n\nAsegúrate de:\n1. Dar permisos de cámara al navegador\n2. Estar usando HTTPS o localhost');
        $('#cameraScannerModal').modal('hide');
    });
}

// Reproducir sonido de éxito al escanear
function playSuccessBeep() {
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        oscillator.frequency.value = 800;
        oscillator.type = 'sine';

        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);

        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.1);
    } catch(e) {
        console.log('No se pudo reproducir el sonido');
    }
}

// Inicializar marco redimensionable
function initResizableFrame() {
    const frame = document.getElementById('resizableFrame');
    const reader = document.getElementById('reader');

    // Posicionar marco en el centro al inicio
    const readerRect = reader.getBoundingClientRect();
    const centerX = readerRect.width / 2;
    const centerY = readerRect.height / 2;

    frame.style.left = (centerX - frameWidth / 2) + 'px';
    frame.style.top = (centerY - frameHeight / 2) + 'px';
    frame.style.width = frameWidth + 'px';
    frame.style.height = frameHeight + 'px';
    frame.style.display = 'block';

    // Variables para el arrastre
    let isDragging = false;
    let currentCorner = null;
    let currentEdge = null;
    let startX = 0;
    let startY = 0;
    let startWidth = 0;
    let startHeight = 0;
    let startLeft = 0;
    let startTop = 0;

    // Función para manejar inicio de arrastre (esquinas)
    const corners = frame.querySelectorAll('.scan-corner');
    corners.forEach(corner => {
        corner.addEventListener('touchstart', function(e) {
            e.preventDefault();
            isDragging = true;
            currentCorner = this;
            const touch = e.touches[0];
            startX = touch.clientX;
            startY = touch.clientY;
            startWidth = frame.offsetWidth;
            startHeight = frame.offsetHeight;
            startLeft = frame.offsetLeft;
            startTop = frame.offsetTop;
        }, { passive: false });
    });

    // Función para manejar inicio de arrastre (bordes)
    const edges = frame.querySelectorAll('.scan-edge');
    edges.forEach(edge => {
        edge.addEventListener('touchstart', function(e) {
            e.preventDefault();
            isDragging = true;
            currentEdge = this;
            const touch = e.touches[0];
            startX = touch.clientX;
            startY = touch.clientY;
            startWidth = frame.offsetWidth;
            startHeight = frame.offsetHeight;
            startLeft = frame.offsetLeft;
            startTop = frame.offsetTop;
        }, { passive: false });
    });

    // Función para manejar movimiento
    document.addEventListener('touchmove', function(e) {
        if (!isDragging) return;

        e.preventDefault();
        const touch = e.touches[0];
        const deltaX = touch.clientX - startX;
        const deltaY = touch.clientY - startY;

        const minWidth = 150;
        const minHeight = 100;
        const maxWidth = readerRect.width - 40;
        const maxHeight = readerRect.height - 40;

        if (currentCorner) {
            // Redimensionar desde esquinas
            if (currentCorner.classList.contains('corner-br')) {
                // Esquina inferior derecha
                const newWidth = Math.max(minWidth, Math.min(maxWidth, startWidth + deltaX));
                const newHeight = Math.max(minHeight, Math.min(maxHeight, startHeight + deltaY));
                frame.style.width = newWidth + 'px';
                frame.style.height = newHeight + 'px';
                frameWidth = newWidth;
                frameHeight = newHeight;
            } else if (currentCorner.classList.contains('corner-bl')) {
                // Esquina inferior izquierda
                const newWidth = Math.max(minWidth, Math.min(maxWidth, startWidth - deltaX));
                const newHeight = Math.max(minHeight, Math.min(maxHeight, startHeight + deltaY));
                if (newWidth > minWidth) {
                    frame.style.left = (startLeft + deltaX) + 'px';
                    frame.style.width = newWidth + 'px';
                    frameWidth = newWidth;
                }
                frame.style.height = newHeight + 'px';
                frameHeight = newHeight;
            } else if (currentCorner.classList.contains('corner-tr')) {
                // Esquina superior derecha
                const newWidth = Math.max(minWidth, Math.min(maxWidth, startWidth + deltaX));
                const newHeight = Math.max(minHeight, Math.min(maxHeight, startHeight - deltaY));
                frame.style.width = newWidth + 'px';
                frameWidth = newWidth;
                if (newHeight > minHeight) {
                    frame.style.top = (startTop + deltaY) + 'px';
                    frame.style.height = newHeight + 'px';
                    frameHeight = newHeight;
                }
            } else if (currentCorner.classList.contains('corner-tl')) {
                // Esquina superior izquierda
                const newWidth = Math.max(minWidth, Math.min(maxWidth, startWidth - deltaX));
                const newHeight = Math.max(minHeight, Math.min(maxHeight, startHeight - deltaY));
                if (newWidth > minWidth) {
                    frame.style.left = (startLeft + deltaX) + 'px';
                    frame.style.width = newWidth + 'px';
                    frameWidth = newWidth;
                }
                if (newHeight > minHeight) {
                    frame.style.top = (startTop + deltaY) + 'px';
                    frame.style.height = newHeight + 'px';
                    frameHeight = newHeight;
                }
            }
        } else if (currentEdge) {
            // Redimensionar desde bordes
            if (currentEdge.classList.contains('edge-r')) {
                const newWidth = Math.max(minWidth, Math.min(maxWidth, startWidth + deltaX));
                frame.style.width = newWidth + 'px';
                frameWidth = newWidth;
            } else if (currentEdge.classList.contains('edge-l')) {
                const newWidth = Math.max(minWidth, Math.min(maxWidth, startWidth - deltaX));
                if (newWidth > minWidth) {
                    frame.style.left = (startLeft + deltaX) + 'px';
                    frame.style.width = newWidth + 'px';
                    frameWidth = newWidth;
                }
            } else if (currentEdge.classList.contains('edge-b')) {
                const newHeight = Math.max(minHeight, Math.min(maxHeight, startHeight + deltaY));
                frame.style.height = newHeight + 'px';
                frameHeight = newHeight;
            } else if (currentEdge.classList.contains('edge-t')) {
                const newHeight = Math.max(minHeight, Math.min(maxHeight, startHeight - deltaY));
                if (newHeight > minHeight) {
                    frame.style.top = (startTop + deltaY) + 'px';
                    frame.style.height = newHeight + 'px';
                    frameHeight = newHeight;
                }
            }
        }
    }, { passive: false });

    // Función para finalizar arrastre
    document.addEventListener('touchend', function(e) {
        if (isDragging) {
            isDragging = false;
            currentCorner = null;
            currentEdge = null;

            // Reiniciar escáner con nuevas dimensiones
            if (html5QrcodeScanner) {
                restartScanner();
            }
        }
    });
}

// Reiniciar escáner con nuevas dimensiones
function restartScanner() {
    if (!html5QrcodeScanner) return;

    // Guardar configuración actual
    const wasScanning = html5QrcodeScanner.getState() === 2; // SCANNING state

    if (wasScanning) {
        html5QrcodeScanner.stop().then(() => {
            // Pequeño delay antes de reiniciar
            setTimeout(() => {
                startCameraScanner();
            }, 100);
        }).catch(err => {
            console.error('Error al reiniciar:', err);
        });
    }
}

// Detener escáner de cámara
function stopCameraScanner() {
    if (html5QrcodeScanner) {
        html5QrcodeScanner.stop().then(() => {
            html5QrcodeScanner.clear();
            html5QrcodeScanner = null;
        }).catch((err) => {
            console.error('Error al detener la cámara:', err);
        });
    }

    // Ocultar marco redimensionable
    const frame = document.getElementById('resizableFrame');
    if (frame) {
        frame.style.display = 'none';
    }
}

// Detener cámara cuando se cierra el modal
$('#cameraScannerModal').on('hidden.bs.modal', function () {
    stopCameraScanner();
});

// Cerrar cámara con botón X
$('#closeCameraBtn').click(function() {
    stopCameraScanner();
});

// Resetear escáner cuando se cierra el modal de crear producto
$('#createModal').on('hidden.bs.modal', function () {
    deactivateScanner();
    $('#codigo_input').val('');
});

// Activar escáner automáticamente al abrir el modal (solo en PC)
$('#createModal').on('shown.bs.modal', function () {
    if (!isMobileDevice()) {
        activateScanner();
    }
});

// ========== FUNCIONES PARA EXPORTAR/IMPORTAR PÁGINA ACTUAL ==========

/**
 * Obtener los IDs de los productos en la página actual
 */
function getCurrentPageProductIds() {
    const ids = [];
    $('.stock-input').each(function() {
        ids.push($(this).data('product-id'));
    });
    return ids;
}

/**
 * Exportar productos de la página actual (ID y Nombre)
 */
function exportCurrentPage() {
    const ids = getCurrentPageProductIds();

    if (ids.length === 0) {
        alert('⚠️ Sin productos\n\nNo hay productos en esta página para exportar');
        return;
    }

    // Crear formulario para enviar los IDs
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("products.export-current-page") }}';
    form.style.display = 'none';

    // Agregar token CSRF
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    form.appendChild(csrfInput);

    // Agregar IDs
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);

    // Mostrar mensaje de éxito con alert nativo
    alert(`Exportando ${ids.length} productos de esta página`);
}

/**
 * Importar productos desde CSV
 */
function importCurrentPage(e) {
    const fileInput = document.getElementById('fileInputPage');

    // Determinar el botón que inició la acción (si aplica)
    const importButton = (e && (e.currentTarget || e.target)) ? (e.currentTarget || e.target) : null;
    const originalText = importButton ? importButton.innerHTML : null;
    if (importButton) {
        importButton.disabled = true;
        importButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importando...';
    }

    // Mostrar indicador de carga
    const file = fileInput.files[0];

    if (!file) {
        if (importButton) {
            importButton.disabled = false;
            importButton.innerHTML = originalText;
        }
        alert('Por favor selecciona un archivo CSV');
        return;
    }

    const formData = new FormData();
    formData.append('file', file);
    formData.append('_token', '{{ csrf_token() }}');

    // Enviar archivo al servidor
    fetch('{{ route("products.import-current-page") }}', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Restaurar botón
        importButton.disabled = false;
        importButton.innerHTML = originalText;

        if (data.success) {
            // Mostrar cambios detectados
            if (data.changes && data.changes.length > 0) {
                displayChanges(data.changes);

                alert(`✅ Importación exitosa!\n\n${data.message}\n\nRevisa los cambios en la tabla:\n🔴 Rojo = Nombre anterior\n🟢 Verde = Nombre nuevo`);

                // Cerrar modal y recargar para ver cambios
                $('#importPageModal').modal('hide');
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                alert('ℹ️ Sin cambios\n\nNo se detectaron cambios en los nombres');
            }
        } else {
            alert('❌ Error\n\n' + (data.error || 'Error al importar el archivo'));
        }
    })
    .catch(error => {
        // Restaurar botón
        importButton.disabled = false;
        importButton.innerHTML = originalText;

        console.error('Error:', error);
        alert('❌ Error al procesar la importación\n\nRevisa la consola para más detalles');
    });
}

/**
 * Mostrar los cambios detectados (actualiza texto del nombre)
 */
function displayChanges(changes) {
    changes.forEach(change => {
        const row = $(`#product-row-${change.id}`);
        if (row.length) {
            const productCell = row.find('td:nth-child(2)');
            productCell.text(change.new_name);
        }
    });
}

/**
 * Escapar HTML para evitar XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Limpiar modal al cerrar
$('#importPageModal').on('hidden.bs.modal', function() {
    $('#fileInputPage').val('');
    $('.custom-file-label').html('Elegir archivo CSV...');
    $('#importResults').hide();
    $('#changesList').html('');
});

// Resetear el modal de actualizar nombres al cerrar
$('#updateNamesModal').on('hidden.bs.modal', function() {
    $('#fileInputUpdateNames').val('');
    $('#fileInputUpdateNames').next('.custom-file-label').html('Elegir archivo...');
    $('#btnUpdateNames').prop('disabled', false);
    $('#btnUpdateNames').html('<i class="fas fa-edit"></i> Actualizar Nombres');
});

// Marcar todas las filas exportables como rojas al cargar la página
$(document).ready(function() {
    // Removed automatic old/new coloring as requested
});

</script>

<style>
/* Mejorar visibilidad del modal */
#importPageModal .modal-body {
    max-height: 70vh;
    overflow-y: auto;
}

#changesList {
    max-height: 300px;
    overflow-y: auto;
}

/* Estilos para el modal de actualizar nombres */
#updateNamesModal .alert-info {
    border-left: 4px solid #17a2b8;
}

#updateNamesModal .alert-success.border-left-success {
    border-left: 5px solid #28a745;
    background: linear-gradient(135deg, #f0fff4 0%, #e8f5e9 100%);
}

#updateNamesModal .alert-success .fa-file-alt {
    opacity: 0.3;
}

#updateNamesModal .alert-info ul {
    padding-left: 20px;
}

#updateNamesModal .alert-info li {
    margin-bottom: 5px;
}

#updateNamesModal .card.bg-light {
    border: 2px dashed #6c757d;
}

#updateNamesModal .table-sm {
    font-size: 0.85rem;
}

#updateNamesModal .modal-footer {
    border-top: 2px solid #ffc107;
}

#updateNamesModal .bg-dark code {
    color: #0f0;
}

/* Animación para el botón de procesamiento */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.fa-spinner.fa-spin {
    animation: spin 1s linear infinite;
}

/* ========== ESTILOS EXISTENTES ========== */

/* Evitar scroll horizontal global */
* {
    max-width: 100%;
}

html, body {
    overflow-x: hidden;
    width: 100%;
}

@keyframes blink {
    0%, 50%, 100% { opacity: 1; }
    25%, 75% { opacity: 0.3; }
}
.blink {
    animation: blink 1.5s ease-in-out infinite;
}

/* Marco redimensionable para escáner */
.resizable-scan-frame {
    position: absolute;
    border: 3px solid #00ff00;
    box-shadow: 0 0 0 2000px rgba(0, 0, 0, 0.5), 0 0 20px rgba(0, 255, 0, 0.8);
    z-index: 1000;
    display: none;
    pointer-events: none;
}

.scan-corner {
    position: absolute;
    width: 40px;
    height: 40px;
    background: #00ff00;
    border: 2px solid white;
    border-radius: 50%;
    box-shadow: 0 0 10px rgba(0, 255, 0, 0.8);
    pointer-events: auto;
    touch-action: none;
    cursor: grab;
    z-index: 10;
}

.scan-corner:active {
    cursor: grabbing;
    background: #00dd00;
}

.corner-tl {
    top: -20px;
    left: -20px;
}

.corner-tr {
    top: -20px;
    right: -20px;
}

.corner-bl {
    bottom: -20px;
    left: -20px;
}

.corner-br {
    bottom: -20px;
    right: -20px;
}

.scan-edge {
    position: absolute;
    background: transparent;
    pointer-events: auto;
    touch-action: none;
    z-index: 9;
}

.edge-t,
.edge-b {
    left: 0;
    right: 0;
    height: 30px;
    cursor: ns-resize;
}

.edge-t {
    top: -15px;
}

.edge-b {
    bottom: -15px;
}

.edge-l,
.edge-r {
    top: 0;
    bottom: 0;
    width: 30px;
    cursor: ew-resize;
}

.edge-l {
    left: -15px;
}

.edge-r {
    right: -15px;
}

.scan-frame-label {
    position: absolute;
    top: -40px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 255, 0, 0.9);
    color: black;
    padding: 5px 15px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: bold;
    white-space: nowrap;
    animation: blink 2s ease-in-out infinite;
}

@media (max-width: 768px) {
    .scan-corner {
        width: 50px;
        height: 50px;
    }

    .corner-tl,
    .corner-tr,
    .corner-bl,
    .corner-br {
        margin: -25px;
    }

    .scan-edge {
        background: rgba(0, 255, 0, 0.1);
    }

    .edge-t,
    .edge-b {
        height: 40px;
    }

    .edge-l,
    .edge-r {
        width: 40px;
    }

    /* Evitar desbordamiento horizontal en móviles */
    body {
        overflow-x: hidden;
    }

    .container-fluid,
    .card,
    .card-body,
    .card-header {
        max-width: 100%;
        overflow-x: hidden;
    }

    /* Header responsive */
    .card-header {
        flex-wrap: wrap;
    }

    .card-title {
        width: 100%;
        margin-bottom: 0.5rem;
    }

    .card-tools {
        width: 100%;
        display: flex;
        justify-content: flex-start;
        gap: 0.25rem;
        flex-wrap: wrap;
    }

    .card-tools .btn {
        font-size: 0.75rem;
    }

    /* Ajustar botones de filtro */
    .btn-group-wrapper {
        width: 100%;
        justify-content: flex-start;
    }

    .btn-group-wrapper .btn {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        white-space: nowrap;
        flex: 0 0 auto;
    }

    /* Ajustar input de búsqueda */
    .input-group {
        max-width: 100%;
    }

    .input-group .btn {
        font-size: 0.8rem;
        padding: 0.375rem 0.75rem;
    }

    /* Limitar ancho de badges */
    .badge {
        font-size: 0.7rem;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Card móvil */
    .product-card-mobile {
        max-width: 100%;
        overflow: hidden;
    }

    .product-card-mobile .card-header {
        padding: 0.5rem !important;
    }

    .product-card-mobile h6 {
        font-size: 0.9rem;
        word-break: break-word;
        overflow-wrap: break-word;
    }

    /* Mejorar accordion en móviles */
    .accordion .card {
        overflow: visible;
    }

    /* Ajustar tamaño de texto pequeño */
    small {
        font-size: 0.7rem;
    }

    /* Card body padding reducido en móviles */
    .card-body {
        padding: 0.75rem !important;
    }
}

</style>

@endsection
