<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio extendido para consultar información ICD-11 directamente desde
 * la interfaz del navegador de la OMS cuando las APIs normales fallan
 */
class Icd11EnhancedBrowserService
{
    /**
     * Intenta extraer información de enfermedad desde múltiples fuentes de la interfaz del navegador
     *
     * @param string $code Código ICD-11
     * @return array Datos extraídos o array vacío si no se encuentra información
     */
    public function fetchDiseaseInfoFromBrowser($code)
    {
        $result = [
            'code' => $code,
            'title' => '',
            'description' => '',
            'source' => 'browser'
        ];

        try {
            // 1. Intentar extraer de la interfaz HTML del navegador
            $htmlInfo = $this->extractFromHtmlBrowser($code);
            if (!empty($htmlInfo['title'])) {
                $result['title'] = $htmlInfo['title'];
            }
            if (!empty($htmlInfo['description'])) {
                $result['description'] = $htmlInfo['description'];
            }

            // 2. Si falta algo, intentar con la API JSON del navegador
            if (empty($result['title']) || empty($result['description'])) {
                $jsonInfo = $this->extractFromJsonApi($code);

                if (empty($result['title']) && !empty($jsonInfo['title'])) {
                    $result['title'] = $jsonInfo['title'];
                }

                if (empty($result['description']) && !empty($jsonInfo['description'])) {
                    $result['description'] = $jsonInfo['description'];
                }
            }

            // Limpiar datos HTML de los textos si existen
            if (!empty($result['title'])) {
                $result['title'] = $this->cleanHtml($result['title']);
            }

            if (!empty($result['description'])) {
                $result['description'] = $this->cleanHtml($result['description']);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Error al obtener datos desde browser fallback', [
                'error' => $e->getMessage(),
                'code' => $code
            ]);

            return $result;
        }
    }

    /**
     * Extrae información desde la interfaz HTML del navegador ICD-11
     */
    private function extractFromHtmlBrowser($code)
    {
        $result = [
            'title' => '',
            'description' => ''
        ];

        // URLs a probar para la interfaz del navegador
        $browserUrls = [
            "https://icd.who.int/browse11/l-m/es/GetConcept/{$code}",
            "https://icd.who.int/ct11/icd11_mms/en/getConcept/{$code}",
            "https://icd.who.int/browse11/l-m/es/http%3a%2f%2fid.who.int%2ficd%2fentity%2f{$code}",
            "https://icd.who.int/browse11/l-m/en/GetConcept/{$code}",
        ];

        foreach ($browserUrls as $url) {
            try {
                $response = Http::timeout(10)->get($url);

                if ($response->successful()) {
                    $html = $response->body();

                    // Intentar extraer el título mediante varios patrones
                    $titlePatterns = [
                        '/<h1[^>]*class="entityTitle"[^>]*>(.*?)<\/h1>/s',
                        '/<div[^>]*class="title"[^>]*>(.*?)<\/div>/s',
                        '/<span[^>]*class="title"[^>]*>(.*?)<\/span>/s',
                        '/<title>(.*?)<\/title>/s'
                    ];

                    foreach ($titlePatterns as $pattern) {
                        if (preg_match($pattern, $html, $matches)) {
                            $title = $this->cleanHtml($matches[1]);
                            // Eliminar textos comunes del título de página si los hay
                            $title = preg_replace('/\s*\|\s*ICD-11.*$/i', '', $title);
                            $title = preg_replace('/\s*-\s*ICD-11.*$/i', '', $title);

                            if (!empty($title)) {
                                $result['title'] = $title;
                                break;
                            }
                        }
                    }

                    // Intentar extraer la descripción
                    $descriptionPatterns = [
                        '/<div[^>]*class="description"[^>]*>(.*?)<\/div>/s',
                        '/<div[^>]*id="definition"[^>]*>(.*?)<\/div>/s',
                        '/<div[^>]*class="definition"[^>]*>(.*?)<\/div>/s'
                    ];

                    foreach ($descriptionPatterns as $pattern) {
                        if (preg_match($pattern, $html, $matches)) {
                            $description = $this->cleanHtml($matches[1]);

                            if (!empty($description)) {
                                $result['description'] = $description;
                                break;
                            }
                        }
                    }

                    // Si encontramos tanto título como descripción, podemos salir
                    if (!empty($result['title']) && !empty($result['description'])) {
                        break;
                    }
                }
            } catch (\Exception $e) {
                Log::debug("Falló la extracción HTML desde {$url}: " . $e->getMessage());
                // Continuar con la siguiente URL
            }
        }

        return $result;
    }

    /**
     * Extrae información desde la API JSON del navegador ICD-11
     */
    private function extractFromJsonApi($code)
    {
        $result = [
            'title' => '',
            'description' => ''
        ];

        // URLs de APIs JSON a probar
        $jsonUrls = [
            "https://icd.who.int/browse11/l-m/en/JsonService/GetConcept?ConceptId={$code}",
            "https://icd.who.int/browse11/l-m/es/JsonService/GetConcept?ConceptId={$code}",
            "https://icd.who.int/ct11/icd11_mms/en/JsonService/GetConcept?ConceptId={$code}"
        ];

        // Si el código tiene una parte numérica, probar también con ella
        if (preg_match('/(\d+)/', $code, $matches)) {
            $numericId = $matches[1];
            $jsonUrls[] = "https://icd.who.int/browse11/l-m/en/JsonService/GetConcept?ConceptId={$numericId}";
        }

        foreach ($jsonUrls as $url) {
            try {
                $response = Http::timeout(10)->get($url);

                if ($response->successful()) {
                    $data = $response->json();

                    // Buscar título en diferentes posibles campos JSON
                    $titleFields = ['Title', 'title', 'label', 'name', 'displayName'];
                    foreach ($titleFields as $field) {
                        if (!empty($data[$field])) {
                            $result['title'] = $data[$field];
                            break;
                        }
                    }

                    // Buscar descripción en diferentes posibles campos JSON
                    $descriptionFields = ['Definition', 'definition', 'description', 'longDefinition', 'browserDescription', 'FullySpecifiedName'];
                    foreach ($descriptionFields as $field) {
                        if (!empty($data[$field])) {
                            $result['description'] = $data[$field];
                            break;
                        }
                    }

                    // Si encontramos lo que buscamos, podemos salir
                    if (!empty($result['title']) && !empty($result['description'])) {
                        break;
                    }
                }
            } catch (\Exception $e) {
                Log::debug("Falló la extracción JSON desde {$url}: " . $e->getMessage());
                // Continuar con la siguiente URL
            }
        }

        return $result;
    }

    /**
     * Limpia texto HTML
     */
    private function cleanHtml($text)
    {
        // Eliminar etiquetas HTML
        $text = strip_tags($text);

        // Convertir entidades HTML
        $text = html_entity_decode($text);

        // Eliminar espacios extra
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}
