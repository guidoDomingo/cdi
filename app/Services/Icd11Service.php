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

            // Construir la URL correcta para la API ICD-11
            // Según la documentación, la URL debe incluir el release y linearization como parte del path
            $url = "https://id.who.int/icd/release/11/2022-02/mms/search";

            // Registrar la URL para depuración
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
}
