@extends('layouts.app')

@section('styles')
<link href="{{ asset('css/icd11.css') }}" rel="stylesheet">
<link href="{{ asset('css/icd11-api-docs.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0">Documentación API ICD-11</h2>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <p>Esta documentación describe las APIs disponibles para interactuar con la Clasificación Internacional de Enfermedades (ICD-11) implementadas en esta aplicación.</p>
                    </div>

                    <div class="api-section">
                        <h3>Endpoints disponibles</h3>

                        <div class="card mb-3">
                            <div class="card-header">
                                <h4 class="mb-0">Búsqueda general</h4>
                            </div>
                            <div class="card-body">
                                <div class="endpoint">
                                    <span class="badge bg-success">GET</span>
                                    <code>/api/icd11/search?query=term</code>
                                </div>
                                <p>Busca términos relacionados con enfermedades o diagnósticos.</p>
                                <div class="params">
                                    <h5>Parámetros:</h5>
                                    <ul>
                                        <li><code>query</code>: Término de búsqueda (requerido)</li>
                                    </ul>
                                </div>
                                <div class="response">
                                    <h5>Respuesta:</h5>
                                    <p>Lista de resultados que coinciden con el término de búsqueda.</p>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header">
                                <h4 class="mb-0">Obtener por código</h4>
                            </div>
                            <div class="card-body">
                                <div class="endpoint">
                                    <span class="badge bg-success">GET</span>
                                    <code>/api/icd11/code/{code}</code>
                                </div>
                                <p>Obtiene información básica sobre un código ICD-11 específico.</p>
                                <div class="params">
                                    <h5>Parámetros:</h5>
                                    <ul>
                                        <li><code>{code}</code>: El código ICD-11 a consultar (por ejemplo, MD12)</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3 border-primary">
                            <div class="card-header bg-primary text-white">
                                <h4 class="mb-0">Detalles de enfermedad por código (Nuevo)</h4>
                            </div>
                            <div class="card-body">
                                <div class="endpoint">
                                    <span class="badge bg-success">GET</span>
                                    <code>/api/icd11/disease/{code}</code>
                                </div>
                                <p>Obtiene información detallada de una enfermedad directamente desde la API oficial de la OMS utilizando su código ICD-11.</p>
                                <div class="params">
                                    <h5>Parámetros:</h5>
                                    <ul>
                                        <li><code>{code}</code>: El código ICD-11 a consultar (por ejemplo, MD12)</li>
                                    </ul>
                                </div>
                                <div class="response">
                                    <h5>Respuesta:</h5>
                                    <p>Datos detallados sobre la enfermedad, incluyendo título, descripción, términos relacionados y otros metadatos.</p>
                                </div>
                                <div class="mt-3">
                                    <a href="{{ route('icd11.disease-tester') }}" class="btn btn-primary">
                                        <i class="fas fa-flask"></i> Probar esta API
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header">
                                <h4 class="mb-0">Entidad por ID</h4>
                            </div>
                            <div class="card-body">
                                <div class="endpoint">
                                    <span class="badge bg-success">GET</span>
                                    <code>/api/icd11/entity/{entityId}</code>
                                </div>
                                <p>Obtiene detalles completos de una entidad ICD-11 por su ID.</p>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header">
                                <h4 class="mb-0">Hijos de una entidad</h4>
                            </div>
                            <div class="card-body">
                                <div class="endpoint">
                                    <span class="badge bg-success">GET</span>
                                    <code>/api/icd11/entity/{entityId}/children</code>
                                </div>
                                <p>Obtiene los nodos hijos de una entidad ICD-11 específica.</p>
                            </div>
                        </div>
                    </div>

                    <div class="api-section mt-5">
                        <h3>Ejemplos de uso</h3>

                        <div class="card">
                            <div class="card-body">
                                <h5>Obtener información detallada de "Tos" (código MD12)</h5>
                                <pre class="api-example"><code>fetch('/api/icd11/disease/MD12')
    .then(response => response.json())
    .then(data => {
        console.log(data);
        // Procesar los resultados
    });</code></pre>
                                <p>Esta solicitud devuelve información detallada sobre el diagnóstico "Tos" con código MD12, incluyendo su descripción clínica y términos relacionados.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <p class="text-muted mb-0">Para más información sobre la API oficial de ICD-11, consulte <a href="https://icd.who.int/icdapi/docs2/APIDoc-Version2/" target="_blank">la documentación oficial de la OMS</a>.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Agregar resaltado de sintaxis si está disponible highlight.js
        if (window.hljs) {
            document.querySelectorAll('pre code').forEach((block) => {
                hljs.highlightBlock(block);
            });
        }
    });
</script>
@endsection
