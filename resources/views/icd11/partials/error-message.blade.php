{{-- Componente para mostrar mensajes de error en la interfaz ICD-11 --}}
@if(isset($error))
<div class="alert alert-danger">
    <div class="d-flex align-items-center">
        <i class="fas fa-exclamation-circle me-2"></i>
        <div>
            <strong>Error:</strong> {{ $error }}
        </div>
    </div>
</div>
@endif