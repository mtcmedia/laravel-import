<?php

namespace Mtc\Import\Traits;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Collection;

/**
 * Trait PopulatesDatabase
 * This trait adds helpers for populating mapped information into the database
 *
 * @package Mtc\Import
 */
trait PopulatesDatabase
{
    /**
     * populate the information into models
     *
     * @param $data
     */
    protected function populateData($data)
    {
        $import_object = $this->setDataOnObject($this->model, $data);
        $import_object->save();

        collect($import_object->importableRelationships())
            ->each(function ($relationship, $relationship_name) use ($data, $import_object) {

                // Check if we can skip populating due to no information
                if ($this->shouldSkipPopulatingRelationship($data, $relationship, $relationship_name)) {
                    return;
                }

                $this->ensureRelationshipDataCollection($data[$relationship_name])
                    ->each(function ($collection, $relationship_data) use ($relationship, $import_object, $relationship_name) {
                        $related_object = $this->setDataOnObject($relationship['model'], $relationship_data);
                        $import_object->{$relationship_name}()->save($related_object);
                    });
            });
    }

    /**
     * Set data from mapped info on data object
     *
     * @param $model_name
     * @param $data
     * @return mixed
     */
    protected function setDataOnObject($model_name, $data)
    {
        $object = App::make($model_name);
        $object->id = $data['id'] ?? null;
        $object->importableColumns()
            ->each(function ($column_name, $column) use ($data, $object) {
                // Find the fallback value of the field
                $fallback_value = $object->getFallbackValueForField($column);

                if (isset($data[$column])) {
                    $object->{$column} = $data[$column];
                } elseif ($fallback_value !== null) {
                    $object->{$column} = $fallback_value;
                }

            });

        return $object;
    }

    /**
     * Check if the relationship is empty and can skip import
     *
     * @param $data
     * @param $relationship
     * @param $relationship_name
     * @return bool
     */
    protected function shouldSkipPopulatingRelationship($data, $relationship, $relationship_name)
    {
        // We don't have definition of importing this relationship
        if (empty($data[$relationship_name]) || empty($relationship['model'])) {
            return true;
        }

        $relationship_data_collection = $data[$relationship_name];
        return $relationship_data_collection instanceof Collection && $relationship_data_collection->isEmpty();
    }

    /**
     * Make sure the relationship data getting processed is a correct collection
     *
     * @param $data
     * @return Collection
     */
    protected function ensureRelationshipDataCollection($data)
    {
        return ($data instanceof Collection) ? $data : collect([$data]);
    }
}
