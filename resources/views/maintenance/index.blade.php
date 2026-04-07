@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-tools"></i> Mantenimiento del Sistema
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Corregir Encoding -->
                        <div class="col-md-6">
                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Corregir Caracteres Especiales</h3>
                                </div>
                                <div class="card-body">
                                    <p>Corrige los caracteres mal codificados (tildes, ñ, etc.) en todos los productos.</p>
                                    <button id="btnFixEncoding" class="btn btn-primary">
                                        <i class="fas fa-spell-check"></i> Ejecutar Corrección
                                    </button>
                                    <div id="encodingStatus" class="mt-3" style="display: none;">
                                        <div class="alert" role="alert"></div>
                                    </div>
                                    <div id="encodingOutput" class="mt-3" style="display: none;">
                                        <h5>Resultado:</h5>
                                        <pre class="bg-light p-3" style="max-height: 300px; overflow-y: auto;"></pre>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Limpiar Caché -->
                        <div class="col-md-6">
                            <div class="card card-outline card-warning">
                                <div class="card-header">
                                    <h3 class="card-title">Limpiar Caché</h3>
                                </div>
                                <div class="card-body">
                                    <p>Limpia la caché del sistema, configuración y vistas.</p>
                                    <button id="btnClearCache" class="btn btn-warning">
                                        <i class="fas fa-broom"></i> Limpiar Caché
                                    </button>
                                    <div id="cacheStatus" class="mt-3" style="display: none;">
                                        <div class="alert" role="alert"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Botón de corrección de encoding
    document.getElementById('btnFixEncoding').addEventListener('click', function() {
        const btn = this;
        const statusDiv = document.getElementById('encodingStatus');
        const outputDiv = document.getElementById('encodingOutput');

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

        statusDiv.style.display = 'block';
        statusDiv.querySelector('.alert').className = 'alert alert-info';
        statusDiv.querySelector('.alert').textContent = 'Ejecutando comando, por favor espere...';

        outputDiv.style.display = 'none';

        fetch('{{ route("maintenance.fix-encoding") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                statusDiv.querySelector('.alert').className = 'alert alert-success';
                statusDiv.querySelector('.alert').innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;

                if (data.output) {
                    outputDiv.style.display = 'block';
                    outputDiv.querySelector('pre').textContent = data.output;
                }
            } else {
                statusDiv.querySelector('.alert').className = 'alert alert-danger';
                statusDiv.querySelector('.alert').innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.message;
            }
        })
        .catch(error => {
            statusDiv.querySelector('.alert').className = 'alert alert-danger';
            statusDiv.querySelector('.alert').innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error: ' + error.message;
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-spell-check"></i> Ejecutar Corrección';
        });
    });

    // Botón de limpiar caché
    document.getElementById('btnClearCache').addEventListener('click', function() {
        const btn = this;
        const statusDiv = document.getElementById('cacheStatus');

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Limpiando...';

        statusDiv.style.display = 'block';
        statusDiv.querySelector('.alert').className = 'alert alert-info';
        statusDiv.querySelector('.alert').textContent = 'Limpiando caché...';

        fetch('{{ route("maintenance.clear-cache") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                statusDiv.querySelector('.alert').className = 'alert alert-success';
                statusDiv.querySelector('.alert').innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
            } else {
                statusDiv.querySelector('.alert').className = 'alert alert-danger';
                statusDiv.querySelector('.alert').innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.message;
            }
        })
        .catch(error => {
            statusDiv.querySelector('.alert').className = 'alert alert-danger';
            statusDiv.querySelector('.alert').innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error: ' + error.message;
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-broom"></i> Limpiar Caché';
        });
    });
});
</script>
@endsection
