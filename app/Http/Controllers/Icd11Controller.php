<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Icd11Service;
use Illuminate\Http\Request;

class Icd11Controller extends Controller
{
    protected $icd11Service;

    /**
     * Constructor del controlador
     */
    public function __construct(Icd11Service $icd11Service)
    {
        $this->icd11Service = $icd11Service;
    }

    /**
     * Muestra la página principal de la interfaz ICD-11
     */
    public function index()
    {
        return view('icd11.index');
    }

    /**
     * Realiza una búsqueda en la API de ICD-11
     */
    public function search(Request $request)
    {
        $query = $request->input('query');
        $format = $request->input('format', 'html');
        $results = [];

        if (empty($query)) {
            if ($format === 'json') {
                return response()->json(['success' => false, 'message' => 'Se requiere un término de búsqueda']);
            }
            return view('icd11.index');
        }

        try {
            // Configurar parámetros de búsqueda
            $searchParams = [
                'useFlexisearch' => $request->input('useFlexisearch', false),
                'flatResults' => $request->input('flatResults', true),
                'highlightingEnabled' => $request->input('highlightingEnabled', true)
            ];

            // Añadir filtros opcionales si están presentes
            if ($request->has('chapterFilter')) {
                $searchParams['chapterFilter'] = $request->input('chapterFilter');
            }

            if ($request->has('subtreeFilter')) {
                $searchParams['subtreeFilter'] = $request->input('subtreeFilter');
            }

            $apiResponse = $this->icd11Service->search($query, $searchParams);

            // Procesar la respuesta de la API para extraer los resultados en el formato esperado
            $processedResults = [];

            // Verificar si hay entidades en 'destinationEntities'
            if (isset($apiResponse['destinationEntities']) && is_array($apiResponse['destinationEntities'])) {
                $processedResults = $apiResponse['destinationEntities'];
            }
            // Si no hay resultados pero la API contestó sin error
            else if (isset($apiResponse['error']) && $apiResponse['error'] === false) {
                // La API respondió correctamente pero no encontró resultados
                $processedResults = [];
            }

            if ($format === 'json') {
                return response()->json(['success' => true, 'data' => $apiResponse, 'processedResults' => $processedResults]);
            }

            return view('icd11.index', ['results' => $processedResults, 'query' => $query]);
        } catch (\Exception $e) {
            if ($format === 'json') {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return view('icd11.index', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Obtiene los detalles de una entidad y sus hijos
     */
    public function getEntity(Request $request, $entityId)
    {
        $format = $request->input('format', 'json');

        try {
            // Obtener detalles de la entidad
            $entity = $this->icd11Service->getEntity($entityId);

            // Obtener los hijos de la entidad
            $children = [];
            try {
                $children = $this->icd11Service->getChildren($entityId);
            } catch (\Exception $childrenEx) {
                // Si hay un error al obtener los hijos, continuamos con la entidad principal
            }

            // Obtener los ancestros de la entidad para la navegación jerárquica
            $ancestors = [];
            try {
                // Si el servicio tiene un método para obtener ancestros, lo usamos
                if (method_exists($this->icd11Service, 'getAncestors')) {
                    $ancestors = $this->icd11Service->getAncestors($entityId);
                }
                // Si no existe el método, podemos implementar una lógica alternativa aquí
            } catch (\Exception $ancestorsEx) {
                // Si hay un error al obtener los ancestros, continuamos sin ellos
            }

            // Añadir los ancestros a la entidad para usarlos en la vista
            $entity['ancestors'] = $ancestors;

            if ($format === 'json') {
                return response()->json([
                    'success' => true,
                    'data' => $entity,
                    'children' => $children,
                    'ancestors' => $ancestors
                ]);
            }

            return view('icd11.index', [
                'entity' => $entity,
                'entityId' => $entityId,
                'children' => $children,
                'query' => $request->input('query', '') // Mantener el término de búsqueda si existe
            ]);
        } catch (\Exception $e) {
            if ($format === 'json') {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return view('icd11.index', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Obtiene los hijos de una entidad
     */
    public function getChildren(Request $request, $entityId)
    {
        $format = $request->input('format', 'json');

        try {
            $children = $this->icd11Service->getChildren($entityId);

            if ($format === 'json') {
                return response()->json(['success' => true, 'data' => $children]);
            }

            return view('icd11.index', [
                'children' => $children,
                'entityId' => $entityId
            ]);
        } catch (\Exception $e) {
            if ($format === 'json') {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return view('icd11.index', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Muestra la página con la herramienta de clasificación embebida ICD-11 ECT
     */
    public function embeddedTool()
    {
        return view('icd11.embedded-tool');
    }

    /**
     * Obtiene el token de autenticación para la API de ICD-11
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getApiToken()
    {
        try {
            // Obtener el token usando el servicio existente
            $token = $this->icd11Service->getToken();

            return response()->json([
                'success' => true,
                'token' => $token
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
