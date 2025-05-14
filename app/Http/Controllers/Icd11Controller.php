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
        try {
            $query = $request->get('query');

            if (empty($query)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El término de búsqueda es requerido',
                    'data' => []
                ], 400);
            }

            // Realizar la búsqueda
            $results = $this->icd11Service->search($query);

            return response()->json([
                'success' => true,
                'data' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al realizar la búsqueda: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Obtiene información de un código ICD-11 específico
     */
    public function getByCode(Request $request, $code)
    {
        try {
            if (empty($code)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El código ICD-11 es requerido',
                    'data' => null
                ], 400);
            }

            // Buscar entidad por código
            $entity = $this->icd11Service->findByCode($code);

            if (!$entity) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró información para el código ICD-11 proporcionado',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $entity
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información del código: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Obtiene detalles completos de una entidad ICD-11
     */
    public function getEntityDetails($id)
    {
        try {
            if (empty($id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El ID de la entidad es requerido',
                    'data' => null
                ], 400);
            }

            // Obtener la entidad
            $entity = $this->icd11Service->getEntity($id);

            return response()->json([
                'success' => true,
                'data' => $entity
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener detalles de la entidad: ' . $e->getMessage(),
                'data' => null
            ], 500);
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
     * Muestra la página con la herramienta de codificación ICD-11
     */
    public function codingTool()
    {
        return view('icd11.coding-tool');
    }

    /**
     * Muestra la página de prueba para la API de enfermedades
     */
    public function diseaseTester()
    {
        return view('icd11.disease-tester');
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

    /**
     * Obtiene detalles de una entidad usando URIs proporcionadas
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEntityByUri(Request $request)
    {
        try {
            // Validar datos mínimos requeridos
            $request->validate([
                'data' => 'required|array',
            ]);

            $data = $request->input('data');

            // Verificar que tenemos al menos una URI o un código
            if (empty($data['uri']) && empty($data['foundationUri']) && empty($data['linearizationUri']) && empty($data['code'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Se requiere al menos una URI (uri, foundationUri, linearizationUri) o un código',
                    'data' => null
                ], 400);
            }

            // Intentar obtener detalles de la entidad
            $entityDetails = $this->icd11Service->getEntityByUri($data);

            if (!$entityDetails) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo obtener información para la entidad especificada',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $entityDetails
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener entidad por URI', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información de la entidad: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Obtiene información detallada de una enfermedad directamente de la API oficial de la OMS por código
     *
     * @param Request $request
     * @param string $code Código ICD-11 (por ejemplo, MD12)
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDetailedDiseaseByCode(Request $request, $code)
    {
        try {
            // Validar que el código no esté vacío
            if (empty($code)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El código ICD-11 es requerido',
                    'data' => null
                ], 400);
            }

            // Registrar solicitud para monitoreo
            \Log::info('Solicitud de enfermedad detallada por código', [
                'code' => $code,
                'ip' => $request->ip()
            ]);

            // Obtener los datos detallados de la enfermedad
            $diseaseData = $this->icd11Service->getDetailedDiseaseByCode($code);

            // Si tenemos datos, devolver respuesta de éxito
            if ($diseaseData) {
                return response()->json([
                    'success' => true,
                    'code' => $code,
                    'data' => $diseaseData
                ]);
            }

            // Si no hay datos, es un error 404
            return response()->json([
                'success' => false,
                'message' => 'No se encontró información detallada para el código "' . $code . '"',
                'data' => null
            ], 404);

        } catch (\Exception $e) {
            \Log::error('Error al obtener detalles de enfermedad por código', [
                'error' => $e->getMessage(),
                'code' => $code
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información detallada: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Muestra la documentación de la API
     */
    public function apiDocs()
    {
        return view('icd11.api-docs');
    }
}
