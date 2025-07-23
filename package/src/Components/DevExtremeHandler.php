<?php

namespace Kuronneko\LaravelDevExtremeEncrypted\Components;

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
        $take = $request->get('take', config('devextreme-encrypted.pagination.default_page_size', 20));
        $searchText = $request->get('searchValue', '');
        $filter = $request->get('filter', []);
        $sort = $request->get('sort', []);

        // Enforce max page size
        $maxPageSize = config('devextreme-encrypted.pagination.max_page_size', 1000);
        if ($take > $maxPageSize) {
            $take = $maxPageSize;
        }

        // Parse filter and sort if they are JSON strings
        if (is_string($filter)) {
            $filter = json_decode($filter, true);
        }
        if (is_string($sort)) {
            $sort = json_decode($sort, true);
        }

        // Debug logging
        if (config('devextreme-encrypted.performance.debug_logging')) {
            Log::info("DevExtremeHandler: Processing request", [
                'skip' => $skip,
                'take' => $take,
                'searchText' => $searchText,
                'filter' => $filter,
                'sort' => $sort
            ]);
        }

        // Apply global search if provided
        if (!empty($searchText) && !empty($this->config['searchableFields'])) {
            $this->filterComponent->applyGlobalSearch($searchText, $this->config['searchableFields']);
        }

        // Apply column filters if provided
        if (!empty($filter) && is_array($filter)) {
            $this->filterComponent->applyFilters($filter);
        }

        // Determine optimization strategy
        $strategy = $this->determineOptimizationStrategy($sort, $filter, $searchText);

        switch ($strategy) {
            case 'sql_only':
                return $this->handleSqlOnlyStrategy($skip, $take, $sort);
            case 'hybrid':
                return $this->handleHybridStrategy($skip, $take, $sort);
            case 'post_process':
            default:
                return $this->handlePostProcessStrategy($skip, $take, $sort);
        }
    }

    /**
     * Determine the optimal pagination strategy
     */
    protected function determineOptimizationStrategy(array $sort, array $filter, string $searchText): string
    {
        if (!config('devextreme-encrypted.performance.auto_optimize', true)) {
            return 'post_process'; // Use original strategy if auto-optimize is disabled
        }

        $hasEncryptedFieldOperation = $this->hasEncryptedFieldOperations($sort, $filter, $searchText);
        $hasRelatedFieldOperation = $this->hasRelatedFieldOperations($sort, $filter);

        if (!$hasEncryptedFieldOperation && !$hasRelatedFieldOperation) {
            return 'sql_only';
        }

        if (!$hasEncryptedFieldOperation && $hasRelatedFieldOperation) {
            return 'hybrid';
        }

        return 'post_process';
    }

    /**
     * Handle requests that can be processed entirely with SQL
     */
    protected function handleSqlOnlyStrategy(int $skip, int $take, array $sort): JsonResponse
    {
        if (config('devextreme-encrypted.performance.debug_logging')) {
            Log::info("DevExtremeHandler: Using SQL-only strategy");
        }

        // Apply SQL-based sorting
        $this->applySafeMainTableSorting($sort);

        // Get total count before applying pagination
        $totalCount = $this->query->count();

        // Apply pagination at SQL level
        $results = $this->query->skip($skip)->take($take)->get();

        // Transform data if transformer is provided
        if ($this->config['dataTransformer'] && is_callable($this->config['dataTransformer'])) {
            $results = $results->map($this->config['dataTransformer']);
        }

        return response()->json([
            'data' => $results,
            'totalCount' => $totalCount,
            'strategy' => 'sql_only'
        ]);
    }

    /**
     * Handle requests with related fields but no encrypted fields
     */
    protected function handleHybridStrategy(int $skip, int $take, array $sort): JsonResponse
    {
        if (config('devextreme-encrypted.performance.debug_logging')) {
            Log::info("DevExtremeHandler: Using hybrid strategy");
        }

        // For now, fall back to post-processing
        // This can be enhanced based on specific requirements
        return $this->handlePostProcessStrategy($skip, $take, $sort);
    }

    /**
     * Handle requests that require post-processing
     */
    protected function handlePostProcessStrategy(int $skip, int $take, array $sort): JsonResponse
    {
        if (config('devextreme-encrypted.performance.debug_logging')) {
            Log::info("DevExtremeHandler: Using post-process strategy");
        }

        // Check memory warning threshold
        $recordCount = $this->query->count();
        $maxRecords = config('devextreme-encrypted.performance.max_in_memory_records', 10000);

        if ($recordCount > $maxRecords) {
            Log::warning("DevExtremeHandler: Large dataset detected", [
                'record_count' => $recordCount,
                'max_recommended' => $maxRecords
            ]);
        }

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

        return response()->json([
            'data' => $results,
            'totalCount' => $totalCount,
            'strategy' => 'post_process'
        ]);
    }

    // ... (include other methods from the original DevExtremeHandler)

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

    // Add other necessary methods from the original implementation...
    // (This is a simplified version - you'd include all methods from your original DevExtremeHandler)
}
