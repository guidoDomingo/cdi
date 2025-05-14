@extends('layouts.app')

@section('styles')
<link href="{{ asset('css/icd11.css') }}" rel="stylesheet">
<link href="{{ asset('css/icd11-disease-tester.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container api-tester-container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0">Tester de API ICD-11 para Enfermedades</h2>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <p>Esta herramienta te permite probar la nueva API para obtener información detallada de enfermedades por código ICD-11 directamente desde la API oficial de la OMS.</p>

                        <div class="mt-3 mb-3">
                            <h5>Códigos de ejemplo:</h5>
                            <div class="d-flex flex-wrap gap-2">
                                <button class="btn btn-sm btn-outline-primary example-code" data-code="MD12">MD12 (Tos)</button>
                                <button class="btn btn-sm btn-outline-primary example-code" data-code="BA00">BA00 (Tifus)</button>
                                <button class="btn btn-sm btn-outline-primary example-code" data-code="1A31">1A31 (Tuberculosis)</button>
                                <button class="btn btn-sm btn-outline-primary example-code" data-code="HA00">HA00 (Apendicitis)</button>
                                <button class="btn btn-sm btn-outline-primary example-code" data-code="9B10">9B10 (Migraña)</button>
                                <button class="btn btn-sm btn-outline-primary example-code" data-code="BA51.4">BA51.4 (COVID-19)</button>
                            </div>
                            <p class="form-text mt-2">Haga clic en cualquiera de estos códigos de ejemplo para probarlos directamente</p>
                        </div>
                    </div>

                    <div class="search-box">
                        <h3><i class="fas fa-search"></i> Buscar por código</h3>
                        <div class="row">
                            <div class="col-md-10">
                                <div class="input-group">
                                    <input type="text" id="disease-code" class="form-control" placeholder="Ingrese código ICD-11 (ej: MD12)">
                                    <button class="btn btn-primary" id="search-disease-btn">
                                        <i class="fas fa-search"></i> Buscar
                                    </button>
                                </div>
                                <div class="form-text">Ingrese un código ICD-11 válido y presione el botón buscar o Enter</div>
                            </div>
                        </div>
                    </div>

                    <!-- Estado de la API -->
                    <div id="api-status" class="alert alert-info d-none">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-info-circle fa-2x"></i>
                            </div>
                            <div>
                                <span id="api-status-message">Utilice la barra de búsqueda para consultar información sobre enfermedades</span>
                            </div>
                        </div>
                    </div>

                    <!-- Resultados de la API -->
                    <div id="api-result"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/icd11-disease-tester.js') }}"></script>
@endsection
