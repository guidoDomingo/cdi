@extends('layouts.app')

@section('content')
<div class="container icd11-container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0">ICD-11 Herramienta de Clasificación Embebida (ECT)</h2>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <p>Esta página integra la Herramienta de Clasificación Embebida (ECT) de ICD-11 proporcionada por la Organización Mundial de la Salud.</p>
                    </div>

                    <!-- Contenedor para la herramienta ECT -->
                    <div class="embedded-tool-container" style="height: 700px; margin-bottom: 20px; position: relative;">
                        <!-- Spinner de carga -->
                        <div id="loading-spinner" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; justify-content: center; align-items: center; background-color: #f8f9fa; z-index: 1000;">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2">Cargando herramienta ICD-11...</p>
                            </div>
                        </div>

                        <!-- iframe con la herramienta oficial de la OMS -->
                        <iframe
                            id="ect-iframe"
                            src="https://icd.who.int/browse/2022-02/mms/es"
                            style="width: 100%; height: 100%; border: 1px solid #ddd;"
                            onload="document.getElementById('loading-spinner').style.display='none';"
                            onerror="handleIframeError()">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function handleIframeError() {
        document.getElementById('loading-spinner').innerHTML =
            '<div class="alert alert-danger m-3">' +
            '<h4 class="alert-heading">Error al cargar la herramienta ECT</h4>' +
            '<p>No se pudo cargar la herramienta de clasificación desde el servidor de la OMS.</p>' +
            '<hr>' +
            '<p class="mb-0">Sugerencias:' +
            '<ul>' +
            '<li>Verifique su conexión a Internet</li>' +
            '<li>Compruebe que los servidores de la OMS estén accesibles</li>' +
            '<li>Intente recargar la página</li>' +
            '</ul></p></div>';
    }

    // Verificar si el iframe se cargó correctamente
    window.addEventListener('load', function() {
        setTimeout(function() {
            const iframe = document.getElementById('ect-iframe');
            if (iframe && document.getElementById('loading-spinner').style.display !== 'none') {
                handleIframeError();
            }
        }, 15000); // Esperar 15 segundos como máximo
    });
</script>
@endsection
