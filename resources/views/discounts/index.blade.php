@extends('layouts.admin')

@section('title', 'Descuentos - Sistema de Inventario')
@section('page-title', 'Descuentos')

@section('content')
@section('styles')
<style>
    /* Paleta profesional y sutil por columna */
    .excel-like {
        padding: 6px;
        background: transparent;
    }
    .excel-like table { width: 100%; border-collapse: separate; }
    .excel-like th, .excel-like td {
        border: none;
        padding: 10px 12px;
        vertical-align: middle;
    }

    /* Header styling: deep navy for good contrast */
    .excel-like thead th { font-weight: 700; color: #0B3D91; }

    /* Nueva paleta profesional (subtle tints, baja opacidad) */
    .excel-like thead th:nth-child(1), .excel-like tbody td:nth-child(1) { background: rgba(230,233,251,0.12); }
    .excel-like thead th:nth-child(2), .excel-like tbody td:nth-child(2) { background: rgba(222,247,241,0.12); }
    .excel-like thead th:nth-child(3), .excel-like tbody td:nth-child(3) { background: rgba(239,246,255,0.12); }
    .excel-like thead th:nth-child(4), .excel-like tbody td:nth-child(4) { background: rgba(241,245,249,0.12); }
    .excel-like thead th:nth-child(5), .excel-like tbody td:nth-child(5) { background: rgba(255,247,237,0.10); }
    .excel-like thead th:nth-child(6), .excel-like tbody td:nth-child(6) { background: rgba(245,243,255,0.10); }
    .excel-like thead th:nth-child(7), .excel-like tbody td:nth-child(7) { background: rgba(236,253,245,0.10); }

    /* Subtle hover: gentle navy tint */
    .excel-like tbody tr:hover td { background: rgba(11,61,145,0.04); }

    /* Right align numeric columns with tabular numbers */
    .excel-like td.text-right { font-variant-numeric: tabular-nums; }

    /* Small visual polish for header cells */
    .excel-like thead th { padding: 12px 12px; letter-spacing: 0.2px; font-size: 0.95rem; }
</style>
@endsection
<div class="row">
    <div class="col-12">
        <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Lista de Descuentos</h3>
                <div class="d-flex align-items-center flex-wrap">
                    <form method="GET" action="{{ route('discounts.index') }}" class="form-inline mr-2">
                        <div class="input-group input-group-sm">
                            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Buscar código, producto o marca">
                            <div class="input-group-append"><button class="btn btn-sm btn-secondary" type="submit">Buscar</button></div>
                        </div>
                    </form>

                    <a href="#" class="btn btn-sm btn-primary">Exportar (CSV)</a>
                    <button type="button" class="btn btn-sm btn-info ml-2" data-toggle="modal" data-target="#importProductsModal">Importar productos</button>
                    <button type="button" class="btn btn-sm btn-success ml-2" data-toggle="modal" data-target="#updatePriceModal">Actualizar precios</button>
                    <button type="button" class="btn btn-sm btn-danger ml-2" data-toggle="modal" data-target="#deleteAllModal">Eliminar todos</button>
                </div>
            </div>

            {{-- Success alerts are shown globally in the layout to avoid duplicates --}}

            @if(session('import_summary'))
                @php $s = session('import_summary'); @endphp
                <div class="card mb-3 border-info">
                    <div class="card-header bg-info text-white">
                        Resultado de la importación
                        @if($s['type'] === 'import_productos')
                            (Importar productos)
                        @elseif($s['type'] === 'update_precios')
                            (Actualizar precios)
                        @endif
                    </div>
                    <div class="card-body">
                        @if($s['type'] === 'import_productos')
                            <!-- Resultado de importación de productos -->
                            <div class="row mb-2">
                                <div class="col-auto"><span class="badge badge-success">Creados: {{ $s['created'] }}</span></div>
                                <div class="col-auto"><span class="badge badge-info">Actualizados: {{ $s['updated'] }}</span></div>
                            </div>
                            <p class="small text-muted">✓ Los productos se han importado/actualizado incluyendo código, producto, marca, costo, precio y stock.</p>
                        @elseif($s['type'] === 'update_precios')
                            <!-- Resultado de actualización de precios -->
                            <div class="row mb-2">
                                <div class="col-auto"><span class="badge badge-primary">Coincidencias: {{ $s['matched'] }}</span></div>
                                <div class="col-auto"><span class="badge badge-success">Registros actualizados: {{ $s['updated'] }}</span></div>
                                <div class="col-auto"><span class="badge badge-warning">Costo actualizados: {{ $s['updated_costo'] }}</span></div>
                                <div class="col-auto"><span class="badge badge-info">Precio actualizados: {{ $s['updated_precio'] }}</span></div>
                                <div class="col-auto"><span class="badge badge-secondary">No encontrados: {{ $s['not_found'] }}</span></div>
                            </div>
                        @endif

                        @if(!empty($s['preview']))
                            <div class="mb-2">
                                <strong>Vista previa (primeras filas leídas):</strong>
                                <div class="table-responsive mt-2">
                                    <table class="table table-sm table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Fila</th>
                                                <th>Código</th>
                                                @if($s['type'] === 'update_precios')
                                                    <th>Normalizado</th>
                                                    <th>Coincide en BD</th>
                                                    <th>Estrategia</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($s['preview'] as $i => $pv)
                                                <tr>
                                                    <td>{{ $i+1 }}</td>
                                                    <td>{{ $pv['codigo_raw'] }}</td>
                                                    @if($s['type'] === 'update_precios')
                                                        <td>{{ $pv['search'] ?? '-' }}</td>
                                                        <td>{{ $pv['matched_db'] ?? '-' }}</td>
                                                        <td>{{ $pv['found_by'] ?? '-' }}</td>
                                                    @endif
                                                </tr>
                                                @if(!empty($pv['candidates']) && $s['type'] === 'update_precios')
                                                    <tr>
                                                        <td></td>
                                                        <td colspan="{{ $s['type'] === 'update_precios' ? 4 : 1 }}">
                                                            <small class="text-muted">Candidatos en BD:</small>
                                                            <ul class="mb-0">
                                                                @foreach($pv['candidates'] as $cand)
                                                                    <li>{{ $cand['codigo'] }} — HEX: {{ $cand['hex'] }}</li>
                                                                @endforeach
                                                            </ul>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        @if(!empty($s['errors']))
                            <div class="mb-2">
                                <strong>Errores:</strong>
                                <ul class="mb-0">
                                    @foreach($s['errors'] as $err)
                                        <li>{{ $err }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if($s['type'] === 'import_productos')
                            <p class="small text-muted">✓ Los productos se han importado/actualizado incluyendo código, producto, marca, costo, precio y stock.</p>
                        @else
                            <p class="small text-muted">Se buscaron los productos por `codigo` (sin espacios). Revisa los no encontrados para corregir códigos si es necesario.</p>
                        @endif
                    </div>
                </div>
            @endif

            <div class="card-body">
                <div class="table-responsive excel-like">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Marca</th>
                                <th class="text-right">Costo</th>
                                <th class="text-right">Precio Cliente</th>
                                <th class="text-right">Stock (Inventario)</th>
                                <th class="text-right">Fecha Ingreso</th>
                                <th class="text-right">Días</th>
                                <th class="text-right">Desc. Auto. (%)</th>
                                <th class="text-right">Precio c/ Descuento</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                                <tr>
                                    <td>{{ $product->codigo }}</td>
                                    <td>{{ $product->producto }}</td>
                                    <td>{{ $product->marca }}</td>
                                    <td class="text-right">S/ {{ number_format($product->costo ?? 0, 2) }}</td>
                                    <td class="text-right">S/ {{ number_format($product->precio_cliente ?? 0, 2) }}</td>
                                    <td class="text-right">{{ $product->stock ?? '-' }}</td>
                                    <td class="text-right">
                                        <form method="POST" action="{{ route('discounts.update-fecha', $product->id) }}" class="form-inline fecha-form" style="display: inline;">
                                            @csrf
                                            <input type="date" name="fecha_ingreso" value="{{ $product->fecha_ingreso ? $product->fecha_ingreso->format('Y-m-d') : '2026-01-01' }}"
                                                   class="form-control form-control-sm" style="width: 120px; cursor: pointer;"
                                                   onchange="this.form.submit();" title="Haz clic para cambiar la fecha">
                                        </form>
                                    </td>
                                    <td class="text-right text-muted small font-weight-bold">{{ $product->dias_desde_ingreso ?? '-' }}</td>
                                    <td class="text-right font-weight-bold" style="color: {{ is_null($product->descuento_automatico) || $product->descuento_automatico >= 0 ? '#28a745' : '#dc3545' }}">
                                        @if(is_null($product->descuento_automatico))
                                            Precio cliente
                                        @else
                                            {{ $product->descuento_automatico > 0 ? '+' : '' }}{{ $product->descuento_automatico }}%
                                        @endif
                                    </td>
                                    <td class="text-right font-weight-bold">
                                        @if(!is_null($product->precio_con_descuento))
                                            S/ {{ number_format($product->precio_con_descuento, 2) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center">No hay productos.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer d-flex flex-column flex-md-row justify-content-between align-items-center">
                <div class="nav-links btn-group btn-group-sm mb-2 mb-md-0">
                    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Inventario</a>
                    <a href="{{ route('patrimonio.index') }}" class="btn btn-outline-secondary">Patrimonio</a>
                    <a href="{{ route('discounts.index') }}" class="btn btn-primary">Descuentos</a>
                </div>

                <div class="pagination-controls d-flex align-items-center">
                    <form method="GET" action="{{ route('discounts.index') }}" class="form-inline mr-2">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend"><span class="input-group-text">Ir a</span></div>
                            <input type="number" name="page" min="1" max="{{ $products->lastPage() }}" class="form-control" style="width:90px" placeholder="Página">
                            <div class="input-group-append"><button class="btn btn-sm btn-secondary" type="submit">Ir</button></div>
                        </div>
                    </form>

                    <div class="btn-group btn-group-sm mr-2" role="group">
                        @if($products->onFirstPage())
                            <button class="btn btn-outline-secondary" disabled>« Anterior</button>
                        @else
                            <a href="{{ $products->previousPageUrl() }}" class="btn btn-outline-secondary">« Anterior</a>
                        @endif

                        @if($products->hasMorePages())
                            <a href="{{ $products->nextPageUrl() }}" class="btn btn-outline-secondary">Siguiente »</a>
                        @else
                            <button class="btn btn-outline-secondary" disabled>Siguiente »</button>
                        @endif
                    </div>

                    <div class="text-muted small">Página {{ $products->currentPage() }} de {{ $products->lastPage() }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal: Importar productos -->
<div class="modal fade" id="importProductsModal" tabindex="-1" role="dialog" aria-labelledby="importProductsModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('discounts.import-products') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="importProductsModalLabel">Importar productos</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">Sube un archivo con columnas: <strong>Código</strong>, <strong>Producto</strong>, <strong>Marca</strong>, <strong>Costo</strong> y <strong>Precio</strong>. El sistema intentará detectar cabeceras automáticamente.</p>
                    <div class="custom-file">
                        <input type="file" name="file" accept=".xls,.xlsx,.csv,.ods" class="custom-file-input" id="modalProductsFile" required>
                        <label class="custom-file-label" for="modalProductsFile">Seleccionar archivo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Importar productos</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal: Actualizar precio -->
<div class="modal fade" id="updatePriceModal" tabindex="-1" role="dialog" aria-labelledby="updatePriceModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('discounts.update-prices') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="updatePriceModalLabel">Actualizar precios desde Excel</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">Sube un archivo con columnas: <strong>Código</strong>, <strong>Costo</strong> y <strong>Precio</strong>. El sistema intentará detectar cabeceras similares.</p>
                    <div class="custom-file">
                        <input type="file" name="file" accept=".xls,.xlsx,.csv,.ods" class="custom-file-input" id="modalPriceFile" required>
                        <label class="custom-file-label" for="modalPriceFile">Seleccionar Excel</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Subir y actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

    @section('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Manejo del modal de importación de productos
        var modalProductsInput = document.getElementById('modalProductsFile');
        var modalProductsLabel = document.querySelector('label[for="modalProductsFile"]');

        if (modalProductsInput) {
            modalProductsInput.addEventListener('change', function (e) {
                var name = (e.target.files && e.target.files.length) ? e.target.files[0].name : 'Seleccionar archivo';
                if (modalProductsLabel) modalProductsLabel.textContent = name;
            });
        }

        $('#importProductsModal').on('hidden.bs.modal', function () {
            if (modalProductsLabel) modalProductsLabel.textContent = 'Seleccionar archivo';
            if (modalProductsInput) modalProductsInput.value = null;
        });

        // Manejo del modal de actualización de precios
        var modalPriceInput = document.getElementById('modalPriceFile');
        var modalPriceLabel = document.querySelector('label[for="modalPriceFile"]');

        if (modalPriceInput) {
            modalPriceInput.addEventListener('change', function (e) {
                var name = (e.target.files && e.target.files.length) ? e.target.files[0].name : 'Seleccionar Excel';
                if (modalPriceLabel) modalPriceLabel.textContent = name;
            });
        }

        $('#updatePriceModal').on('hidden.bs.modal', function () {
            if (modalPriceLabel) modalPriceLabel.textContent = 'Seleccionar Excel';
            if (modalPriceInput) modalPriceInput.value = null;
        });
    });
    </script>

    <!-- Modal de confirmación para eliminar todos -->
    <div class="modal fade" id="deleteAllModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Eliminar todos los productos</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p><strong>⚠️ Advertencia:</strong> Esta acción eliminará <strong>TODOS</strong> los productos descargados de la tabla de descuentos.</p>
                    <p>Después podrás re-importar el Excel con los productos actualizados y los caracteres se limpiarán correctamente.</p>
                    <p class="text-muted small">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <form method="POST" action="{{ route('discounts.delete-all') }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn btn-danger">Sí, eliminar todos</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endsection

@endsection

