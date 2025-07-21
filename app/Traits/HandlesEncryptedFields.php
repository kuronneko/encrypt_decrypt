<?php

namespace App\Traits;

use Illuminate\Support\Facades\Crypt;

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
     * Apply filter to encrypted fields
     */
    public function applyEncryptedFieldFilter($query, string $field, string $operator, $value)
    {
        // Get all records and filter in PHP (necessary for encrypted fields)
        // This is not ideal for performance but necessary for encrypted fields
        $modelClass = get_class($this->newModelInstance());
        $allRecords = $modelClass::all();
        $matchingIds = [];

        foreach ($allRecords as $record) {
            $decryptedValue = $record->{$field}; // This will auto-decrypt via accessor

            $matches = false;
            switch ($operator) {
                case 'contains':
                    $matches = stripos($decryptedValue, $value) !== false;
                    break;
                case '=':
                    $matches = strcasecmp($decryptedValue, $value) === 0;
                    break;
                case '<>':
                    $matches = strcasecmp($decryptedValue, $value) !== 0;
                    break;
                case '>':
                    $matches = strcmp($decryptedValue, $value) > 0;
                    break;
                case '<':
                    $matches = strcmp($decryptedValue, $value) < 0;
                    break;
                case '>=':
                    $matches = strcmp($decryptedValue, $value) >= 0;
                    break;
                case '<=':
                    $matches = strcmp($decryptedValue, $value) <= 0;
                    break;
            }

            if ($matches) {
                $matchingIds[] = $record->id;
            }
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
     * Apply global search to encrypted fields
     */
    public function applyEncryptedFieldsGlobalSearch($query, string $searchText, array $encryptedFields = null)
    {
        $encryptedFields = $encryptedFields ?? $this->getEncryptedFields();

        if (empty($encryptedFields)) {
            return $query;
        }

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
     * Get a new model instance for the current class
     */
    public function newModelInstance()
    {
        // This should be overridden in the model to return the appropriate model instance
        throw new \Exception('newModelInstance method must be implemented in the model');
    }
}
