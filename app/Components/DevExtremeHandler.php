<?php

namespace App\Components;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class DevExtremeHandler
{
    protected Builder $query;
    protected array $config;
    protected DevExtremeFilter $filterComponent;

    public function __construct(Builder $query, array $config = [])
    {
        $this->query = $query;
        $this->config = array_merge($this->getDefaultConfig(), $config);

        // Initialize filter component
        $this->filterComponent = new DevExtremeFilter(
            $this->query,
            $this->config['encryptedFieldsHandler'],
            $this->config['relatedModels'],
            $this->config['searchableFields']
        );
    }

    /**
     * Handle DevExtreme request and return JSON response
     */
    public function handle(Request $request): JsonResponse
    {
        // Get parameters from DevExtreme
        $skip = $request->get('skip', 0);
        $take = $request->get('take', 10);
        $searchText = $request->get('searchValue', '');
        $filter = $request->get('filter', []);
        $sort = $request->get('sort', []);

        // Parse filter and sort if they are JSON strings
        if (is_string($filter)) {
            $filter = json_decode($filter, true);
        }
        if (is_string($sort)) {
            $sort = json_decode($sort, true);
        }

        // Apply global search if provided
        if (!empty($searchText) && !empty($this->config['searchableFields'])) {
            $this->filterComponent->applyGlobalSearch($searchText, $this->config['searchableFields']);
        }

        // Apply column filters if provided
        if (!empty($filter) && is_array($filter)) {
            $this->filterComponent->applyFilters($filter);
        }

        // Check if we need to sort by encrypted related fields
        $needsPostSorting = $this->needsPostSorting($sort);

        if ($needsPostSorting) {
            // For encrypted field sorting, we need to get all data, transform it, then sort
            $results = $this->query->get();

            // Transform data first
            if ($this->config['dataTransformer'] && is_callable($this->config['dataTransformer'])) {
                $results = $results->map($this->config['dataTransformer']);
            }

            // Apply sorting after transformation
            $results = $this->applyPostSorting($results, $sort);

            // Get total count
            $totalCount = $results->count();

            // Apply pagination manually
            $results = $results->slice($skip, $take)->values();

        } else {
            // Apply SQL-based sorting if provided
            $this->applySorting($sort);

            // Get total count before applying pagination
            $totalCount = $this->query->count();

            // Apply pagination
            $results = $this->query->skip($skip)->take($take)->get();

            // Transform data if transformer is provided
            if ($this->config['dataTransformer'] && is_callable($this->config['dataTransformer'])) {
                $results = $results->map($this->config['dataTransformer']);
            }
        }

        return response()->json([
            'data' => $results,
            'totalCount' => $totalCount
        ]);
    }

    /**
     * Apply sorting to the query
     */
    protected function applySorting(array $sort): void
    {
        if (!empty($sort) && is_array($sort)) {
            foreach ($sort as $sortItem) {
                if (isset($sortItem['selector'])) {
                    $field = $sortItem['selector'];
                    $direction = isset($sortItem['desc']) && $sortItem['desc'] ? 'desc' : 'asc';

                    // Check if this is a related model field
                    $isRelatedField = false;

                    // Check if field contains dot notation (e.g., locations.postal_code)
                    if (strpos($field, '.') !== false) {
                        $this->applyRelatedSorting($field, $direction);
                        $isRelatedField = true;
                    } else {
                        // Check if this field might be a related model field without the relation prefix
                        foreach ($this->config['relatedModels'] as $relationName => $relatedModel) {
                            if ($relatedModel && $relatedModel->isEncryptedField($field)) {
                                // This field exists as an encrypted field in this related model
                                // For encrypted fields, we can't sort in SQL, so we'll skip sorting
                                // and let the frontend handle it after data transformation
                                $isRelatedField = true;
                                break;
                            } elseif ($relatedModel && $this->isFieldInRelatedModel($relatedModel, $field)) {
                                // This field exists as a non-encrypted field in this related model
                                // We can sort this using SQL joins
                                $this->applyRelatedSorting($relationName . '.' . $field, $direction);
                                $isRelatedField = true;
                                break;
                            }
                        }
                    }

                    // If it's not a related field, apply regular sorting
                    if (!$isRelatedField) {
                        $this->query->orderBy($field, $direction);
                    }
                }
            }
        } else {
            // Apply default sorting
            foreach ($this->config['defaultSort'] as $field => $direction) {
                $this->query->orderBy($field, $direction);
            }
        }
    }

    /**
     * Apply sorting to related model fields
     */
    protected function applyRelatedSorting(string $field, string $direction): void
    {
        // Parse the relationship and field (e.g., locations.postal_code)
        $parts = explode('.', $field, 2);
        if (count($parts) !== 2) {
            return;
        }

        $relationName = $parts[0];
        $relationField = $parts[1];

        // For related model sorting, we need to join the related table
        // This assumes a one-to-one or many-to-one relationship
        $this->query->join($relationName, function($join) use ($relationName) {
            $join->on('users.id', '=', $relationName . '.user_id');
        })->orderBy($relationName . '.' . $relationField, $direction);
    }

    /**
     * Check if we need to sort after data transformation (for encrypted fields)
     */
    protected function needsPostSorting(array $sort): bool
    {
        if (empty($sort)) {
            return false;
        }

        foreach ($sort as $sortItem) {
            if (isset($sortItem['selector'])) {
                $field = $sortItem['selector'];

                // Check if this is an encrypted field in any related model
                foreach ($this->config['relatedModels'] as $relationName => $relatedModel) {
                    if ($relatedModel && $relatedModel->isEncryptedField($field)) {
                        return true; // Only encrypted fields need post-sorting
                    }
                }
            }
        }

        return false;
    }

    /**
     * Apply sorting after data transformation
     */
    protected function applyPostSorting($results, array $sort)
    {
        if (empty($sort)) {
            return $results;
        }

        // Convert to array for sorting
        $resultsArray = $results->toArray();

        foreach (array_reverse($sort) as $sortItem) {
            if (isset($sortItem['selector'])) {
                $field = $sortItem['selector'];
                $direction = isset($sortItem['desc']) && $sortItem['desc'] ? 'desc' : 'asc';

                usort($resultsArray, function($a, $b) use ($field, $direction) {
                    $valueA = $a[$field] ?? '';
                    $valueB = $b[$field] ?? '';

                    // Handle numeric comparison
                    if (is_numeric($valueA) && is_numeric($valueB)) {
                        $result = $valueA <=> $valueB;
                    } else {
                        // String comparison
                        $result = strcasecmp((string)$valueA, (string)$valueB);
                    }

                    return $direction === 'desc' ? -$result : $result;
                });
            }
        }

        return collect($resultsArray);
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
     * Get default configuration
     */
    protected function getDefaultConfig(): array
    {
        return [
            'searchableFields' => [],
            'encryptedFieldsHandler' => null,
            'relatedModels' => [],
            'defaultSort' => ['id' => 'desc'],
            'dataTransformer' => null,
        ];
    }

    /**
     * Static factory method for quick creation with fresh query
     */
    public static function create(Builder $query, array $config = []): self
    {
        // Clone the query to ensure we start with a fresh state
        $freshQuery = clone $query;
        return new static($freshQuery, $config);
    }
}
