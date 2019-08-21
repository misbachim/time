<?php

namespace App\Business\Helper;

use Illuminate\Support\Facades\DB;

/**
 * @property array searchData
 * @property array fieldMap
 * @property array conjunctionMap
 * @property array specialConjunctions
 * @property string tableName
 * @property  builder
 * @property array selectedFields
 * @property array whereClauses
 */
class SearchQueryBuilder
{
    public function __construct(array $searchData, array $fieldMap)
    {
        $this->searchData = $searchData;
        $this->fieldMap = $fieldMap;
        $this->conjunctionMap = [
            'c' => 'like',
            'nc' => 'not like',
            '=' => '=',
            '!=' => '!=',
            '<' => '<',
            '>' => '>',
            '<=' => '<=',
            '>=' => '>=',
            'b' => 'Between',
            'nb' => 'NotBetween',
            'i' => 'In',
            'ni' => 'NotIn'
        ];
        $this->specialConjunctions = ['b', 'nb', 'i', 'ni'];

        $this->tableName = '';
        $this->builder = DB::table($this->tableName);

        $this->customFields = [];
        $this->selectedFields = [];
        $this->whereClauses = [];
        $this->rawWhereClauses = [];

        $this->shouldDistinctOn = false;
        $this->distinctOnColumn = null;

        $this->shouldSort = false;
        $this->sortField = null;
        $this->sortOrder = 'asc';
    }

    public function table($tableName)
    {
        $this->tableName = $tableName;
        $this->builder = DB::table($this->tableName);
        info("[SQB] DB::table($this->tableName)");

        // The order is VERY important.
        $this->initSelectedFields();
        $this->initWhereClauses();
        $this->initHavingClauses();

        return $this;
    }

    public function select(...$fields)
    {
        $this->customFields = $fields;

        $fields = array_unique(array_merge($fields, $this->selectedFields));
        $selectStr = implode(',', $fields);

        if ($this->shouldDistinctOn) {
            $this->builder = $this->builder->selectRaw("distinct on ($this->distinctOnColumn) $selectStr");
        } else {
            $this->builder = $this->builder->selectRaw($selectStr);
        }
        info("[SQB] select $selectStr");
        return $this;
    }

    public function distinct()
    {
        $this->builder = $this->builder->distinct();
        info("[SQB] distinct");
        return $this;
    }

    public function distinctOn(...$columns)
    {
        $raw = '';
        foreach ($columns as $column) {
            if ($raw === '') {
                $raw = $column;
            } else {
                $raw .= ', '.$column;
            }
        }
        $this->shouldDistinctOn = true;
        $this->distinctOnColumn = $raw;
        info("[SQB] distinct on", $columns);
        return $this;
    }

    public function join(...$joinClauses)
    {
        $this->builder = $this->builder->join(...$joinClauses);
        info('[SQB] join', $joinClauses);
        return $this;
    }

    public function leftJoin(...$leftJoinClauses)
    {
        $this->builder = $this->builder->leftJoin(...$leftJoinClauses);
        info('[SQB] leftJoin', $leftJoinClauses);
        return $this;
    }

    public function combineWhere(...$whereClauses)
    {
        $this->builder = $this->builder->where(...$whereClauses)->where($this->whereClauses);
        foreach ($this->rawWhereClauses as $rawWhereClause) {
            $this->builder = $this->builder->whereRaw($rawWhereClause['clause'], $rawWhereClause['bindings']);
        }
        info('[SQB] where', array_merge($whereClauses, $this->whereClauses, $this->rawWhereClauses));
        return $this->specialWhere();
    }

    public function where(...$whereClauses)
    {
        $this->builder = $this->builder->where(...$whereClauses);
        info('[SQB] where', $whereClauses);
        return $this;
    }

    public function orderBy($sortField, $sortOrder)
    {
        if (in_array($sortField, $this->searchData['selectedFields']) &&
            ($sortOrder === 'asc' || $sortOrder === 'desc')) {

            $this->shouldSort = true;
            $this->sortField = $this->fieldMap[$sortField]['name'];
            $this->sortOrder = $sortOrder;

            info("[SQB] orderBy $this->sortField $this->sortOrder");
        }
        return $this;
    }

    public function limit($limit)
    {
        $this->builder = $this->builder->limit($limit);
        info("[SQB] limit $limit");
        return $this;
    }

    public function offset($offset)
    {
        $this->builder = $this->builder->offset($offset);
        info("[SQB] offset $offset");
        return $this;
    }

    public function get()
    {
        info('[SQB] get');
        if ($this->shouldSort) {
            if ($this->sortOrder === 'desc') {
                return $this->builder->get()->sortByDesc($this->sortField)->values();
            } else {
                return $this->builder->get()->sortBy($this->sortField)->values();
            }
        }
        return $this->builder->get();
    }

    public function hist(...$columns)
    {
        $raw = '';
        foreach ($columns as $column) {
            if ($raw === '') {
                $raw = $column;
            } else {
                $raw .= ', '.$column;
            }
        }

        info('[SQB] hist get');
        $result = $this->builder->orderByRaw($raw.' desc')->get();

        if ($this->shouldSort) {
            if ($this->sortOrder === 'desc') {
                return $result->sortByDesc($this->sortField)->values();
            } else {
                return $result->sortBy($this->sortField)->values();
            }
        }
        return $result;
    }

    public function count($column = null)
    {
        if ($this->shouldDistinctOn) {
            return $this->builder->count(DB::raw("distinct $this->distinctOnColumn"));
        }

        info('[SQB] count');
        if ($column) {
            return $this->builder->count(DB::raw($column));
        } else {
            return $this->builder->count();
        }
    }

    private function initSelectedFields()
    {
        foreach ($this->searchData['selectedFields'] as $fieldCode) {
            $field = $this->fieldMap[$fieldCode];
            $column = $this->locate($field);
            array_push($this->selectedFields, "$column as \"${field['name']}\"");
        }
    }

    private function initWhereClauses()
    {
        foreach ($this->searchData['criteria'] as $criterion) {
            if (in_array($criterion['conj'], $this->specialConjunctions)) {
                continue;
            }

            $field = $this->fieldMap[$criterion['field']];
            if (array_key_exists('having', $field) && $field['having']) {
                continue;
            }

            $column = $this->locate($field);
            $conj = $this->conjunctionMap[$criterion['conj']];
            $val = ($criterion['conj'] === 'c' || $criterion['conj'] === 'nc') ?
                '%'.$criterion['val'].'%' : $criterion['val'];

            if (array_key_exists('rawWhere', $field) && $field['rawWhere']) {
                $condition = $conj.' ?';

                if (array_key_exists('whereClause', $field) && $field['whereClause']) {
                    $clause = $this->locate($field, 'whereClause');
                    $bindings = [];
                } else {
                    if (is_string($val)) {
                        $clause = "LOWER($column) $condition";
                        $val = strtolower($val);
                    } else {
                        $clause = "$column $condition";
                    }
                    $bindings = [$val];
                }

                array_push(
                    $this->rawWhereClauses,
                    [
                        'clause' => $clause,
                        'bindings' => $bindings
                    ]
                );
            } else {
                array_push($this->whereClauses, [$column, $conj, $val]);
            }
        }
    }

    private function specialWhere()
    {
        foreach ($this->specialConjunctions as $sconj) {
            foreach ($this->searchData['criteria'] as $criterion) {
                if ($criterion['conj'] !== $sconj) {
                    continue;
                }

                $field = $this->fieldMap[$criterion['field']];
                $vals = is_array($criterion['val']) ? $criterion['val'] : [$criterion['val']];
                $where = 'where'.$this->conjunctionMap[$sconj];

                info("[SQB] $where (${field['column']})", $vals);
                $this->builder = $this->builder->where(
                    function ($query) use ($where, $field, $vals) {
                        $query->$where(DB::raw($this->locate($field)), $vals);
                    }
                );
            }
        }

        return $this;
    }

    private function initHavingClauses() {
        $grouped = false;

        foreach ($this->searchData['criteria'] as $criterion) {
            $field = $this->fieldMap[$criterion['field']];

            if (array_key_exists('having', $field) && $field['having']) {
                if (in_array($criterion['conj'], $this->specialConjunctions)) {
                    continue;
                }

                if (! $grouped) {
                    $columns = '';
                    foreach ($this->customFields as $customField) {
                        if ($columns === '') {
                            $columns = $customField;
                        } else {
                            $columns .= ", $customField";
                        }
                    }
                    foreach ($this->searchData['selectedFields'] as $fieldCode) {
                        $column = $this->locate($this->fieldMap[$fieldCode]);
                        if ($columns === '') {
                            $columns = $column;
                        } else {
                            $columns .= ", $column";
                        }
                    }
                    $this->builder = $this->builder->groupBy(DB::raw($columns));
                    $grouped = true;
                }

                $this->builder = $this->builder->havingRaw(
                    $this->locate($field).' '.$this->conjunctionMap[$criterion['conj']].' ?',
                    [$criterion['val']]
                );
            }
        }
    }

    private function locate($field, $key = 'column')
    {
        if (strpos($field[$key], '!!') !== false) {
            return str_replace('!!', $field['table'], $field[$key]);
        } else {
            return $field[$key];
        }
    }
}
