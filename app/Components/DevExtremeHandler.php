<?php

namespace App\Components;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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

        // Log what we're processing
        Log::info("DevExtremeHandler: Processing request", [
            'skip' => $skip,
            'take' => $take,
            'searchText' => $searchText,
            'filter' => $filter,
            'sort' => $sort
        ]);

        // Apply global search if provided
        if (!empty($searchText) && !empty($this->config['searchableFields'])) {
            $this->filterComponent->applyGlobalSearch($searchText, $this->config['searchableFields']);
        }

        // Apply column filters if provided
        if (!empty($filter) && is_array($filter)) {
            $this->filterComponent->applyFilters($filter);
        }

        // Check if we need post-processing (for any related fields or encrypted fields)
        $needsPostProcessing = $this->needsPostProcessing($sort, $filter);

        if ($needsPostProcessing) {
            Log::info("DevExtremeHandler: Using post-processing approach");

            // Get all data without sorting, then process in memory
            $results = $this->query->get();

            // Transform data first
            if ($this->config['dataTransformer'] && is_callable($this->config['dataTransformer'])) {
                $results = $results->map($this->config['dataTransformer']);
            }

            // Apply sorting after transformation
            if (!empty($sort)) {
                $results = $this->applyPostSorting($results, $sort);
            }

            // Get total count
            $totalCount = $results->count();

            // Apply pagination manually
            $results = $results->slice($skip, $take)->values();

        } else {
            Log::info("DevExtremeHandler: Using SQL-based approach");

            // Apply SQL-based sorting only for main table fields
            $this->applySafeMainTableSorting($sort);

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
     * Check if we need post-processing instead of SQL-based operations
     */
    protected function needsPostProcessing(array $sort, array $filter): bool
    {
        // Check sorting
        if (!empty($sort)) {
            foreach ($sort as $sortItem) {
                if (isset($sortItem['selector'])) {
                    $field = $sortItem['selector'];

                    // If it's a related field or might be a related field, use post-processing
                    if ($this->isRelatedField($field)) {
                        return true;
                    }
                }
            }
        }

        // Check filtering - if any filters involve related fields, use post-processing
        if (!empty($filter)) {
            if ($this->hasRelatedFieldFilters($filter)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a field is a related field
     */
    protected function isRelatedField(string $field): bool
    {
        // Direct dot notation
        if (strpos($field, '.') !== false) {
            return true;
        }

        // Check if field exists in any related model
        foreach ($this->config['relatedModels'] as $relationName => $relatedModel) {
            if ($relatedModel && $this->isFieldInRelatedModel($relatedModel, $field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Recursively check if filters contain related fields
     */
    protected function hasRelatedFieldFilters(array $filter): bool
    {
        // Handle simple filter array [field, operator, value]
        if (count($filter) === 3 && !is_array($filter[0]) && is_string($filter[0])) {
            return $this->isRelatedField($filter[0]);
        }

        // Handle complex nested filters
        foreach ($filter as $filterItem) {
            if (is_array($filterItem)) {
                if ($this->hasRelatedFieldFilters($filterItem)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Apply safe sorting only to main table fields (avoid JOINs)
     */
    protected function applySafeMainTableSorting(array $sort): void
    {
        if (!empty($sort) && is_array($sort)) {
            foreach ($sort as $sortItem) {
                if (isset($sortItem['selector'])) {
                    $field = $sortItem['selector'];
                    $direction = isset($sortItem['desc']) && $sortItem['desc'] ? 'desc' : 'asc';

                    // Only sort by main table fields to avoid JOIN issues
                    if (!$this->isRelatedField($field)) {
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
     * Apply sorting after data transformation (in-memory)
     */
    protected function applyPostSorting($results, array $sort)
    {
        if (empty($sort)) {
            return $results;
        }

        // Convert to array for sorting
        $resultsArray = $results->toArray();

        // Apply sorting in reverse order (since we're doing stable sorts)
        foreach (array_reverse($sort) as $sortItem) {
            if (isset($sortItem['selector'])) {
                $field = $sortItem['selector'];
                $direction = isset($sortItem['desc']) && $sortItem['desc'] ? 'desc' : 'asc';

                Log::info("DevExtremeHandler: Applying post-sort", [
                    'field' => $field,
                    'direction' => $direction
                ]);

                usort($resultsArray, function($a, $b) use ($field, $direction) {
                    $valueA = $a[$field] ?? '';
                    $valueB = $b[$field] ?? '';

                    // Handle null values
                    if ($valueA === null && $valueB === null) return 0;
                    if ($valueA === null) return 1;
                    if ($valueB === null) return -1;

                    // Handle numeric comparison
                    if (is_numeric($valueA) && is_numeric($valueB)) {
                        $result = $valueA <=> $valueB;
                    } else {
                        // String comparison (case insensitive)
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
