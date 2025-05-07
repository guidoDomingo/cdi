{{-- Componente para mostrar resultados de búsqueda ICD-11 --}}
@if(isset($results) && count($results) > 0)
    <div class="search-results-container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3><i class="fas fa-list"></i> Resultados de la búsqueda</h3>
            <span class="badge bg-primary">{{ count($results) }} resultados</span>
        </div>
        
        <div id="search-results" class="list-group">
            @foreach($results as $item)
            <a href="{{ route('icd11.entity', ['entityId' => $item['id'] ?? '']) }}" class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1">{{ $item['title'] ?? 'Sin título' }}</h5>
                    <span class="entity-code">{{ $item['code'] ?? '' }}</span>
                </div>
                @if(isset($item['description']) && !empty($item['description']))
                    <p class="mb-1">{{ $item['description'] }}</p>
                @elseif(isset($item['definition']) && !empty($item['definition']))
                    <p class="mb-1">{{ $item['definition'] }}</p>
                @else
                    <p class="mb-1 text-muted">Sin descripción disponible</p>
                @endif
                
                @if(isset($item['chapter']) && !empty($item['chapter']))
                    <small class="text-muted">
                        <i class="fas fa-book"></i> Capítulo: {{ $item['chapter'] }}
                    </small>
                @endif
            </a>
            @endforeach
        </div>
        
        <div class="mt-3 text-center">
            <p class="text-muted">Haga clic en un resultado para ver más detalles</p>
        </div>
    </div>
@else
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> No se encontraron resultados para la búsqueda "{{ $query ?? '' }}".
    </div>
@endif