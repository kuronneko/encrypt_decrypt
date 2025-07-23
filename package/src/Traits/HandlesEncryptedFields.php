<?php

namespace Kuronneko\LaravelDevExtremeEncrypted\Traits;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;

trait HandlesEncryptedFields
{
    /**
     * Define which fields are encrypted in the model
     * Override this method in your model to specify encrypted fields
     */
    protected function getEncryptedFields(): array
    {
        return $this->encryptedFields ?? [];
    }

    /**
     * Check if a field is encrypted
     */
    public function isEncryptedField(string $field): bool
    {
        return in_array($field, $this->getEncryptedFields());
    }

    /**
     * Apply filter to encrypted fields with caching
     */
    public function applyEncryptedFieldFilter($query, string $field, string $operator, $value)
    {
        $cacheEnabled = config('devextreme-encrypted.cache.enabled', true);
        $cacheDuration = config('devextreme-encrypted.cache.duration', 5);

        if ($cacheEnabled) {
            $cacheKey = $this->getEncryptedFieldCacheKey($field, $operator, $value);

            $matchingIds = Cache::remember($cacheKey, $cacheDuration * 60, function() use ($field, $operator, $value) {
                return $this->findMatchingEncryptedRecords($field, $operator, $value);
            });
        } else {
            $matchingIds = $this->findMatchingEncryptedRecords($field, $operator, $value);
        }

        if (!empty($matchingIds)) {
            $query->whereIn('id', $matchingIds);
        } else {
            // No matches found, return empty result
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /**
     * Apply global search to encrypted fields with caching
     */
    public function applyEncryptedFieldsGlobalSearch($query, string $searchText, array $encryptedFields = null)
    {
        $encryptedFields = $encryptedFields ?? $this->getEncryptedFields();

        if (empty($encryptedFields)) {
            return [];
        }

        $cacheEnabled = config('devextreme-encrypted.cache.enabled', true);
        $cacheDuration = config('devextreme-encrypted.cache.duration', 5);

        if ($cacheEnabled) {
            $cacheKey = $this->getGlobalSearchCacheKey($searchText, $encryptedFields);

            $matchingIds = Cache::remember($cacheKey, $cacheDuration * 60, function() use ($searchText, $encryptedFields) {
                return $this->findMatchingEncryptedRecordsGlobal($searchText, $encryptedFields);
            });
        } else {
            $matchingIds = $this->findMatchingEncryptedRecordsGlobal($searchText, $encryptedFields);
        }

        return $matchingIds;
    }

    /**
     * Find matching records for a specific encrypted field
     */
    private function findMatchingEncryptedRecords(string $field, string $operator, $value): array
    {
        $modelClass = get_class($this->newModelInstance());
        $allRecords = $modelClass::all();
        $matchingIds = [];

        foreach ($allRecords as $record) {
            $decryptedValue = $record->{$field}; // This will auto-decrypt via accessor

            if ($this->checkFieldMatch($decryptedValue, $operator, $value)) {
                $matchingIds[] = $record->id;
            }
        }

        return $matchingIds;
    }

    /**
     * Find matching records for global search across multiple encrypted fields
     */
    private function findMatchingEncryptedRecordsGlobal(string $searchText, array $encryptedFields): array
    {
        $modelClass = get_class($this->newModelInstance());
        $allRecords = $modelClass::all();
        $matchingIds = [];

        foreach ($allRecords as $record) {
            foreach ($encryptedFields as $field) {
                $decryptedValue = $record->{$field}; // This will auto-decrypt via accessor
                if (stripos($decryptedValue, $searchText) !== false) {
                    $matchingIds[] = $record->id;
                    break; // Found match, no need to check other fields for this record
                }
            }
        }

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
     * Generate cache key for encrypted field search
     */
    private function getEncryptedFieldCacheKey(string $field, string $operator, $value): string
    {
        $modelClass = get_class($this->newModelInstance());
        return sprintf(
            'devextreme_encrypted_%s_%s_%s_%s',
            str_replace('\\', '_', $modelClass),
            $field,
            $operator,
            md5(serialize($value))
        );
    }

    /**
     * Generate cache key for global encrypted search
     */
    private function getGlobalSearchCacheKey(string $searchText, array $encryptedFields): string
    {
        $modelClass = get_class($this->newModelInstance());
        return sprintf(
            'devextreme_global_%s_%s_%s',
            str_replace('\\', '_', $modelClass),
            md5($searchText),
            md5(serialize($encryptedFields))
        );
    }

    /**
     * Clear encrypted fields cache for this model
     */
    public function clearEncryptedFieldsCache(): void
    {
        $modelClass = get_class($this->newModelInstance());
        $pattern = sprintf('devextreme_*_%s_*', str_replace('\\', '_', $modelClass));

        // Note: This is a simplified cache clearing mechanism
        // In production, you might want to use cache tags or a more sophisticated approach
        if (config('devextreme-encrypted.cache.enabled')) {
            Cache::flush(); // This clears all cache - consider using cache tags for better performance
        }
    }
}
