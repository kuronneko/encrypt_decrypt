# DevExtreme with Encrypted Fields - Modular Component System

This system provides a modular, reusable approach to handle DevExtreme DataGrids with support for encrypted fields, filtering, sorting, and pagination.

## Components Overview

### 1. `HandlesEncryptedFields` Trait
Base trait for handling encrypted field operations in models.

### 2. `OptimizedEncryptedFields` Trait  
Advanced trait with caching optimization for better performance with encrypted fields.

### 3. `DevExtremeOperations` Trait
Controller trait that provides DevExtreme request handling capabilities.

### 4. `DevExtremeFilter` Component
Standalone component for applying filters to queries with support for encrypted fields.

## Usage Examples

### Basic Model Setup

```php
<?php

namespace App\Models;

use App\Traits\HandlesEncryptedFields;
// or use App\Traits\OptimizedEncryptedFields; for better performance

class User extends Model
{
    use HandlesEncryptedFields; // or OptimizedEncryptedFields

    /**
     * Define which fields are encrypted
     */
    protected $encryptedFields = [
        'email',
        'telephone', // Add any other encrypted fields
    ];

    // Add encrypted field accessors/mutators
    public function setEmailAttribute($value) {
        // Encryption logic
    }

    public function getEmailAttribute($value) {
        // Decryption logic
    }

    public function newModelInstance() {
        return new static();
    }
}
```

### Controller Setup

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
                    'email' => $user->email, // Auto-decrypted
                    'telephone' => $user->telephone, // Auto-decrypted
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                ];
            }
        ];

        return $this->handleDevExtremeRequest($request, $query, $options);
    }
}
```

## Adding New Encrypted Fields

### Step 1: Update Model
```php
protected $encryptedFields = [
    'email',
    'telephone',
    'address',    // New field
    'ssn',        // New field
];
```

### Step 2: Add Accessors/Mutators
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

### Step 3: Update Controller Configuration
```php
'searchableFields' => ['id', 'name', 'email', 'telephone', 'address', 'ssn'],
```

### Step 4: Update Frontend (DevExtreme Configuration)
```javascript
{
    dataField: "address",
    caption: "Address",
    filterOperations: ["contains", "=", "<>"],
    allowEditing: true,
},
{
    dataField: "ssn",
    caption: "SSN",
    filterOperations: ["contains", "=", "<>"],
    allowEditing: true,
},
```

## Performance Considerations

### For Better Performance:
1. Use `OptimizedEncryptedFields` trait instead of `HandlesEncryptedFields`
2. Set appropriate cache duration:
   ```php
   protected int $encryptedFieldsCacheDuration = 10; // minutes
   ```
3. Clear cache when encrypted data changes:
   ```php
   $user->clearEncryptedFieldsCache();
   ```

### Cache Management:
```php
// Clear cache after updating encrypted fields
$user->update($data);
$user->clearEncryptedFieldsCache();
```

## Extending for Other Models

### Example: Contact Model
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

    // Add accessor/mutator methods for each encrypted field
}
```

### Example: ContactController
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

## Features

✅ **Modular Design**: Easy to reuse across different models and controllers  
✅ **Encrypted Field Support**: Automatic handling of encrypted field filtering and searching  
✅ **Performance Optimized**: Caching support for encrypted field operations  
✅ **DevExtreme Compatible**: Full support for pagination, filtering, sorting  
✅ **Type Safety**: Proper handling of different data types (dates, numbers, strings)  
✅ **Extensible**: Easy to add new encrypted fields or models  
✅ **Global Search**: Search across both regular and encrypted fields  
✅ **Filter Operations**: Support for contains, equals, greater than, etc.  

## Configuration Options

| Option | Type | Description |
|--------|------|-------------|
| `searchableFields` | array | Fields that can be searched globally |
| `encryptedFieldsHandler` | object | Model instance that handles encrypted fields |
| `defaultSort` | array | Default sorting configuration |
| `dataTransformer` | callable | Function to transform data before returning |

This modular system makes it easy to add encrypted field support to any model with minimal code duplication and maximum reusability.
