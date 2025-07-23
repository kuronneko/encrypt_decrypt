<?php

namespace Kuronneko\LaravelDevExtremeEncrypted\Components;

class DevExtremeConfig
{
    protected array $config = [];

    /**
     * Create a new configuration instance
     */
    public static function make(): self
    {
        return new static();
    }

    /**
     * Set the encrypted fields handler
     */
    public function encryptedFieldsHandler($handler): self
    {
        $this->config['encryptedFieldsHandler'] = $handler;
        return $this;
    }

    /**
     * Add a related model
     */
    public function relatedModel(string $relationName, $model): self
    {
        $this->config['relatedModels'][$relationName] = $model;
        return $this;
    }

    /**
     * Set searchable fields
     */
    public function searchableFields(array $fields): self
    {
        // Filter out protected fields
        $protectedFields = config('devextreme-encrypted.security.protected_fields', []);
        $this->config['searchableFields'] = array_diff($fields, $protectedFields);
        return $this;
    }

    /**
     * Set default sorting
     */
    public function sortBy(string $field, string $direction = 'asc'): self
    {
        $this->config['defaultSort'] = [$field => $direction];
        return $this;
    }

    /**
     * Set data transformer function
     */
    public function transform(callable $transformer): self
    {
        $this->config['dataTransformer'] = $transformer;
        return $this;
    }

    /**
     * Enable audit logging for this configuration
     */
    public function withAuditLogging(bool $enabled = true): self
    {
        $this->config['auditLogging'] = $enabled;
        return $this;
    }

    /**
     * Set custom cache duration for this configuration
     */
    public function cacheDuration(int $minutes): self
    {
        $this->config['cacheDuration'] = $minutes;
        return $this;
    }

    /**
     * Set optimization strategy
     */
    public function optimizationStrategy(string $strategy): self
    {
        $this->config['optimizationStrategy'] = $strategy;
        return $this;
    }

    /**
     * Build and return the configuration array
     */
    public function build(): array
    {
        return array_merge([
            'searchableFields' => [],
            'encryptedFieldsHandler' => null,
            'relatedModels' => [],
            'defaultSort' => ['id' => 'desc'],
            'dataTransformer' => null,
            'auditLogging' => config('devextreme-encrypted.security.audit_logging', false),
            'cacheDuration' => config('devextreme-encrypted.cache.duration', 5),
            'optimizationStrategy' => 'auto',
        ], $this->config);
    }
}
