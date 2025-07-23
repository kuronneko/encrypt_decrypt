<?php

namespace App\Traits;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;

trait AdvancedOptimizedEncryptedFields
{
    use HandlesEncryptedFields;

    /**
     * Cache duration for encrypted field searches (in minutes)
     */
    protected int $encryptedFieldsCacheDuration = 120; // 2 hours

    /**
     * Cache duration for hash mappings (longer since these are more stable)
     */
    protected int $hashMappingCacheDuration = 480; // 8 hours

    /**
     * Apply filter to encrypted fields with advanced caching optimization
     * Now supports both main model and related model fields
     */
    public function applyEncryptedFieldFilter($query, string $field, string $operator, $value, Model $targetModel = null)
    {
        $targetModel = $targetModel ?? $this;

        // For exact matches, use hash-based lookup for maximum efficiency
        if ($operator === '=' || $operator === 'equals') {
            return $this->applyHashBasedExactMatch($query, $field, $value, $targetModel);
        }

        // For other operators, use the standard cached approach
        $cacheKey = $this->getEncryptedFieldCacheKey($field, $operator, $value, $targetModel);

        $matchingIds = Cache::remember($cacheKey, $this->encryptedFieldsCacheDuration, function() use ($field, $operator, $value, $targetModel) {
            return $this->findMatchingEncryptedRecords($field, $operator, $value, $targetModel);
        });

        if (!empty($matchingIds)) {
            $query->whereIn('id', $matchingIds);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /**
     * Apply filter to encrypted fields in related models with optimization
     */
    public function applyEncryptedRelatedModelFilter($query, string $relationName, string $field, string $operator, $value, Model $relatedModel)
    {
        $cacheKey = $this->getRelatedEncryptedFieldCacheKey($relationName, $field, $operator, $value, $relatedModel);

        $matchingIds = Cache::remember($cacheKey, $this->encryptedFieldsCacheDuration, function() use ($field, $operator, $value, $relatedModel) {
            return $this->findMatchingEncryptedRecords($field, $operator, $value, $relatedModel);
        });

        if (!empty($matchingIds)) {
            $query->whereHas($relationName, function($q) use ($matchingIds) {
                $q->whereIn('id', $matchingIds);
            });
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /**
     * Hash-based exact match - works with any model
     */
    protected function applyHashBasedExactMatch($query, string $field, $value, Model $targetModel)
    {
        // Create a hash of the search value
        $searchHash = $this->createSearchHash($value);

        // Get the hash mapping for this field on the target model
        $hashMappingKey = $this->getHashMappingCacheKey($field, $targetModel);

        $hashMapping = Cache::remember($hashMappingKey, $this->hashMappingCacheDuration, function() use ($field, $targetModel) {
            return $this->buildHashMapping($field, $targetModel);
        });

        // Find IDs that match the hash
        $matchingIds = $hashMapping[$searchHash] ?? [];

        if (!empty($matchingIds)) {
            $query->whereIn('id', $matchingIds);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /**
     * Build a hash mapping: hash(decrypted_value) => [array_of_ids]
     * This allows for instant lookups without decryption
     * Now works with any model, not just the current one
     */
    protected function buildHashMapping(string $field, Model $targetModel): array
    {
        $modelClass = get_class($targetModel);
        $hashMapping = [];

        // Process in chunks to handle large datasets
        $modelClass::chunk(1000, function($records) use (&$hashMapping, $field) {
            foreach ($records as $record) {
                try {
                    // Decrypt the field value
                    $decryptedValue = $record->{$field};

                    // Create hash of the decrypted value
                    $hash = $this->createSearchHash($decryptedValue);

                    // Store the ID under this hash
                    if (!isset($hashMapping[$hash])) {
                        $hashMapping[$hash] = [];
                    }
                    $hashMapping[$hash][] = $record->id;

                } catch (\Exception $e) {
                    // Skip records that can't be decrypted
                    continue;
                }
            }
        });

        return $hashMapping;
    }

    /**
     * Create a consistent hash for search values
     */
    protected function createSearchHash($value): string
    {
        // Normalize the value (trim whitespace, convert to lowercase for case-insensitive search)
        $normalizedValue = trim(strtolower($value));

        // Create a hash that's consistent but not easily reversible
        return hash('sha256', $normalizedValue);
    }

    /**
     * Apply global search to encrypted fields with caching optimization
     * Now supports searching across multiple models and their encrypted fields
     */
    public function applyEncryptedFieldsGlobalSearch($query, string $searchText, array $encryptedFields = null, Model $targetModel = null)
    {
        $targetModel = $targetModel ?? $this;
        $encryptedFields = $encryptedFields ?? $this->getEncryptedFieldsForModel($targetModel);

        if (empty($encryptedFields)) {
            return [];
        }

        $cacheKey = $this->getGlobalSearchCacheKey($searchText, $encryptedFields, $targetModel);

        $matchingIds = Cache::remember($cacheKey, $this->encryptedFieldsCacheDuration, function() use ($searchText, $encryptedFields, $targetModel) {
            return $this->findMatchingEncryptedRecordsGlobal($searchText, $encryptedFields, $targetModel);
        });

        return $matchingIds;
    }

    /**
     * Apply global search across multiple models (main + related models)
     */
    public function applyMultiModelEncryptedSearch($query, string $searchText, array $modelConfigs = [])
    {
        $allMatchingIds = [];

        // Search in main model
        $mainEncryptedFields = $this->getEncryptedFields();
        if (!empty($mainEncryptedFields)) {
            $mainMatchingIds = $this->applyEncryptedFieldsGlobalSearch($query, $searchText, $mainEncryptedFields, $this);
            if (!empty($mainMatchingIds)) {
                $allMatchingIds = array_merge($allMatchingIds, $mainMatchingIds);
            }
        }

        // Search in related models
        foreach ($modelConfigs as $relationName => $config) {
            $relatedModel = $config['model'] ?? null;
            $encryptedFields = $config['encrypted_fields'] ?? null;

            if ($relatedModel && method_exists($relatedModel, 'getEncryptedFields')) {
                $encryptedFields = $encryptedFields ?? $relatedModel->getEncryptedFields();

                if (!empty($encryptedFields)) {
                    $relatedMatchingIds = $this->applyEncryptedFieldsGlobalSearch($query, $searchText, $encryptedFields, $relatedModel);

                    if (!empty($relatedMatchingIds)) {
                        // Filter main query based on related model matches
                        $query->orWhereHas($relationName, function($q) use ($relatedMatchingIds) {
                            $q->whereIn('id', $relatedMatchingIds);
                        });
                    }
                }
            }
        }

        return $query;
    }

    /**
     * Get encrypted fields for any model
     */
    protected function getEncryptedFieldsForModel(Model $model): array
    {
        if (method_exists($model, 'getEncryptedFields')) {
            return $model->getEncryptedFields();
        }

        // Fallback to property if method doesn't exist
        return $model->encryptedFields ?? [];
    }

    /**
     * Find matching records for a specific encrypted field using chunking
     * Now works with any model, not just the current one
     */
    private function findMatchingEncryptedRecords(string $field, string $operator, $value, Model $targetModel): array
    {
        $modelClass = get_class($targetModel);
        $matchingIds = [];

        $modelClass::chunk(1000, function($records) use (&$matchingIds, $field, $operator, $value) {
            foreach ($records as $record) {
                try {
                    $decryptedValue = $record->{$field};
                    if ($this->checkFieldMatch($decryptedValue, $operator, $value)) {
                        $matchingIds[] = $record->id;
                    }
                } catch (\Exception $e) {
                    // Skip records that can't be decrypted
                    continue;
                }
            }
        });

        return $matchingIds;
    }

    /**
     * Find matching records for global search across multiple encrypted fields
     * Now works with any model and supports multiple models
     */
    private function findMatchingEncryptedRecordsGlobal(string $searchText, array $encryptedFields, Model $targetModel = null): array
    {
        $targetModel = $targetModel ?? $this;
        $modelClass = get_class($targetModel);
        $matchingIds = [];

        $modelClass::chunk(1000, function($records) use (&$matchingIds, $searchText, $encryptedFields) {
            foreach ($records as $record) {
                foreach ($encryptedFields as $field) {
                    try {
                        $decryptedValue = $record->{$field};
                        if (stripos($decryptedValue, $searchText) !== false) {
                            $matchingIds[] = $record->id;
                            break; // Found match, no need to check other fields for this record
                        }
                    } catch (\Exception $e) {
                        // Skip this field if it can't be decrypted
                        continue;
                    }
                }
            }
        });

        return $matchingIds;
    }

    /**
     * Check if a decrypted value matches the filter criteria
     */
    private function checkFieldMatch($decryptedValue, string $operator, $value): bool
    {
        switch ($operator) {
            case 'contains':
                return stripos($decryptedValue, $value) !== false;
            case '=':
            case 'equals':
                return strcasecmp($decryptedValue, $value) === 0;
            case '<>':
                return strcasecmp($decryptedValue, $value) !== 0;
            case '>':
                return strcmp($decryptedValue, $value) > 0;
            case '<':
                return strcmp($decryptedValue, $value) < 0;
            case '>=':
                return strcmp($decryptedValue, $value) >= 0;
            case '<=':
                return strcmp($decryptedValue, $value) <= 0;
            default:
                return false;
        }
    }

    /**
     * Generate cache key for hash mapping (supports any model)
     */
    private function getHashMappingCacheKey(string $field, Model $targetModel): string
    {
        $modelClass = get_class($targetModel);
        return sprintf(
            'hash_mapping_%s_%s',
            str_replace('\\', '_', $modelClass),
            $field
        );
    }

    /**
     * Generate cache key for encrypted field search (supports any model)
     */
    private function getEncryptedFieldCacheKey(string $field, string $operator, $value, Model $targetModel): string
    {
        $modelClass = get_class($targetModel);
        return sprintf(
            'encrypted_field_%s_%s_%s_%s',
            str_replace('\\', '_', $modelClass),
            $field,
            $operator,
            $this->createSearchHash($value)
        );
    }

    /**
     * Generate cache key for related model encrypted field search
     */
    private function getRelatedEncryptedFieldCacheKey(string $relationName, string $field, string $operator, $value, Model $relatedModel): string
    {
        $modelClass = get_class($relatedModel);
        return sprintf(
            'encrypted_related_%s_%s_%s_%s_%s',
            str_replace('\\', '_', $modelClass),
            $relationName,
            $field,
            $operator,
            $this->createSearchHash($value)
        );
    }

    /**
     * Generate cache key for global encrypted search (supports any model)
     */
    private function getGlobalSearchCacheKey(string $searchText, array $encryptedFields, Model $targetModel): string
    {
        $modelClass = get_class($targetModel);
        return sprintf(
            'encrypted_global_%s_%s_%s',
            str_replace('\\', '_', $modelClass),
            $this->createSearchHash($searchText),
            md5(serialize($encryptedFields))
        );
    }

    /**
     * Clear all encrypted fields cache for this model or a specific model
     */
    public function clearEncryptedFieldsCache(Model $targetModel = null): void
    {
        $targetModel = $targetModel ?? $this;
        $modelClass = get_class($targetModel);
        $modelKey = str_replace('\\', '_', $modelClass);

        // In a real application, you'd want to use cache tags or a more sophisticated approach
        // For now, we'll use a simple flush
        Cache::flush();
    }

    /**
     * Clear cache for all related models as well
     */
    public function clearAllRelatedEncryptedCache(array $relatedModels = []): void
    {
        // Clear cache for main model
        $this->clearEncryptedFieldsCache();

        // Clear cache for all related models
        foreach ($relatedModels as $relatedModel) {
            if ($relatedModel instanceof Model) {
                $this->clearEncryptedFieldsCache($relatedModel);
            }
        }
    }

    /**
     * Clear hash mapping cache for a specific field (supports any model)
     */
    public function clearHashMappingCache(string $field, Model $targetModel = null): void
    {
        $targetModel = $targetModel ?? $this;
        $cacheKey = $this->getHashMappingCacheKey($field, $targetModel);
        Cache::forget($cacheKey);
    }

    /**
     * Automatically clear cache when model data changes
     */
    protected static function bootAdvancedOptimizedEncryptedFields()
    {
        static::saved(function ($model) {
            if (method_exists($model, 'clearEncryptedFieldsCache')) {
                $model->clearEncryptedFieldsCache();
            }
        });

        static::deleted(function ($model) {
            if (method_exists($model, 'clearEncryptedFieldsCache')) {
                $model->clearEncryptedFieldsCache();
            }
        });
    }
}
