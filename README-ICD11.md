# Integración de la API ICD-11 de la OMS

Este documento describe cómo se ha integrado la API de la Clasificación Internacional de Enfermedades (ICD-11) de la Organización Mundial de la Salud (OMS) en este proyecto Laravel.

## Configuración

### 1. Variables de entorno

Agrega las siguientes variables en tu archivo `.env`:

```
ICD11_CLIENT_ID=tu_client_id
ICD11_CLIENT_SECRET=tu_client_secret
```

Reemplaza `tu_client_id` y `tu_client_secret` con las credenciales que ya tienes para la API de ICD-11.

### 2. Estructura de la integración

La integración consta de los siguientes componentes:

- **Icd11Service**: Clase de servicio que maneja la autenticación y las solicitudes a la API.
- **Icd11ServiceProvider**: Proveedor de servicios que registra el servicio en el contenedor de Laravel.
- **Icd11Controller**: Controlador de ejemplo que muestra cómo usar el servicio.

## Uso del servicio

### Inyección de dependencias

Puedes usar el servicio en cualquier parte de tu aplicación mediante inyección de dependencias:

```php
use App\Services\Icd11Service;

class MiClase
{
    protected $icd11Service;
    
    public function __construct(Icd11Service $icd11Service)
    {
        $this->icd11Service = $icd11Service;
    }
    
    public function miMetodo()
    {
        // Usar el servicio
        $resultados = $this->icd11Service->search('diabetes');
    }
}
```

### Métodos disponibles

El servicio `Icd11Service` proporciona los siguientes métodos:

- **search($query, $params = [])**: Busca entidades en ICD-11.
- **getEntity($entityId)**: Obtiene una entidad por su ID.
- **getChildren($entityId)**: Obtiene los hijos de una entidad.
- **getParents($entityId)**: Obtiene los padres de una entidad.
- **request($endpoint, $params = [], $method = 'GET')**: Método genérico para realizar solicitudes a cualquier endpoint de la API.

### Ejemplo de rutas

Puedes agregar las siguientes rutas en tu archivo `routes/web.php` o `routes/api.php`:

```php
// Para web.php
Route::get('/icd11', [App\Http\Controllers\Icd11Controller::class, 'index']);
Route::get('/icd11/search', [App\Http\Controllers\Icd11Controller::class, 'search']);
Route::get('/icd11/entity/{entityId}', [App\Http\Controllers\Icd11Controller::class, 'getEntity']);
Route::get('/icd11/entity/{entityId}/children', [App\Http\Controllers\Icd11Controller::class, 'getChildren']);

// Para api.php
Route::prefix('api/icd11')->group(function () {
    Route::get('/search', [App\Http\Controllers\Icd11Controller::class, 'search']);
    Route::get('/entity/{entityId}', [App\Http\Controllers\Icd11Controller::class, 'getEntity']);
    Route::get('/entity/{entityId}/children', [App\Http\Controllers\Icd11Controller::class, 'getChildren']);
});
```

## Documentación de la API

Para más información sobre los endpoints disponibles y los parámetros que aceptan, consulta la documentación oficial de la API ICD-11:

[https://id.who.int/icd/release/11/swagger.html](https://id.who.int/icd/release/11/swagger.html)