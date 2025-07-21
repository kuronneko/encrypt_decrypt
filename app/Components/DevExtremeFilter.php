<?php

namespace App\Components;

use Illuminate\Database\Eloquent\Builder;

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

        // Check if we have searchable fields restriction and enforce it
        if (!empty($this->searchableFields) && !$this->isFieldSearchable($field)) {
            // Field is not in searchableFields array, skip this filter
            return $this->query;
        }

        // Handle related model fields (e.g., locations.postal_code)
        if (strpos($field, '.') !== false) {
            return $this->applyRelatedModelFilter($field, $operator, $value);
        }

        // Check if this field might be a related model field without the relation prefix
        // For example, 'postal_code' might actually be 'locations.postal_code'
        foreach ($this->relatedModels as $relationName => $relatedModel) {
            if ($relatedModel && $relatedModel->isEncryptedField($field)) {
                // This field exists as an encrypted field in this related model
                return $this->applyRelatedModelFilter($relationName . '.' . $field, $operator, $value);
            }
        }

        // Check if this field might be a non-encrypted field in a related model
        // We need to check the actual related model's fillable/table structure
        foreach ($this->relatedModels as $relationName => $relatedModel) {
            if ($this->isFieldInRelatedModel($relatedModel, $field)) {
                // This field exists in this related model (but it's not encrypted)
                return $this->applyRelatedModelFilter($relationName . '.' . $field, $operator, $value);
            }
        }

        // Handle encrypted fields if handler is provided
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
                // For exact date matching, we might want to match the whole day
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
     * Apply filter to related model fields (e.g., locations.postal_code)
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

        // Check if we have a handler for this related model
        $relatedModel = $this->relatedModels[$relationName] ?? null;

        if ($relatedModel && $relatedModel->isEncryptedField($relationField)) {
            // Handle encrypted fields in related models
            return $this->applyEncryptedRelatedModelFilter($relationName, $relationField, $operator, $value, $relatedModel);
        }

        // Handle regular related model fields
        return $this->applyRegularRelatedModelFilter($relationName, $relationField, $operator, $value);
    }

    /**
     * Apply filter to encrypted fields in related models
     */
    protected function applyEncryptedRelatedModelFilter(string $relationName, string $field, string $operator, $value, $relatedModel): Builder
    {
        // For encrypted fields, we need to get all related records and filter in PHP
        // This is less efficient but necessary for encrypted data

        // First, get all records from the related table
        $relatedModelInstance = get_class($relatedModel);
        $allRelatedRecords = $relatedModelInstance::all();
        $matchingIds = [];

        foreach ($allRelatedRecords as $record) {
            $decryptedValue = $record->{$field}; // This will auto-decrypt

            if ($this->matchesOperator($decryptedValue, $operator, $value)) {
                $matchingIds[] = $record->id;
            }
        }

        // Now filter the main query based on matching related record IDs
        if (!empty($matchingIds)) {
            $this->query->whereHas($relationName, function($q) use ($matchingIds) {
                $q->whereIn('id', $matchingIds);
            });
        } else {
            // No matches found, ensure no results are returned
            $this->query->whereRaw('1 = 0');
        }

        return $this->query;
    }    /**
     * Apply filter to regular fields in related models
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
     * Check if a field exists in a related model
     */
    protected function isFieldInRelatedModel($relatedModel, string $field): bool
    {
        if (!$relatedModel) {
            return false;
        }

        // Get the model's fillable fields
        $fillable = $relatedModel->getFillable();

        // Check if the field is in fillable array
        if (!empty($fillable) && in_array($field, $fillable)) {
            return true;
        }

        // If fillable is empty or field not in fillable, check actual table structure
        try {
            $tableName = $relatedModel->getTable();
            $columns = \Illuminate\Support\Facades\Schema::getColumnListing($tableName);
            return in_array($field, $columns);
        } catch (\Exception $e) {
            // If we can't get the table structure, return false
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
     * Apply global search
     */
    public function applyGlobalSearch(string $searchText, array $searchableFields = []): Builder
    {
        if (empty($searchText) || empty($searchableFields)) {
            return $this->query;
        }

        $this->query->where(function($q) use ($searchText, $searchableFields) {
            $regularFields = [];
            $encryptedFields = [];
            $relatedFields = [];

            // Separate regular, encrypted, and related fields
            foreach ($searchableFields as $field) {
                if (strpos($field, '.') !== false) {
                    // Related model field
                    $relatedFields[] = $field;
                } elseif ($this->encryptedFieldsHandler && $this->encryptedFieldsHandler->isEncryptedField($field)) {
                    $encryptedFields[] = $field;
                } else {
                    // Check if this field might be in a related model
                    $isRelatedField = false;
                    foreach ($this->relatedModels as $relationName => $relatedModel) {
                        if ($relatedModel && ($relatedModel->isEncryptedField($field) || $this->isFieldInRelatedModel($relatedModel, $field))) {
                            $relatedFields[] = $relationName . '.' . $field;
                            $isRelatedField = true;
                            break;
                        }
                    }

                    if (!$isRelatedField) {
                        $regularFields[] = $field;
                    }
                }
            }

            // Apply search to regular fields
            foreach ($regularFields as $field) {
                $q->orWhere($field, 'like', '%' . $searchText . '%');
            }

            // Apply search to encrypted fields
            if (!empty($encryptedFields) && $this->encryptedFieldsHandler) {
                $matchingIds = $this->encryptedFieldsHandler->applyEncryptedFieldsGlobalSearch(
                    $q,
                    $searchText,
                    $encryptedFields
                );

                if (!empty($matchingIds)) {
                    $q->orWhereIn('id', $matchingIds);
                }
            }

            // Apply search to related model fields
            foreach ($relatedFields as $field) {
                $parts = explode('.', $field, 2);
                if (count($parts) === 2) {
                    $relationName = $parts[0];
                    $relationField = $parts[1];

                    // Check if it's an encrypted field in the related model
                    $relatedModel = $this->relatedModels[$relationName] ?? null;

                    if ($relatedModel && $relatedModel->isEncryptedField($relationField)) {
                        // Handle encrypted related field search
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
                    } else {
                        // Handle regular related field search
                        $q->orWhereHas($relationName, function($subQ) use ($relationField, $searchText) {
                            $subQ->where($relationField, 'like', '%' . $searchText . '%');
                        });
                    }
                }
            }
        });

        return $this->query;
    }

    /**
     * Check if a field is in the searchable fields list
     */
    protected function isFieldSearchable(string $field): bool
    {
        if (empty($this->searchableFields)) {
            return true; // If no restrictions, all fields are searchable
        }

        // Direct match
        if (in_array($field, $this->searchableFields)) {
            return true;
        }

        // Check if field might be part of a related field (e.g., 'postal_code' could be 'locations.postal_code')
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
