/**
 * Tester para la API de enfermedades detalladas ICD-11
 * Este script permite probar la nueva API para obtener información detallada
 * de enfermedades por código
 */

// Función para buscar y mostrar los detalles de una enfermedad
function searchDisease() {
    // Obtener el código ingresado
    const codeInput = document.getElementById('disease-code');
    const resultDiv = document.getElementById('api-result');
    const statusDiv = document.getElementById('api-status');
    const code = codeInput.value.trim();

    // Validar que el código no esté vacío
    if (!code) {
        showStatus('warning', 'Por favor, ingrese un código ICD-11 válido');
        return;
    }

    // Mostrar que estamos buscando
    showStatus('info', `<i class="fas fa-spinner fa-spin"></i> Buscando información para el código ${code}...`);
    resultDiv.innerHTML = '';

    // Realizar la petición a la API
    fetch(`/api/icd11/disease/${code}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showStatus('success', `<i class="fas fa-check"></i> Información encontrada para ${code}`);
                displayResult(data.data);
            } else {
                showStatus('danger', `<i class="fas fa-times"></i> ${data.message || 'Error al buscar el código'}`);
                resultDiv.innerHTML = '<div class="alert alert-warning">No se encontraron datos para este código</div>';
            }
        })
        .catch(error => {
            console.error('Error al consultar la API:', error);
            showStatus('danger', `<i class="fas fa-exclamation-triangle"></i> Error al consultar la API`);
            resultDiv.innerHTML = '<div class="alert alert-danger">Error al procesar la solicitud. Consulte la consola para más detalles.</div>';
        });
}

// Muestra el estado de la petición
function showStatus(type, message) {
    const statusDiv = document.getElementById('api-status');
    const statusMessageSpan = document.getElementById('api-status-message');

    // Configurar el tipo de alerta
    statusDiv.className = `alert alert-${type}`;

    // Actualizar el mensaje
    statusMessageSpan.innerHTML = message;

    // Asegurarse de que sea visible
    statusDiv.classList.remove('d-none');

    // Configurar el ícono según el tipo de mensaje
    const iconElement = statusDiv.querySelector('i');
    if (iconElement) {
        // Eliminar clases anteriores
        iconElement.className = '';

        // Añadir clases según el tipo
        switch (type) {
            case 'info':
                iconElement.className = 'fas fa-info-circle fa-2x';
                break;
            case 'success':
                iconElement.className = 'fas fa-check-circle fa-2x';
                break;
            case 'warning':
                iconElement.className = 'fas fa-exclamation-triangle fa-2x';
                break;
            case 'danger':
                iconElement.className = 'fas fa-times-circle fa-2x';
                break;
            default:
                iconElement.className = 'fas fa-info-circle fa-2x';
        }
    }
}

// Muestra los resultados de la API formateados
function displayResult(data) {
    const resultDiv = document.getElementById('api-result');
    let output = '<div class="result-container">';

    // Información básica
    output += `<div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">Información básica</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <p><strong>Código:</strong> ${data.code || 'No disponible'}</p>
                </div>
                <div class="col-md-8">
                    <p><strong>Título:</strong> ${data.title || 'No disponible'}</p>
                </div>
            </div>`;

    // Descripción si está disponible
    if (data.description || data.longDefinition || data.definition) {
        const description = data.description ||
                           (data.longDefinition ? data.longDefinition.content : null) ||
                           (data.definition ? data.definition.content : null);

        if (description) {
            output += `<div class="mt-3">
                <h4>Descripción:</h4>
                <p>${description}</p>
            </div>`;
        }
    }

    output += `</div></div>`;

    // Términos relacionados si existen
    if (data.inclusion && data.inclusion.length > 0) {
        output += `<div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h3 class="mb-0">Términos relacionados</h3>
            </div>
            <div class="card-body">
                <ul class="list-group">`;

        data.inclusion.forEach(term => {
            if (term.label) {
                output += `<li class="list-group-item">${term.label}</li>`;
            }
        });

        output += `</ul>
            </div>
        </div>`;
    }

    // Exclusiones si existen
    if (data.exclusion && data.exclusion.length > 0) {
        output += `<div class="card mb-4">
            <div class="card-header bg-warning">
                <h3 class="mb-0">Exclusiones</h3>
            </div>
            <div class="card-body">
                <ul class="list-group">`;

        data.exclusion.forEach(term => {
            if (term.label) {
                output += `<li class="list-group-item">${term.label}</li>`;
            }
        });

        output += `</ul>
            </div>
        </div>`;
    }

    // Datos técnicos (URIs)
    if (data.uri || data.foundationUri || data.linearizationUri) {
        output += `<div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h3 class="mb-0">Datos técnicos</h3>
            </div>
            <div class="card-body small">
                <ul class="list-group">`;

        if (data.uri) {
            output += `<li class="list-group-item"><strong>URI:</strong> ${data.uri}</li>`;
        }

        if (data.foundationUri) {
            output += `<li class="list-group-item"><strong>Foundation URI:</strong> ${data.foundationUri}</li>`;
        }

        if (data.linearizationUri) {
            output += `<li class="list-group-item"><strong>Linearization URI:</strong> ${data.linearizationUri}</li>`;
        }

        output += `</ul>
            </div>
        </div>`;
    }

    // JSON completo
    output += `<div class="card">
        <div class="card-header bg-dark text-white">
            <h3 class="mb-0">JSON completo</h3>
        </div>
        <div class="card-body">
            <pre class="json-code">${JSON.stringify(data, null, 2)}</pre>
        </div>
    </div>`;

    output += '</div>';

    resultDiv.innerHTML = output;
}

// Inicializar eventos cuando el documento esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Asignar evento al botón de búsqueda
    const searchButton = document.getElementById('search-disease-btn');
    if (searchButton) {
        searchButton.addEventListener('click', function(e) {
            e.preventDefault();
            searchDisease();
        });
    }

    // Permitir búsqueda al presionar Enter en el campo de código
    const codeInput = document.getElementById('disease-code');
    if (codeInput) {
        codeInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchDisease();
            }
        });
    }

    // Configurar los botones de ejemplo
    const exampleButtons = document.querySelectorAll('.example-code');
    exampleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const code = this.getAttribute('data-code');
            if (code) {
                // Establecer el código en el campo de entrada
                document.getElementById('disease-code').value = code;

                // Ejecutar la búsqueda automáticamente
                searchDisease();

                // Resaltar el botón seleccionado
                exampleButtons.forEach(btn => btn.classList.remove('active', 'btn-primary', 'text-white'));
                this.classList.add('active', 'btn-primary', 'text-white');
            }
        });
    });
});
