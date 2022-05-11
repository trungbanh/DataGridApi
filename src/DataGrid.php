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
            'data' => $this->collection,
            'paginate' => $this->paginate,
        ];
    }

    public function getCollection()
    {
        $queryString = $this->getQueryString(url()->full());

        if (count($queryString)) {
            $filteredOrderSortedCollection = $this->sortOrFilterCollection(
                $this->queryBuilder,
                $queryString
            );
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
}