<?php

namespace App\Utils;

class ControllerUtils
{
    /**
     * Parse query parameters fiters
     *
     * @param array $query
     * @return array
     */
    public static function getRequestFilters(array $query): array
    {
        try {
            if (!isset($query['filters'])) {
                return [];
            }

            $filters = self::getExplodedParams($query['filters']);
            $filtersArray = self::getFiltersArray($filters);

            return $filtersArray;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Parse query parameters relationships
     *
     * @param array $query
     * @return array
     */
    public static function getRequestRelationships(array $query): array
    {
        try {
            if (!isset($query['relations'])) {
                return [];
            }

            $relations = self::getExplodedParams($query['relations']);

            return $relations;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Paring query orderBy
     *
     * @param array $query
     * &orderBy=!id
     *    '!' => 'DESC',
     *    ':' => 'ASC'
     * @return array
     */
    public static function getRequestOrderBy(array $query): array
    {

        try {
            if (!isset($query['orderBy'])) {
                return [];
            }

            $orderByParams = self::getExplodedParams($query['orderBy']);

            $orderBy = self::getOrderByArray($orderByParams);

            return $orderBy;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * This method creates a multi array to resolve
     * filtering by relation column
     *
     * @param array $relationsFilters
     * @return array
     */
    public static function getRequestRelationsFilters(array $query): array
    {
        try {
            if (!isset($query['relationsFilters'])) {
                return [];
            }

            $user = \Illuminate\Support\Facades\Auth::user();
            $explodedParams = self::getExplodedParams($query['relationsFilters']);
            $relationsFilters = [];
            $relationFilterCondition = isset($query['relationFilterCondition']) ? $query['relationFilterCondition'] : 'and';
            $operators = implode('', self::getOperators());
            foreach ($explodedParams as $k => $explodedParam) {
                $relationExploded = explode('.', $explodedParam);
                unset($relationExploded[max(array_keys($relationExploded))]);

                $relation = implode('.', $relationExploded);

                $operators = self::getOperators();
                $operator = null;
                $operatorKey = null;
                foreach ($operators as $k => $v) {
                    $params = explode($k, $explodedParam);

                    if (count($params) > 1) {
                        if (strtolower($params[1]) === 'null') {
                            $operator = null;
                        }
                        $operator = $v;
                        $operatorKey = $k;
                    }
                }

                $explodedByDot = explode('.', $explodedParam);

                $explodedColumn = isset($explodedByDot[max(array_keys($explodedByDot))])
                    ? explode($operatorKey, $explodedByDot[max(array_keys($explodedByDot))])
                    : null;

                $column = isset($explodedColumn)
                    ? $explodedColumn[0]
                    : null;

                $explodedValue = explode($operatorKey, $explodedParam);
                $value = isset($explodedValue[1])
                    ? $explodedValue[1]
                    : null;


                $relationsFilters[] = [
                    'relation' => $relation,
                    'column' => $column,
                    'operator' => $operator,
                    'value' => $value,
                ];
            }

            $relationsFilters = collect($relationsFilters)
                ->groupBy('relation')
                ->toArray();

            $relationsClosure = [];
            foreach ($relationsFilters as $relation => $filtersRelation) {
                $relationsClosure[] = [
                    $relation =>
                    function ($query) use ($filtersRelation, $relationFilterCondition, $user) {
                        $conditions = implode(' ' . $relationFilterCondition . ' ', array_map(
                            function ($filter) {
                                if ($filter['operator'] === 'LIKE' || $filter['operator'] === 'NOT LIKE') {
                                    $filter['value'] = '\'' . $filter['value'] . '\'';
                                }
                                return $filter['column'] . ' ' . $filter['operator'] . ' ' . $filter['value'];
                            },
                            $filtersRelation
                        ));

                        $entityClass = $query->getRelated();
                        $methodExists = method_exists($entityClass, 'getCustomFields');

                        $selectableFields = is_null($entityClass)
                            ? ['*']
                            : $entityClass->getFillable();

                        if ($methodExists && !is_null($user)) {
                            $customFields = isset($entityClass->getCustomFields()[$user->db_name])
                                ? $entityClass->getCustomFields()[$user->db_name]
                                : [];

                            $selectableFields = array_merge(
                                $selectableFields,
                                $customFields
                            );
                        }

                        return $query->select($selectableFields)->whereRaw($conditions);
                    }
                ];
            }

            $relationships = \Illuminate\Support\Arr::collapse($relationsClosure);

            return $relationships;
        } catch (\Exception $ex) {
            throw $ex;
        }

        return [];
    }

    /**
     * @param string $params
     * @return array
     */
    public static function getExplodedParams(String $params): array
    {
        $params = str_replace('[', '', $params);
        $params = str_replace(']', '', $params);
        $params = str_replace(' ', '', $params);
        $params = explode(',', $params);

        return $params;
    }

    /**
     * @param array $filters
     * @return array
     */
    public static function getFiltersArray(array $filters): array
    {
        $operators = self::getOperators();

        $filtersArray = [];
        foreach ($filters as $filter) {
            foreach ($operators as $k => $v) {
                $params = explode($k, $filter);
                if (count($params) > 1) {
                    if (strtolower($params[1]) === 'null') {
                        $params[1] = null;
                    }
                    $column = $params[0];
                    $operator = $v;
                    if ($operator === 'LIKE' || $operator === 'NOT LIKE') {
                        $value = '\'' . $params[1] . '\'';
                    }
                    $value = $params[1];

                    $filtersArray[] = [$column, $operator, $value];
                }
            }
        }

        return $filtersArray;
    }


    /**
     * This method, creates an associative array
     * for ascending or descending sort order for the entity
     * '' => 'ASC'
     * '!' => 'DESC'
     *
     * @param array $ordersParams
     * @return array
     */
    public static function getOrderByArray(array $orders): array
    {
        $ordersArray = [];
        foreach ($orders as $filter) {

            if (strpos($filter, '!') !== false) {
                $value = str_replace("!", "", $filter);
                $operator = 'DESC';
            } else {
                $value = $filter;
                $operator = 'ASC';
            }

            $ordersArray[] = [$value, $operator];
        }

        return $ordersArray;
    }

    /**
     * @return array
     */
    private static function getOperators(): array
    {
        return [
            ':' => '=',
            '!' => '!=',
            '>' => '>',
            '<' => '<',
            '~' => 'LIKE',
            '|' => 'NOT LIKE'
        ];
    }
}