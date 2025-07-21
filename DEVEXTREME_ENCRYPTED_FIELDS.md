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

## Advanced Features: Related Models Support

### Related Model Configuration

The system now supports filtering and sorting on encrypted fields from related models. This is particularly useful when you need to display and filter data from relationships.

#### Example: User with Location Relationship

```php
// User Model
class User extends Model
{
    use OptimizedEncryptedFields;
    
    protected $encryptedFields = ['email'];
    
    public function locations()
    {
        return $this->hasMany(Location::class);
    }
}

// Location Model
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

#### Controller Configuration for Related Models

```php
class UserController extends Controller
{
    use DevExtremeOperations;

    public function list(Request $request)
    {
        // Start building the query with location relationship
        $query = User::with('locations');

        // Create model instances to handle encrypted fields
        $userModel = new User();
        $locationModel = new Location();

        // Define configuration for DevExtreme operations
        $options = [
            'searchableFields' => ['id', 'name', 'email', 'locations.postal_code'],
            'encryptedFieldsHandler' => $userModel, // Handler for main model encrypted fields
            'relatedModels' => [
                'locations' => $locationModel // Handler for location encrypted fields
            ],
            'defaultSort' => ['id' => 'desc'],
            'dataTransformer' => function($user) {
                // Get the first location's postal code (since user has one location)
                $postalCode = $user->locations->first() ? $user->locations->first()->postal_code : null;
                
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email, // Will be auto-decrypted by the model's accessor
                    'postal_code' => $postalCode, // Will be auto-decrypted by the Location model's accessor
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $user->updated_at->format('Y-m-d H:i:s')
                ];
            }
        ];

        // Use the trait to handle the DevExtreme request
        return $this->handleDevExtremeRequest($request, $query, $options);
    }
}
```

### Frontend DevExtreme Configuration

Add the related field column to your DataGrid:

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
        caption: "Name",
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
        caption: "Postal Code",
        filterOperations: ["contains"],
        hidingPriority: 2,
        allowEditing: false,
        cellTemplate: function (container, options) {
            const postalCode = options.value || 'N/A';
            container.append(`<span>${postalCode}</span>`);
        },
    },
    // ... other columns
]
```

## Advanced Filtering and Sorting

### Filtering Support

The system automatically handles different types of filtering scenarios:

#### 1. **Direct Field Filtering**
```http
GET /users/list/?filter=["email","contains","john@example.com"]
```

#### 2. **Related Model Field Filtering**
```http
GET /users/list/?filter=["postal_code","contains","32040"]
```

#### 3. **Complex Filtering**
```http
GET /users/list/?filter=[["email","contains","john"],["postal_code","contains","123"]]
```

### Sorting Support

The system provides intelligent sorting with two approaches:

#### 1. **SQL-Based Sorting** (for non-encrypted fields)
- Direct database sorting
- High performance for large datasets
- Used for regular fields

#### 2. **Post-Transform Sorting** (for encrypted fields)
- Retrieves all data, decrypts, then sorts in PHP
- Required for encrypted fields
- Maintains data integrity

```http
GET /users/list/?sort=[{"selector":"postal_code","desc":false}]
```

### How It Works Under the Hood

#### Encrypted Field Detection
1. **Field Analysis**: System checks if the requested field exists in any related model's encrypted fields
2. **Route Selection**: Chooses between SQL sorting or post-transform sorting
3. **Processing**: Applies appropriate filtering/sorting strategy

#### Filtering Process for Encrypted Related Fields
```php
// 1. Get all related records
$allRelatedRecords = Location::all();

// 2. Decrypt and filter in PHP
foreach ($allRelatedRecords as $record) {
    $decryptedValue = $record->postal_code; // Auto-decrypted
    if (stripos($decryptedValue, $searchTerm) !== false) {
        $matchingIds[] = $record->id;
    }
}

// 3. Apply SQL filter based on matches
$query->whereHas('locations', function($q) use ($matchingIds) {
    $q->whereIn('id', $matchingIds);
});
```

#### Sorting Process for Encrypted Fields
```php
// 1. Check if sorting requires post-processing
if ($needsPostSorting) {
    // 2. Get all filtered data
    $results = $query->get();
    
    // 3. Transform data (decrypt)
    $results = $results->map($dataTransformer);
    
    // 4. Sort in PHP
    $results = $this->applyPostSorting($results, $sort);
    
    // 5. Apply pagination
    $results = $results->slice($skip, $take);
}
```

### Performance Considerations for Related Models

#### Optimization Strategies
1. **Eager Loading**: Always use `with()` to prevent N+1 queries
2. **Selective Processing**: Post-sorting only activates for encrypted field sorting
3. **Caching**: Use `OptimizedEncryptedFields` trait for better caching
4. **Indexing**: Ensure proper database indexes on foreign keys

#### Memory Management
- Post-sorting loads all filtered records into memory
- Consider pagination limits for large datasets
- Monitor memory usage with encrypted field operations

### Configuration Options for Related Models

| Option | Type | Description |
|--------|------|-------------|
| `relatedModels` | array | Map of relationship names to model instances |
| `searchableFields` | array | Can include related fields like 'locations.postal_code' |
| `dataTransformer` | callable | Must handle related model data transformation |

### Error Handling

The system gracefully handles common scenarios:
- **Missing Relations**: Returns null/empty values instead of errors
- **Decryption Failures**: Falls back to original encrypted values
- **Invalid Filters**: Ignores malformed filter criteria

### Security Considerations

1. **Access Control**: Always implement proper authorization before exposing encrypted data
2. **Audit Trails**: Consider logging access to encrypted fields
3. **Field Validation**: Validate which fields can be filtered/sorted
4. **Rate Limiting**: Implement rate limiting for expensive operations

This advanced implementation provides comprehensive support for related model encrypted fields while maintaining performance and security standards.

## Dynamic Field Detection System

### Overview

The system includes a sophisticated dynamic field detection mechanism that automatically identifies fields in related models without requiring hardcoded configurations. This makes the system truly modular and reusable across any Laravel models and database structures.

### How Dynamic Detection Works

#### 1. **Multi-Level Field Resolution**

The system uses a cascading approach to detect if a field exists in a related model:

```php
protected function isFieldInRelatedModel($relatedModel, string $field): bool
{
    if (!$relatedModel) {
        return false;
    }

    // Level 1: Check fillable fields
    $fillable = $relatedModel->getFillable();
    if (!empty($fillable) && in_array($field, $fillable)) {
        return true;
    }

    // Level 2: Query actual database schema
    try {
        $tableName = $relatedModel->getTable();
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing($tableName);
        return in_array($field, $columns);
    } catch (\Exception $e) {
        return false; // Graceful fallback
    }
}
```

#### 2. **Automatic Model Inspection**

When a field like `city` is requested for filtering or sorting:

1. **Check if field has dot notation** (`locations.city`) → Direct related field
2. **Check main model encrypted fields** → Handle with decryption
3. **Check main model regular fields** → Standard SQL operations
4. **Iterate through related models**:
   - Check if field is encrypted in related model → Post-processing required
   - Check if field exists in related model schema → SQL join operations
5. **Fallback to regular field handling**

#### 3. **Real-Time Schema Detection**

The system queries the actual database schema using Laravel's Schema facade:

```php
// Gets actual column names from the database table
$columns = \Illuminate\Support\Facades\Schema::getColumnListing($tableName);
```

This means it works with:
- ✅ Any existing table structure
- ✅ Migration-created tables
- ✅ Legacy database schemas
- ✅ Dynamic table modifications

### Implementation Examples

#### Example 1: E-commerce System

```php
// Product Model
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

// Category Model  
class Category extends Model
{
    protected $fillable = ['name', 'description', 'parent_id'];
}

// Controller automatically detects:
// - 'supplier_contact' as encrypted field in Product
// - 'name', 'description' as regular fields in Category
// - Applies appropriate filtering/sorting strategy
```

#### Example 2: CRM System

```php
// Contact Model
class Contact extends Model
{
    use OptimizedEncryptedFields;
    
    protected $encryptedFields = ['ssn', 'phone', 'email'];
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}

// Company Model
class Company extends Model
{
    protected $fillable = ['name', 'industry', 'size', 'website'];
}

// DevExtreme configuration
$options = [
    'searchableFields' => ['name', 'ssn', 'phone', 'company_name', 'industry'],
    'relatedModels' => [
        'company' => new Company()
    ]
];

// System automatically handles:
// - 'ssn', 'phone' → Encrypted field processing
// - 'company_name', 'industry' → Related model SQL operations
```

### Advanced Features

#### 1. **Error Resilience**

```php
try {
    $columns = \Illuminate\Support\Facades\Schema::getColumnListing($tableName);
    return in_array($field, $columns);
} catch (\Exception $e) {
    // Graceful fallback - system continues working even if schema query fails
    return false;
}
```

#### 2. **Performance Optimization**

- **Schema Caching**: Database schema queries are cached by Laravel
- **Early Returns**: Stops checking as soon as field is found
- **Selective Processing**: Only queries schema when fillable check fails

#### 3. **Flexible Model Support**

```php
// Works with any model configuration:

// Model with explicit fillable
class ModelA extends Model
{
    protected $fillable = ['field1', 'field2'];
}

// Model with guarded (empty fillable)
class ModelB extends Model
{
    protected $guarded = ['id'];
}

// Model with no mass assignment protection
class ModelC extends Model
{
    protected $guarded = [];
}
```

### Configuration Best Practices

#### 1. **Optimal Fillable Configuration**

For best performance, always define `$fillable` arrays:

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

#### 2. **Controller Configuration**

```php
public function list(Request $request)
{
    $query = User::with('locations', 'profile', 'orders');

    $options = [
        'searchableFields' => [
            'id', 'name', 'email',           // User fields
            'city', 'postal_code',           // Location fields (auto-detected)
            'bio', 'preferences',            // Profile fields (auto-detected)
            'total', 'status'                // Order fields (auto-detected)
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

### Debugging and Troubleshooting

#### 1. **Field Detection Logging**

Add logging to see how fields are being detected:

```php
protected function isFieldInRelatedModel($relatedModel, string $field): bool
{
    if (!$relatedModel) {
        \Log::debug("Related model is null for field: {$field}");
        return false;
    }

    $fillable = $relatedModel->getFillable();
    if (!empty($fillable) && in_array($field, $fillable)) {
        \Log::debug("Field {$field} found in fillable for " . get_class($relatedModel));
        return true;
    }

    try {
        $tableName = $relatedModel->getTable();
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing($tableName);
        $exists = in_array($field, $columns);
        \Log::debug("Field {$field} " . ($exists ? 'found' : 'not found') . " in table {$tableName}");
        return $exists;
    } catch (\Exception $e) {
        \Log::error("Schema detection failed for {$field}: " . $e->getMessage());
        return false;
    }
}
```

#### 2. **Common Issues and Solutions**

| Issue | Cause | Solution |
|-------|-------|----------|
| Field not detected | Empty `$fillable` and schema query fails | Define explicit `$fillable` array |
| Slow performance | Too many schema queries | Optimize `$fillable` definitions |
| Incorrect field detection | Wrong table name or connection | Verify model `$table` property |
| Migration conflicts | Schema changes during requests | Use database transactions |

This dynamic system ensures your DevExtreme implementation remains flexible and maintainable while providing optimal performance for any model structure.
