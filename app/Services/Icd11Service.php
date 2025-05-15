<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Services\Icd11EnhancedBrowserService;

class Icd11Service
{
    protected $baseUrl = 'https://icdaccessmanagement.who.int';
    protected $apiBaseUrl = 'https://id.who.int/icd/entity';
    protected $apiVersion = 'v2';
    protected $releaseId = 'release/11/2024';
    protected $linearization = 'mms';
    protected $clientId;
    protected $clientSecret;
    protected $token;
    protected $tokenExpiration;

    /**
     * Servicio mejorado para búsqueda en navegador
     */
    protected $browserService;

    /**
     * Constructor del servicio ICD-11
     */
    public function __construct(Icd11EnhancedBrowserService $browserService = null)
    {
        $this->clientId = config('services.icd11.client_id');
        $this->clientSecret = config('services.icd11.client_secret');

        // Inicializar el servicio de navegador mejorado
        $this->browserService = $browserService ?? new Icd11EnhancedBrowserService();

        // Intentar obtener el token desde la caché
        if (Cache::has('icd11_token')) {
            $tokenData = Cache::get('icd11_token');
            $this->token = $tokenData['token'];
            $this->tokenExpiration = $tokenData['expiration'];
        }
    }

    /**
     * Obtiene un token de autenticación
     *
     * @return string
     */
    public function getToken()
    {
        try {
            // Verificar si el token actual es válido
            if ($this->token && $this->tokenExpiration && $this->tokenExpiration > now()) {
                return $this->token;
            }

            if (empty($this->clientId) || empty($this->clientSecret)) {
                throw new \Exception('Las credenciales de ICD-11 no están configuradas correctamente');
            }

            // Obtener un nuevo token
            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ])
                ->asForm()
                ->post($this->baseUrl . '/connect/token', [
                    'grant_type' => 'client_credentials',
                    'scope' => 'icdapi_access'
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (!isset($data['access_token'])) {
                    throw new \Exception('Respuesta de autenticación inválida');
                }

                $this->token = $data['access_token'];
                $this->tokenExpiration = now()->addSeconds($data['expires_in'] ?? 3600);

                // Guardar el token en caché
                Cache::put('icd11_token', [
                    'token' => $this->token,
                    'expiration' => $this->tokenExpiration
                ], $this->tokenExpiration);

                return $this->token;
            }

            throw new \Exception('Error en la respuesta del servidor: ' . $response->status() . ' - ' . $response->body());
        } catch (\Exception $e) {
            // Limpiar token y caché en caso de error
            $this->token = null;
            $this->tokenExpiration = null;
            Cache::forget('icd11_token');

            throw new \Exception('Error al obtener el token de autenticación: ' . $e->getMessage());
        }
    }

    /**
     * Realiza una solicitud a la API de ICD-11
     *
     * @param string $endpoint
     * @param array $params
     * @param string $method
     * @return array
     */
    public function request($endpoint, $params = [], $method = 'GET')
    {
        try {
            $token = $this->getToken();

            $response = Http::withToken($token)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Accept-Language' => 'es',
                    'API-Version' => 'v2'
                ]);

            // Construir la URL base
            $url = rtrim($this->apiBaseUrl, '/');

            // Añadir el endpoint
            if (!empty($endpoint)) {
                $url .= '/' . ltrim($endpoint, '/');
            }

            // Añadir parámetros comunes
            $params['releaseId'] = $this->releaseId;
            $params['linearization'] = $this->linearization;
            $params['language'] = 'es';

            // Registrar la URL para depuración
            \Log::debug('ICD-11 API Request', [
                'url' => $url,
                'method' => $method,
                'params' => $params
            ]);

            if ($method === 'GET') {
                $response = $response->get($url, $params);
            } elseif ($method === 'POST') {
                $response = $response->post($url, $params);
            } elseif ($method === 'PUT') {
                $response = $response->put($url, $params);
            } elseif ($method === 'DELETE') {
                $response = $response->delete($url, $params);
            }

            if ($response->successful()) {
                $data = $response->json();
                if (empty($data)) {
                    throw new \Exception('La respuesta no contiene datos');
                }
                return $data;
            }

            $errorMessage = 'Error en la solicitud a ICD-11: ';
            if ($response->status() === 404) {
                $errorMessage .= 'El recurso solicitado no fue encontrado. URL: ' . $url;
            } else {
                $errorMessage .= 'Código de estado: ' . $response->status() . ', Respuesta: ' . $response->body();
            }

            throw new \Exception($errorMessage);
        } catch (\Exception $e) {
            \Log::error('Error en la solicitud a ICD-11', [
                'error' => $e->getMessage(),
                'endpoint' => $endpoint,
                'params' => $params
            ]);
            throw $e;
        }
    }

    /**
     * Busca entidades en ICD-11
     *
     * @param string $query
     * @param array $params
     * @return array
     */
    public function search($query, $params = [])
    {
        try {
            $token = $this->getToken();

            // Configurar los parámetros de búsqueda
            $searchParams = [
                'q' => $query,
                'useFlexisearch' => $params['useFlexisearch'] ?? false,
                'flatResults' => $params['flatResults'] ?? true,
                'highlightingEnabled' => $params['highlightingEnabled'] ?? true
            ];

            // Añadir parámetros adicionales si existen
            if (isset($params['chapterFilter'])) {
                $searchParams['chapterFilter'] = $params['chapterFilter'];
            }

            if (isset($params['subtreeFilter'])) {
                $searchParams['subtreeFilter'] = $params['subtreeFilter'];
            }

            // Construir la URL correcta para la API ICD-11 usando las propiedades de la clase
            $url = "https://id.who.int/icd/{$this->releaseId}/{$this->linearization}/search";

            // Log para depuración
            \Log::debug('ICD-11 Search API Request', [
                'url' => $url,
                'params' => $searchParams
            ]);

            $response = Http::withToken($token)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Accept-Language' => 'es',
                    'API-Version' => 'v2'
                ])
                ->get($url, $searchParams);

            if ($response->successful()) {
                $data = $response->json();
                if (empty($data)) {
                    throw new \Exception('La respuesta de búsqueda no contiene datos');
                }
                return $data;
            }

            $errorMessage = 'Error en la búsqueda ICD-11: ';
            if ($response->status() === 404) {
                $errorMessage .= 'El recurso solicitado no fue encontrado. URL: ' . $url;
            } else {
                $errorMessage .= 'Código de estado: ' . $response->status() . ', Respuesta: ' . $response->body();
            }

            throw new \Exception($errorMessage);
        } catch (\Exception $e) {
            \Log::error('Error en la búsqueda ICD-11', [
                'error' => $e->getMessage(),
                'query' => $query,
                'params' => $params
            ]);
            throw $e;
        }
    }

    /**
     * Busca una entidad específicamente por su código
     *
     * @param string $code Código ICD-11 (por ejemplo, MD12)
     * @return array|null Información de la entidad o null si no se encuentra
     */
    public function findByCode($code)
    {
        try {
            // Primero intentamos buscar desde la caché para mejorar rendimiento
            $cacheKey = 'icd11_code_' . $code;
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            \Log::debug('Buscando código ICD-11', ['code' => $code]);

            // Intento 1: Búsqueda directa por código exacto
            try {
                $results = $this->search($code, [
                    'flatResults' => true,
                    'useFlexisearch' => false,
                ]);

                // Revisar los resultados para una coincidencia exacta
                $entity = $this->extractEntityFromSearchResults($results, $code);
                if ($entity) {
                    Cache::put($cacheKey, $entity, now()->addHours(24));
                    return $entity;
                }
            } catch (\Exception $e) {
                \Log::warning('Error en la primera búsqueda por código', [
                    'code' => $code,
                    'error' => $e->getMessage()
                ]);
            }

            // Intento 2: Intentar buscar en diferentes secciones si está disponible
            try {
                // Intentar con búsqueda flexible
                $results = $this->search($code, [
                    'flatResults' => true,
                    'useFlexisearch' => true,
                ]);

                // Revisar los resultados para una coincidencia parcial
                $entity = $this->extractEntityFromSearchResults($results, $code);
                if ($entity) {
                    Cache::put($cacheKey, $entity, now()->addHours(24));
                    return $entity;
                }
            } catch (\Exception $e) {
                \Log::warning('Error en la segunda búsqueda por código', [
                    'code' => $code,
                    'error' => $e->getMessage()
                ]);
            }

            // Intento 3: Intentar buscar directamente por URL del código si la API lo soporta
            try {
                $codeEntity = $this->request("code/$code");
                if ($codeEntity && !empty($codeEntity)) {
                    $entity = [
                        'code' => $code,
                        'title' => $codeEntity['title'] ?? 'Sin título',
                        'uri' => $codeEntity['uri'] ?? null,
                        'foundationUri' => $codeEntity['foundationUri'] ?? null,
                        'fullySpecifiedName' => $codeEntity['fullySpecifiedName'] ?? ($codeEntity['title'] ?? 'Sin título'),
                    ];
                    Cache::put($cacheKey, $entity, now()->addHours(24));
                    return $entity;
                }
            } catch (\Exception $e) {
                \Log::warning('Error en la tercera búsqueda por código', [
                    'code' => $code,
                    'error' => $e->getMessage()
                ]);
            }

            // Si llegamos aquí, no se encontró la entidad
            return null;
        } catch (\Exception $e) {
            \Log::error('Error al buscar entidad por código', [
                'error' => $e->getMessage(),
                'code' => $code
            ]);
            return null;
        }
    }

    /**
     * Extrae una entidad de los resultados de búsqueda
     *
     * @param array $results Resultados de la búsqueda
     * @param string $code Código a buscar
     * @return array|null Entidad encontrada o null
     */
    private function extractEntityFromSearchResults($results, $code)
    {
        // Posibles estructuras de resultados según la versión de la API
        $searchPaths = [
            'destinationEntities',
            'matches',
            'entities',
            'linearizationEntities'
        ];

        foreach ($searchPaths as $path) {
            if (!empty($results[$path])) {
                foreach ($results[$path] as $item) {
                    // Comprobar coincidencia exacta
                    if (isset($item['code']) && strtolower($item['code']) === strtolower($code)) {
                        \Log::debug('Encontrada coincidencia exacta de código', [
                            'code' => $code,
                            'item' => $item
                        ]);

                        return [
                            'code' => $item['code'],
                            'title' => $item['title'],
                            'uri' => $item['uri'] ?? null,
                            'foundationUri' => $item['foundationUri'] ?? null,
                            'fullySpecifiedName' => $item['fullySpecifiedName'] ?? $item['title'],
                        ];
                    }
                }
            }
        }

        \Log::debug('No se encontró coincidencia para el código', ['code' => $code]);
        return null;
    }

    /**
     * Obtiene una entidad por su ID
     *
     * @param string $entityId
     * @return array
     */
    public function getEntity($entityId)
    {
        return $this->request($entityId);
    }

    /**
     * Obtiene los hijos de una entidad
     *
     * @param string $entityId
     * @return array
     */
    public function getChildren($entityId)
    {
        return $this->request($entityId . '/children');
    }

    /**
     * Obtiene los padres de una entidad
     *
     * @param string $entityId
     * @return array
     */
    public function getParents($entityId)
    {
        return $this->request($entityId . '/parents');
    }

    /**
     * Obtiene los ancestros de una entidad para construir la navegación jerárquica
     *
     * @param string $entityId
     * @return array
     */
    public function getAncestors($entityId)
    {
        // Intentar obtener los ancestros desde la caché para mejorar el rendimiento
        $cacheKey = 'icd11_ancestors_' . $entityId;
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $ancestors = [];
        try {
            // Obtener los padres directos de la entidad
            $parents = $this->getParents($entityId);

            // Si hay padres, procesarlos y obtener sus ancestros recursivamente
            if (!empty($parents)) {
                foreach ($parents as $parent) {
                    // Añadir el padre actual a la lista de ancestros
                    $ancestors[] = [
                        'id' => $parent['id'] ?? '',
                        'title' => $parent['title'] ?? 'Sin título',
                        'code' => $parent['code'] ?? ''
                    ];

                    // Obtener los ancestros del padre actual (limitado a evitar recursión infinita)
                    // Solo obtenemos un nivel más para evitar llamadas API excesivas
                    try {
                        $parentParents = $this->getParents($parent['id']);
                        foreach ($parentParents as $grandparent) {
                            $ancestors[] = [
                                'id' => $grandparent['id'] ?? '',
                                'title' => $grandparent['title'] ?? 'Sin título',
                                'code' => $grandparent['code'] ?? ''
                            ];
                        }
                    } catch (\Exception $e) {
                        // Si hay un error al obtener los ancestros del padre, continuamos
                    }
                }
            }

            // Invertir el array para que los ancestros estén en orden jerárquico (de arriba hacia abajo)
            $ancestors = array_reverse($ancestors);

            // Guardar en caché para futuras consultas (1 hora)
            Cache::put($cacheKey, $ancestors, now()->addHours(1));

            return $ancestors;
        } catch (\Exception $e) {
            // Si hay un error, devolver un array vacío
            return [];
        }
    }

    /**
     * Obtiene detalles de una entidad usando sus URIs
     *
     * @param array $data Datos de la entidad incluyendo URIs
     * @return array|null Información completa de la entidad o null si no se encuentra
     */
    public function getEntityByUri($data)
    {
        try {
            // Verificar que tengamos al menos una URI para consultar
            if (empty($data['uri']) && empty($data['foundationUri']) && empty($data['linearizationUri'])) {
                throw new \Exception('Se requiere al menos una URI para obtener detalles de la entidad');
            }

            \Log::debug('Obteniendo detalles de entidad por URI', ['data' => $data]);

            // Definir una clave para caché única basada en URI disponibles
            $cacheKey = 'icd11_uri_' . md5(json_encode($data));
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            // Intentar obtener por URI principal si está disponible
            $entityDetails = null;

            if (!empty($data['uri'])) {
                try {
                    // Extraer el ID de entidad de la URI
                    $parts = explode('/', $data['uri']);
                    $entityId = end($parts);

                    if (!empty($entityId)) {
                        $entityDetails = $this->getEntity($entityId);
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error al obtener entidad por URI principal', [
                        'uri' => $data['uri'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Si no tenemos detalles todavía, intentar con la URI de foundation
            if (!$entityDetails && !empty($data['foundationUri'])) {
                try {
                    // Hacer una solicitud directa a la URI
                    $token = $this->getToken();

                    $response = Http::withToken($token)
                        ->withHeaders([
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json',
                            'Accept-Language' => 'es',
                            'API-Version' => $this->apiVersion
                        ])
                        ->get($data['foundationUri'], [
                            'releaseId' => $this->releaseId,
                            'language' => 'es'
                        ]);

                    if ($response->successful()) {
                        $entityDetails = $response->json();
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error al obtener entidad por foundationUri', [
                        'foundationUri' => $data['foundationUri'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Si todavía no tenemos detalles, intentar con linearizationUri
            if (!$entityDetails && !empty($data['linearizationUri'])) {
                try {
                    // Hacer una solicitud directa a la URI
                    $token = $this->getToken();

                    $response = Http::withToken($token)
                        ->withHeaders([
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json',
                            'Accept-Language' => 'es',
                            'API-Version' => $this->apiVersion
                        ])
                        ->get($data['linearizationUri'], [
                            'releaseId' => $this->releaseId,
                            'language' => 'es'
                        ]);

                    if ($response->successful()) {
                        $entityDetails = $response->json();
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error al obtener entidad por linearizationUri', [
                        'linearizationUri' => $data['linearizationUri'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Si no tenemos detalles pero tenemos código, intentar buscar por código
            if (!$entityDetails && !empty($data['code'])) {
                try {
                    $entityDetails = $this->findByCode($data['code']);
                } catch (\Exception $e) {
                    \Log::warning('Error al obtener entidad por código como fallback', [
                        'code' => $data['code'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if ($entityDetails) {
                // Combinar los detalles obtenidos con los datos proporcionados
                $result = array_merge(
                    is_array($entityDetails) ? $entityDetails : ['title' => $entityDetails],
                    [
                        'code' => $data['code'] ?? ($entityDetails['code'] ?? null),
                        'uri' => $data['uri'] ?? ($entityDetails['uri'] ?? null),
                        'foundationUri' => $data['foundationUri'] ?? ($entityDetails['foundationUri'] ?? null),
                        'linearizationUri' => $data['linearizationUri'] ?? ($entityDetails['linearizationUri'] ?? null)
                    ]
                );

                // Guardar en caché
                Cache::put($cacheKey, $result, now()->addHours(24));

                return $result;
            }

            return null;
        } catch (\Exception $e) {
            \Log::error('Error al obtener detalles de entidad por URI', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return null;
        }
    }

    /**
     * Obtiene información detallada de una enfermedad directamente de la API oficial de la OMS por su código
     *
     * @param string $code Código ICD-11 (por ejemplo, MD12)
     * @return array Información detallada de la enfermedad
     * @throws \Exception si hay un error al obtener los datos
     */
    public function getDetailedDiseaseByCode($code)
    {
        try {
            // Verificar que el código no esté vacío
            if (empty($code)) {
                throw new \Exception('El código ICD-11 es requerido');
            }

            // Clave de caché para evitar solicitudes repetidas
            $cacheKey = 'icd11_detailed_' . $code;

            // Intentar recuperar de la caché primero
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            // Obtener token de autenticación
            $token = $this->getToken();

            // Inicializar el resultado con información básica
            $result = [
                'code' => $code,
                'title' => '',  // Se llenará posteriormente
            ];

            $entityUri = null;
            $foundationUri = null;

            // Intentar obtener la entidad directamente por código primero - sin depender de findByCode
            try {
                // Construir la URL para obtener información por código directamente
                $directCodeUrl = "https://id.who.int/icd/entity/search?q={$code}";

                $directResponse = Http::withToken($token)
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'Accept-Language' => 'es',
                        'API-Version' => 'v2'
                    ])
                    ->get($directCodeUrl);

                if ($directResponse->successful()) {
                    $data = $directResponse->json();

                    // Buscar la entidad en cualquier estructura de respuesta posible
                    $entityFound = false;
                    $searchPaths = ['destinationEntities', 'matches', 'entities', 'linearizationEntities'];

                    foreach ($searchPaths as $path) {
                        if (!empty($data[$path])) {
                            foreach ($data[$path] as $item) {
                                // Buscar coincidencia exacta o parcial
                                if (isset($item['code']) && (strtolower($item['code']) === strtolower($code) ||
                                    strpos(strtolower($item['code']), strtolower($code)) === 0)) {

                                    // Obtener datos básicos
                                    $result = [
                                        'code' => $item['code'] ?? $code,
                                        'title' => $item['title'] ?? 'Sin título',
                                        'uri' => $item['uri'] ?? null,
                                        'foundationUri' => $item['foundationUri'] ?? null,
                                        'linearizationUri' => $item['linearizationUri'] ?? null,
                                        'fullySpecifiedName' => $item['fullySpecifiedName'] ?? ($item['title'] ?? 'Sin título'),
                                    ];

                                    // Guardar las URIs si están disponibles
                                    if (!empty($item['uri'])) $entityUri = $item['uri'];
                                    if (!empty($item['foundationUri'])) $foundationUri = $item['foundationUri'];

                                    $entityFound = true;
                                    break 2; // Salir de ambos bucles
                                }
                            }
                        }
                    }

                    if (!$entityFound) {
                        // Intentar buscar directamente sin estructura
                        if (!empty($data['code']) && (strtolower($data['code']) === strtolower($code) ||
                            strpos(strtolower($data['code']), strtolower($code)) === 0)) {

                            $result = [
                                'code' => $data['code'] ?? $code,
                                'title' => $data['title'] ?? 'Sin título',
                                'uri' => $data['uri'] ?? null,
                                'foundationUri' => $data['foundationUri'] ?? null,
                                'linearizationUri' => $data['linearizationUri'] ?? null,
                                'fullySpecifiedName' => $data['fullySpecifiedName'] ?? ($data['title'] ?? 'Sin título'),
                            ];

                            // Guardar las URIs
                            if (!empty($data['uri'])) $entityUri = $data['uri'];
                            if (!empty($data['foundationUri'])) $foundationUri = $data['foundationUri'];
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::info('Búsqueda directa por código falló, continuando con otros métodos', [
                    'code' => $code,
                    'error' => $e->getMessage()
                ]);
                // No lanzar excepción, simplemente continuar con otros métodos
            }

            // Probar múltiples versiones de la API para garantizar compatibilidad
            $apiVersions = [
                'https://id.who.int/icd/release/11/2024/mms/codeinfo/' . urlencode($code),
                'https://id.who.int/icd/release/11/2022-02/mms/codeinfo/' . urlencode($code),
                'https://id.who.int/icd/entity/' . urlencode($code)
            ];

            $response = null;
            $responseData = null;

            foreach ($apiVersions as $url) {
                \Log::debug('Consultando API OMS para código detallado', [
                    'url' => $url,
                    'code' => $code
                ]);

                try {
                    // Realizar la solicitud a la API de la OMS
                    $response = Http::withToken($token)
                        ->withHeaders([
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json',
                            'Accept-Language' => 'es',
                            'API-Version' => 'v2'
                        ])
                        ->get($url);

                    if ($response->successful()) {
                        $data = $response->json();
                        if (!empty($data) && (!isset($data['error']) || !$data['error'])) {
                            $responseData = $data;
                            break; // Salir del bucle si obtenemos datos válidos
                        }
                    }
                } catch (\Exception $e) {
                    \Log::info('Falló el intento con URL: ' . $url, [
                        'error' => $e->getMessage()
                    ]);
                    // Continuar con el siguiente URL
                }
            }            // Usar los datos de la respuesta si los obtuvimos
            if ($responseData) {
                $result = array_merge($result, $responseData);

                // Si tenemos un stemId, usarlo para obtener información detallada
                if (!empty($responseData['stemId'])) {
                    try {
                        \Log::info('Intentando obtener detalles usando stemId', ['stemId' => $responseData['stemId']]);

                        // Extraer el ID numérico del stemId
                        if (preg_match('#/(\d+)$#', $responseData['stemId'], $matches)) {
                            $stemNumericId = $matches[1];

                            // Construir URL para el endpoint /entity/
                            $stemUrl = "https://id.who.int/icd/entity/{$stemNumericId}";

                            $stemResponse = Http::withToken($token)
                                ->withHeaders([
                                    'Accept' => 'application/json',
                                    'Content-Type' => 'application/json',
                                    'Accept-Language' => 'es',
                                    'API-Version' => 'v2'
                                ])
                                ->get($stemUrl);

                            if ($stemResponse->successful()) {
                                $stemData = $stemResponse->json();

                                if (!empty($stemData)) {
                                    // Extraer título y otros datos importantes
                                    if (!empty($stemData['title']) && (empty($result['title']) || $result['title'] === '')) {
                                        $result['title'] = $stemData['title'];
                                    }

                                    // Añadir otros datos útiles del stemId
                                    foreach (['definition', 'description', 'longDefinition', 'fullySpecifiedName', 'inclusion', 'exclusion'] as $field) {
                                        if (!empty($stemData[$field]) && empty($result[$field])) {
                                            $result[$field] = $stemData[$field];
                                        }
                                    }

                                    // Guardar URIs si están disponibles
                                    if (!empty($stemData['uri']) && empty($entityUri)) {
                                        $entityUri = $stemData['uri'];
                                    }
                                    if (!empty($stemData['foundationUri']) && empty($foundationUri)) {
                                        $foundationUri = $stemData['foundationUri'];
                                    }
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::info('Error al obtener datos usando stemId: ' . $e->getMessage());
                    }
                }
            }

            // Si aún no tenemos título, intentar buscarlo en otras fuentes
            if (empty($result['title']) || $result['title'] === '') {
                // Intentemos obtener al menos el título directamente desde la API de búsqueda
                try {
                    // Probar con ambas versiones de la API de búsqueda
                    $searchUrls = [
                        "https://id.who.int/icd/{$this->releaseId}/{$this->linearization}/search",
                        "https://id.who.int/icd/release/11/2022-02/mms/search",
                        "https://icd.who.int/ct11/icd11_mms/en/search"
                    ];

                    $foundTitle = false;
                    foreach ($searchUrls as $searchUrl) {
                        $searchResponse = Http::withToken($token)
                            ->withHeaders([
                                'Accept' => 'application/json',
                                'Content-Type' => 'application/json',
                                'Accept-Language' => 'es',
                                'API-Version' => 'v2'
                            ])
                            ->get($searchUrl, [
                                'q' => $code,
                                'useFlexisearch' => true,
                                'flatResults' => true
                            ]);

                        if ($searchResponse->successful()) {
                            $searchData = $searchResponse->json();

                            // Buscar el título en los resultados de búsqueda
                            foreach (['destinationEntities', 'matches', 'entities', 'linearizationEntities'] as $path) {
                                if (!empty($searchData[$path])) {
                                    foreach ($searchData[$path] as $item) {
                                        if (isset($item['code']) && strtolower($item['code']) === strtolower($code)) {
                                            if (!empty($item['title'])) {
                                                $result['title'] = $item['title'];
                                            }
                                            if (!empty($item['uri']) && empty($entityUri)) {
                                                $entityUri = $item['uri'];
                                            }
                                            if (!empty($item['foundationUri']) && empty($foundationUri)) {
                                                $foundationUri = $item['foundationUri'];
                                            }
                                            // Si encontramos el título, podemos terminar la búsqueda
                                            if (!empty($result['title']) && $result['title'] !== '') {
                                                // Establecer una bandera para salir de los bucles externos
                                                $foundTitle = true;
                                                break 3; // Salir de los tres bucles anidados
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        // Si ya encontramos el título, salir del bucle de URLs
                        if ($foundTitle) {
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    \Log::info('Error al buscar el título: ' . $e->getMessage());
                }
            }

            // Método adicional: buscar en la página HTML oficial de la OMS
            if (empty($result['title']) || $result['title'] === '') {
                try {
                    // Consultar directamente la página de navegación de la OMS
                    $browserUrl = "https://icd.who.int/browse11/l-m/es/http%3a%2f%2fid.who.int%2ficd%2fentity%2f{$code}";
                    $htmlResponse = Http::get($browserUrl);

                    if ($htmlResponse->successful()) {
                        $htmlContent = $htmlResponse->body();

                        // Extraer el título del HTML
                        if (preg_match('/<h1[^>]*class="entityTitle"[^>]*>(.*?)<\/h1>/s', $htmlContent, $matches)) {
                            $title = trim(strip_tags($matches[1]));
                            if (!empty($title)) {
                                $result['title'] = $title;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::info('Error al extraer título del HTML: ' . $e->getMessage());
                }
            }

            // Intentar obtener detalles del endpont de entidad
            if ($entityUri) {
                try {
                    // Extraer el ID de entidad de la URI
                    $parts = explode('/', $entityUri);
                    $entityId = end($parts);

                    if (!empty($entityId)) {
                        // Intentar obtener detalles completos de la entidad
                        $entityDetails = $this->getEntity($entityId);

                        if ($entityDetails && is_array($entityDetails)) {
                            // Fusionar con los resultados
                            $result = array_merge($result, $entityDetails);
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error al obtener detalles adicionales de entidad', [
                        'uri' => $entityUri,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Si no hay información detallada (definition, description), intentar con el endpoint de definiciones
            if (empty($result['definition']) && $entityUri) {
                try {
                    // Extraer el ID de entidad de la URI
                    $parts = explode('/', $entityUri);
                    $entityId = end($parts);

                    if (!empty($entityId)) {
                        // Intentar obtener la definición específicamente
                        $definitionResponse = Http::withToken($token)
                            ->withHeaders([
                                'Accept' => 'application/json',
                                'Content-Type' => 'application/json',
                                'Accept-Language' => 'es',
                                'API-Version' => 'v2'
                            ])
                            ->get("https://id.who.int/icd/entity/{$entityId}/definition", [
                                'releaseId' => $this->releaseId,
                                'linearization' => $this->linearization,
                                'language' => 'es'
                            ]);

                        if ($definitionResponse->successful()) {
                            $definitionData = $definitionResponse->json();
                            if (!empty($definitionData)) {
                                $result['definition'] = $definitionData;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error al obtener definición específica', [
                        'entityId' => $entityId ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Intentar obtener inclusiones específicamente si no existen
            if (empty($result['inclusion']) && $entityUri) {
                try {
                    $parts = explode('/', $entityUri);
                    $entityId = end($parts);

                    if (!empty($entityId)) {
                        $inclusionsResponse = Http::withToken($token)
                            ->withHeaders([
                                'Accept' => 'application/json',
                                'Content-Type' => 'application/json',
                                'Accept-Language' => 'es',
                                'API-Version' => 'v2'
                            ])
                            ->get("https://id.who.int/icd/entity/{$entityId}/inclusion", [
                                'releaseId' => $this->releaseId,
                                'linearization' => $this->linearization,
                                'language' => 'es'
                            ]);

                        if ($inclusionsResponse->successful()) {
                            $inclusionData = $inclusionsResponse->json();
                            if (!empty($inclusionData)) {
                                $result['inclusion'] = $inclusionData;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error al obtener inclusiones', [
                        'entityId' => $entityId ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Intentar obtener exclusiones específicamente si no existen
            if (empty($result['exclusion']) && $entityUri) {
                try {
                    $parts = explode('/', $entityUri);
                    $entityId = end($parts);

                    if (!empty($entityId)) {
                        $exclusionsResponse = Http::withToken($token)
                            ->withHeaders([
                                'Accept' => 'application/json',
                                'Content-Type' => 'application/json',
                                'Accept-Language' => 'es',
                                'API-Version' => 'v2'
                            ])
                            ->get("https://id.who.int/icd/entity/{$entityId}/exclusion", [
                                'releaseId' => $this->releaseId,
                                'linearization' => $this->linearization,
                                'language' => 'es'
                            ]);

                        if ($exclusionsResponse->successful()) {
                            $exclusionData = $exclusionsResponse->json();
                            if (!empty($exclusionData)) {
                                $result['exclusion'] = $exclusionData;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error al obtener exclusiones', [
                        'entityId' => $entityId ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Si tenemos un foundationUri y todavía no tenemos descripción ni definición
            // intentamos obtener información desde la foundation
            if ($foundationUri && (empty($result['description']) || empty($result['definition']) || empty($result['longDefinition']))) {
                try {
                    $foundationResponse = Http::withToken($token)
                        ->withHeaders([
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json',
                            'Accept-Language' => 'es',
                            'API-Version' => 'v2'
                        ])
                        ->get($foundationUri, [
                            'releaseId' => $this->releaseId,
                            'language' => 'es'
                        ]);

                    if ($foundationResponse->successful()) {
                        $foundationData = $foundationResponse->json();

                        // Si hay datos de foundation, intentar extraer lo que nos falta
                        if ($foundationData) {
                            // Extraer solo aquellos campos que nos interesan y que no tenemos aún
                            foreach (['description', 'definition', 'longDefinition', 'inclusion', 'exclusion'] as $field) {
                                if (!empty($foundationData[$field]) && empty($result[$field])) {
                                    $result[$field] = $foundationData[$field];
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error al obtener datos desde foundation', [
                        'foundationUri' => $foundationUri,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Procesar description si existe en algún formato (puede venir en diferentes formatos)
            if (empty($result['description']) && !empty($result['definition'])) {
                // Si no hay description pero sí una definición, usarla como description
                if (is_array($result['definition']) && isset($result['definition']['content'])) {
                    $result['description'] = $result['definition']['content'];
                } elseif (is_string($result['definition'])) {
                    $result['description'] = $result['definition'];
                }
            }

            // Si aún no tenemos descripciones, buscar en todos los campos posibles
            foreach (['longDefinition', 'browserDescription', 'fullySpecifiedName'] as $possibleField) {
                if (empty($result['description']) && !empty($result[$possibleField])) {
                    if (is_array($result[$possibleField]) && isset($result[$possibleField]['content'])) {
                        $result['description'] = $result[$possibleField]['content'];
                    } elseif (is_string($result[$possibleField])) {
                        $result['description'] = $result[$possibleField];
                    }
                }
            }

            // Intentar obtener la descripción directamente desde el browser description endpoint si existe entityId
            if (empty($result['description']) && !empty($entityId)) {
                try {
                    $browserDescriptionResponse = Http::withToken($token)
                        ->withHeaders([
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json',
                            'Accept-Language' => 'es',
                            'API-Version' => 'v2'
                        ])
                        ->get("https://id.who.int/icd/entity/{$entityId}/browserDescription", [
                            'releaseId' => $this->releaseId,
                            'linearization' => $this->linearization,
                            'language' => 'es'
                        ]);

                    if ($browserDescriptionResponse->successful()) {
                        $browserData = $browserDescriptionResponse->json();
                        if (!empty($browserData)) {
                            if (is_array($browserData) && isset($browserData['content'])) {
                                $result['description'] = $browserData['content'];
                                $result['browserDescription'] = $browserData;
                            } elseif (is_string($browserData)) {
                                $result['description'] = $browserData;
                                $result['browserDescription'] = $browserData;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error al obtener browserDescription', [
                        'entityId' => $entityId ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }            // Probar múltiples endpoints de Foundation Component para encontrar datos detallados
            if (empty($result['description']) || empty($result['inclusion']) || empty($result['exclusion'])) {
                $foundationUrls = [
                    "https://id.who.int/icd/release/11/2024/mms/foundationComponent/{$code}",
                    "https://id.who.int/icd/release/11/2022-02/mms/foundationComponent/{$code}",
                    "https://id.who.int/icd/entity/foundationComponent/{$code}"
                ];

                // Si tenemos un entityUri, también construir URLs basadas en él
                if ($entityUri) {
                    $parts = explode('/', $entityUri);
                    $entityId = end($parts);
                    if ($entityId) {
                        $foundationUrls[] = "https://id.who.int/icd/entity/{$entityId}/foundation";
                        $foundationUrls[] = "https://id.who.int/icd/entity/foundation/{$entityId}";
                    }
                }

                foreach ($foundationUrls as $fcUrl) {
                    try {
                        $fcResponse = Http::withToken($token)
                            ->withHeaders([
                                'Accept' => 'application/json',
                                'Content-Type' => 'application/json',
                                'Accept-Language' => 'es',
                                'API-Version' => 'v2'
                            ])
                            ->get($fcUrl);

                        if ($fcResponse->successful()) {
                            $fcData = $fcResponse->json();
                            if (!empty($fcData)) {
                                // Guardar datos completos del componente foundation
                                $result['foundationComponent'] = $fcData;

                                // Buscar descripción en diferentes campos del Foundation Component
                                foreach (['definition', 'description', 'textualDefinition', 'longDefinition', 'browserDescription'] as $descField) {
                                    if (!empty($fcData[$descField])) {
                                        if (is_array($fcData[$descField]) && isset($fcData[$descField]['content'])) {
                                            $result['description'] = $fcData[$descField]['content'];
                                        } elseif (is_string($fcData[$descField])) {
                                            $result['description'] = $fcData[$descField];
                                        }
                                    }
                                }

                                // Extraer inclusiones y exclusiones si están disponibles
                                if (!empty($fcData['inclusion'])) {
                                    $result['inclusion'] = $fcData['inclusion'];
                                }
                                if (!empty($fcData['exclusion'])) {
                                    $result['exclusion'] = $fcData['exclusion'];
                                }

                                // Si encontramos datos útiles, detenemos la búsqueda
                                if (!empty($result['description']) &&
                                    !empty($result['inclusion']) &&
                                    !empty($result['exclusion'])) {
                                    break;
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::info('Error al obtener foundation component desde URL: ' . $fcUrl, [
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }            // Si aún no tenemos descripción o título, intentar con el browser API que es más confiable
            if (empty($result['description']) || empty($result['title']) || $result['title'] === '') {
                try {
                    // Probar múltiples variantes de URLs del navegador ICD-11
                    $browserUrls = [
                        "https://icd.who.int/browse11/l-m/es/GetConcept/{$code}",
                        "https://icd.who.int/ct11/icd11_mms/en/getConcept/{$code}",
                        "https://icd.who.int/browse11/l-m/es/http%3a%2f%2fid.who.int%2ficd%2fentity%2f{$code}"
                    ];

                    foreach ($browserUrls as $browserUrl) {
                        $browserResponse = Http::get($browserUrl);

                        if ($browserResponse->successful()) {
                            $htmlContent = $browserResponse->body();

                            // Intentar extraer la descripción del HTML usando expresiones regulares
                            if (empty($result['description']) &&
                                preg_match('/<div[^>]*class="description"[^>]*>(.*?)<\/div>/s', $htmlContent, $matches)) {
                                $description = trim(strip_tags($matches[1]));
                                if (!empty($description)) {
                                    $result['description'] = $description;
                                    $result['browserHtmlDescription'] = $matches[1];
                                }
                            }

                            // Intentar extraer el título si aún no lo tenemos
                            if ((empty($result['title']) || $result['title'] === '') &&
                                preg_match('/<h1[^>]*class="entityTitle"[^>]*>(.*?)<\/h1>/s', $htmlContent, $titleMatches)) {
                                $title = trim(strip_tags($titleMatches[1]));
                                if (!empty($title)) {
                                    $result['title'] = $title;
                                }
                            }

                            // Si ya tenemos tanto título como descripción, podemos salir del bucle
                            if (!empty($result['description']) && !empty($result['title']) && $result['title'] !== '') {
                                break;
                            }
                        }
                    }

                    // Intento adicional: consultar la API alternativa de la OMS
                    if (empty($result['description']) || empty($result['title']) || $result['title'] === '') {
                        $alternativeUrl = "https://icd.who.int/browse11/l-m/en/JsonService/GetConcept?ConceptId={$code}";
                        $alternativeResponse = Http::get($alternativeUrl);

                        if ($alternativeResponse->successful()) {
                            $jsonData = $alternativeResponse->json();

                            if (!empty($jsonData)) {
                                // Extraer título si existe en la respuesta alternativa
                                if ((empty($result['title']) || $result['title'] === '') && !empty($jsonData['Title'])) {
                                    $result['title'] = $jsonData['Title'];
                                }

                                // Extraer descripción si existe en la respuesta alternativa
                                if (empty($result['description']) && !empty($jsonData['Definition'])) {
                                    $result['description'] = $jsonData['Definition'];
                                }

                                // Guardar datos JSON adicionales que pueden ser útiles
                                $result['browserJsonData'] = $jsonData;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::info('Error al obtener datos del navegador', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Último recurso: usar el servicio de navegador mejorado si todavía nos faltan datos
            if (empty($result['title']) || $result['title'] === '' || empty($result['description'])) {
                \Log::info('Utilizando servicio de navegador mejorado para código: ' . $code);

                try {
                    $browserResults = $this->browserService->fetchDiseaseInfoFromBrowser($code);

                    // Aplicar los resultados del navegador solo si tienen datos
                    if (!empty($browserResults['title']) && (empty($result['title']) || $result['title'] === '')) {
                        $result['title'] = $browserResults['title'];
                        $result['browser_title_source'] = true;
                    }

                    if (!empty($browserResults['description']) && empty($result['description'])) {
                        $result['description'] = $browserResults['description'];
                        $result['browser_description_source'] = true;
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error al utilizar servicio de navegador mejorado', [
                        'error' => $e->getMessage(),
                        'code' => $code
                    ]);
                }
            }

            // Si aún no tenemos un título y hemos agotado todas las opciones, poner un marcador
            if (empty($result['title']) || $result['title'] === '') {
                $result['title'] = "Código {$code}";
                $result['title_not_found'] = true;
            }

            // Guardar en caché para futuras consultas
            Cache::put($cacheKey, $result, now()->addHours(24));

            return $result;

        } catch (\Exception $e) {
            \Log::error('Error al obtener información detallada por código', [
                'error' => $e->getMessage(),
                'code' => $code
            ]);

            throw $e;
        }
    }

    /**
     * Obtiene información extendida (descripción, términos relacionados, etc.) para una entidad
     *
     * @param array $entityData Los datos básicos de la entidad
     * @return array Información extendida
     */
    protected function getExtendedInformation($entityData)
    {
        try {
            $result = [];
            $token = $this->getToken();

            // Definir los campos que queremos obtener
            $specialEndpoints = [
                'definition',
                'longDefinition',
                'description',
                'inclusion',
                'exclusion',
                'browserDescription',
                'fullySpecifiedName'
            ];

            // Si tenemos un URI, intentar obtener información adicional
            if (isset($entityData['uri']) || isset($entityData['foundationUri'])) {
                $uri = $entityData['uri'] ?? $entityData['foundationUri'];

                // Extraer el ID de la entidad del URI
                $entityId = null;
                if (preg_match('#/([^/]+)$#', $uri, $matches)) {
                    $entityId = $matches[1];
                }

                if ($entityId) {
                    // Intentar obtener la entidad completa por su ID primero
                    try {
                        $entityDetails = $this->getEntity($entityId);

                        if ($entityDetails) {
                            // Extraer campos importantes
                            foreach ($specialEndpoints as $field) {
                                if (isset($entityDetails[$field]) && !isset($entityData[$field])) {
                                    $result[$field] = $entityDetails[$field];
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Error al obtener entidad completa en getExtendedInformation', [
                            'entityId' => $entityId,
                            'error' => $e->getMessage()
                        ]);
                    }

                    // Para cada campo especial, si aún no lo tenemos, intentar obtenerlo directamente
                    foreach ($specialEndpoints as $endpoint) {
                        if (empty($result[$endpoint]) && empty($entityData[$endpoint])) {
                            try {
                                $endpointUrl = "https://id.who.int/icd/entity/{$entityId}/{$endpoint}";

                                $endpointResponse = Http::withToken($token)
                                    ->withHeaders([
                                        'Accept' => 'application/json',
                                        'Content-Type' => 'application/json',
                                        'Accept-Language' => 'es',
                                        'API-Version' => 'v2'
                                    ])
                                    ->get($endpointUrl, [
                                        'releaseId' => $this->releaseId,
                                        'linearization' => $this->linearization,
                                        'language' => 'es'
                                    ]);

                                if ($endpointResponse->successful()) {
                                    $endpointData = $endpointResponse->json();
                                    if (!empty($endpointData)) {
                                        $result[$endpoint] = $endpointData;
                                    }
                                }
                            } catch (\Exception $e) {
                                \Log::debug("No se pudo obtener {$endpoint} para entidad {$entityId}", [
                                    'error' => $e->getMessage()
                                ]);
                                // No hacemos nada, simplemente continuamos con el siguiente endpoint
                            }
                        }
                    }

                    // Si aún no tenemos descripción, intentar obtenerla desde la linearizationUri
                    if (empty($result['description']) && empty($entityData['description']) && !empty($entityData['linearizationUri'])) {
                        try {
                            $linearizationResponse = Http::withToken($token)
                                ->withHeaders([
                                    'Accept' => 'application/json',
                                    'Content-Type' => 'application/json',
                                    'Accept-Language' => 'es',
                                    'API-Version' => 'v2'
                                ])
                                ->get($entityData['linearizationUri'], [
                                    'releaseId' => $this->releaseId,
                                    'language' => 'es'
                                ]);

                            if ($linearizationResponse->successful()) {
                                $linearizationData = $linearizationResponse->json();
                                foreach ($specialEndpoints as $field) {
                                    if (!empty($linearizationData[$field])) {
                                        $result[$field] = $linearizationData[$field];
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            \Log::warning('Error al obtener datos desde linearizationUri', [
                                'linearizationUri' => $entityData['linearizationUri'],
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            }

            // Si tenemos definición pero no descripción, usar definición como descripción
            if (empty($result['description']) && !empty($result['definition'])) {
                if (is_array($result['definition']) && isset($result['definition']['content'])) {
                    $result['description'] = $result['definition']['content'];
                } elseif (is_string($result['definition'])) {
                    $result['description'] = $result['definition'];
                }
            }

            // Si aún no hay descripción pero hay longDefinition, usarla
            if (empty($result['description']) && !empty($result['longDefinition'])) {
                if (is_array($result['longDefinition']) && isset($result['longDefinition']['content'])) {
                    $result['description'] = $result['longDefinition']['content'];
                } elseif (is_string($result['longDefinition'])) {
                    $result['description'] = $result['longDefinition'];
                }
            }

            // Si todavía no tenemos inclusiones pero tenemos uri, intentar con /linearization
            if (empty($result['inclusion']) && empty($entityData['inclusion']) && !empty($entityId)) {
                try {
                    $linearizationUrl = "https://id.who.int/icd/entity/{$entityId}/linearization";

                    $linearizationResponse = Http::withToken($token)
                        ->withHeaders([
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json',
                            'Accept-Language' => 'es',
                            'API-Version' => 'v2'
                        ])
                        ->get($linearizationUrl, [
                            'releaseId' => $this->releaseId,
                            'linearization' => $this->linearization,
                            'language' => 'es'
                        ]);

                    if ($linearizationResponse->successful()) {
                        $linearizationData = $linearizationResponse->json();
                        if (!empty($linearizationData)) {
                            foreach ($specialEndpoints as $field) {
                                if (!empty($linearizationData[$field]) && empty($result[$field])) {
                                    $result[$field] = $linearizationData[$field];
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::debug("No se pudo obtener información de linearization para entidad {$entityId}", [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return $result;
        } catch (\Exception $e) {
            \Log::warning('Error al obtener información extendida', [
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }
}
