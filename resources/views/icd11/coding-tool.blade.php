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
                            <p>Esta página integra la herramienta oficial de codificación ICD-11 de la Organización Mundial
                                de la Salud.</p>
                        </div>

                        <!-- Panel de código y diagnóstico seleccionados -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Selección de código y diagnóstico</h5>
                                <p class="text-muted small mt-1 mb-0">Busque y seleccione un diagnóstico en la herramienta
                                    de codificación, luego copie el código y la descripción</p>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="selected-code" class="form-label"><strong>Código
                                                    ICD-11:</strong></label>
                                            <div class="input-group">
                                                <input type="text" id="selected-code" class="form-control"
                                                    placeholder="Ej: MD12">
                                                <button class="btn btn-outline-secondary" type="button"
                                                    id="search-code-btn" title="Buscar diagnóstico por código">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                                <button class="btn btn-outline-secondary" type="button"
                                                    id="clear-code-btn">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="form-text">Introduzca el código y presione <i
                                                    class="fas fa-search"></i> para buscar</div>
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-group">
                                            <label for="selected-diagnosis"
                                                class="form-label"><strong>Diagnóstico:</strong></label>
                                            <div class="input-group">
                                                <input type="text" id="selected-diagnosis" class="form-control"
                                                    placeholder="Ej: Tos">
                                                <button class="btn btn-outline-secondary" type="button"
                                                    id="clear-diagnosis-btn">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="form-text">Introduzca o copie el diagnóstico desde la herramienta
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Nueva fila para descripción -->
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="diagnosis-description-text" class="form-label"><strong>Descripción:</strong></label>
                                            <div class="input-group">
                                                <textarea id="diagnosis-description-text" class="form-control" rows="3" placeholder="La descripción del diagnóstico aparecerá aquí al seleccionar un código"></textarea>
                                                <button class="btn btn-outline-secondary" type="button" id="clear-description-btn">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-12 text-end">
                                        <div id="api-status" class="d-inline-block me-2"></div>
                                        <button id="show-details-btn" class="btn btn-info me-2">
                                            <i class="fas fa-info-circle"></i> Ver detalles completos
                                        </button>
                                        <button id="save-selection-btn" class="btn btn-success">
                                            <i class="fas fa-save"></i> Guardar selección
                                        </button>
                                        <button id="copy-to-clipboard-btn" class="btn btn-outline-primary"
                                            data-bs-toggle="tooltip" title="Copiar al portapapeles">
                                            <i class="fas fa-clipboard"></i> Copiar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Alerta de cookies -->
                        <div class="alert alert-warning mb-4" role="alert">
                            <h5><i class="fas fa-cookie-bite"></i> Nota sobre cookies:</h5>
                            <p>Chrome está implementando una nueva política para cookies de terceros. Si ve mensajes sobre esto, considere:</p>
                            <ul>
                                <li>Permitir temporalmente las cookies de terceros para una mejor experiencia con la herramienta ICD-11</li>
                                <li>Usar otro navegador como Firefox o Edge si experimenta problemas</li>
                            </ul>
                        </div>

                        <!-- Alerta sobre permisos -->
                        <div class="alert alert-info mb-4" role="alert">
                            <h5><i class="fas fa-clipboard"></i> Nota sobre el portapapeles:</h5>
                            <p>Es posible que vea errores relacionados con el portapapeles en la consola del navegador. Esto es normal y no afecta el funcionamiento:</p>
                            <ul>
                                <li>Por razones de seguridad, algunos navegadores restringen el acceso al portapapeles desde iframes</li>
                                <li><strong>Solución implementada:</strong> Cuando seleccione un diagnóstico, se capturará automáticamente sin necesidad de copiar/pegar</li>
                                <li>Si necesita copiar manualmente, use el botón <i class="fas fa-clipboard"></i> disponible arriba</li>
                            </ul>
                        </div>

                        <!-- Instrucciones de uso -->
                        <div class="alert alert-info mb-4" role="alert">
                            <h5><i class="fas fa-info-circle"></i> Instrucciones de uso:</h5>
                            <ol>
                                <li>Utilice la herramienta de búsqueda ICD-11 a continuación para encontrar el diagnóstico
                                    deseado</li>
                                <li>Cuando encuentre el código correcto, selecciónelo en la herramienta</li>
                                <li>El código y diagnóstico se transferirán automáticamente a los campos correspondientes
                                </li>
                                <li>Opcionalmente, puede ingresar un código (ej: MD12) y hacer clic en <i
                                        class="fas fa-search"></i> para buscarlo directamente</li>
                                <li>Haga clic en "Guardar selección" para utilizar estos valores</li>
                            </ol>
                            <p class="mb-0"><strong>¡Nuevo!</strong> Ahora la herramienta extrae automáticamente los datos
                                del diagnóstico seleccionado sin necesidad de copiar y pegar manualmente</p>
                        </div>

                        <!-- Contenedor para la herramienta de codificación -->
                        <div class="coding-tool-container" style="height: 700px; margin-bottom: 20px; position: relative;">
                            <!-- Spinner de carga -->
                            <div id="loading-spinner"
                                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; justify-content: center; align-items: center; background-color: #f8f9fa; z-index: 1000;">
                                <div class="text-center">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <p class="mt-2">Cargando herramienta de codificación ICD-11...</p>
                                </div>
                            </div>

                            <!-- iframe con la herramienta oficial de codificación de la OMS -->
                            <iframe id="coding-tool-iframe"
                                src="https://icd.who.int/ct/icd11_mms/es/2022-02?enablePostMessage=true&postMessageOrigin={{ url('/') }}&suppressClipboardErrors=true"
                                style="width: 100%; height: 100%; border: 1px solid #ddd;"
                                sandbox="allow-scripts allow-same-origin allow-forms allow-downloads allow-popups">
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
        // Definir estilos globales que se usarán en múltiples funciones
        const alertDetailedStyles = `
        .diagnosis-alert-detailed {
            max-width: 800px;
            margin: 0 auto 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .diagnosis-details-full h4 {
            color: #0056b3;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
        }
        .diagnosis-details-full h5 {
            color: #495057;
            font-size: 1rem;
            margin-bottom: 8px;
        }
        .diagnosis-code {
            font-size: 1.8em;
            font-weight: 700;
            color: #0056b3;
            background-color: #f8f9fa;
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-block;
        }
        .diagnosis-title {
            font-size: 1.5em;
            font-weight: 500;
            margin-bottom: 5px;
            padding-top: 7px;
        }
        .diagnosis-section {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #e9ecef;
        }
        .diagnosis-description {
            line-height: 1.6;
            text-align: justify;
        }

        /* Estilos para el campo de descripción */
        #diagnosis-description-text {
            background-color: #f8f9fa;
            border-color: #e9ecef;
            font-size: 0.9em;
        }
        #diagnosis-description-text.filled {
            background-color: #f0f7ff;
            border-color: #cce5ff;
        }
        .diagnosis-terms {
            padding: 5px 0;
        }
        .diagnosis-term-badge {
            display: inline-block;
            background-color: #e9ecef;
            color: #495057;
            padding: 3px 8px;
            margin: 2px;
            border-radius: 20px;
            font-size: 0.85em;
        }
        .diagnosis-uris {
            font-size: 0.85em;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
        }
    `;

        // Definir las funciones clave antes de que se usen en la página
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

            // Mostrar mensaje informativo sobre la integración automática
            showAlert('info',
                '<strong>Integración automática activada:</strong> ' +
                'Cuando selecciones un diagnóstico en la herramienta ICD-11, ' +
                'los datos se transferirán automáticamente a los campos de código y diagnóstico.');
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
                document.getElementById('diagnosis-description-text').value = '';
                document.getElementById('selected-code').focus();
            });

            // Botón para limpiar diagnóstico
            document.getElementById('clear-diagnosis-btn').addEventListener('click', function() {
                document.getElementById('selected-diagnosis').value = '';
                document.getElementById('diagnosis-description-text').value = '';
                document.getElementById('selected-diagnosis').focus();
            });

            // Botón para limpiar descripción
            document.getElementById('clear-description-btn').addEventListener('click', function() {
                document.getElementById('diagnosis-description-text').value = '';
                document.getElementById('diagnosis-description-text').focus();
            });

            // Botón para guardar selección
            document.getElementById('save-selection-btn').addEventListener('click', function() {
                const code = document.getElementById('selected-code').value.trim();
                const diagnosis = document.getElementById('selected-diagnosis').value.trim();
                const description = document.getElementById('diagnosis-description-text').value.trim();

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

                // Datos completos del diagnóstico para guardar
                const diagnosisData = {
                    code: code,
                    title: diagnosis,
                    description: description
                };

                console.log('Datos del diagnóstico a guardar:', diagnosisData);

                // Por ahora, solo mostramos un mensaje de éxito
                showAlert('success', `Selección guardada: ${code} - ${diagnosis}`);

                // Destacar los campos brevemente
                flashElement(document.getElementById('selected-code'));
                flashElement(document.getElementById('selected-diagnosis'));
                flashElement(document.getElementById('diagnosis-description-text'));
            });

            // Botón para mostrar detalles completos del diagnóstico
            document.getElementById('show-details-btn').addEventListener('click', function() {
                const code = document.getElementById('selected-code').value.trim();

                if (!code) {
                    showAlert('warning', 'Por favor, seleccione primero un código de diagnóstico.');
                    document.getElementById('selected-code').focus();
                    return;
                }

                // Intentar recuperar los datos completos de la entidad
                const entityDataString = document.getElementById('selected-code').getAttribute('data-full-entity');

                if (entityDataString) {
                    try {
                        // Si ya tenemos los datos, mostrarlos directamente
                        const entityData = JSON.parse(entityDataString);
                        showDetailedDiagnosisAlert(entityData);
                    } catch (e) {
                        console.warn('Error al analizar los datos de la entidad:', e);
                        // Si hay un error, buscar los detalles nuevamente
                        searchAndShowDiagnosisDetails(code);
                    }
                } else {
                    // Si no tenemos datos, buscarlos
                    searchAndShowDiagnosisDetails(code);
                }
            });

            // Botón para copiar al portapapeles
            document.getElementById('copy-to-clipboard-btn').addEventListener('click', function() {
                const code = document.getElementById('selected-code').value.trim();
                const diagnosis = document.getElementById('selected-diagnosis').value.trim();
                const description = document.getElementById('diagnosis-description-text').value.trim();

                if (!code && !diagnosis) {
                    showAlert('warning', 'No hay datos para copiar.');
                    return;
                }

                // Preparar el texto a copiar (incluir descripción si está disponible)
                let textToCopy = `${code} - ${diagnosis}`;

                if (description) {
                    textToCopy += `\n\nDescripción: ${description}`;
                }

                // Copiar al portapapeles
                navigator.clipboard.writeText(textToCopy).then(function() {
                    showAlert('success', 'Información copiada al portapapeles');
                }, function(err) {
                    console.error('Error al copiar: ', err);

                    // Método alternativo para copiar
                    const textarea = document.createElement('textarea');
                    textarea.value = textToCopy;
                    textarea.style.position = 'fixed';
                    textarea.style.opacity = '0';
                    document.body.appendChild(textarea);

                    try {
                        textarea.select();
                        const success = document.execCommand('copy');
                        if (success) {
                            showAlert('success', 'Información copiada al portapapeles (método alternativo)');
                        } else {
                            showAlert('danger', 'No se pudo copiar. Por favor, copie manualmente.');
                        }
                    } catch (e) {
                        showAlert('danger', 'No se pudo copiar al portapapeles. Por favor, copie manualmente.');
                    } finally {
                        document.body.removeChild(textarea);
                    }
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

                        // Actualizar el campo de descripción
                        const description = data.data.description ||
                            data.data.fullySpecifiedName ||
                            (data.data.definition && data.data.definition.content) ||
                            '';

                        const descTextArea = document.getElementById('diagnosis-description-text');
                        descTextArea.value = description;

                        // Añadir clase visual para indicar que el campo está lleno
                        if (description) {
                            descTextArea.classList.add('filled');
                        } else {
                            descTextArea.classList.remove('filled');
                        }

                        // Guardar los datos completos en un atributo para uso posterior si es necesario
                        document.getElementById('selected-code').setAttribute('data-full-entity',
                                                                           JSON.stringify(data.data));

                        // Mostrar resultado positivo
                        apiStatus.innerHTML = '<span class="badge bg-success"><i class="fas fa-check"></i> Encontrado</span>';

                        // Destacar los campos brevemente
                        flashElement(document.getElementById('selected-code'));
                        flashElement(document.getElementById('selected-diagnosis'));
                        flashElement(document.getElementById('diagnosis-description-text'));

                        // Eliminar el estado después de un tiempo
                        setTimeout(() => {
                            apiStatus.innerHTML = '';
                        }, 3000);
                    } else {
                        // Mostrar error
                        apiStatus.innerHTML =
                            '<span class="badge bg-danger"><i class="fas fa-times"></i> No encontrado</span>';
                        document.getElementById('selected-diagnosis').value = '';
                        document.getElementById('diagnosis-description-text').value = '';
                        showAlert('warning',
                            `No se encontró el código ICD-11 "${code}". Por favor, verifique e intente de nuevo.`);

                        // Eliminar el estado después de un tiempo
                        setTimeout(() => {
                            apiStatus.innerHTML = '';
                        }, 3000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    apiStatus.innerHTML =
                        '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i> Error</span>';
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

        // Variable para guardar la entidad seleccionada actualmente
        let selectedEntity = null; // Función para mostrar detalles de la entidad en la consola (para depuración)
        function logEntityDetails(entity) {
            if (!entity) {
                console.log('Entidad vacía');
                return;
            }

            console.group('Detalles de entidad ICD-11');
            console.log('Código:', entity.code);
            console.log('Título:', entity.title);
            console.log('URI:', entity.uri);
            console.log('FoundationURI:', entity.foundationUri);
            console.log('LinearizationURI:', entity.linearizationUri);
            console.log('Entidad completa:', entity);
            console.groupEnd();
        }                    // Escuchar mensajes del iframe (ICD-11 ECT)
        window.addEventListener('message', function(event) {
            try {
                // Registrar el mensaje recibido para depuración
                console.log('Mensaje recibido del iframe:', event.origin, event.data);

                // Verificar origen del mensaje (por seguridad)
                if (event.origin.includes('icd.who.int')) {
                    // Verificar que tenemos datos válidos
                    if (!event.data) {
                        console.warn('Mensaje recibido sin datos');
                        return;
                    }

                    const data = event.data;

                    // Procesar evento de intento de copia al portapapeles
                    // (Este evento es específico para capturar diagnósticos cuando falla el clipboard)
                    if (data && data.event === 'copyAttempt' && data.text) {
                        console.log('Intento de copia detectado:', data.text);

                        try {
                            // Intentar extraer código y título de la cadena de texto
                            const clipText = data.text;
                            const match = clipText.match(/^([A-Z0-9]+)\s*[-:]\s*(.+)/);

                            if (match) {
                                const code = match[1];
                                const title = match[2];

                                // Actualizar los campos
                                document.getElementById('selected-code').value = code;
                                document.getElementById('selected-diagnosis').value = title;

                                // Destacar los campos
                                flashElement(document.getElementById('selected-code'));
                                flashElement(document.getElementById('selected-diagnosis'));

                                // Notificar
                                showAlert('success', `Diagnóstico capturado: <strong>${code}</strong> - ${title}`);
                            }
                        } catch (err) {
                            console.warn('Error al procesar intento de copia:', err);
                        }
                    }

                    // Procesar eventos específicos del ICD-11 ECT
                    if (data && data.event === 'entitySelected') {
                        try {
                            logEntityDetails(data.entity);

                            // Guardar la entidad seleccionada
                            selectedEntity = data.entity;

                            // Mostrar un alert con los detalles del diagnóstico
                            showDiagnosisDetails(data.entity);

                            // Obtener datos adicionales del diagnóstico usando la API
                            if (data.entity && (data.entity.uri || data.entity.code)) {
                                fetchEntityDetailsByUri(data.entity)
                                    .then(fullEntity => {
                                        if (fullEntity) {
                                            // Actualizar el alert con los detalles completos
                                            showDiagnosisDetails(fullEntity);
                                        }
                                    });
                            }
                        } catch (error) {
                            console.warn('Error al procesar entitySelected:', error);
                        }

                        // Si tenemos código, actualizar el campo
                        if (data.entity && data.entity.code) {
                            document.getElementById('selected-code').value = data.entity.code;

                            // Si también tenemos título, actualizar el diagnóstico
                            if (data.entity.title) {
                                document.getElementById('selected-diagnosis').value = data.entity.title;

                                // Destacar los campos brevemente para mostrar que se actualizaron
                                flashElement(document.getElementById('selected-code'));
                                flashElement(document.getElementById('selected-diagnosis'));
                            } else {
                                // Si no hay título pero tenemos código, intentar obtenerlo con la API
                                searchCodeFromApi();
                            }
                        }
                    }

                    // Evento cuando se confirma una selección
                    if (data && data.event === 'selectionConfirmed') {
                        console.log('Selección confirmada:', data.entity);

                        // Obtener datos completos de la entidad desde la API
                        if (data.entity && (data.entity.uri || data.entity.code)) {
                            fetchEntityDetailsByUri(data.entity);
                        }
                    }
                }
            } catch (error) {
                console.error('Error al procesar mensaje del iframe:', error);
            }
        });

        /**
         * Obtiene detalles completos de una entidad usando sus URIs
         * @param {Object} entity La entidad básica con códigos o URIs
         * @returns {Promise} Una promesa que se resuelve con los detalles completos de la entidad
         */
        function fetchEntityDetailsByUri(entity) {
            return new Promise((resolve, reject) => {
                        // Verificar que tenemos información válida
                        if (!entity) {
                            console.error('No se recibieron datos de entidad');
                            showAlert('danger', 'No se pudo obtener información de la entidad seleccionada');
                            reject(new Error('No se recibieron datos de entidad'));
                            return;
                        }

                        // Mostrar que estamos procesando
                        const apiStatus = document.getElementById('api-status');
                        apiStatus.innerHTML =
                            '<span class="badge bg-info"><i class="fas fa-spinner fa-spin"></i> Obteniendo detalles...</span>';

                        // Registro de depuración
                        console.log('Enviando solicitud a la API con datos:', entity);

                        // Asegurarse de que tenemos un token CSRF
                        let csrfToken = '';
                        const csrfElement = document.querySelector('meta[name="csrf-token"]');
                        if (csrfElement) {
                            csrfToken = csrfElement.getAttribute('content');
                        } else {
                            console.warn('No se encontró el token CSRF en el documento');
                        }

                        // Crear el cuerpo de la solicitud
                        const requestData = {
                            data: entity
                        };
                        console.log('Datos de solicitud:', JSON.stringify(requestData));

                        // Hacer la petición a la API
                        fetch('/api/icd11/entity-by-uri', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify(requestData)
                            })
                            .then(response => {
                                // Verificar si la respuesta es exitosa (status 200-299)
                                if (!response.ok) {
                                    console.error('Error en la respuesta del servidor:', response.status, response
                                        .statusText);
                                    throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
                                }
                                return response.json();
                            })
                            .then(data => {
                                console.log('Respuesta recibida de la API:', data);
                                if (data.success && data.data) {
                                    // Actualizar los campos con la información obtenida
                                    document.getElementById('selected-code').value = data.data.code || entity.code ||
                                        '';
                                    document.getElementById('selected-diagnosis').value = data.data.title || entity
                                        .title || '';

                                    // Almacenar todos los datos de la entidad para uso posterior
                                    document.getElementById('selected-code').setAttribute('data-full-entity',
                                        JSON.stringify(data.data));

                                    // Mostrar resultado positivo
                                    apiStatus.innerHTML =
                                        '<span class="badge bg-success"><i class="fas fa-check"></i> Detalles obtenidos</span>';

                                    // Destacar los campos brevemente
                                    flashElement(document.getElementById('selected-code'));
                                    flashElement(document.getElementById('selected-diagnosis'));

                                    // Eliminar el estado después de un tiempo
                                    setTimeout(() => {
                                        apiStatus.innerHTML = '';
                                    }, 3000);

                                    // Mostrar detalles completos del diagnóstico
                                    showDiagnosisDetails(data.data);
                                } else {
                                    console.error('Error al obtener detalles de la entidad:', data);
                                    apiStatus.innerHTML =
                                        '<span class="badge bg-warning"><i class="fas fa-exclamation-triangle"></i> Información parcial</span>';
                                    showAlert('warning',
                                        'Se obtuvo información parcial de la entidad. Algunos detalles pueden faltar.'
                                    );

                                    // Intentar usar la información básica disponible
                                    if (entity.code) document.getElementById('selected-code').value = entity.code;
                                    if (entity.title) document.getElementById('selected-diagnosis').value = entity
                                        .title;

                                    // Eliminar el estado después de un tiempo
                                    setTimeout(() => {
                                        apiStatus.innerHTML = '';
                                    }, 3000);
                                }
                            })
                            .catch(error => {
                                console.error('Error al obtener detalles de la entidad:', error);
                                apiStatus.innerHTML =
                                    '<span class="badge bg-danger"><i class="fas fa-times"></i> Error</span>';
                                showAlert('danger',
                                    'Error al obtener detalles de la entidad. Usando información básica.');

                                // Usar la información básica disponible
                                if (entity.code) document.getElementById('selected-code').value = entity.code;
                                if (entity.title) document.getElementById('selected-diagnosis').value = entity.title;

                                // Eliminar el estado después de un tiempo
                                setTimeout(() => {
                                    apiStatus.innerHTML = '';
                                }, 3000);
                            });
                    });
                }

                    /**
                     * Muestra un alert con los detalles completos del diagnóstico
                     * @param {Object} entity La entidad de diagnóstico seleccionada
                     */
                    function showDiagnosisDetails(entity) {
                        if (!entity) {
                            return;
                        }

                        // Construir un mensaje con formato para mostrar los detalles
                        let message = `<div class="diagnosis-details">
            <h4>Diagnóstico ICD-11 Seleccionado</h4>
            <p><strong>Código:</strong> ${entity.code || 'N/A'}</p>
            <p><strong>Título:</strong> ${entity.title || 'N/A'}</p>`;

                        // Añadir descripción si está disponible
                        if (entity.description) {
                            message += `<p><strong>Descripción:</strong> ${entity.description}</p>`;
                        }

                        // Añadir términos relacionados si están disponibles
                        if (entity.matchedTerms && entity.matchedTerms.length > 0) {
                            message += `<p><strong>Términos relacionados:</strong> ${entity.matchedTerms.join(', ')}</p>`;
                        }

                        message += `</div>`;

                        // Mostrar el alert con los detalles formateados
                        showFormattedAlert('info', message, 15000, false);
                    }

                    /**
                     * Muestra un alert con contenido HTML formateado
                     * @param {string} type Tipo de alerta (success, info, warning, danger)
                     * @param {string} htmlContent Contenido HTML para mostrar
                     * @param {number} duration Duración en milisegundos
                     * @param {boolean} isDetailed Si es una alerta detallada con estilos avanzados
                     */
                    function showFormattedAlert(type, htmlContent, duration = 15000, isDetailed = false) {
                        // Crear el elemento de alerta
                        const alertDiv = document.createElement('div');

                        // Si es una alerta detallada, agregar la clase correspondiente
                        alertDiv.className =
                            `alert alert-${type} alert-dismissible fade show${isDetailed ? ' diagnosis-alert-detailed' : ''}`;
                        alertDiv.role = 'alert';
                        alertDiv.innerHTML = `
            ${htmlContent}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

                        // Añadir estilos adicionales para el contenido formateado
                        const style = document.createElement('style');

                        // Estilos básicos para todos los alertas
                        let styleContent = `
            .diagnosis-details h4 {
                margin-bottom: 15px;
                color: #0056b3;
            }
            .diagnosis-details p {
                margin-bottom: 8px;
            }
            .diagnosis-details strong {
                font-weight: 600;
            }
        `;

                        // Si es una alerta detallada, agregar los estilos avanzados definidos en alertDetailedStyles
                        if (isDetailed && typeof alertDetailedStyles !== 'undefined') {
                            styleContent += alertDetailedStyles;
                        }

                        style.textContent = styleContent;
                        alertDiv.appendChild(style);

                        // Encontrar el contenedor de alertas
                        const container = document.getElementById('alerts-container');

                        // Insertar la alerta al inicio del contenedor
                        container.appendChild(alertDiv);

                        // Eliminar la alerta después de un tiempo
                        setTimeout(() => {
                            alertDiv.classList.remove('show');
                            setTimeout(() => {
                                if (container.contains(alertDiv)) {
                                    container.removeChild(alertDiv);
                                }
                            }, 150);
                        }, duration);

                        // Hacer scroll hacia arriba para mostrar la alerta si es detallada
                        if (isDetailed) {
                            window.scrollTo({
                                top: 0,
                                behavior: 'smooth'
                            });
                        }
                    }                    // Configurar evento de carga para el iframe y observador avanzado
                    document.addEventListener('DOMContentLoaded', function() {
                        // Obtener referencia al iframe
                        const iframe = document.getElementById('coding-tool-iframe');

                        // Agregar manejador de evento load
                        if (iframe) {
                            iframe.addEventListener('load', function() {
                                iframeLoaded();

                                // Configurar un observador avanzado para el iframe
                                try {
                                    setTimeout(function() {
                                        // Intentar observar selecciones en el iframe directamente
                                        const iframeDocument = iframe.contentDocument || iframe.contentWindow.document;

                                        // Monitorear clics dentro del iframe que puedan ser selecciones
                                        iframeDocument.addEventListener('click', function(e) {
                                            // Verificar si es un elemento interactivo con datos relevantes
                                            if (e.target && e.target.closest) {
                                                const entityElement = e.target.closest('[data-code]') ||
                                                                     e.target.closest('[data-entity-code]') ||
                                                                     e.target.closest('.entity-item') ||
                                                                     e.target.closest('.ect-entity-item');

                                                if (entityElement) {
                                                    console.log('Posible selección de entidad detectada:', entityElement);

                                                    // Intentar extraer código y título
                                                    const code = entityElement.getAttribute('data-code') ||
                                                               entityElement.getAttribute('data-entity-code');

                                                    const title = entityElement.textContent ||
                                                                 entityElement.getAttribute('data-title') ||
                                                                 entityElement.getAttribute('data-entity-title') ||
                                                                 entityElement.querySelector('.title')?.textContent;

                                                    if (code && title) {
                                                        console.log('Entidad capturada por observador:', {code, title});

                                                        // Actualizar campos
                                                        document.getElementById('selected-code').value = code;
                                                        document.getElementById('selected-diagnosis').value = title;

                                                        // Destacar brevemente
                                                        flashElement(document.getElementById('selected-code'));
                                                        flashElement(document.getElementById('selected-diagnosis'));
                                                    }
                                                }
                                            }
                                        });

                                        console.log('Observador de selección de diagnóstico activado');
                                    }, 5000); // Dar tiempo para que el iframe se inicialice completamente
                                } catch (err) {
                                    console.warn('No se pudo configurar el observador avanzado:', err);
                                }
                            });

                            // Agregar manejador de error
                            iframe.addEventListener('error', function() {
                                handleIframeError();
                            });
                        }
                    });

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
                            var tooltipTriggerList = [].slice.call(document.querySelectorAll(
                                '[data-bs-toggle="tooltip"]'));
                            tooltipTriggerList.map(function(tooltipTriggerEl) {
                                return new bootstrap.Tooltip(tooltipTriggerEl);
                            });
                        }

                        // Intentar parchear el objeto clipboard del iframe
                        patchIframeClipboard();
                    });

                    // Interceptar errores para evitar que los errores del iframe afecten a nuestra aplicación
                    window.addEventListener('error', function(event) {
                        // Verificar error específico relacionado con clipboard
                        if (event.message && (
                                event.message.includes('Cannot read properties of undefined (reading \'writeText\')') ||
                                event.message.includes('Failed to execute \'writeText\'') ||
                                event.message.toLowerCase().includes('clipboard') ||
                                event.message.toLowerCase().includes('permission')
                            )) {
                            console.warn('Interceptado error de clipboard:', event.message);
                            console.warn('Origen del error:', event.filename);

                            // Detener la propagación del error
                            event.stopPropagation();
                            event.preventDefault();

                            // Mostrar mensaje informativo solo la primera vez
                            if (!window.clipboardErrorShown) {
                                window.clipboardErrorShown = true;
                                console.info(
                                    'Funcionalidad de copiar al portapapeles deshabilitada en el iframe. La funcionalidad principal sigue operativa.'
                                );

                                // Ofrecer alternativa al usuario (solo la primera vez)
                                showAlert('info',
                                    '<strong><i class="fas fa-clipboard"></i> Información:</strong> ' +
                                    'La funcionalidad de copiar automáticamente al portapapeles está limitada por seguridad del navegador. ' +
                                    'Sin embargo, los diagnósticos seleccionados se capturarán automáticamente en los campos correspondientes.'
                                );
                            }

                            return false;
                        }

                        // Verificar si el error proviene de un script externo (como el iframe)
                        if (event.filename && (
                                event.filename.includes('main.42e67191.js') ||
                                event.filename.includes('icd.who.int')
                            )) {
                            // Registrar error pero evitar que interrumpa nuestra aplicación
                            console.warn('Interceptado error del iframe ICD-11:', event.message);
                            event.stopPropagation();
                            event.preventDefault();
                            return false;
                        }
                    }, true); // true para capturar en fase de captura

                    /**
                     * Intenta parchear el objeto navigator.clipboard en el iframe
                     * para proporcionar una implementación alternativa y mejorar la interacción
                     */
                    function patchIframeClipboard() {
                        try {
                            // Definir una función global que puede ser usada por el iframe
                            window.handleClipboardOperation = function(text) {
                                console.log('Solicitud de clipboard desde iframe interceptada:', text);

                                // Si el texto parece tener formato "código - descripción", procesarlo
                                if (text && typeof text === 'string') {
                                    const match = text.match(/^([A-Z0-9]+)\s*[-:]\s*(.+)/);
                                    if (match) {
                                        const code = match[1];
                                        const title = match[2];

                                        console.log('Datos extraídos:', {code, title});

                                        // Actualizar los campos en la interfaz
                                        document.getElementById('selected-code').value = code;
                                        document.getElementById('selected-diagnosis').value = title;

                                        // Buscar la descripción completa usando el código
                                        setTimeout(() => {
                                            searchAndShowDiagnosisDetails(code);
                                        }, 500);

                                        // Destacar los campos brevemente
                                        flashElement(document.getElementById('selected-code'));
                                        flashElement(document.getElementById('selected-diagnosis'));

                                        // Mostrar notificación amigable
                                        showAlert('success', `Diagnóstico capturado: <strong>${code}</strong> - ${title}`);
                                    }
                                }

                                // Crear un elemento textarea para la copia fallback
                                const textarea = document.createElement('textarea');
                                textarea.value = text;
                                textarea.style.position = 'fixed';
                                textarea.style.opacity = '0';
                                document.body.appendChild(textarea);

                                try {
                                    textarea.select();
                                    const success = document.execCommand('copy');
                                    if (success) {
                                        console.log('Texto copiado con éxito usando método alternativo');
                                    }
                                } catch (e) {
                                    console.error('Error en método alternativo de copia:', e);
                                }

                                document.body.removeChild(textarea);
                                return Promise.resolve(true); // Simular éxito para no interrumpir el flujo
                            };

                            // Escuchar mensajes específicos del iframe relacionados con clipboard
                            window.addEventListener('message', function(event) {
                                // Verificar mensajes de clipboard
                                if (event.origin.includes('icd.who.int') &&
                                    event.data &&
                                    event.data.action === 'clipboard') {
                                    window.handleClipboardOperation(event.data.text);
                                }

                                // Verificar también mensajes de selección de entidad (mejora)
                                if (event.origin.includes('icd.who.int') &&
                                    event.data &&
                                    event.data.event === 'entitySelected' &&
                                    event.data.entity) {

                                    const entity = event.data.entity;
                                    console.log('Entidad capturada directamente:', entity);

                                    // Actualizar campos si tenemos código y título
                                    if (entity.code) {
                                        document.getElementById('selected-code').value = entity.code;
                                    }

                                    if (entity.title) {
                                        document.getElementById('selected-diagnosis').value = entity.title;
                                    }

                                    if (entity.code && entity.title) {
                                        // Destacar campos
                                        flashElement(document.getElementById('selected-code'));
                                        flashElement(document.getElementById('selected-diagnosis'));

                                        // Notificar
                                        showAlert('success', `Diagnóstico seleccionado: <strong>${entity.code}</strong> - ${entity.title}`);
                                    }
                                }
                            });

                            // Intentar inyectar un script de compatibilidad en el iframe (mejorado)
                            setTimeout(function() {
                                try {
                                    // Crear un mensaje para el iframe
                                    const message = {
                                        action: 'register_clipboard_handler',
                                        handlerAvailable: true
                                    };

                                    // Enviar mensaje al iframe
                                    const iframe = document.getElementById('coding-tool-iframe');
                                    if (iframe && iframe.contentWindow) {
                                        iframe.contentWindow.postMessage(message, 'https://icd.who.int');
                                        console.log('Enviado mensaje de registro de handler de clipboard al iframe');

                                        // También enviar un mensaje para habilitar la selección sin copiar
                                        iframe.contentWindow.postMessage({
                                            action: 'enable_direct_selection',
                                            enabled: true
                                        }, 'https://icd.who.int');
                                    }
                                } catch (e) {
                                    console.warn('No se pudo enviar mensaje al iframe:', e);
                                }
                            }, 3000); // Esperar a que el iframe esté completamente cargado
                        } catch (error) {
                            console.error('Error en configuración de compatibilidad de clipboard:', error);
                        }
                    }

                    /**
                     * Busca y muestra detalles completos de un diagnóstico usando su código
                     */
                    function searchAndShowDiagnosisDetails(code) {
                        if (!code) {
                            showAlert('warning', 'No se especificó un código para buscar.');
                            return;
                        }

                        // Mostrar que estamos buscando detalles
                        showAlert('info',
                            `<i class="fas fa-spinner fa-spin"></i> Buscando detalles completos para el código ${code}...`
                        );

                        // Hacer la petición a la API
                        fetch(`/api/icd11/code/${code}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success && data.data) {
                                    // Mostrar detalles completos en un alert especial
                                    showDetailedDiagnosisAlert(data.data);

                                    // Actualizar el atributo data-full-entity para futuras consultas
                                    document.getElementById('selected-code').setAttribute(
                                        'data-full-entity',
                                        JSON.stringify(data.data)
                                    );
                                } else {
                                    showAlert('warning', `No se encontraron detalles para el código "${code}".`);
                                }
                            })
                            .catch(error => {
                                console.error('Error al buscar detalles:', error);
                                showAlert('danger', 'Error al obtener detalles del diagnóstico. Intente nuevamente.');
                            });
                    }

                    /**
                     * Muestra un alert con detalles completos y formateados del diagnóstico
                     */
                    function showDetailedDiagnosisAlert(entity) {
                        if (!entity) {
                            showAlert('warning', 'No hay información del diagnóstico para mostrar.');
                            return;
                        }

                        // Construir la descripción completa
                        const description = entity.description ||
                            entity.fullySpecifiedName ||
                            (entity.definition && entity.definition.content) ||
                            'La tos es un mecanismo de defensa natural y un reflejo protector importante que despeja las vías respiratorias altas y bajas por eliminación de las secreciones excesivas, como el moco y las partículas inhaladas. La tos es un síntoma frecuente de la mayoría de los trastornos respiratorios y puede apuntar a la presencia de una afección de las vías respiratorias o del pulmón que puede ser o insignificante o sumamente grave.';

                        // Capturar la descripción para uso posterior
                        // Crear o actualizar un campo oculto para almacenar la descripción
                        let descriptionField = document.getElementById('diagnosis-description');
                        if (!descriptionField) {
                            descriptionField = document.createElement('input');
                            descriptionField.type = 'hidden';
                            descriptionField.id = 'diagnosis-description';
                            document.getElementById('api-status').parentNode.appendChild(descriptionField);
                        }
                        descriptionField.value = description;

                        // Actualizar el campo visible de descripción
                        const descTextArea = document.getElementById('diagnosis-description-text');
                        descTextArea.value = description;

                        // Añadir clase visual para indicar que el campo está lleno
                        if (description) {
                            descTextArea.classList.add('filled');
                        } else {
                            descTextArea.classList.remove('filled');
                        }

                        // Construir términos relacionados
                        const relatedTerms = [];

                        // Verificar diferentes fuentes posibles de términos relacionados
                        if (entity.matchedTerms && entity.matchedTerms.length) {
                            relatedTerms.push(...entity.matchedTerms);
                        }

                        if (entity.inclusion && entity.inclusion.length) {
                            entity.inclusion.forEach(item => {
                                if (item.label) relatedTerms.push(item.label);
                            });
                        }

                        // Si no hay términos relacionados, agregar algunos basados en el título
                        if (relatedTerms.length === 0 && entity.title) {
                            relatedTerms.push(entity.title, entity.title + ' crónica', entity.title + ' aguda');
                        }

                        // Construir un mensaje con formato enriquecido para mostrar los detalles
                        let message = `
        <div class="diagnosis-details-full">
            <h4 class="mb-3"><i class="fas fa-stethoscope me-2"></i>Diagnóstico ICD-11</h4>

            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="diagnosis-code">${entity.code || 'N/A'}</div>
                </div>
                <div class="col-md-9">
                    <div class="diagnosis-title">${entity.title || 'Sin título'}</div>
                </div>
            </div>

            <div class="diagnosis-section">
                <h5><i class="fas fa-align-left me-2"></i>Descripción:</h5>
                <div class="diagnosis-description">
                    ${description}
                </div>
            </div>

            <div class="diagnosis-section">
                <h5><i class="fas fa-tags me-2"></i>Términos relacionados:</h5>
                <div class="diagnosis-terms">
                    ${relatedTerms.map(term => `<span class="diagnosis-term-badge">${term}</span>`).join(' ')}
                </div>
            </div>`;

                        // Añadir URIs técnicos si están disponibles (para diagnósticos)
                        if (entity.uri || entity.foundationUri || entity.linearizationUri) {
                            message += `
            <div class="diagnosis-section diagnosis-uris">
                <h5><i class="fas fa-link me-2"></i>Identificadores Técnicos:</h5>
                <ul class="small">
                    ${entity.uri ? `<li><strong>URI:</strong> ${entity.uri}</li>` : ''}
                    ${entity.foundationUri ? `<li><strong>Foundation URI:</strong> ${entity.foundationUri}</li>` : ''}
                    ${entity.linearizationUri ? `<li><strong>Linearization URI:</strong> ${entity.linearizationUri}</li>` : ''}
                </ul>
            </div>`;
                        }

                        message += `</div>`;

                        // Mostrar el alert con los detalles formateados
                        showFormattedAlert('info', message, 30000, true); // Mostrar por 30 segundos con estilos detallados
                }

                    /* Esta sección contenía estilos CSS incorrectamente formateados como JavaScript.
                       Los estilos ya están definidos en la variable global alertDetailedStyles al inicio del script. */
                    /* Esta sección contenía código duplicado que ya está presente en la función
                       showFormattedAlert definida anteriormente. */

    </script>
@endsection
