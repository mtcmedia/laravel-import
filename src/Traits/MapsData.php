<?php

namespace Mtc\Import\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Carbon\Carbon;

/**
 * Trait MapsData
 * This trait adds functionality to map fields against the available data collection
 *
 * @package Mtc\Import
 */
trait MapsData
{
    /**
     * Map the data array against values in data set
     *
     * @param array $field_list
     * @param object $data
     * @return Collection
     */
    protected function mapData($field_list, $data, $depth = 0)
    {
        return collect($field_list)
            ->map(function ($requested_field) use ($data, $depth) {

                // If the requested field is a string we run the mapping of the fields value
                if (is_string($requested_field)) {
                    return $this->findValueInData($requested_field, $data);
                }

                // we have one entry for this array, we are expecting this to be a hasMany/belongsTo relationships
                if ($this->isLoopedArray($requested_field)) {
                    return $this->mapLoop($requested_field[0], $data);
                }

                // This is a nested data object so we map data one level deeper
                return $this->mapData($requested_field, $data, ++$depth);
            });
    }

    /**
     * Check if the requested field is for looped data array
     * Looped array is one that contains a loop operator in it.
     * Looped array identifies that there could be multiple entries of this data block
     *
     * @param $requested_field
     * @return bool
     */
    protected function isLoopedArray($requested_field)
    {
        if (is_array($requested_field) && count($requested_field) == 1) {
            return collect($requested_field[0])
                    ->filter(function ($value) {
                        return strpos($value, config('import.loop_operator')) !== false;
                    })
                    ->count() > 0;
        }
        return false;
    }

    /**
     * Here we map a looped array.
     * A looped data set is one that has loop identifier in it.
     * As a result we get a single hasMany type relationship where one entry has multiple entries
     *
     * @param  $incoming_field_list
     * @param object $data
     * @return mixed
     */
    protected function mapLoop($incoming_field_list, $data)
    {
        /** @var Collection $incoming_field_list */
        $incoming_field_list = collect($incoming_field_list);

        // We find the first key with loop in it
        $first_key_with_loop = $incoming_field_list
            ->filter(function ($value) {
                return strpos($value, config('import.loop_operator')) !== false;
            })
            ->keys()
            ->first();

        // Here we jump data ahead to position where the loop lies
        $loop_data = $this->findValueInData($incoming_field_list[$first_key_with_loop], $data);

        // We don't have any data in loop so we have an empty set and we can skip further processing
        if (!$loop_data) {
            return [];
        }

        $identifier_length = strlen(config('import.loop_operator'));

        // We also update field list to remove the names up to loop identifier so field names are after them
        $incoming_field_list = $incoming_field_list
            ->map(function ($field_name) use ($identifier_length) {
                $loop_identifier_position = strpos($field_name, config('import.loop_operator'));
                // We add + 1 here to take the field separator "." as well
                return substr($field_name, $loop_identifier_position + $identifier_length + 1);
            });

        // Now we can take the loop data and map each entry to the data we need
        return $loop_data
            ->map(function ($data_entry) use ($incoming_field_list) {
                // This is done by returning a map of required field list for each entry we have
                return $incoming_field_list
                    ->map(function ($requested_field) use ($data_entry) {
                        return $this->findValueInData($requested_field, $data_entry);
                    });

            });
    }

    /**
     * Find the value of the field in data set
     *
     * @param $requested_field
     * @param $data
     * @param int $index
     * @return int|mixed|string
     */
    protected function findValueInData($requested_field, $data)
    {
        if (strpos($requested_field, '~') === 0) {
            return $this->parseContextFunction($requested_field, $data);
        }

        // We convert field string value to tree we are traversing
        $tree = explode('.', $requested_field);
        $tree_length = count($tree);

        do {
            // We move one level deeper by shifting a variable from tree into local varable
            $field_to_access = array_shift($tree);

            // If this is a loop operator we return the whole collection of values
            if ($field_to_access === config('import.loop_operator')) {
                return $data;
            }

            if ($this->isValueFilter($field_to_access)) {
                $data = $this->filterValueCollection($field_to_access, $data);
                continue;
            }

            // If we are trying to access a specific number value of the collection (e.g. 0th index)
            if (is_numeric($field_to_access) and $data instanceof Collection) {
                if (empty($data[$field_to_access])) {
                    return null;
                }
                $data = $data[$field_to_access];
                continue;
            } elseif ($data instanceof Collection && $data->count() == 1) {
                // We have loaded a relationship that has only one child
                $data = $data->first();
            }

            if ($data && property_exists($data, $field_to_access)) {
                $data = $data->{$field_to_access};
            } elseif ($tree_length > 1) {
                // Tree was longer than 1 element and we didn't find the value
                $data = null;
            } elseif (defined($field_to_access)) {
                $data = constant($field_to_access);
            } else {
                $data = $field_to_access;
            }
        } while (!empty($tree));

        // we need to deal with some weird edge-cases like empty xml cdata object
        if (is_object($data) && !(array)$data) {
            return null;
        }

        return is_string($data) ? trim($data) : $data;
    }

    /**
     * Parse a context function
     * Since data is not always one to one between systems we have situations where data
     * need some manipulation to convert from one value to a different value.
     * There are few built-in functions like comparison, variable casting, concatenation etc
     * As well as ability to call an external method to run the required functionality.
     * All context functions use ~method(attribute1,attribute2) format
     *
     * @param string $requested_method
     * @param $data
     * @return int|mixed|string
     */
    protected function parseContextFunction($requested_method, $data)
    {
        if (strpos($requested_method, '~compare') !== false) {
            $compare_args = explode(',', trim(str_replace('~compare', '', $requested_method), '()'));
            return $this->compare($data, ...$compare_args);
        }

        if (strpos($requested_method, '~int') !== false) {
            $value_to_process = trim(str_replace('~int', '', $requested_method), '()');
            return (int)$this->findValueInData($data, $value_to_process);
        }

        if (strpos($requested_method, '~float') !== false) {
            $value_to_process = trim(str_replace('~float', '', $requested_method), '()');
            return (float)$this->findValueInData($data, $value_to_process);
        }

        if (strpos($requested_method, '~now') !== false) {
            $date_format = trim(str_replace('~now', '', $requested_method), '()');
            if (empty($date_format)) {
                $date_format = Carbon::DEFAULT_TO_STRING_FORMAT;
            }
            return Carbon::now()->format($date_format);
        }

        if (strpos($requested_method, '~concat') !== false) {
            $concatenate_values = explode(',', trim(str_replace('~concat', '', $requested_method), '()'));
            return collect($concatenate_values)
                ->map(function ($value) use ($data) {
                    return $this->findValueInData($value, $data);
                })
                ->implode('');

        }

        if (strpos($requested_method, '~external') !== false) {
            $callable_parts = explode(',', trim(str_replace('~external', '', $requested_method), '()'));

            // Callable is first 2 attributes of th external method attributes (first the class then method)
            $callable = [
                array_shift($callable_parts),
                array_shift($callable_parts),
            ];
            $callable_parts['data'] = $data;
            return App::call($callable, $callable_parts);
        }

        // did not map, return un-parsed value
        return $requested_method;
    }

    /**
     * @param $data
     * @param $field
     * @param $compare_value
     * @param string $compare_operator
     * @param bool $true_value
     * @param bool $false_value
     * @return int|mixed|string
     */
    protected function compare($data, $field, $compare_value, $compare_operator = '==', $true_value = false, $false_value = false)
    {
        $comparison_is_true = false;
        $field_value = $this->findValueInData($field, $data);

        switch ($compare_operator) {
            case '==':
                $comparison_is_true = $field_value == $compare_value;
                break;
            case '>':
                $comparison_is_true = $field_value > $compare_value;
                break;
            case '<':
                $comparison_is_true = $field_value < $compare_value;
                break;
            case '<=':
                $comparison_is_true = $field_value <= $compare_value;
                break;
            case '>=':
                $comparison_is_true = $field_value >= $compare_value;
                break;
            case '!=':
                $comparison_is_true = $field_value != $compare_value;
                break;
        }

        return $comparison_is_true ? $this->findValueInData($true_value, $data) : $this->findValueInData($false_value, $data);
    }

    /**
     * Check if the requested field is value filter
     * i.e. we are trying to filter a collection down by a value in filter
     *
     * @param string $requested_field
     * @return bool
     */
    protected function isValueFilter($requested_field)
    {
        return strpos($requested_field, '(') === 0
            && substr($requested_field, -1) === ')';
    }

    /**
     * Filter value collection
     * Finds the entries that match the provided filter
     * Filter format is (attribute,value)
     *
     * @param string $filter
     * @param Collection $data
     * @return mixed
     */
    protected function filterValueCollection($filter, $data)
    {
        if (!$data instanceof Collection) {
            $data = collect($data);
        }

        list($filter_column, $filter_value) = explode(',', trim($filter, '()'));
        return $data->where($filter_column, $filter_value);
    }
}
