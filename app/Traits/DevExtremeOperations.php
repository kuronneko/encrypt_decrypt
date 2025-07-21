<?php

namespace App\Traits;

use App\Components\DevExtremeFilter;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

trait DevExtremeOperations
{
    /**
     * Handle DevExtreme list request with pagination, filtering, and sorting
     */
    protected function handleDevExtremeRequest(Request $request, Builder $query, array $options = [])
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

        // Get configuration options
        $searchableFields = $options['searchableFields'] ?? [];
        $encryptedFieldsHandler = $options['encryptedFieldsHandler'] ?? null;
        $dataTransformer = $options['dataTransformer'] ?? null;

        // Create filter component
        $filterComponent = new DevExtremeFilter($query, $encryptedFieldsHandler);

        // Apply global search if provided
        if (!empty($searchText) && !empty($searchableFields)) {
            $filterComponent->applyGlobalSearch($searchText, $searchableFields);
        }

        // Apply column filters if provided
        if (!empty($filter) && is_array($filter)) {
            $filterComponent->applyFilters($filter);
        }

        // Apply sorting if provided
        $this->applySorting($query, $sort, $options['defaultSort'] ?? ['id' => 'desc']);

        // Get total count before applying pagination
        $totalCount = $query->count();

        // Apply pagination
        $results = $query->skip($skip)->take($take)->get();

        // Transform data if transformer is provided
        if ($dataTransformer && is_callable($dataTransformer)) {
            $results = $results->map($dataTransformer);
        }

        return response()->json([
            'data' => $results,
            'totalCount' => $totalCount
        ]);
    }

    /**
     * Apply sorting to the query
     */
    protected function applySorting(Builder $query, array $sort, array $defaultSort = [])
    {
        if (!empty($sort) && is_array($sort)) {
            foreach ($sort as $sortItem) {
                if (isset($sortItem['selector'])) {
                    $field = $sortItem['selector'];
                    $direction = isset($sortItem['desc']) && $sortItem['desc'] ? 'desc' : 'asc';
                    $query->orderBy($field, $direction);
                }
            }
        } else {
            // Apply default sorting
            foreach ($defaultSort as $field => $direction) {
                $query->orderBy($field, $direction);
            }
        }
    }
}
