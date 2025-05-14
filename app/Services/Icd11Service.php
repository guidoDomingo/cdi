<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

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
     * Constructor del servicio ICD-11
     */
    public function __construct()
    {
        $this->clientId = config('services.icd11.client_id');
        $this->clientSecret = config('services.icd11.client_secret');

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

            // URL específica para la API oficial de la OMS para buscar por código
            $url = 'https://id.who.int/icd/release/11/2022-02/mms/codeinfo/' . urlencode($code);

            \Log::debug('Consultando API OMS para código detallado', [
                'url' => $url,
                'code' => $code
            ]);

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

                // Si no hay datos, intentar con un endpoint alternativo
                if (empty($data) || (isset($data['error']) && $data['error'])) {
                    // URL alternativa - intentar obtener por el endpoint de entidad directamente
                    $altUrl = 'https://id.who.int/icd/entity/' . urlencode($code);

                    $altResponse = Http::withToken($token)
                        ->withHeaders([
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json',
                            'Accept-Language' => 'es',
                            'API-Version' => 'v2'
                        ])
                        ->get($altUrl);

                    if ($altResponse->successful()) {
                        $data = $altResponse->json();
                    }
                }

                if (empty($data)) {
                    throw new \Exception('No se encontraron datos para el código ' . $code);
                }

                // Intentar obtener información adicional, como la descripción
                $extendedInfo = $this->getExtendedInformation($data);
                if ($extendedInfo) {
                    $data = array_merge($data, $extendedInfo);
                }

                // Guardar en caché para futuras consultas
                Cache::put($cacheKey, $data, now()->addHours(24));

                return $data;
            }

            $errorMessage = 'Error en la API de la OMS: ';
            if ($response->status() === 404) {
                $errorMessage .= 'Código no encontrado: ' . $code;
            } else {
                $errorMessage .= 'Código de estado: ' . $response->status() . ', Respuesta: ' . $response->body();
            }

            throw new \Exception($errorMessage);

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

            // Si tenemos un URI, intentar obtener información adicional
            if (isset($entityData['uri']) || isset($entityData['foundationUri'])) {
                $uri = $entityData['uri'] ?? $entityData['foundationUri'];

                // Extraer el ID de la entidad del URI
                $entityId = null;
                if (preg_match('#/([^/]+)$#', $uri, $matches)) {
                    $entityId = $matches[1];
                }

                if ($entityId) {
                    // Intentar obtener la entidad completa por su ID
                    $entityDetails = $this->getEntity($entityId);

                    if ($entityDetails) {
                        // Extraer campos importantes
                        if (isset($entityDetails['definition']) && !isset($entityData['definition'])) {
                            $result['definition'] = $entityDetails['definition'];
                        }

                        if (isset($entityDetails['longDefinition']) && !isset($entityData['longDefinition'])) {
                            $result['longDefinition'] = $entityDetails['longDefinition'];
                        }

                        if (isset($entityDetails['description']) && !isset($entityData['description'])) {
                            $result['description'] = $entityDetails['description'];
                        }

                        if (isset($entityDetails['inclusion']) && !isset($entityData['inclusion'])) {
                            $result['inclusion'] = $entityDetails['inclusion'];
                        }

                        if (isset($entityDetails['exclusion']) && !isset($entityData['exclusion'])) {
                            $result['exclusion'] = $entityDetails['exclusion'];
                        }
                    }
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
