<?php

namespace Mtc\Import\Traits;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Trait Importable
 * Add this Trait to model you want to make importable
 *
 * @package Mtc\Import\Traits
 */
trait Importable
{
    /**
     *  Get the list of importable columns
     *
     * @return mixed
     */
    public function importableColumns($prefix = '')
    {
        if (!empty($this->importable)) {
            return $this->formatImportFields($this->importable, $prefix);
        }

        if (!empty($this->fillable)) {
            return $this->formatImportFields($this->fillable, $prefix);
        }

        $columns = $this->getConnection()
            ->getSchemaBuilder()
            ->getColumnListing($this->getTable());

        return $this->formatImportFields(collect($columns)->reject($this->primaryKey), $prefix);
    }

    /**
     * Get the list of importable relationships
     *
     * @return mixed
     */
    public function importableRelationships()
    {
        if (!empty($this->importable_relationships)) {
            return $this->importable_relationships;
        }

        return [];
    }

    /**
     * Convert fields to user friendly format
     *
     * @param $fields
     * @param string $prefix
     * @return Collection
     */
    protected function formatImportFields($fields, $prefix = '')
    {
        if (!$fields instanceof Collection) {
            $fields = collect($fields);
        }
        return $fields
            ->keyBy(function ($field_name) use ($prefix) {
                return !empty($prefix) ? $this->getTable() . '.' . $field_name : $field_name;
            })
            ->map(function ($field_name) use ($prefix) {
                return $prefix . ' ' . title_case(str_replace('_', ' ', $field_name));
            });
    }

    /**
     * Get the fallback value set on model
     *
     * @param $field
     * @return null
     */
    public function getFallbackValueForField($field)
    {
        if (isset($this->importable_defaults[$field])) {
            return $this->importable_defaults[$field] === 'NOW' ? Carbon::now() : $this->importable_defaults[$field];
        }

        return null;
    }

}
