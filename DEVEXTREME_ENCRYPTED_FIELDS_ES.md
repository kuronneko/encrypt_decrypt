# DevExtreme con Campos Encriptados - Sistema de Componentes Modular

Este sistema proporciona un enfoque modular y reutilizable para manejar DataGrids de DevExtreme con soporte para campos encriptados, filtrado, ordenación y paginación.

## Descripción de Componentes

### 1. Trait `HandlesEncryptedFields`
Trait base para manejar operaciones de campos encriptados en modelos.

### 2. Trait `OptimizedEncryptedFields`  
Trait avanzado con optimización de caché para mejor rendimiento con campos encriptados.

### 3. Trait `DevExtremeOperations`
Trait de controlador que proporciona capacidades de manejo de solicitudes DevExtreme.

### 4. Componente `DevExtremeFilter`
Componente independiente para aplicar filtros a consultas con soporte para campos encriptados.

## Ejemplos de Uso

### Configuración Básica del Modelo

```php
<?php

namespace App\Models;

use App\Traits\HandlesEncryptedFields;
// o use App\Traits\OptimizedEncryptedFields; para mejor rendimiento

class User extends Model
{
    use HandlesEncryptedFields; // o OptimizedEncryptedFields

    /**
     * Define qué campos están encriptados
     */
    protected $encryptedFields = [
        'email',
        'telephone', // Agregar cualquier otro campo encriptado
    ];

    // Agregar accessors/mutators para campos encriptados
    public function setEmailAttribute($value) {
        // Lógica de encriptación
    }

    public function getEmailAttribute($value) {
        // Lógica de desencriptación
    }

    public function newModelInstance() {
        return new static();
    }
}
```

### Configuración del Controlador

```php
<?php

namespace App\Http\Controllers;

use App\Traits\DevExtremeOperations;

class UserController extends Controller
{
    use DevExtremeOperations;

    public function list(Request $request)
    {
        $query = User::query();
        $userModel = new User();

        $options = [
            'searchableFields' => ['id', 'name', 'email', 'telephone'],
            'encryptedFieldsHandler' => $userModel,
            'defaultSort' => ['id' => 'desc'],
            'dataTransformer' => function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email, // Auto-desencriptado
                    'telephone' => $user->telephone, // Auto-desencriptado
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                ];
            }
        ];

        return $this->handleDevExtremeRequest($request, $query, $options);
    }
}
```

## Agregar Nuevos Campos Encriptados

### Paso 1: Actualizar Modelo
```php
protected $encryptedFields = [
    'email',
    'telephone',
    'address',    // Nuevo campo
    'ssn',        // Nuevo campo
];
```

### Paso 2: Agregar Accessors/Mutators
```php
public function setAddressAttribute($value) {
    if (!empty($value)) {
        try {
            Crypt::decryptString($value);
            $this->attributes['address'] = $value;
        } catch (\Exception $e) {
            $this->attributes['address'] = Crypt::encryptString($value);
        }
    }
}

public function getAddressAttribute($value) {
    if (!empty($value)) {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }
    return $value;
}
```

### Paso 3: Actualizar Configuración del Controlador
```php
'searchableFields' => ['id', 'name', 'email', 'telephone', 'address', 'ssn'],
```

### Paso 4: Actualizar Frontend (Configuración DevExtreme)
```javascript
{
    dataField: "address",
    caption: "Dirección",
    filterOperations: ["contains", "=", "<>"],
    allowEditing: true,
},
{
    dataField: "ssn",
    caption: "NSS",
    filterOperations: ["contains", "=", "<>"],
    allowEditing: true,
},
```

## Consideraciones de Rendimiento

### Para Mejor Rendimiento:
1. Usar trait `OptimizedEncryptedFields` en lugar de `HandlesEncryptedFields`
2. Establecer duración de caché apropiada:
   ```php
   protected int $encryptedFieldsCacheDuration = 10; // minutos
   ```
3. Limpiar caché cuando cambien los datos encriptados:
   ```php
   $user->clearEncryptedFieldsCache();
   ```

### Gestión de Caché:
```php
// Limpiar caché después de actualizar campos encriptados
$user->update($data);
$user->clearEncryptedFieldsCache();
```

## Extender para Otros Modelos

### Ejemplo: Modelo Contact
```php
class Contact extends Model
{
    use OptimizedEncryptedFields;

    protected $encryptedFields = [
        'email',
        'telephone',
        'address',
        'notes',
    ];

    // Agregar métodos accessor/mutator para cada campo encriptado
}
```

### Ejemplo: ContactController
```php
class ContactController extends Controller
{
    use DevExtremeOperations;

    public function list(Request $request)
    {
        $query = Contact::query();
        $contactModel = new Contact();

        $options = [
            'searchableFields' => ['name', 'email', 'telephone', 'company'],
            'encryptedFieldsHandler' => $contactModel,
            'defaultSort' => ['created_at' => 'desc'],
            'dataTransformer' => function($contact) {
                return [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'telephone' => $contact->telephone,
                    'company' => $contact->company,
                    'created_at' => $contact->created_at->format('Y-m-d H:i:s'),
                ];
            }
        ];

        return $this->handleDevExtremeRequest($request, $query, $options);
    }
}
```

## Características

✅ **Diseño Modular**: Fácil de reutilizar en diferentes modelos y controladores  
✅ **Soporte de Campos Encriptados**: Manejo automático de filtrado y búsqueda de campos encriptados  
✅ **Optimizado para Rendimiento**: Soporte de caché para operaciones de campos encriptados  
✅ **Compatible con DevExtreme**: Soporte completo para paginación, filtrado, ordenación  
✅ **Seguridad de Tipos**: Manejo adecuado de diferentes tipos de datos (fechas, números, cadenas)  
✅ **Extensible**: Fácil de agregar nuevos campos encriptados o modelos  
✅ **Búsqueda Global**: Búsqueda en campos regulares y encriptados  
✅ **Operaciones de Filtro**: Soporte para contiene, igual, mayor que, etc.  

## Opciones de Configuración

| Opción | Tipo | Descripción |
|--------|------|-------------|
| `searchableFields` | array | Campos que pueden ser buscados globalmente |
| `encryptedFieldsHandler` | object | Instancia del modelo que maneja campos encriptados |
| `defaultSort` | array | Configuración de ordenación por defecto |
| `dataTransformer` | callable | Función para transformar datos antes de devolver |

Este sistema modular hace fácil agregar soporte de campos encriptados a cualquier modelo con mínima duplicación de código y máxima reutilización.

## Características Avanzadas: Soporte de Modelos Relacionados

### Configuración de Modelos Relacionados

El sistema ahora soporta filtrado y ordenación en campos encriptados de modelos relacionados. Esto es particularmente útil cuando necesitas mostrar y filtrar datos de relaciones.

#### Ejemplo: Relación Usuario con Ubicación

```php
// Modelo User
class User extends Model
{
    use OptimizedEncryptedFields;
    
    protected $encryptedFields = ['email'];
    
    public function locations()
    {
        return $this->hasMany(Location::class);
    }
}

// Modelo Location
class Location extends Model
{
    use OptimizedEncryptedFields;
    
    protected $encryptedFields = [
        'address',
        'postal_code',
        'region',
        'notes',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

#### Configuración del Controlador para Modelos Relacionados

```php
class UserController extends Controller
{
    use DevExtremeOperations;

    public function list(Request $request)
    {
        // Comenzar construyendo la consulta con la relación de ubicación
        $query = User::with('locations');

        // Crear instancias del modelo para manejar campos encriptados
        $userModel = new User();
        $locationModel = new Location();

        // Definir configuración para operaciones DevExtreme
        $options = [
            'searchableFields' => ['id', 'name', 'email', 'locations.postal_code'],
            'encryptedFieldsHandler' => $userModel, // Manejador para campos encriptados del modelo principal
            'relatedModels' => [
                'locations' => $locationModel // Manejador para campos encriptados de ubicación
            ],
            'defaultSort' => ['id' => 'desc'],
            'dataTransformer' => function($user) {
                // Obtener el código postal de la primera ubicación (ya que el usuario tiene una ubicación)
                $postalCode = $user->locations->first() ? $user->locations->first()->postal_code : null;
                
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email, // Será auto-desencriptado por el accessor del modelo
                    'postal_code' => $postalCode, // Será auto-desencriptado por el accessor del modelo Location
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $user->updated_at->format('Y-m-d H:i:s')
                ];
            }
        ];

        // Usar el trait para manejar la solicitud DevExtreme
        return $this->handleDevExtremeRequest($request, $query, $options);
    }
}
```

### Configuración Frontend DevExtreme

Agregar la columna del campo relacionado a tu DataGrid:

```javascript
columns: [
    {
        dataField: "id",
        caption: "ID",
        width: 70,
        dataType: "number",
    },
    {
        dataField: "name",
        caption: "Nombre",
        filterOperations: ["contains"],
        allowEditing: true,
    },
    {
        dataField: "email",
        caption: "Email",
        filterOperations: ["contains"],
        allowEditing: true,
    },
    {
        dataField: "postal_code",
        caption: "Código Postal",
        filterOperations: ["contains"],
        hidingPriority: 2,
        allowEditing: false,
        cellTemplate: function (container, options) {
            const postalCode = options.value || 'N/A';
            container.append(`<span>${postalCode}</span>`);
        },
    },
    // ... otras columnas
]
```

## Filtrado y Ordenación Avanzados

### Soporte de Filtrado

El sistema maneja automáticamente diferentes tipos de escenarios de filtrado:

#### 1. **Filtrado de Campo Directo**
```http
GET /users/list/?filter=["email","contains","juan@ejemplo.com"]
```

#### 2. **Filtrado de Campo de Modelo Relacionado**
```http
GET /users/list/?filter=["postal_code","contains","32040"]
```

#### 3. **Filtrado Complejo**
```http
GET /users/list/?filter=[["email","contains","juan"],["postal_code","contains","123"]]
```

### Soporte de Ordenación

El sistema proporciona ordenación inteligente con dos enfoques:

#### 1. **Ordenación Basada en SQL** (para campos no encriptados)
- Ordenación directa en base de datos
- Alto rendimiento para grandes conjuntos de datos
- Usado para campos regulares

#### 2. **Ordenación Post-Transformación** (para campos encriptados)
- Recupera todos los datos, desencripta, luego ordena en PHP
- Requerido para campos encriptados
- Mantiene integridad de datos

```http
GET /users/list/?sort=[{"selector":"postal_code","desc":false}]
```

### Cómo Funciona Internamente

#### Detección de Campos Encriptados
1. **Análisis de Campos**: El sistema verifica si el campo solicitado existe en los campos encriptados de cualquier modelo relacionado
2. **Selección de Ruta**: Elige entre ordenación SQL o post-transformación
3. **Procesamiento**: Aplica la estrategia apropiada de filtrado/ordenación

#### Proceso de Filtrado para Campos Relacionados Encriptados
```php
// 1. Obtener todos los registros relacionados
$allRelatedRecords = Location::all();

// 2. Desencriptar y filtrar en PHP
foreach ($allRelatedRecords as $record) {
    $decryptedValue = $record->postal_code; // Auto-desencriptado
    if (stripos($decryptedValue, $searchTerm) !== false) {
        $matchingIds[] = $record->id;
    }
}

// 3. Aplicar filtro SQL basado en coincidencias
$query->whereHas('locations', function($q) use ($matchingIds) {
    $q->whereIn('id', $matchingIds);
});
```

#### Proceso de Ordenación para Campos Encriptados
```php
// 1. Verificar si la ordenación requiere post-procesamiento
if ($needsPostSorting) {
    // 2. Obtener todos los datos filtrados
    $results = $query->get();
    
    // 3. Transformar datos (desencriptar)
    $results = $results->map($dataTransformer);
    
    // 4. Ordenar en PHP
    $results = $this->applyPostSorting($results, $sort);
    
    // 5. Aplicar paginación
    $results = $results->slice($skip, $take);
}
```

### Consideraciones de Rendimiento para Modelos Relacionados

#### Estrategias de Optimización
1. **Carga Ansiosa**: Siempre usar `with()` para prevenir consultas N+1
2. **Procesamiento Selectivo**: La post-ordenación solo se activa para ordenación de campos encriptados
3. **Caché**: Usar trait `OptimizedEncryptedFields` para mejor caché
4. **Indexación**: Asegurar índices apropiados de base de datos en claves foráneas

#### Gestión de Memoria
- La post-ordenación carga todos los registros filtrados en memoria
- Considerar límites de paginación para grandes conjuntos de datos
- Monitorear uso de memoria con operaciones de campos encriptados

### Opciones de Configuración para Modelos Relacionados

| Opción | Tipo | Descripción |
|--------|------|-------------|
| `relatedModels` | array | Mapa de nombres de relación a instancias de modelo |
| `searchableFields` | array | Puede incluir campos relacionados como 'locations.postal_code' |
| `dataTransformer` | callable | Debe manejar transformación de datos de modelo relacionado |

### Manejo de Errores

El sistema maneja graciosamente escenarios comunes:
- **Relaciones Faltantes**: Devuelve valores null/vacíos en lugar de errores
- **Fallas de Desencriptación**: Recurre a valores encriptados originales
- **Filtros Inválidos**: Ignora criterios de filtro mal formados

### Consideraciones de Seguridad

1. **Control de Acceso**: Siempre implementar autorización apropiada antes de exponer datos encriptados
2. **Rastros de Auditoría**: Considerar registrar acceso a campos encriptados
3. **Validación de Campos**: Validar qué campos pueden ser filtrados/ordenados
4. **Limitación de Tasa**: Implementar limitación de tasa para operaciones costosas

Esta implementación avanzada proporciona soporte integral para campos encriptados de modelos relacionados mientras mantiene estándares de rendimiento y seguridad.

## Sistema de Detección Dinámica de Campos

### Descripción General

El sistema incluye un mecanismo sofisticado de detección dinámica de campos que identifica automáticamente los campos en modelos relacionados sin requerir configuraciones hardcodeadas. Esto hace que el sistema sea verdaderamente modular y reutilizable en cualquier modelo de Laravel y estructura de base de datos.

### Cómo Funciona la Detección Dinámica

#### 1. **Resolución de Campos Multi-Nivel**

El sistema usa un enfoque en cascada para detectar si un campo existe en un modelo relacionado:

```php
protected function isFieldInRelatedModel($relatedModel, string $field): bool
{
    if (!$relatedModel) {
        return false;
    }

    // Nivel 1: Verificar campos fillable
    $fillable = $relatedModel->getFillable();
    if (!empty($fillable) && in_array($field, $fillable)) {
        return true;
    }

    // Nivel 2: Consultar esquema real de base de datos
    try {
        $tableName = $relatedModel->getTable();
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing($tableName);
        return in_array($field, $columns);
    } catch (\Exception $e) {
        return false; // Respaldo elegante
    }
}
```

#### 2. **Inspección Automática de Modelos**

Cuando un campo como `city` es solicitado para filtrado u ordenación:

1. **Verificar si el campo tiene notación de punto** (`locations.city`) → Campo relacionado directo
2. **Verificar campos encriptados del modelo principal** → Manejar con desencriptación
3. **Verificar campos regulares del modelo principal** → Operaciones SQL estándar
4. **Iterar a través de modelos relacionados**:
   - Verificar si el campo está encriptado en modelo relacionado → Requiere post-procesamiento
   - Verificar si el campo existe en el esquema del modelo relacionado → Operaciones SQL join
5. **Respaldo al manejo de campo regular**

#### 3. **Detección de Esquema en Tiempo Real**

El sistema consulta el esquema real de la base de datos usando la fachada Schema de Laravel:

```php
// Obtiene nombres reales de columnas de la tabla de base de datos
$columns = \Illuminate\Support\Facades\Schema::getColumnListing($tableName);
```

Esto significa que funciona con:
- ✅ Cualquier estructura de tabla existente
- ✅ Tablas creadas por migraciones
- ✅ Esquemas de base de datos legacy
- ✅ Modificaciones dinámicas de tabla

### Ejemplos de Implementación

#### Ejemplo 1: Sistema de E-commerce

```php
// Modelo Product
class Product extends Model
{
    use OptimizedEncryptedFields;
    
    protected $encryptedFields = ['supplier_contact'];
    protected $fillable = ['name', 'price', 'category_id', 'supplier_contact'];
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}

// Modelo Category  
class Category extends Model
{
    protected $fillable = ['name', 'description', 'parent_id'];
}

// El controlador detecta automáticamente:
// - 'supplier_contact' como campo encriptado en Product
// - 'name', 'description' como campos regulares en Category
// - Aplica la estrategia apropiada de filtrado/ordenación
```

#### Ejemplo 2: Sistema CRM

```php
// Modelo Contact
class Contact extends Model
{
    use OptimizedEncryptedFields;
    
    protected $encryptedFields = ['ssn', 'phone', 'email'];
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}

// Modelo Company
class Company extends Model
{
    protected $fillable = ['name', 'industry', 'size', 'website'];
}

// Configuración DevExtreme
$options = [
    'searchableFields' => ['name', 'ssn', 'phone', 'company_name', 'industry'],
    'relatedModels' => [
        'company' => new Company()
    ]
];

// El sistema maneja automáticamente:
// - 'ssn', 'phone' → Procesamiento de campos encriptados
// - 'company_name', 'industry' → Operaciones SQL de modelo relacionado
```

### Características Avanzadas

#### 1. **Resistencia a Errores**

```php
try {
    $columns = \Illuminate\Support\Facades\Schema::getColumnListing($tableName);
    return in_array($field, $columns);
} catch (\Exception $e) {
    // Respaldo elegante - el sistema continúa funcionando aún si falla la consulta de esquema
    return false;
}
```

#### 2. **Optimización de Rendimiento**

- **Caché de Esquema**: Las consultas de esquema de base de datos son cacheadas por Laravel
- **Retornos Tempranos**: Deja de verificar tan pronto como se encuentra el campo
- **Procesamiento Selectivo**: Solo consulta esquema cuando falla la verificación de fillable

#### 3. **Soporte Flexible de Modelos**

```php
// Funciona con cualquier configuración de modelo:

// Modelo con fillable explícito
class ModelA extends Model
{
    protected $fillable = ['field1', 'field2'];
}

// Modelo con guarded (fillable vacío)
class ModelB extends Model
{
    protected $guarded = ['id'];
}

// Modelo sin protección de asignación masiva
class ModelC extends Model
{
    protected $guarded = [];
}
```

### Mejores Prácticas de Configuración

#### 1. **Configuración Óptima de Fillable**

Para mejor rendimiento, siempre definir arrays `$fillable`:

```php
class Location extends Model
{
    use OptimizedEncryptedFields;
    
    protected $fillable = [
        'user_id', 'name', 'address', 'city', 'state', 
        'country', 'postal_code', 'region', 'latitude', 
        'longitude', 'notes', 'is_active'
    ];
    
    protected $encryptedFields = [
        'address', 'postal_code', 'region', 'notes'
    ];
}
```

#### 2. **Configuración de Controlador**

```php
public function list(Request $request)
{
    $query = User::with('locations', 'profile', 'orders');

    $options = [
        'searchableFields' => [
            'id', 'name', 'email',           // Campos de User
            'city', 'postal_code',           // Campos de Location (auto-detectados)
            'bio', 'preferences',            // Campos de Profile (auto-detectados)
            'total', 'status'                // Campos de Order (auto-detectados)
        ],
        'relatedModels' => [
            'locations' => new Location(),
            'profile' => new UserProfile(),
            'orders' => new Order()
        ]
    ];

    return $this->handleDevExtremeRequest($request, $query, $options);
}
```

### Depuración y Solución de Problemas

#### 1. **Logging de Detección de Campos**

Agregar logging para ver cómo se están detectando los campos:

```php
protected function isFieldInRelatedModel($relatedModel, string $field): bool
{
    if (!$relatedModel) {
        \Log::debug("Modelo relacionado es null para campo: {$field}");
        return false;
    }

    $fillable = $relatedModel->getFillable();
    if (!empty($fillable) && in_array($field, $fillable)) {
        \Log::debug("Campo {$field} encontrado en fillable para " . get_class($relatedModel));
        return true;
    }

    try {
        $tableName = $relatedModel->getTable();
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing($tableName);
        $exists = in_array($field, $columns);
        \Log::debug("Campo {$field} " . ($exists ? 'encontrado' : 'no encontrado') . " en tabla {$tableName}");
        return $exists;
    } catch (\Exception $e) {
        \Log::error("Detección de esquema falló para {$field}: " . $e->getMessage());
        return false;
    }
}
```

#### 2. **Problemas Comunes y Soluciones**

| Problema | Causa | Solución |
|----------|-------|----------|
| Campo no detectado | `$fillable` vacío y falla consulta de esquema | Definir array `$fillable` explícito |
| Rendimiento lento | Demasiadas consultas de esquema | Optimizar definiciones `$fillable` |
| Detección incorrecta de campo | Nombre de tabla o conexión incorrecta | Verificar propiedad `$table` del modelo |
| Conflictos de migración | Cambios de esquema durante solicitudes | Usar transacciones de base de datos |

Este sistema dinámico asegura que tu implementación DevExtreme permanezca flexible y mantenible mientras proporciona rendimiento óptimo para cualquier estructura de modelo.
