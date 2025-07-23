<?php

namespace Kuronneko\LaravelDevExtremeEncrypted\Components;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class DevExtremeFilter
{
    protected $query;
    protected $encryptedFieldsHandler;
    protected $relatedModels;
    protected $searchableFields;

    public function __construct(Builder $query, $encryptedFieldsHandler = null, $relatedModels = [], $searchableFields = [])
    {
        $this->query = $query;
        $this->encryptedFieldsHandler = $encryptedFieldsHandler;
        $this->relatedModels = $relatedModels;
        $this->searchableFields = $searchableFields;
    }

    /**
     * Apply DevExtreme filters to the query
     */
    public function applyFilters(array $filters): Builder
    {
        if (empty($filters)) {
            return $this->query;
        }

        if (config('devextreme-encrypted.performance.debug_logging')) {
            Log::info("DevExtremeFilter: Applying filters", ['filters' => $filters]);
        }

        // Handle simple filter array [field, operator, value]
        if (count($filters) === 3 && !is_array($filters[0])) {
            return $this->applySingleFilter($filters);
        }

        // Handle complex filters (AND/OR operations)
        foreach ($filters as $filterItem) {
            if (is_array($filterItem)) {
                if (count($filterItem) === 3 && !is_array($filterItem[0])) {
                    // Simple filter [field, operator, value]
                    $this->applySingleFilter($filterItem);
                } else {
                    // Complex nested filter
                    $this->applyFilters($filterItem);
                }
            }
        }

        return $this->query;
    }

    /**
     * Apply global search across searchable fields
     */
    public function applyGlobalSearch(string $searchText, array $searchableFields = []): Builder
    {
        if (empty($searchText) || empty($searchableFields)) {
            return $this->query;
        }

        if (config('devextreme-encrypted.performance.debug_logging')) {
            Log::info("DevExtremeFilter: Applying global search", [
                'searchText' => $searchText,
                'fields' => $searchableFields
            ]);
        }

        $this->query->where(function($q) use ($searchText, $searchableFields) {
            foreach ($searchableFields as $field) {
                // Check for protected fields
                if (in_array($field, config('devextreme-encrypted.security.protected_fields', []))) {
                    continue;
                }

                // Handle related model fields with dot notation
                if (strpos($field, '.') !== false) {
                    $this->applyRelatedFieldSearch($q, $field, $searchText);
                } elseif ($this->encryptedFieldsHandler && $this->encryptedFieldsHandler->isEncryptedField($field)) {
                    // Handle encrypted field in main model
                    $matchingIds = $this->encryptedFieldsHandler->applyEncryptedFieldsGlobalSearch($q, $searchText, [$field]);
                    if (!empty($matchingIds)) {
                        $q->orWhereIn('id', $matchingIds);
                    }
                } else {
                    // Handle regular field in main model
                    $q->orWhere($field, 'like', '%' . $searchText . '%');
                }
            }
        });

        return $this->query;
    }

    /**
     * Apply a single filter condition
     */
    protected function applySingleFilter(array $filter): Builder
    {
        if (!isset($filter[0], $filter[1], $filter[2])) {
            return $this->query;
        }

        $field = $filter[0];
        $operator = $filter[1];
        $value = $filter[2];

        // Check for protected fields
        if (in_array($field, config('devextreme-encrypted.security.protected_fields', []))) {
            return $this->query;
        }

        if (config('devextreme-encrypted.performance.debug_logging')) {
            Log::info("DevExtremeFilter: Processing single filter", [
                'field' => $field,
                'operator' => $operator,
                'value' => $value
            ]);
        }

        // Handle related model fields with dot notation
        if (strpos($field, '.') !== false) {
            return $this->applyRelatedModelFilter($field, $operator, $value);
        }

        // Handle encrypted fields in main model
        if ($this->encryptedFieldsHandler && $this->encryptedFieldsHandler->isEncryptedField($field)) {
            return $this->encryptedFieldsHandler->applyEncryptedFieldFilter($this->query, $field, $operator, $value);
        }

        // Handle date fields
        if ($this->isDateField($field)) {
            return $this->applyDateFilter($field, $operator, $value);
        }

        // Handle regular fields
        return $this->applyRegularFilter($field, $operator, $value);
    }

    /**
     * Apply search for related field
     */
    protected function applyRelatedFieldSearch($q, string $field, string $searchText): void
    {
        [$relationName, $relationField] = explode('.', $field, 2);

        if (isset($this->relatedModels[$relationName])) {
            $relatedModel = $this->relatedModels[$relationName];

            if ($this->encryptedFieldsHandler && $relatedModel->isEncryptedField($relationField)) {
                // Handle encrypted related field
                $this->handleEncryptedRelatedSearch($q, $relationName, $relationField, $searchText, $relatedModel);
            } else {
                // Handle regular related field
                $q->orWhereHas($relationName, function($subQ) use ($relationField, $searchText) {
                    $subQ->where($relationField, 'like', '%' . $searchText . '%');
                });
            }
        }
    }

    /**
     * Handle encrypted related field search
     */
    protected function handleEncryptedRelatedSearch($q, $relationName, $relationField, $searchText, $relatedModel): void
    {
        // For encrypted fields, we need to get all related records and filter in PHP
        $relatedModelInstance = get_class($relatedModel);
        $allRelatedRecords = $relatedModelInstance::all();
        $matchingIds = [];

        foreach ($allRelatedRecords as $record) {
            $decryptedValue = $record->{$relationField}; // Auto-decrypt

            if (stripos($decryptedValue, $searchText) !== false) {
                $matchingIds[] = $record->id;
            }
        }

        // Filter main query based on matching related record IDs
        if (!empty($matchingIds)) {
            $q->orWhereHas($relationName, function($subQ) use ($matchingIds) {
                $subQ->whereIn('id', $matchingIds);
            });
        }
    }

    /**
     * Apply filter for related model field
     */
    protected function applyRelatedModelFilter(string $field, string $operator, $value): Builder
    {
        [$relationName, $relationField] = explode('.', $field, 2);

        if (isset($this->relatedModels[$relationName])) {
            $relatedModel = $this->relatedModels[$relationName];

            if ($relatedModel && method_exists($relatedModel, 'isEncryptedField') && $relatedModel->isEncryptedField($relationField)) {
                // Handle encrypted related field
                return $this->applyEncryptedRelatedFilter($relationName, $relationField, $operator, $value, $relatedModel);
            } else {
                // Handle regular related field
                return $this->query->whereHas($relationName, function($q) use ($relationField, $operator, $value) {
                    $this->applyRegularFilter($relationField, $operator, $value, $q);
                });
            }
        }

        return $this->query;
    }

    /**
     * Apply filter for encrypted related field
     */
    protected function applyEncryptedRelatedFilter($relationName, $field, $operator, $value, $relatedModel): Builder
    {
        // Get all related records and filter in PHP
        $relatedModelInstance = get_class($relatedModel);
        $allRelatedRecords = $relatedModelInstance::all();
        $matchingIds = [];

        foreach ($allRelatedRecords as $record) {
            $decryptedValue = $record->{$field}; // Auto-decrypt

            if ($this->matchesOperator($decryptedValue, $operator, $value)) {
                $matchingIds[] = $record->id;
            }
        }

        // Filter main query based on matching related record IDs
        if (!empty($matchingIds)) {
            $this->query->whereHas($relationName, function($q) use ($matchingIds) {
                $q->whereIn('id', $matchingIds);
            });
        } else {
            // No matches found
            $this->query->whereRaw('1 = 0');
        }

        return $this->query;
    }

    /**
     * Apply date filter
     */
    protected function applyDateFilter(string $field, string $operator, $value, Builder $query = null): Builder
    {
        $query = $query ?: $this->query;

        // Convert value to proper date format if needed
        if (is_string($value)) {
            try {
                $value = \Carbon\Carbon::parse($value);
            } catch (\Exception $e) {
                // If parsing fails, treat as regular filter
                return $this->applyRegularFilter($field, $operator, $value, $query);
            }
        }

        switch ($operator) {
            case '=':
                $query->whereDate($field, $value);
                break;
            case '<>':
                $query->whereDate($field, '!=', $value);
                break;
            case '>':
                $query->whereDate($field, '>', $value);
                break;
            case '<':
                $query->whereDate($field, '<', $value);
                break;
            case '>=':
                $query->whereDate($field, '>=', $value);
                break;
            case '<=':
                $query->whereDate($field, '<=', $value);
                break;
            default:
                $query->whereDate($field, $value);
        }

        return $query;
    }

    /**
     * Apply regular filter
     */
    protected function applyRegularFilter(string $field, string $operator, $value, Builder $query = null): Builder
    {
        $query = $query ?: $this->query;

        switch ($operator) {
            case 'contains':
                $query->where($field, 'like', '%' . $value . '%');
                break;
            case 'notcontains':
                $query->where($field, 'not like', '%' . $value . '%');
                break;
            case 'startswith':
                $query->where($field, 'like', $value . '%');
                break;
            case 'endswith':
                $query->where($field, 'like', '%' . $value);
                break;
            case '=':
                $query->where($field, $value);
                break;
            case '<>':
                $query->where($field, '!=', $value);
                break;
            case '>':
                $query->where($field, '>', $value);
                break;
            case '<':
                $query->where($field, '<', $value);
                break;
            case '>=':
                $query->where($field, '>=', $value);
                break;
            case '<=':
                $query->where($field, '<=', $value);
                break;
            default:
                $query->where($field, $value);
        }

        return $query;
    }

    /**
     * Check if a field is a date field
     */
    protected function isDateField(string $field): bool
    {
        if (!config('devextreme-encrypted.field_detection.auto_detect_dates', true)) {
            return false;
        }

        $patterns = config('devextreme-encrypted.field_detection.date_field_patterns', ['*_at', '*_date', 'date_*']);

        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a decrypted value matches the given operator and value
     */
    protected function matchesOperator($decryptedValue, string $operator, $value): bool
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
}
