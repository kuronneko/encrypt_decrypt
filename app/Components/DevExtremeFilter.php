<?php

namespace App\Components;

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

        Log::info("DevExtremeFilter: Applying filters", ['filters' => $filters]);

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

        Log::info("DevExtremeFilter: Processing single filter", [
            'field' => $field,
            'operator' => $operator,
            'value' => $value
        ]);

        // Check if we have searchable fields restriction and enforce it
        if (!empty($this->searchableFields) && !$this->isFieldSearchable($field)) {
            Log::info("DevExtremeFilter: Field not searchable, skipping", ['field' => $field]);
            return $this->query;
        }

        // Handle related model fields with dot notation (e.g., locations.postal_code)
        if (strpos($field, '.') !== false) {
            Log::info("DevExtremeFilter: Field has dot notation", ['field' => $field]);
            return $this->applyRelatedModelFilter($field, $operator, $value);
        }

        // PRIORITY 1: Check if field exists in main table first (to prevent conflicts)
        if ($this->isFieldInMainTable($field)) {
            Log::info("DevExtremeFilter: Field found in main table", ['field' => $field]);

            // Handle encrypted fields in main model
            if ($this->encryptedFieldsHandler && $this->encryptedFieldsHandler->isEncryptedField($field)) {
                Log::info("DevExtremeFilter: Field is encrypted in main model", ['field' => $field]);
                return $this->encryptedFieldsHandler->applyEncryptedFieldFilter($this->query, $field, $operator, $value);
            }

            // Handle date fields
            if ($this->isDateField($field)) {
                Log::info("DevExtremeFilter: Field is date field", ['field' => $field]);
                return $this->applyDateFilter($field, $operator, $value);
            }

            // Handle regular fields in main table
            Log::info("DevExtremeFilter: Field is regular field in main table", ['field' => $field]);
            return $this->applyRegularFilter($field, $operator, $value);
        }

        // PRIORITY 2: Only check related models if field doesn't exist in main table
        foreach ($this->relatedModels as $relationName => $relatedModel) {
            if ($this->isFieldInRelatedModel($relatedModel, $field)) {
                Log::info("DevExtremeFilter: Found field in related model (fallback)", [
                    'field' => $field,
                    'relationName' => $relationName
                ]);
                return $this->applyRelatedModelFilter($relationName . '.' . $field, $operator, $value);
            }
        }

        // If field is not found anywhere, log and skip
        Log::warning("DevExtremeFilter: Field not found in any table", ['field' => $field]);
        return $this->query;
    }

    /**
     * Apply filter to related model fields using whereHas (no JOIN)
     */
    protected function applyRelatedModelFilter(string $field, string $operator, $value): Builder
    {
        // Parse the relationship and field (e.g., locations.postal_code)
        $parts = explode('.', $field, 2);
        if (count($parts) !== 2) {
            return $this->query;
        }

        $relationName = $parts[0];
        $relationField = $parts[1];

        Log::info("DevExtremeFilter: Applying related model filter", [
            'relation' => $relationName,
            'field' => $relationField,
            'operator' => $operator,
            'value' => $value
        ]);

        // Check if we have a handler for this related model
        $relatedModel = $this->relatedModels[$relationName] ?? null;

        if ($relatedModel && $relatedModel->isEncryptedField($relationField)) {
            // Handle encrypted fields in related models
            return $this->applyEncryptedRelatedModelFilter($relationName, $relationField, $operator, $value, $relatedModel);
        }

        // Handle regular related model fields using whereHas (safer than JOIN)
        return $this->applyRegularRelatedModelFilter($relationName, $relationField, $operator, $value);
    }

    /**
     * Apply filter to encrypted fields in related models using optimized caching
     */
    protected function applyEncryptedRelatedModelFilter(string $relationName, string $field, string $operator, $value, $relatedModel): Builder
    {
        Log::info("DevExtremeFilter: Applying encrypted related field filter with caching", [
            'relation' => $relationName,
            'field' => $field,
            'operator' => $operator
        ]);

        // Use the optimized cached method from AdvancedOptimizedEncryptedFields trait
        if ($this->encryptedFieldsHandler && method_exists($this->encryptedFieldsHandler, 'applyEncryptedRelatedModelFilter')) {
            return $this->encryptedFieldsHandler->applyEncryptedRelatedModelFilter(
                $this->query,
                $relationName,
                $field,
                $operator,
                $value,
                $relatedModel
            );
        }

        // Fallback to the old method if the optimized one isn't available
        Log::warning("DevExtremeFilter: Falling back to non-cached method for encrypted related field");

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
     * Apply filter to regular fields in related models using whereHas
     */
    protected function applyRegularRelatedModelFilter(string $relationName, string $field, string $operator, $value): Builder
    {
        $this->query->whereHas($relationName, function($q) use ($field, $operator, $value) {
            switch ($operator) {
                case 'contains':
                    $q->where($field, 'like', '%' . $value . '%');
                    break;
                case '=':
                    $q->where($field, $value);
                    break;
                case '<>':
                    $q->where($field, '!=', $value);
                    break;
                case '>':
                    $q->where($field, '>', $value);
                    break;
                case '<':
                    $q->where($field, '<', $value);
                    break;
                case '>=':
                    $q->where($field, '>=', $value);
                    break;
                case '<=':
                    $q->where($field, '<=', $value);
                    break;
                case 'between':
                    if (is_array($value) && count($value) >= 2) {
                        $q->whereBetween($field, [$value[0], $value[1]]);
                    }
                    break;
            }
        });

        return $this->query;
    }

    /**
     * Apply filter to date fields
     */
    protected function applyDateFilter(string $field, string $operator, $value): Builder
    {
        // Convert the value to a proper date format if needed
        if (is_string($value)) {
            try {
                $value = \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                // If parsing fails, use the original value
            }
        }

        switch ($operator) {
            case '=':
                if (is_string($value) && strlen($value) <= 10) {
                    $this->query->whereDate($field, $value);
                } else {
                    $this->query->where($field, $value);
                }
                break;
            case '<>':
                $this->query->where($field, '!=', $value);
                break;
            case '>':
                $this->query->where($field, '>', $value);
                break;
            case '<':
                $this->query->where($field, '<', $value);
                break;
            case '>=':
                $this->query->where($field, '>=', $value);
                break;
            case '<=':
                $this->query->where($field, '<=', $value);
                break;
            case 'between':
                if (is_array($value) && count($value) >= 2) {
                    $this->query->whereBetween($field, [$value[0], $value[1]]);
                }
                break;
        }

        return $this->query;
    }

    /**
     * Apply filter to regular fields
     */
    protected function applyRegularFilter(string $field, string $operator, $value): Builder
    {
        switch ($operator) {
            case 'contains':
                $this->query->where($field, 'like', '%' . $value . '%');
                break;
            case '=':
                $this->query->where($field, $value);
                break;
            case '<>':
                $this->query->where($field, '!=', $value);
                break;
            case '>':
                $this->query->where($field, '>', $value);
                break;
            case '<':
                $this->query->where($field, '<', $value);
                break;
            case '>=':
                $this->query->where($field, '>=', $value);
                break;
            case '<=':
                $this->query->where($field, '<=', $value);
                break;
            case 'between':
                if (is_array($value) && count($value) >= 2) {
                    $this->query->whereBetween($field, [$value[0], $value[1]]);
                }
                break;
        }

        return $this->query;
    }

    /**
     * Apply global search
     */
    public function applyGlobalSearch(string $searchText, array $searchableFields = []): Builder
    {
        if (empty($searchText) || empty($searchableFields)) {
            return $this->query;
        }

        Log::info("DevExtremeFilter: Applying global search", [
            'searchText' => $searchText,
            'searchableFields' => $searchableFields
        ]);

        $this->query->where(function($q) use ($searchText, $searchableFields) {
            foreach ($searchableFields as $field) {
                if (strpos($field, '.') !== false) {
                    // Related model field
                    $parts = explode('.', $field, 2);
                    if (count($parts) === 2) {
                        $relationName = $parts[0];
                        $relationField = $parts[1];

                        $relatedModel = $this->relatedModels[$relationName] ?? null;

                        if ($relatedModel && $relatedModel->isEncryptedField($relationField)) {
                            // Handle encrypted related field search
                            $this->handleEncryptedRelatedSearch($q, $relationName, $relationField, $searchText, $relatedModel);
                        } else {
                            // Handle regular related field search
                            $q->orWhereHas($relationName, function($subQ) use ($relationField, $searchText) {
                                $subQ->where($relationField, 'like', '%' . $searchText . '%');
                            });
                        }
                    }
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
     * Handle encrypted related field search
     */
    protected function handleEncryptedRelatedSearch($q, $relationName, $relationField, $searchText, $relatedModel): void
    {
        $relatedModelInstance = get_class($relatedModel);
        $allRelatedRecords = $relatedModelInstance::all();
        $matchingIds = [];

        foreach ($allRelatedRecords as $record) {
            $decryptedValue = $record->{$relationField};
            if (stripos((string)$decryptedValue, (string)$searchText) !== false) {
                $matchingIds[] = $record->id;
            }
        }

        if (!empty($matchingIds)) {
            $q->orWhereHas($relationName, function($subQ) use ($matchingIds) {
                $subQ->whereIn('id', $matchingIds);
            });
        }
    }

    /**
     * Check if a value matches the given operator
     */
    protected function matchesOperator($fieldValue, string $operator, $searchValue): bool
    {
        switch ($operator) {
            case 'contains':
                return stripos((string)$fieldValue, (string)$searchValue) !== false;
            case '=':
                return $fieldValue == $searchValue;
            case '<>':
                return $fieldValue != $searchValue;
            case '>':
                return $fieldValue > $searchValue;
            case '<':
                return $fieldValue < $searchValue;
            case '>=':
                return $fieldValue >= $searchValue;
            case '<=':
                return $fieldValue <= $searchValue;
            default:
                return false;
        }
    }

    /**
     * Check if a field exists in the main table
     */
    protected function isFieldInMainTable(string $field): bool
    {
        if (!$this->encryptedFieldsHandler) {
            return false;
        }

        // Check if it's in the fillable fields of the main model
        $fillable = $this->encryptedFieldsHandler->getFillable();
        if (!empty($fillable) && in_array($field, $fillable)) {
            return true;
        }

        // Check the actual table columns
        try {
            $tableName = $this->encryptedFieldsHandler->getTable();
            $columns = \Illuminate\Support\Facades\Schema::getColumnListing($tableName);
            return in_array($field, $columns);
        } catch (\Exception $e) {
            Log::error("DevExtremeFilter: Error checking main table columns", [
                'field' => $field,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if a field exists in a related model
     */
    protected function isFieldInRelatedModel($relatedModel, string $field): bool
    {
        if (!$relatedModel) {
            return false;
        }

        $fillable = $relatedModel->getFillable();

        // Check fillable first
        if (!empty($fillable) && in_array($field, $fillable)) {
            return true;
        }

        // Check table columns
        try {
            $tableName = $relatedModel->getTable();
            $columns = \Illuminate\Support\Facades\Schema::getColumnListing($tableName);
            return in_array($field, $columns);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if a field is a date field
     */
    protected function isDateField(string $field): bool
    {
        $dateFields = ['created_at', 'updated_at', 'deleted_at', 'email_verified_at'];
        return in_array($field, $dateFields);
    }

    /**
     * Check if a field is in the searchable fields list
     */
    protected function isFieldSearchable(string $field): bool
    {
        if (empty($this->searchableFields)) {
            return true;
        }

        // Direct match
        if (in_array($field, $this->searchableFields)) {
            return true;
        }

        // Check if field is part of a related field
        foreach ($this->searchableFields as $searchableField) {
            if (strpos($searchableField, '.') !== false) {
                $parts = explode('.', $searchableField, 2);
                if (count($parts) === 2 && $parts[1] === $field) {
                    return true;
                }
            }
        }

        return false;
    }
}
