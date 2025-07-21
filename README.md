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
    └── HandlesEncryptedFields.php # Trait para campos encriptados
```

## 🔧 Instalación y Configuración

### 1. Configurar el Modelo Principal

```php
<?php
// app/Models/User.php

namespace App\Models;

use App\Traits\HandlesEncryptedFields;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Crypt;

class User extends Authenticatable
{
    use HandlesEncryptedFields;

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

use App\Traits\HandlesEncryptedFields;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HandlesEncryptedFields;

    protected $encryptedFields = [
        'postal_code',
        'address',
    ];

    protected $fillable = [
        'user_id',
        'city',
        'postal_code',
        'address',
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
3. Verificar que el trait `HandlesEncryptedFields` esté incluido

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

## 📊 Rendimiento

- **Campos normales**: Ordenamiento y filtrado en base de datos (rápido)
- **Campos encriptados**: Ordenamiento en aplicación (más lento)
- **Recomendación**: Usar campos encriptados solo cuando sea necesario

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
$("#dataGrid").dxDataGrid({
    dataSource: {
        store: {
            type: "odata",
            url: "/users/list",
            key: "id"
        }
    },
    remoteOperations: {
        filtering: true,
        sorting: true,
        paging: true
    },
    columns: [
        { dataField: "id", caption: "ID" },
        { dataField: "name", caption: "Nombre" },
        { dataField: "email", caption: "Email" },
        { dataField: "city", caption: "Ciudad" },
        { dataField: "postal_code", caption: "Código Postal" },
        { dataField: "address", caption: "Dirección" }
    ],
    filterRow: { visible: true },
    searchPanel: { visible: true },
    paging: { pageSize: 10 }
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

El sistema maneja automáticamente la encriptación/desencriptación y optimiza el rendimiento usando ordenamiento en base de datos cuando es posible.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
