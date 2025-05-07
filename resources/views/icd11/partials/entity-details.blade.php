{{-- Componente para mostrar detalles de una entidad ICD-11 --}}
@if(isset($entity))
<div class="entity-details-container">
    <h3><i class="fas fa-info-circle"></i> Detalles de la entidad</h3>
    
    @if(isset($entity['ancestors']) && count($entity['ancestors']) > 0)
    <div class="breadcrumb-navigation">
        <a href="{{ route('icd11.index') }}"><i class="fas fa-home"></i> Inicio</a>
        @foreach($entity['ancestors'] as $ancestor)
            <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
            <a href="{{ route('icd11.entity', ['entityId' => $ancestor['id'] ?? '']) }}">{{ $ancestor['title'] ?? 'Ancestro' }}</a>
        @endforeach
        <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
        <span>{{ $entity['title'] ?? 'Entidad actual' }}</span>
    </div>
    @endif
    
    <div class="card">
        <div class="card-header bg-info text-white">
            <h4 class="mb-0">{{ $entity['title'] ?? 'Sin título' }}</h4>
            <span class="entity-code">{{ $entity['code'] ?? '' }}</span>
        </div>
        <div class="card-body">
            <div class="entity-section">
                @if(isset($entity['description']))
                <p><strong>Descripción:</strong> {{ $entity['description'] }}</p>
                @endif
                
                @if(isset($entity['definition']))
                <p><strong>Definición:</strong> {{ $entity['definition'] }}</p>
                @endif
            </div>
            
            <div class="entity-section">
                @if(isset($entity['inclusions']) && count($entity['inclusions']) > 0)
                <p><strong>Inclusiones:</strong></p>
                <ul>
                    @foreach($entity['inclusions'] as $inclusion)
                    <li>{{ $inclusion }}</li>
                    @endforeach
                </ul>
                @endif
                
                @if(isset($entity['exclusions']) && count($entity['exclusions']) > 0)
                <p><strong>Exclusiones:</strong></p>
                <ul>
                    @foreach($entity['exclusions'] as $exclusion)
                    <li>{{ $exclusion }}</li>
                    @endforeach
                </ul>
                @endif
            </div>
            
            <div class="hierarchy-container">
                <h5><i class="fas fa-sitemap"></i> Clasificación jerárquica</h5>
                @if(isset($children) && count($children) > 0)
                    <div class="children-list list-group">
                        @foreach($children as $child)
                        <a href="{{ route('icd11.entity', ['entityId' => $child['id'] ?? '']) }}" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">{{ $child['title'] ?? 'Sin título' }}</h6>
                                <span class="entity-code">{{ $child['code'] ?? '' }}</span>
                            </div>
                        </a>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-info">Esta entidad no tiene subcategorías o aún no se han cargado.</div>
                    <a href="{{ route('icd11.children', ['entityId' => $entityId]) }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-sync-alt"></i> Cargar subcategorías
                    </a>
                @endif
            </div>
            
            <div class="additional-info mt-4">
                <h5><i class="fas fa-info-circle"></i> Información adicional</h5>
                <p>Esta información es proporcionada por la Clasificación Internacional de Enfermedades (ICD-11) de la Organización Mundial de la Salud.</p>
                <a href="https://icd.who.int/browse11/l-m/es" target="_blank" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-external-link-alt"></i> Ver en sitio oficial de la OMS
                </a>
            </div>
        </div>
    </div>
</div>
@endif