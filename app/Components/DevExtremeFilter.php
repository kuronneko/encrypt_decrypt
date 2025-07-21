<?php

namespace App\Components;

use Illuminate\Database\Eloquent\Builder;

class DevExtremeFilter
{
    protected $query;
    protected $encryptedFieldsHandler;

    public function __construct(Builder $query, $encryptedFieldsHandler = null)
    {
        $this->query = $query;
        $this->encryptedFieldsHandler = $encryptedFieldsHandler;
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

            // Separate regular and encrypted fields
            foreach ($searchableFields as $field) {
                if ($this->encryptedFieldsHandler && $this->encryptedFieldsHandler->isEncryptedField($field)) {
                    $encryptedFields[] = $field;
                } else {
                    $regularFields[] = $field;
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
        });

        return $this->query;
    }
}
