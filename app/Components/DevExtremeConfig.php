<?php

namespace App\Components;

use Illuminate\Database\Eloquent\Model;

class DevExtremeConfig
{
    protected array $config = [
        'searchableFields' => [],
        'encryptedFieldsHandler' => null,
        'relatedModels' => [],
        'defaultSort' => ['id' => 'desc'],
        'dataTransformer' => null,
    ];

    /**
     * Set searchable fields for global search
     */
    public function searchableFields(array $fields): self
    {
        $this->config['searchableFields'] = $fields;
        return $this;
    }

    /**
     * Set the main model for encrypted field handling
     */
    public function encryptedFieldsHandler(Model $model): self
    {
        $this->config['encryptedFieldsHandler'] = $model;
        return $this;
    }

    /**
     * Add a related model for handling encrypted fields
     */
    public function relatedModel(string $relationName, Model $model): self
    {
        $this->config['relatedModels'][$relationName] = $model;
        return $this;
    }

    /**
     * Set multiple related models at once
     */
    public function relatedModels(array $models): self
    {
        $this->config['relatedModels'] = array_merge($this->config['relatedModels'], $models);
        return $this;
    }

    /**
     * Set default sorting
     */
    public function defaultSort(array $sort): self
    {
        $this->config['defaultSort'] = $sort;
        return $this;
    }

    /**
     * Set default sorting by field and direction
     */
    public function sortBy(string $field, string $direction = 'asc'): self
    {
        $this->config['defaultSort'] = [$field => $direction];
        return $this;
    }

    /**
     * Set data transformer callback
     */
    public function transform(callable $transformer): self
    {
        $this->config['dataTransformer'] = $transformer;
        return $this;
    }

    /**
     * Build and return the configuration array
     */
    public function build(): array
    {
        return $this->config;
    }

    /**
     * Static factory method
     */
    public static function make(): self
    {
        return new static();
    }

    /**
     * Quick setup for User-Location relationship (common use case)
     */
/*     public static function userLocationSetup(): self
    {
        return static::make()
            ->encryptedFieldsHandler(new \App\Models\User())
            ->relatedModel('locations', new \App\Models\Location())
            ->searchableFields([
                'id', 'name', 'email', 'city',
                'locations.postal_code', 'locations.address'
            ])
            ->sortBy('id', 'desc');
    } */

    /**
     * Quick setup for Location-User relationship (reverse relationship)
     */
/*     public static function locationUserSetup(): self
    {
        return static::make()
            ->encryptedFieldsHandler(new \App\Models\Location())
            ->relatedModel('user', new \App\Models\User())
            ->searchableFields([
                'id', 'name', 'city', 'state', 'country',
                'address', 'postal_code', 'user.name', 'user.email'
            ])
            ->sortBy('id', 'desc');
    } */

    /**
     * Simple setup for single model without relationships
     */
    public static function simpleSetup(string $modelClass, array $searchableFields = []): self
    {
        return static::make()
            ->encryptedFieldsHandler(new $modelClass())
            ->searchableFields($searchableFields)
            ->sortBy('id', 'desc');
    }
}
