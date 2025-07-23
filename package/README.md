# Laravel DevExtreme Encrypted Fields

A Laravel package that provides seamless integration between DevExtreme DataGrid and Laravel models with encrypted fields, featuring optimized pagination and performance strategies.

## Features

- 🔐 **Encrypted Fields Support**: Search and filter encrypted model fields
- ⚡ **Optimized Pagination**: Smart query optimization based on field types
- 🔍 **Global Search**: Search across multiple fields including encrypted ones
- 📊 **DevExtreme Integration**: Direct integration with DevExtreme DataGrid
- 🚀 **Performance Optimized**: Multiple strategies for different scenarios
- 🛡️ **Security**: Built-in protection for sensitive fields
- 📈 **Configurable**: Extensive configuration options

## Installation

Install the package via Composer:

```bash
composer require kuronneko/laravel-devextreme-encrypted
```

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Kuronneko\LaravelDevExtremeEncrypted\DevExtremeEncryptedServiceProvider" --tag="config"
```

## Quick Start

### 1. Prepare Your Model

Add the encrypted fields trait to your model:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kuronneko\LaravelDevExtremeEncrypted\Traits\HandlesEncryptedFields;

class User extends Model
{
    use HandlesEncryptedFields;

    /**
     * Define which fields are encrypted
     */
    protected $encryptedFields = [
        'email',
        'phone',
        'ssn',
    ];

    // Your model code...
}
```

### 2. Create a Controller

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Location;
use Illuminate\Http\Request;
use Kuronneko\LaravelDevExtremeEncrypted\Components\DevExtremeHandler;
use Kuronneko\LaravelDevExtremeEncrypted\Components\DevExtremeConfig;

class UserController extends Controller
{
    public function list(Request $request)
    {
        $query = User::with('locations');

        $config = DevExtremeConfig::make()
            ->encryptedFieldsHandler(new User())
            ->relatedModel('locations', new Location())
            ->searchableFields([
                'id',                     // Regular field
                'name',                   // Regular field
                'email',                  // Encrypted field
                'locations.city',         // Related field
                'locations.postal_code',  // Related encrypted field
            ])
            ->sortBy('id', 'desc')
            ->transform(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email, // Auto-decrypted
                    'city' => $user->locations->first()?->city ?? '',
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                ];
            })
            ->build();

        return DevExtremeHandler::create($query, $config)->handle($request);
    }
}
```

### 3. Frontend Integration

Use with DevExtreme DataGrid:

```javascript
$("#gridContainer").dxDataGrid({
    dataSource: {
        store: {
            type: "custom",
            load: function(loadOptions) {
                return $.ajax({
                    url: "/users/list",
                    data: loadOptions
                });
            }
        }
    },
    columns: [
        { dataField: "id", caption: "ID" },
        { dataField: "name", caption: "Name" },
        { dataField: "email", caption: "Email" },
        { dataField: "city", caption: "City" },
        { dataField: "created_at", caption: "Created" }
    ],
    searchPanel: { visible: true },
    paging: { pageSize: 20 },
    filterRow: { visible: true },
    sorting: { mode: "multiple" }
});
```

## Configuration

The package provides extensive configuration options in `config/devextreme-encrypted.php`:

```php
return [
    'cache' => [
        'enabled' => true,
        'duration' => 5, // minutes
    ],
    'performance' => [
        'auto_optimize' => true,
        'max_in_memory_records' => 10000,
        'debug_logging' => false,
    ],
    'security' => [
        'protected_fields' => [
            'password',
            'remember_token',
        ],
        'audit_logging' => false,
    ],
    // ... more options
];
```

## Performance Strategies

The package automatically chooses the best strategy based on your query:

### 1. SQL-Only Strategy
- **When**: No encrypted or related fields in operations
- **Performance**: Fastest - uses pure SQL pagination
- **Use case**: Simple grids with only main table fields

### 2. Hybrid Strategy  
- **When**: Related fields but no encrypted fields
- **Performance**: Good - SQL with some post-processing
- **Use case**: Grids with relationships but no encryption

### 3. Post-Process Strategy
- **When**: Encrypted fields are involved
- **Performance**: Slower - loads all records for decryption
- **Use case**: Grids requiring encrypted field operations

## Advanced Usage

### Multiple Related Models

```php
$config = DevExtremeConfig::make()
    ->encryptedFieldsHandler(new User())
    ->relatedModel('locations', new Location())
    ->relatedModel('profile', new UserProfile())
    ->relatedModel('orders', new Order())
    ->searchableFields([
        'name',
        'email',
        'locations.city',
        'profile.bio',
        'orders.order_number',
    ])
    ->build();
```

### Custom Cache Duration

```php
$config = DevExtremeConfig::make()
    ->encryptedFieldsHandler(new User())
    ->cacheDuration(10) // 10 minutes
    ->build();
```

### Audit Logging

```php
$config = DevExtremeConfig::make()
    ->encryptedFieldsHandler(new User())
    ->withAuditLogging(true)
    ->build();
```

## Security Features

- **Protected Fields**: Automatically excludes sensitive fields from search
- **Audit Logging**: Optional logging of encrypted field access
- **Field Validation**: Prevents unauthorized field access

## Performance Tips

1. **Minimize Encrypted Searchable Fields**: Only make encrypted fields searchable when necessary
2. **Use Caching**: Enable caching for repeated searches
3. **Optimize Relations**: Only eager load required relationships
4. **Monitor Memory**: Watch for large datasets with encrypted fields

## Testing

Run the tests:

```bash
vendor/bin/phpunit
```

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Support

If you discover any security vulnerabilities, please send an e-mail to the package maintainer.
