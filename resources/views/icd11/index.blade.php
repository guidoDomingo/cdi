@extends('layouts.app')

@section('styles')
<link href="{{ asset('css/icd11.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container icd11-container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0">Consulta ICD-11 (Clasificación Internacional de Enfermedades)</h2>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <p>Esta herramienta permite buscar y consultar la Clasificación Internacional de Enfermedades (ICD-11) de la Organización Mundial de la Salud.</p>
                    </div>
                    
                    <div class="search-container mb-4">
                        <h3>Buscar enfermedades o condiciones</h3>
                        <form id="search-form" class="mb-3" action="{{ route('icd11.search') }}" method="GET">
                            <div class="input-group">
                                <input type="text" id="search-query" name="query" class="form-control" placeholder="Ingrese término de búsqueda (ej: diabetes, hipertensión)" value="{{ $query ?? '' }}" required>
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search"></i> Buscar
                                    </button>
                                </div>
                            </div>
                            <div class="form-text mt-2">
                                <i class="fas fa-info-circle"></i> Ingrese términos como nombres de enfermedades, síntomas o códigos ICD-11.
                            </div>
                        </form>
                    </div>
                    
                    @include('icd11.partials.error-message', ['error' => $error ?? null])
                    
                    @if(isset($results))
                    <div id="results-container">
                        @include('icd11.partials.search-results', ['results' => $results, 'query' => $query ?? ''])
                    </div>
                    @endif
                    
                    @if(isset($entity))
                    <div id="entity-details" class="mt-4">
                        @include('icd11.partials.entity-details', ['entity' => $entity, 'children' => $children ?? [], 'entityId' => $entityId ?? ''])
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mejorar la experiencia de usuario en la búsqueda
        const searchForm = document.getElementById('search-form');
        const searchInput = document.getElementById('search-query');
        
        if (searchForm && searchInput) {
            // Enfocar el campo de búsqueda automáticamente si está vacío
            if (searchInput.value === '') {
                searchInput.focus();
            }
            
            // Añadir animación simple al enviar el formulario
            searchForm.addEventListener('submit', function() {
                const button = this.querySelector('button[type="submit"]');
                if (button) {
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';
                    button.disabled = true;
                }
            });
        }
        
        // Añadir comportamiento para los enlaces de entidades
        const entityLinks = document.querySelectorAll('#search-results a, .children-list a');
        entityLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                // Mostrar indicador de carga al hacer clic en un enlace
                this.classList.add('disabled');
                this.innerHTML += ' <i class="fas fa-spinner fa-spin"></i>';
            });
        });
    });
</script>
@endsection