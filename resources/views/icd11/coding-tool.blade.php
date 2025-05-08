@extends('layouts.app')

@section('content')
<div class="container icd11-container">
    <!-- Contenedor para alertas -->
    <div id="alerts-container"></div>

    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0">Herramienta de Codificación ICD-11</h2>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <p>Esta página integra la herramienta oficial de codificación ICD-11 de la Organización Mundial de la Salud.</p>
                    </div>

                    <!-- Panel de código y diagnóstico seleccionados -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Selección de código y diagnóstico</h5>
                            <p class="text-muted small mt-1 mb-0">Busque y seleccione un diagnóstico en la herramienta de codificación, luego copie el código y la descripción</p>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="selected-code" class="form-label"><strong>Código ICD-11:</strong></label>
                                        <div class="input-group">
                                            <input type="text" id="selected-code" class="form-control" placeholder="Ej: MD12">
                                            <button class="btn btn-outline-secondary" type="button" id="search-code-btn" title="Buscar diagnóstico por código">
                                                <i class="fas fa-search"></i>
                                            </button>
                                            <button class="btn btn-outline-secondary" type="button" id="clear-code-btn">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">Introduzca el código y presione <i class="fas fa-search"></i> para buscar</div>
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <div class="form-group">
                                        <label for="selected-diagnosis" class="form-label"><strong>Diagnóstico:</strong></label>
                                        <div class="input-group">
                                            <input type="text" id="selected-diagnosis" class="form-control" placeholder="Ej: Tos">
                                            <button class="btn btn-outline-secondary" type="button" id="clear-diagnosis-btn">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">Introduzca o copie el diagnóstico desde la herramienta</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12 text-end">
                                    <div id="api-status" class="d-inline-block me-2"></div>
                                    <button id="save-selection-btn" class="btn btn-success">
                                        <i class="fas fa-save"></i> Guardar selección
                                    </button>
                                    <button id="copy-to-clipboard-btn" class="btn btn-outline-primary" data-bs-toggle="tooltip" title="Copiar al portapapeles">
                                        <i class="fas fa-clipboard"></i> Copiar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Instrucciones de uso -->
                    <div class="alert alert-info mb-4" role="alert">
                        <h5><i class="fas fa-info-circle"></i> Instrucciones de uso:</h5>
                        <ol>
                            <li>Utilice la herramienta de búsqueda ICD-11 a continuación para encontrar el diagnóstico deseado</li>
                            <li>Cuando encuentre el código correcto, selecciónelo en la herramienta</li>
                            <li>Copie el código (ej: MD12) en el campo correspondiente y haga clic en <i class="fas fa-search"></i> para obtener el diagnóstico automáticamente</li>
                            <li>También puede ingresar manualmente el diagnóstico si lo prefiere</li>
                            <li>Haga clic en "Guardar selección" para utilizar estos valores</li>
                        </ol>
                        <p class="mb-0"><strong>Consejo:</strong> Puede ver el código seleccionado en la barra de estado de la herramienta donde dice "Seleccionado: XXX"</p>
                    </div>

                    <!-- Contenedor para la herramienta de codificación -->
                    <div class="coding-tool-container" style="height: 700px; margin-bottom: 20px; position: relative;">
                        <!-- Spinner de carga -->
                        <div id="loading-spinner" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; justify-content: center; align-items: center; background-color: #f8f9fa; z-index: 1000;">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2">Cargando herramienta de codificación ICD-11...</p>
                            </div>
                        </div>

                        <!-- iframe con la herramienta oficial de codificación de la OMS -->
                        <iframe
                            id="coding-tool-iframe"
                            src="https://icd.who.int/ct/icd11_mms/es/2022-02"
                            style="width: 100%; height: 100%; border: 1px solid #ddd;"
                            onload="iframeLoaded()"
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
            '<h4 class="alert-heading">Error al cargar la herramienta de codificación</h4>' +
            '<p>No se pudo cargar la herramienta de codificación desde el servidor de la OMS.</p>' +
            '<hr>' +
            '<p class="mb-0">Sugerencias:' +
            '<ul>' +
            '<li>Verifique su conexión a Internet</li>' +
            '<li>Compruebe que los servidores de la OMS estén accesibles</li>' +
            '<li>Intente recargar la página</li>' +
            '</ul></p></div>';
    }

    function iframeLoaded() {
        // Ocultar el spinner de carga
        document.getElementById('loading-spinner').style.display = 'none';

        // Configurar eventos para los botones
        setupButtonEvents();
    }

    function setupButtonEvents() {
        // Botón para buscar código
        document.getElementById('search-code-btn').addEventListener('click', function() {
            searchCodeFromApi();
        });

        // Evento para buscar al presionar Enter en el campo de código
        document.getElementById('selected-code').addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchCodeFromApi();
            }
        });

        // Botón para limpiar código
        document.getElementById('clear-code-btn').addEventListener('click', function() {
            document.getElementById('selected-code').value = '';
            document.getElementById('selected-code').focus();
        });

        // Botón para limpiar diagnóstico
        document.getElementById('clear-diagnosis-btn').addEventListener('click', function() {
            document.getElementById('selected-diagnosis').value = '';
            document.getElementById('selected-diagnosis').focus();
        });

        // Botón para guardar selección
        document.getElementById('save-selection-btn').addEventListener('click', function() {
            const code = document.getElementById('selected-code').value.trim();
            const diagnosis = document.getElementById('selected-diagnosis').value.trim();

            if (!code) {
                showAlert('warning', 'Por favor, introduzca un código ICD-11.');
                document.getElementById('selected-code').focus();
                return;
            }

            if (!diagnosis) {
                showAlert('warning', 'Por favor, introduzca un diagnóstico.');
                document.getElementById('selected-diagnosis').focus();
                return;
            }

            // Aquí puedes añadir el código para guardar o procesar la selección
            // Por ejemplo, enviarla a un servidor, mostrarla en otra parte, etc.

            // Por ahora, solo mostramos un mensaje de éxito
            showAlert('success', 'Selección guardada: ' + code + ' - ' + diagnosis);

            // Destacar los campos brevemente
            flashElement(document.getElementById('selected-code'));
            flashElement(document.getElementById('selected-diagnosis'));
        });

        // Botón para copiar al portapapeles
        document.getElementById('copy-to-clipboard-btn').addEventListener('click', function() {
            const code = document.getElementById('selected-code').value.trim();
            const diagnosis = document.getElementById('selected-diagnosis').value.trim();

            if (!code && !diagnosis) {
                showAlert('warning', 'No hay datos para copiar.');
                return;
            }

            const textToCopy = `${code} - ${diagnosis}`;

            // Copiar al portapapeles
            navigator.clipboard.writeText(textToCopy).then(function() {
                showAlert('success', 'Copiado al portapapeles: ' + textToCopy);
            }, function(err) {
                console.error('Error al copiar: ', err);
                showAlert('danger', 'No se pudo copiar al portapapeles. Por favor, copie manualmente.');
            });
        });
    }

    function searchCodeFromApi() {
        const code = document.getElementById('selected-code').value.trim();
        if (!code) {
            showAlert('warning', 'Por favor, introduzca un código ICD-11 para buscar.');
            document.getElementById('selected-code').focus();
            return;
        }

        // Mostrar que estamos buscando
        const apiStatus = document.getElementById('api-status');
        apiStatus.innerHTML = '<span class="badge bg-info"><i class="fas fa-spinner fa-spin"></i> Buscando...</span>';

        // Hacer la petición a la API
        fetch(`/api/icd11/code/${code}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    // Actualizar el campo de diagnóstico con el resultado
                    document.getElementById('selected-diagnosis').value = data.data.title || '';

                    // Guardar los datos completos en un atributo para uso posterior si es necesario
                    document.getElementById('selected-code').setAttribute('data-full-entity',
                                                                       JSON.stringify(data.data));

                    // Mostrar resultado positivo
                    apiStatus.innerHTML = '<span class="badge bg-success"><i class="fas fa-check"></i> Encontrado</span>';

                    // Destacar los campos brevemente
                    flashElement(document.getElementById('selected-code'));
                    flashElement(document.getElementById('selected-diagnosis'));

                    // Eliminar el estado después de un tiempo
                    setTimeout(() => {
                        apiStatus.innerHTML = '';
                    }, 3000);
                } else {
                    // Mostrar error
                    apiStatus.innerHTML = '<span class="badge bg-danger"><i class="fas fa-times"></i> No encontrado</span>';
                    document.getElementById('selected-diagnosis').value = '';
                    showAlert('warning', `No se encontró el código ICD-11 "${code}". Por favor, verifique e intente de nuevo.`);

                    // Eliminar el estado después de un tiempo
                    setTimeout(() => {
                        apiStatus.innerHTML = '';
                    }, 3000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                apiStatus.innerHTML = '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i> Error</span>';
                showAlert('danger', 'Error al consultar la API. Por favor, intente más tarde.');

                // Eliminar el estado después de un tiempo
                setTimeout(() => {
                    apiStatus.innerHTML = '';
                }, 3000);
            });
    }

    function showAlert(type, message) {
        // Crear el elemento de alerta
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.role = 'alert';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

        // Encontrar el contenedor de alertas
        const container = document.getElementById('alerts-container');

        // Insertar la alerta al inicio del contenedor
        container.appendChild(alertDiv);

        // Eliminar la alerta después de 5 segundos
        setTimeout(() => {
            alertDiv.classList.remove('show');
            setTimeout(() => {
                if (container.contains(alertDiv)) {
                    container.removeChild(alertDiv);
                }
            }, 150);
        }, 5000);
    }

    function flashElement(element) {
        // Añadir clase para destacar brevemente el elemento
        element.classList.add('bg-success', 'text-white');

        // Eliminar la clase después de un breve período
        setTimeout(function() {
            element.classList.remove('bg-success', 'text-white');
        }, 1000);
    }

    // Verificar si el iframe se cargó correctamente después de un tiempo
    window.addEventListener('load', function() {
        setTimeout(function() {
            const iframe = document.getElementById('coding-tool-iframe');
            if (iframe && document.getElementById('loading-spinner').style.display !== 'none') {
                handleIframeError();
            }
        }, 15000); // Esperar 15 segundos como máximo

        // Inicializar tooltips de Bootstrap si está disponible
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    });
</script>
@endsection
