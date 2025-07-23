# Sistema DevExtreme con Campos Encriptados

Sistema completo para implementar componentes DevExtreme con soporte para campos encriptados en Laravel. Permite filtrado, búsqueda y ordenamiento tanto de campos normales como encriptados.

## 🚀 Características

- ✅ **Ordenamiento** - Funciona con campos normales y encriptados
- ✅ **Filtrado** - Soporte completo para filtros de columnas
- ✅ **Búsqueda global** - Búsqueda en múltiples campos
- ✅ **Campos encriptados** - Manejo automático de encriptación/desencriptación
- ✅ **Modelos relacionados** - Soporte para relaciones con campos encriptados
- ✅ **Validación de campos** - Restricción de campos buscables
- ✅ **Paginación** - Paginación optimizada para grandes datasets

## 📁 Estructura de Archivos

```
app/
├── Components/
│   ├── DevExtremeHandler.php      # Manejador principal
│   ├── DevExtremeFilter.php       # Manejo de filtros
│   └── DevExtremeConfig.php       # Configuración fluida
├── Http/Controllers/
│   └── UserController.php         # Ejemplo de controlador
├── Models/
│   ├── User.php                   # Modelo con campos encriptados
│   └── Location.php               # Modelo relacionado
└── Traits/
    ├── HandlesEncryptedFields.php           # Trait básico para campos encriptados
    ├── OptimizedEncryptedFields.php         # Trait optimizado con caché
    └── AdvancedOptimizedEncryptedFields.php # Trait avanzado con caché y hash mapping
```

## 🔧 Instalación y Configuración

## 🚀 Traits para Campos Encriptados

Este sistema incluye tres traits diferentes para manejar campos encriptados, cada uno optimizado para diferentes casos de uso:

### 1. HandlesEncryptedFields (Básico)
Trait básico que proporciona funcionalidad fundamental para campos encriptados.

### 2. OptimizedEncryptedFields (Optimizado)  
Versión mejorada con caché para mejor rendimiento en aplicaciones medianas.

### 3. AdvancedOptimizedEncryptedFields (Avanzado) ⭐
**Recomendado para aplicaciones grandes con muchos datos encriptados**

Trait más avanzado que incluye:

- ✅ **Hash Mapping**: Para búsquedas exactas ultra-rápidas
- ✅ **Caché Inteligente**: Sistema de caché multinivel con diferentes duraciones
- ✅ **Búsqueda Multi-Modelo**: Búsqueda simultánea en múltiples modelos relacionados
- ✅ **Procesamiento por Chunks**: Manejo eficiente de grandes datasets
- ✅ **Auto-limpieza de Caché**: Limpieza automática cuando los datos cambian

#### Configuración del Trait Avanzado

```php
<?php
// app/Models/User.php

namespace App\Models;

use App\Traits\AdvancedOptimizedEncryptedFields;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use AdvancedOptimizedEncryptedFields; // ← Usar el trait avanzado

    // Configurar duración del caché (opcional)
    protected int $encryptedFieldsCacheDuration = 120;    // 2 horas para búsquedas
    protected int $hashMappingCacheDuration = 480;        // 8 horas para hash mapping

    protected $encryptedFields = [
        'email',
        'telephone',
    ];

    // ... resto de la configuración del modelo
}
```

#### Características Avanzadas

**Hash Mapping para Búsquedas Exactas:**
```php
// Búsquedas exactas usan hash mapping (ultra-rápido)
$users = User::where('email', '=', 'usuario@email.com')->get();

// El sistema crea un hash del valor y lo busca directamente
// sin necesidad de desencriptar todos los registros
```

**Búsqueda Multi-Modelo:**
```php
// En el controlador usando DevExtremeHandler
$config = DevExtremeConfig::make()
    ->encryptedFieldsHandler(new User())
    ->relatedModel('locations', new Location())
    ->searchableFields([
        'name',
        'email',                    // Campo encriptado del modelo principal
        'locations.address',        // Campo encriptado del modelo relacionado
        'locations.postal_code',    // Otro campo encriptado relacionado
    ])
    ->build();

// El trait maneja automáticamente la búsqueda en ambos modelos
// y optimiza las consultas usando caché y hash mapping
```

**Control Manual del Caché:**
```php
// Limpiar caché de un modelo específico
$user = new User();
$user->clearEncryptedFieldsCache();

// Limpiar caché de múltiples modelos relacionados
$user->clearAllRelatedEncryptedCache([
    new Location(),
    new Company(),
]);

// Limpiar hash mapping de un campo específico
$user->clearHashMappingCache('email');
```

**Configuración de Rendimiento:**
```php
// En tu modelo, puedes ajustar la configuración de caché
class User extends Authenticatable
{
    use AdvancedOptimizedEncryptedFields;

    // Para aplicaciones con datos muy estables
    protected int $encryptedFieldsCacheDuration = 240;   // 4 horas
    protected int $hashMappingCacheDuration = 1440;      // 24 horas

    // Para aplicaciones con datos que cambian frecuentemente
    protected int $encryptedFieldsCacheDuration = 60;    // 1 hora
    protected int $hashMappingCacheDuration = 120;       // 2 horas
}
```

**Comparación de Rendimiento:**

| Trait | Búsqueda Exacta | Búsqueda Contiene | Multi-Modelo | Grandes Datasets |
|-------|----------------|-------------------|--------------|------------------|
| HandlesEncryptedFields | ❌ Lento | ❌ Lento | ❌ Manual | ❌ Problemas |
| OptimizedEncryptedFields | ✅ Rápido | ✅ Rápido | ⚠️ Limitado | ⚠️ Aceptable |
| AdvancedOptimizedEncryptedFields | ⭐ Ultra-rápido | ⭐ Muy rápido | ⭐ Automático | ⭐ Excelente |


### 1. Configurar el Modelo Principal

```php
<?php
// app/Models/User.php

namespace App\Models;

use App\Traits\AdvancedOptimizedEncryptedFields; // ← Usar el trait avanzado
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Crypt;

class User extends Authenticatable
{
    use AdvancedOptimizedEncryptedFields; // ← Recomendado para mejor rendimiento

    // Configuración del caché (opcional)
    protected int $encryptedFieldsCacheDuration = 120;    // 2 horas
    protected int $hashMappingCacheDuration = 480;        // 8 horas

    // Definir campos encriptados
    protected $encryptedFields = [
        'email',
        'telephone', // Agregar más según necesites
    ];

    protected $fillable = [
        'name',
        'email', 
        'password',
        'telephone',
    ];

    // Encriptar al guardar
    public function setEmailAttribute($value)
    {
        try {
            Crypt::decryptString($value);
            $this->attributes['email'] = $value;
        } catch (\Exception $e) {
            $this->attributes['email'] = Crypt::encryptString($value);
        }
    }

    // Desencriptar al obtener
    public function getEmailAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    // Relación
    public function locations()
    {
        return $this->hasMany(Location::class);
    }
}
```

### 2. Configurar Modelo Relacionado

```php
<?php
// app/Models/Location.php

namespace App\Models;

use App\Traits\AdvancedOptimizedEncryptedFields; // ← Usar el trait avanzado
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use AdvancedOptimizedEncryptedFields;

    protected $encryptedFields = [
        'postal_code',
        'address',
        'region',
        'notes',
    ];

    protected $fillable = [
        'user_id',
        'city',
        'postal_code',
        'address',
        'region',
        'notes',
    ];

    // Implementar accessors/mutators como en User.php
    public function setPostalCodeAttribute($value) { /* ... */ }
    public function getPostalCodeAttribute($value) { /* ... */ }
    public function setAddressAttribute($value) { /* ... */ }
    public function getAddressAttribute($value) { /* ... */ }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### 3. Implementar en el Controlador

```php
<?php
// app/Http/Controllers/UserController.php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Location;
use App\Components\{DevExtremeHandler, DevExtremeConfig};
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function list(Request $request)
    {
        $query = User::with('locations');

        $config = DevExtremeConfig::make()
            // Modelo principal con campos encriptados
            ->encryptedFieldsHandler(new User())
            
            // Modelos relacionados
            ->relatedModel('locations', new Location())
            
            // Campos permitidos para búsqueda/filtrado
            ->searchableFields([
                'id', 
                'name', 
                'email',                    // Campo encriptado
                'locations.city',           // Campo relacionado normal
                'locations.postal_code',    // Campo relacionado encriptado
                'locations.address'         // Campo relacionado encriptado
            ])
            
            // Ordenamiento por defecto
            ->sortBy('id', 'asc')
            
            // Transformación de datos
            ->transform(function ($user) {
                $location = $user->locations->first();
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,                    // Auto-desencriptado
                    'city' => $location?->city,
                    'postal_code' => $location?->postal_code,   // Auto-desencriptado
                    'address' => $location?->address,           // Auto-desencriptado
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                ];
            })
            ->build();

        return DevExtremeHandler::create($query, $config)->handle($request);
    }
}
```

## 📝 Uso Básico

### Ejemplo Mínimo

```php
public function basicList(Request $request)
{
    $query = User::query();
    
    $config = DevExtremeConfig::make()
        ->searchableFields(['id', 'name', 'email'])
        ->build();
    
    return DevExtremeHandler::create($query, $config)->handle($request);
}
```

### Con Transformación de Datos

```php
$config = DevExtremeConfig::make()
    ->searchableFields(['name', 'email'])
    ->transform(function($user) {
        return [
            'id' => $user->id,
            'display_name' => strtoupper($user->name),
            'email' => $user->email,
            'status' => $user->active ? 'Activo' : 'Inactivo'
        ];
    })
    ->build();
```

## 🔐 Agregar Nuevos Campos Encriptados

### Paso 1: Actualizar el Modelo

```php
// En tu modelo (User.php, Location.php, etc.)
protected $encryptedFields = [
    'email',
    'telephone',     // ← NUEVO CAMPO
    'address',       // ← NUEVO CAMPO
];

protected $fillable = [
    'name',
    'email',
    'telephone',     // ← AGREGAR AQUÍ
    'address',       // ← AGREGAR AQUÍ
];
```

### Paso 2: Crear Accessors y Mutators

```php
// Para el campo 'telephone'
public function setTelephoneAttribute($value)
{
    if (!empty($value)) {
        try {
            Crypt::decryptString($value);
            $this->attributes['telephone'] = $value;
        } catch (\Exception $e) {
            $this->attributes['telephone'] = Crypt::encryptString($value);
        }
    }
}

public function getTelephoneAttribute($value)
{
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

### Paso 3: Actualizar Configuración

```php
$config = DevExtremeConfig::make()
    ->encryptedFieldsHandler(new User())
    ->searchableFields([
        'id', 
        'name', 
        'email',
        'telephone',    // ← AGREGAR EL NUEVO CAMPO
        'address'       // ← AGREGAR EL NUEVO CAMPO
    ])
    ->transform(function ($user) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'telephone' => $user->telephone,  // ← INCLUIR EN RESPUESTA
            'address' => $user->address,      // ← INCLUIR EN RESPUESTA
        ];
    })
    ->build();
```

## 🔗 Agregar Nuevos Modelos Relacionados

### Paso 1: Crear el Modelo

```php
<?php
// app/Models/Company.php

namespace App\Models;

use App\Traits\HandlesEncryptedFields;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HandlesEncryptedFields;

    protected $encryptedFields = [
        'tax_id',
        'bank_account',
    ];

    protected $fillable = [
        'user_id',
        'name',
        'tax_id',
        'bank_account',
    ];

    // Implementar accessors/mutators
    public function setTaxIdAttribute($value) { /* ... */ }
    public function getTaxIdAttribute($value) { /* ... */ }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### Paso 2: Agregar Relación en User

```php
// En User.php
public function companies()
{
    return $this->hasMany(Company::class);
}
```

### Paso 3: Actualizar Configuración

```php
$query = User::with(['locations', 'companies']); // ← INCLUIR NUEVA RELACIÓN

$config = DevExtremeConfig::make()
    ->encryptedFieldsHandler(new User())
    ->relatedModel('locations', new Location())
    ->relatedModel('companies', new Company())     // ← AGREGAR NUEVO MODELO
    ->searchableFields([
        'id', 
        'name', 
        'email',
        'locations.city',
        'locations.postal_code',
        'companies.name',           // ← CAMPOS DEL NUEVO MODELO
        'companies.tax_id',         // ← CAMPO ENCRIPTADO
    ])
    ->transform(function ($user) {
        $location = $user->locations->first();
        $company = $user->companies->first();
        
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'city' => $location?->city,
            'postal_code' => $location?->postal_code,
            'company_name' => $company?->name,          // ← DATOS DEL NUEVO MODELO
            'company_tax_id' => $company?->tax_id,      // ← AUTO-DESENCRIPTADO
        ];
    })
    ->build();
```

## ⚙️ Configuración Avanzada

### Múltiples Ordenamientos

```php
$config = DevExtremeConfig::make()
    ->sortBy('name', 'asc')
    ->sortBy('created_at', 'desc')  // Ordenamiento secundario
    ->build();
```

### Filtrado Personalizado

```php
// Solo permitir ciertos campos para filtrado
->searchableFields(['id', 'name'])  // Solo estos campos son filtrables
```

### Transformación Compleja

```php
->transform(function ($user) {
    return [
        'id' => $user->id,
        'full_info' => [
            'personal' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'locations' => $user->locations->map(function($loc) {
                return [
                    'city' => $loc->city,
                    'address' => $loc->address, // Auto-desencriptado
                ];
            }),
        ],
        'stats' => [
            'locations_count' => $user->locations->count(),
            'created_days_ago' => $user->created_at->diffInDays(now()),
        ]
    ];
})
```

## 🐛 Troubleshooting

### Problema: El ordenamiento no funciona
**Solución:** Verificar que el campo esté en `searchableFields` y que tenga el accessor correcto.

### Problema: Los campos encriptados no se desencriptan
**Solución:** 
1. Verificar que el campo esté en `$encryptedFields`
2. Implementar correctamente `getXxxAttribute()`
3. Verificar que el trait esté incluido

### Problema: Filtrado no funciona en campos relacionados
**Solución:** 
1. Usar notación de punto: `locations.postal_code`
2. Incluir el campo en `searchableFields`
3. Verificar que la relación esté cargada con `with()`

### Problema: Error de memoria con muchos registros
**Solución:** Los campos encriptados usan post-sorting (obtiene todos los registros). Para datasets grandes, considera:
1. Usar filtros más específicos
2. Implementar paginación del lado del servidor
3. Limitar los campos encriptados solo a los necesarios
4. **⭐ Usar `AdvancedOptimizedEncryptedFields` para mejor manejo de memoria**

### Problemas específicos del AdvancedOptimizedEncryptedFields

### Problema: El caché no se actualiza después de cambiar datos
**Solución:** El trait incluye auto-limpieza, pero si tienes problemas:
```php
// Limpiar caché manualmente
$user = new User();
$user->clearEncryptedFieldsCache();

// O limpiar todo el caché
Cache::flush();
```

### Problema: Búsquedas lentas en el primer acceso
**Esto es normal:** El trait construye el hash mapping en el primer acceso. Las siguientes búsquedas serán ultra-rápidas.

### Problema: Mucho uso de memoria con datasets gigantes
**Solución:** Ajustar la configuración de chunks:
```php
// En config/cache.php o .env
ENCRYPTED_FIELDS_CHUNK_SIZE=500  // Reducir el tamaño de chunk por defecto
```

### Problema: El hash mapping no funciona para búsquedas parciales
**Esto es por diseño:** El hash mapping solo funciona para búsquedas exactas (`=` o `equals`). Las búsquedas con `contains` usan el método de caché estándar.

## 📊 Rendimiento

### Comparación de Traits

| Aspecto | HandlesEncryptedFields | OptimizedEncryptedFields | AdvancedOptimizedEncryptedFields |
|---------|----------------------|------------------------|--------------------------------|
| **Búsqueda Exacta** | 🔴 Muy lento (descifra todo) | 🟡 Rápido (con caché) | 🟢 Ultra-rápido (hash mapping) |
| **Búsqueda Contiene** | 🔴 Muy lento | 🟡 Rápido (con caché) | 🟢 Muy rápido (caché optimizado) |
| **Multi-Modelo** | 🔴 Manual | 🟡 Limitado | 🟢 Automático y optimizado |
| **Memoria** | 🔴 Problemas con datasets grandes | 🟡 Mejor con caché | 🟢 Chunks + caché inteligente |
| **Primer acceso** | 🔴 Lento | 🔴 Lento | 🟡 Lento (construye hash) |
| **Accesos siguientes** | 🔴 Siempre lento | 🟢 Rápido | 🟢 Ultra-rápido |

### Rendimiento por Tipo de Operación

- **Campos normales**: Ordenamiento y filtrado en base de datos (ultra-rápido)
- **Campos encriptados (básico)**: Ordenamiento en aplicación (muy lento)
- **Campos encriptados (optimizado)**: Ordenamiento en aplicación con caché (rápido)
- **Campos encriptados (avanzado)**: Hash mapping + caché multinivel (ultra-rápido)

### Recomendaciones de Uso

#### Usar HandlesEncryptedFields cuando:
- ✅ Aplicación pequeña (< 1,000 registros)
- ✅ Pocas búsquedas en campos encriptados
- ✅ Prototipo o desarrollo inicial

#### Usar OptimizedEncryptedFields cuando:
- ✅ Aplicación mediana (1,000 - 10,000 registros)
- ✅ Búsquedas frecuentes pero no críticas
- ✅ Recursos de caché limitados

#### Usar AdvancedOptimizedEncryptedFields cuando: ⭐
- ✅ Aplicación grande (> 10,000 registros)
- ✅ Búsquedas críticas para la experiencia del usuario
- ✅ Múltiples modelos con campos encriptados
- ✅ Alto volumen de búsquedas exactas
- ✅ Disponibilidad de Redis o caché robusto

### Configuración de Caché Recomendada

```php
// Para aplicaciones pequeñas-medianas
protected int $encryptedFieldsCacheDuration = 60;     // 1 hora
protected int $hashMappingCacheDuration = 240;        // 4 horas

// Para aplicaciones grandes con datos estables
protected int $encryptedFieldsCacheDuration = 240;    // 4 horas
protected int $hashMappingCacheDuration = 1440;       // 24 horas

// Para aplicaciones con datos que cambian constantemente
protected int $encryptedFieldsCacheDuration = 30;     // 30 minutos
protected int $hashMappingCacheDuration = 120;        // 2 horas
```

## 🔍 Logging y Debug

Para habilitar logs detallados, el sistema usa el log de Laravel:

```php
// En .env
LOG_LEVEL=debug

// Los logs aparecerán en storage/logs/laravel.log
```

## 📱 Frontend (DevExtreme)

Ejemplo de configuración del DataGrid:

```javascript
function sendRequest(url, method, data) {
    var d = $.Deferred();
    method = method || "GET";
    $.ajax(url, {
        method: method || "GET",
        data: data,
        cache: false,
        xhrFields: {
            withCredentials: true,
        },
    })
        .done(function (result) {
            d.resolve(result);
        })
        .fail(function (xhr) {
            if (xhr && xhr.responseJSON && xhr.responseJSON.errors) {
                Object.keys(xhr.responseJSON.errors)
                    .reverse()
                    .forEach((key) => {
                        xhr.responseJSON.errors[key].forEach((errorMessage) => {
                            showNotification("error", errorMessage);
                        });
                    });
            } else {
                showNotification("error", "Error al obtener los datos");
            }
            d.reject(xhr);
        });
    return d.promise();
}

$(document).ready(async function (e) {
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });

    let tableDataUrl = `/users/list/`;

    let detailGrid;
    let items = new DevExpress.data.CustomStore({
        key: "id",
        load: function (loadOptions) {
            // Prepare parameters for server-side processing
            let params = {};

            // Pagination parameters
            if (loadOptions.skip !== undefined) {
                params.skip = loadOptions.skip;
            }
            if (loadOptions.take !== undefined) {
                params.take = loadOptions.take;
            }

            // Search parameters
            if (loadOptions.searchValue) {
                params.searchValue = loadOptions.searchValue;
            }

            // Filter parameters
            if (loadOptions.filter) {
                params.filter = JSON.stringify(loadOptions.filter);
            }

            // Sort parameters
            if (loadOptions.sort) {
                params.sort = JSON.stringify(loadOptions.sort);
            }

            return sendRequest(
                tableDataUrl + "?" + $.param(params),
                "GET"
            ).then(function (result) {
                return {
                    data: result.data,
                    totalCount: result.totalCount,
                };
            });
        },
    });

    DevExpress.localization.locale("es-CL");

    const dataGrid = $("#usersGrid")
        .dxDataGrid({
            dataSource: {
                store: items,
                paginate: true,
                pageSize: 10,
            },

            remoteOperations: {
                paging: true,
                filtering: true,
                sorting: true,
                grouping: false,
                summary: false,
            },
            columnAutoWidth: true,
            showBorders: true,
            hoverStateEnabled: true,
            columnHidingEnabled: true,
            allowColumnReordering: true,
            wordWrapEnabled: true,
            /*            searchPanel: {
                // 1 panel para buscar palabras
                visible: true,
                width: "90%",
                placeholder: "Buscar...",
            }, */
            headerFilter: {
                visible: false,
            },
            filterRow: {
                visible: true,
                applyFilter: "auto",
                betweenStartText: "Inicio",
                betweenEndText: "Fin",
            },
            pager: {
                allowedPageSizes: [10, 25, 50, 100],
                showInfo: true,
                showNavigationButtons: true,
                showPageSizeSelector: true,
                visible: "auto",
            },
            paging: {
                pageSize: 10,
            },

            columnChooser: {
                enabled: false,
                mode: "select",
            },
            columns: [
                {
                    dataField: "id",
                    caption: "ID",
                    filterOperations: [
                        "=",
                        "<>",
                        "<",
                        "<=",
                        ">",
                        ">=",
                        "between",
                    ],
                    hidingPriority: 1,
                    allowEditing: true,
                    width: 70,
                    dataType: "number",
                },
                {
                    dataField: "name",
                    caption: "Nombre",
                    filterOperations: ["contains"],
                    hidingPriority: 1,
                    allowEditing: true,
                },
                {
                    dataField: "email",
                    caption: "Email",
                    filterOperations: ["contains"],
                    hidingPriority: 1,
                    allowEditing: true,
                },
                {
                    dataField: "city",
                    caption: "Ciudad",
                    filterOperations: ["contains"],
                    hidingPriority: 1,
                    allowEditing: true,
                },
                {
                    dataField: "postal_code",
                    caption: "Código Postal",
                    filterOperations: ["contains"],
                    hidingPriority: 2,
                    allowEditing: false,
                    cellTemplate: function (container, options) {
                        const postalCode = options.value || "N/A";
                        container.append(`<span>${postalCode}</span>`);
                    },
                },
                {
                    dataField: "created_at",
                    caption: "Fecha de creación",
                    filterOperations: [
                        "=",
                        "<>",
                        "<",
                        "<=",
                        ">",
                        ">=",
                        "between",
                    ],
                    hidingPriority: 6,
                    dataType: "datetime",
                    format: "dd/MM/yyyy HH:mm",
                },
                {
                    dataField: "updated_at",
                    caption: "Última actualización",
                    filterOperations: [
                        "=",
                        "<>",
                        "<",
                        "<=",
                        ">",
                        ">=",
                        "between",
                    ],
                    hidingPriority: 6,
                    dataType: "datetime",
                    format: "dd/MM/yyyy HH:mm",
                },
                {
                    dataField: "email_verified_at",
                    caption: "Email verificado",
                    filterOperations: ["contains"],
                    hidingPriority: 6,
                    cellTemplate: function (container, options) {
                        const isVerified = options.value ? true : false;
                        const icon = isVerified ? "fa-check" : "fa-times";
                        const color = isVerified ? "green" : "red";
                        const text = isVerified
                            ? "Verificado"
                            : "No verificado";

                        container.append(
                            `<span style="color: ${color};">
                                <i class="fa-solid ${icon}"></i> ${text}
                            </span>`
                        );
                    },
                },
            ],
        })
        .dxDataGrid("instance");

    window.refreshUsersGrid = function() {
        $("#usersGrid").dxDataGrid("instance").refresh();
    };

    $("#refreshBtn").on("click", function() {
        refreshUsersGrid();
    });
});
```

---

## 🎯 Resumen

Este sistema permite manejar componentes DevExtreme con campos encriptados de manera transparente. Los desarrolladores pueden:

1. **Definir campos encriptados** en los modelos
2. **Configurar relaciones** con otros modelos
3. **Restringir campos buscables** por seguridad
4. **Transformar datos** antes de enviarlos al frontend
5. **Obtener funcionalidad completa** de DevExtreme sin preocuparse por la encriptación

### 🚀 Características del AdvancedOptimizedEncryptedFields

El trait avanzado añade capacidades de nivel empresarial:

- **Hash Mapping**: Búsquedas exactas instantáneas sin desencriptar
- **Caché Inteligente**: Sistema multinivel con auto-limpieza
- **Multi-Modelo**: Búsqueda simultánea en múltiples modelos relacionados
- **Chunks Optimizados**: Manejo eficiente de datasets masivos
- **Auto-gestión**: Limpieza automática del caché al cambiar datos

### Elección del Trait Correcto

| Tamaño de App | Registros | Trait Recomendado |
|---------------|-----------|-------------------|
| Pequeña | < 1K | HandlesEncryptedFields |
| Mediana | 1K - 10K | OptimizedEncryptedFields |
| Grande | > 10K | **AdvancedOptimizedEncryptedFields** ⭐ |

El sistema maneja automáticamente la encriptación/desencriptación y optimiza el rendimiento usando ordenamiento en base de datos cuando es posible, y técnicas avanzadas de caché para campos encriptados.
