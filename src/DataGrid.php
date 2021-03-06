<?php

namespace BanhTrung\DataGridApi;

abstract class DataGrid
{
    /**
     *
     * @var string
     */
    protected $index;

    /**
     *
     * @var array
     */
    protected $collection = [];


    /**
     *
     * @var string
     */
    protected $sortOrder = 'ASC';


    /**
     *
     * @var array
     */
    protected $columns = [];


    /**
     *
     * @var object
     */
    protected $queryBuilder;


    /**
     *
     * @var bool
     */
    protected $paginate = true;


    /**
     *
     * @var int
     */
    protected $itemsPerPage = 10;


    protected $enabledFilterMap = false;


    protected $operators = [
        'eq'       => '=',
        'lt'       => '<',
        'gt'       => '>',
        'lte'      => '<=',
        'gte'      => '>=',
        'neqs'     => '<>',
        'neqn'     => '!=',
        'eqo'      => '<=>',
        'like'     => 'like',
        'blike'    => 'like binary',
        'nlike'    => 'not like',
        'ilike'    => 'ilike',
        'and'      => '&',
        'bor'      => '|',
        'regex'    => 'regexp',
        'notregex' => 'not regexp',
    ];

    /**
     *
     * @param string $alias
     * @param string $column
     * @return void
     */
    public function addFilter($alias, $column)
    {
        $this->filterMap[$alias] = $column;
        $this->enabledFilterMap = true;
    }


    /**
     * Complete column details.
     *
     * @var array
     */
    protected $completedColumnsDetails = [];

    public abstract function addColumns();

    public abstract function prepareQueryBuilder();

    public function addColumn($column)
    {
        $this->columns[] = $column;

        $this->setCompleteColumnDetails($column);
    }

    public function setCompleteColumnDetails($column)
    {
        $this->completedColumnsDetails[] = $column;
    }

    public function setQueryBuilder($queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    public function toJson()
    {
        $this->addColumns();
        $this->prepareQueryBuilder();
        $this->getCollection();

        return response()->json($this->prepareData());
    }

    public function prepareData()
    {
        return [
            'index' => $this->index,
            'columns' => $this->completedColumnsDetails,
            'data' => $this->collection,
            'paginate' => $this->paginate,
        ];
    }

    public function getCollection()
    {
        $queryString = $this->getQueryString(url()->full());

        if (count($queryString)) {
            $filteredOrSortedCollection = $this->sortOrFilterCollection(
                $this->queryBuilder,
                $queryString
            );

            return $this->collection = $this->generateResults($filteredOrSortedCollection);
        }

        return $this->collection = $this->generateResults($this->queryBuilder);
    }

    public function getQueryString($fullUrl)
    {
        $queryString = explode('?', $fullUrl)[1] ?? null;

        $parsedQueryStrings = $this->parseQueryStrings($queryString);
        $this->itemsPerPage = isset($parsedQueryStrings['perPage']) ? $parsedQueryStrings['perPage']['eq'] : $this->itemsPerPage;
        unset($parsedQueryStrings['perPage']);


        return $this->updateQueryStrings($parsedQueryStrings);
    }


    public function parseQueryStrings($queryString)
    {
        $parsedQueryStrings = [];
        if ($queryString) {
            parse_str(urldecode($queryString), $parsedQueryStrings);
            unset($parsedQueryStrings['page']);
        }

        return $parsedQueryStrings;
    }


    public function updateQueryStrings($parsedQueryStrings)
    {
        if (isset($parsedQueryStrings['grand_total'])) {
            foreach ($parsedQueryStrings['grand_total'] as $key => $value) {
                $parsedQueryStrings['grand_total'][$key] = str_replace(',', '.', $parsedQueryStrings['grand_total'][$key]);
            }
        }

        foreach ($parsedQueryStrings as $key => $value) {
            if (in_array($key, ['locale'])) {
                if (!is_array($value)) {
                    unset($parsedQueryStrings[$key]);
                }
            } else if (!is_array($value)) {
                unset($parsedQueryStrings[$key]);
            }
        }

        return $parsedQueryStrings;
    }

    public function sortOrFilterCollection($collection, $parseInfo)
    {
        foreach ($parseInfo as $key => $info) {
            $columnType = $this->findColumnType($key)[0] ?? null;
            $columnName = $this->findColumnType($key)[1] ?? null;

            if ($this->exceptionCheckInColumns($columnName)) {
                return $collection;
            }

            match ($key) {
                'sort'   => $this->sortCollection($collection, $info),
                'search' => $this->searchCollection($collection, $info),
                default  => $this->filterCollection($collection, $info, $columnType, $columnName)
            };
        }

        return $collection;
    }


    /**
     * Generate paginated results.
     *
     * @param  object  $queryBuilderOrCollection
     * @return \Illuminate\Support\Collection
     */
    private function paginatedResults($queryBuilderOrCollection)
    {
        return $queryBuilderOrCollection->orderBy(
            $this->index,
            $this->sortOrder
        )->paginate($this->itemsPerPage)->appends(request()->except('page'));
    }

    /**
     * Generate default results.
     *
     * @param  object  $queryBuilderOrCollection
     * @return \Illuminate\Support\Collection
     */
    private function defaultResults($queryBuilderOrCollection)
    {
        return $queryBuilderOrCollection->orderBy($this->index, $this->sortOrder)->get();
    }

    private function generateResults($queryBuilderOrCollection)
    {
        if ($this->paginate) {
            if ($this->itemsPerPage > 0) {
                return $this->paginatedResults($queryBuilderOrCollection);
            }
        } else {
            return $this->defaultResults($queryBuilderOrCollection);
        }
    }

    public function findColumnType($columnAlias)
    {
        foreach ($this->completedColumnsDetails as $column) {
            if ($column['index'] == $columnAlias) {
                return [$column['type'], $column['index']];
            }
        }
    }


    public function exceptionCheckInColumns($columnName)
    {
        foreach ($this->completedColumnsDetails as $column) {
            if ($column['index'] === $columnName && !$column['filterable']) {
                return true;
            }
        }
        return false;
    }


    /**
     * Resolve query.
     *
     * @param  object  $query
     * @param  string  $columnName
     * @param  string  $condition
     * @param  string  $filterValue
     * @param  null|boolean  $nullCheck
     * @return void
     */
    private function resolveQuery($query, $columnName, $condition, $filterValue, $clause = 'where')
    {
        $query->$clause(
            $columnName,
            $this->operators[$condition],
            $filterValue
        );
    }


    /**
     * Main resolve method.
     *
     * @param  \Illuminate\Support\Collection  $collection
     * @param  string  $columnName
     * @param  string  $condition
     * @param  string  $filterValue
     * @param  string  $clause
     * @param  string  $method
     * @return void
     */
    private function resolve($collection, $columnName, $condition, $filterValue, $clause = 'where', $method = 'resolveQuery')
    {
        if ($this->enabledFilterMap && isset($this->filterMap[$columnName])) {
            $this->$method($collection, $this->filterMap[$columnName], $condition, $filterValue, $clause);
        } else if ($this->enabledFilterMap && !isset($this->filterMap[$columnName])) {
            $this->$method($collection, $columnName, $condition, $filterValue, $clause);
        } else {
            $this->$method($collection, $columnName, $condition, $filterValue, $clause);
        }
    }


    /**
     * Sort collection.
     *
     * @param  \Illuminate\Support\Collection  $collection
     * @param  array  $info
     * @return void
     */
    private function sortCollection($collection, $info)
    {
        $availableOptions = ['asc', 'desc'];
        $selectedSortOption = strtolower(array_values($info)[0]);
        $countKeys = count(array_keys($info));
        if ($countKeys > 1) {
            throw new \Exception(__('not support for multi-sort'));
        }

        $columnName = $this->findColumnType(array_keys($info)[0]);
        $collection->orderBy(
            $columnName[1],
            in_array($selectedSortOption, $availableOptions) ? $selectedSortOption : 'asc'
        );
    }


    /**
     * Search collection.
     *
     * @param  \Illuminate\Support\Collection  $collection
     * @param  array  $info
     * @return void
     */
    public function searchCollection($collection, $info)
    {
        $countKeys = count(array_keys($info));

        if ($countKeys > 1) {
            throw new \Exception("not support for multi-search", 1);
        }

        if ($countKeys == 1) {
            $collection->where(function ($collection) use ($info) {
                foreach ($this->completedColumnsDetails as $column) {
                    if ($column['searchable'] == true) {
                        $this->resolve($collection, $column['index'], 'like', '%' . $info['all'] . '%', 'orWhere');
                    }
                }
            });
        }
    }


    /**
     * Filter collection.
     *
     * @param  \Illuminate\Support\Collection  $collection
     * @param  array  $info
     * @param  string  $columnType
     * @param  string  $columnName
     * @return void
     */
    private function filterCollection($collection, $info, $columnType, $columnName)
    {
        if (array_keys($info)[0] === 'like' || array_keys($info)[0] === 'nlike') {
            foreach ($info as $condition => $filterValue) {
                $this->resolve($collection, $columnName, $condition, '%' . $filterValue . '%');
            }
        } else {
            foreach ($info as $condition => $filterValue) {

                $condition = ($condition === 'undefined') ? '=' : $condition;

                match ($columnType) {
                    'boolean'  => $this->resolve($collection, $columnName, $condition, $filterValue, 'where', 'resolveBooleanQuery'),
                    'checkbox' => $this->resolve($collection, $columnName, $condition, $filterValue, 'whereIn', 'resolveCheckboxQuery'),
                    'price'    => $this->resolve($collection, $columnName, $condition, $filterValue, 'having'),
                    'datetime' => $this->resolve($collection, $columnName, $condition, $filterValue, 'whereDate'),
                    default    => $this->resolve($collection, $columnName, $condition, $filterValue)
                };
            }
        }
    }
}