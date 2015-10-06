<?php

namespace Waldo\DatatableBundle\Util;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\JsonResponse;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;

use Waldo\DatatableBundle\Util\Factory\Query\QueryInterface;
use Waldo\DatatableBundle\Util\Factory\Query\DoctrineBuilder;
use Waldo\DatatableBundle\Util\Formatter\Renderer;

class Datatable
{

    /**
     * @var Renderer
     */
    protected $rendererEngine;

    /**
     * @var array
     */
    protected $fixedData;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var array
     */
    protected $multiple;

    /**
     * @var \Waldo\DatatableBundle\Util\Factory\Query\QueryInterface
     */
    protected $queryBuilder;

    /**
     * @var RequestStack
     */
    protected $request;

    /**
     * @var closure
     */
    protected $renderer;

    /**
     * @var array
     */
    protected $renderers;

    /**
     * @var Renderer
     */
    protected $rendererObj;

    /**
     * @var boolean
     */
    protected $search;

    /**
     * @var boolean
     */
    protected $globalSearch;

    /**
     * @var array
     */
    protected $searchFields = array();

    /**
     * @var array
     */
    protected $notFilterableFields = array();

    /**
     * @var array
     */
    protected $notSortableFields = array();

    /**
     * @var array
     */
    protected $hiddenFields = array();

    /**
     * @var array
     */
    protected static $instances = array();

    /**
     * @var Datatable
     */
    protected static $currentInstance;

    /**
     * class constructor
     *
     *
     * @param EntityManager $entityManager
     * @param RequestStack $request
     * @param DoctrineBuilder $doctrineBuilder
     * @param Renderer $renderer
     * @param array $config
     */
    public function __construct(
            EntityManager $entityManager,
            RequestStack $request,
            DoctrineBuilder $doctrineBuilder,
            Renderer $renderer, $config)
    {
        $this->em = $entityManager;
        $this->request = $request;
        $this->queryBuilder = $doctrineBuilder;
        $this->rendererEngine = $renderer;
        $this->config = $config;

        self::$currentInstance = $this;

        $this->applyDefaults();
    }

    /**
     * apply default value from datatable config
     *
     * @return void
     */
    protected function applyDefaults()
    {
        if (isset($this->config['all'])) {
            $this->search = $this->config['all']['search'];
        }
    }

    /**
     * add join
     *
     * @example:
     *      ->setJoin(
     *              'r.event',
     *              'e',
     *              \Doctrine\ORM\Query\Expr\Join::INNER_JOIN,
     *              \Doctrine\ORM\Query\Expr\Join::WITH,
     *              'e.name like %test%')
     *
     * @param string      $join          The relationship to join.
     * @param string      $alias         The alias of the join.
     * @param string|Join::INNER_JOIN    $type      The type of the join Join::INNER_JOIN | Join::LEFT_JOIN
     * @param string|null $conditionType The condition type constant. Either ON or WITH.
     * @param string|null $condition     The condition for the join.
     *
     * @return Datatable
     */
    public function addJoin($join, $alias, $type = Join::INNER_JOIN, $conditionType = null, $condition = null)
    {
        $this->queryBuilder->addJoin($join, $alias, $type, $conditionType, $condition);
        return $this;
    }

    /**
     * execute
     *
     * @param int $hydrationMode
     *
     * @return JsonResponse
     */
    public function execute()
    {
        $request = $this->request->getCurrentRequest();

        $iTotalRecords = $this->queryBuilder->getTotalRecords();
        $iTotalDisplayRecords = $this->queryBuilder->getTotalDisplayRecords();

        list($data, $objects) = $this->queryBuilder->getData();

        $id_index = array_search('_identifier_', array_keys($this->getFields()));
        $ids = array();

        array_walk($data, function($val, $key) use ($id_index, &$ids) {
            $ids[$key] = $val[$id_index];
        });

        if (!is_null($this->fixedData)) {
            $this->fixedData = array_reverse($this->fixedData);
            foreach ($this->fixedData as $item) {
                array_unshift($data, $item);
            }
        }

        if (!is_null($this->renderer)) {
            array_walk($data, $this->renderer);
        }

        if (!is_null($this->rendererObj)) {
            $this->rendererObj->applyTo($data, $objects);
        }

        if (!empty($this->multiple)) {
            array_walk($data,
                    function($val, $key) use(&$data, $ids) {
                array_unshift($val, sprintf('<input type="checkbox" name="dataTables[actions][]" value="%s" />', $ids[$key]));
                $data[$key] = $val;
            });
        }

        $output = array(
            "draw" => $request->query->getInt('draw'),
            "recordsTotal" => $iTotalRecords,
            "recordsFiltered" => $iTotalDisplayRecords,
            "data" => $data
        );

        return new JsonResponse($output);
    }

    /**
     * get datatable instance by id
     *  return current instance if null
     *
     * @param string $id
     *
     * @return Datatable .
     */
    public static function getInstance($id)
    {
        $instance = null;

        if (array_key_exists($id, self::$instances)) {
            $instance = self::$instances[$id];
        } else {
            $instance = self::$currentInstance;
        }

        if ($instance === null) {
            throw new \Exception('No instance found for datatable, you should set a datatable id in your action with "setDatatableId" using the id from your view');
        }

        return $instance;
    }

    public static function clearInstance()
    {
        self::$instances = array();
    }

    /**
     * get entity name
     *
     * @return string
     */
    public function getEntityName()
    {
        return $this->queryBuilder->getEntityName();
    }

    /**
     * get entity alias
     *
     * @return string
     */
    public function getEntityAlias()
    {
        return $this->queryBuilder->getEntityAlias();
    }

    /**
     * get fields
     *
     * @return array
     */
    public function getFields()
    {
        return $this->queryBuilder->getFields();
    }

    /**
     * get order field
     *
     * @return string
     */
    public function getOrderField()
    {
        return $this->queryBuilder->getOrderField();
    }

    /**
     * get order type
     *
     * @return string
     */
    public function getOrderType()
    {
        return $this->queryBuilder->getOrderType();
    }

    /**
     * get query builder
     *
     * @return QueryInterface
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * get search
     *
     * @return boolean
     */
    public function getSearch()
    {
        return $this->search;
    }

    /**
     * get global_search
     *
     * @return boolean
     */
    public function getGlobalSearch()
    {
        return $this->globalSearch;
    }

    /**
     * set entity
     *
     * @param type $entityName
     * @param type $entityAlias
     *
     * @return Datatable
     */
    public function setEntity($entityName, $entityAlias)
    {
        $this->queryBuilder->setEntity($entityName, $entityAlias);
        return $this;
    }

    /**
     * set fields
     *
     * @param array $fields
     *
     * @return Datatable
     */
    public function setFields(array $fields)
    {
        $this->queryBuilder->setFields($fields);
        return $this;
    }

    /**
     * set order
     *
     * @param type $orderField
     * @param type $orderType
     *
     * @return Datatable
     */
    public function setOrder($orderField, $orderType)
    {
        $this->queryBuilder->setOrder($orderField, $orderType);
        return $this;
    }

    /**
     * set fixed data
     *
     * @param type $data
     *
     * @return Datatable
     */
    public function setFixedData($data)
    {
        $this->fixedData = $data;
        return $this;
    }

    /**
     * set query builder
     *
     * @param QueryInterface $queryBuilder
     */
    public function setQueryBuilder(QueryInterface $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * set a php closure as renderer
     *
     * @example:
     *
     *  $controller_instance = $this;
     *  $datatable = $this->get('datatable')
     *       ->setEntity("BaseBundle:Entity", "e")
     *       ->setFields($fields)
     *       ->setOrder("e.created", "desc")
     *       ->setRenderer(
     *               function(&$data) use ($controller_instance)
     *               {
     *                   foreach ($data as $key => $value)
     *                   {
     *                       if ($key == 1)
     *                       {
     *                           $data[$key] = $controller_instance
     *                               ->get('templating')
     *                               ->render('BaseBundle:Entity:_decorator.html.twig',
     *                                       array(
     *                                           'data' => $value
     *                                       )
     *                               );
     *                       }
     *                   }
     *               }
     *         )
     *
     * @param \Closure $renderer
     *
     * @return Datatable
     */
    public function setRenderer(\Closure $renderer)
    {
        $this->renderer = $renderer;
        return $this;
    }

    /**
     * set renderers as twig views
     *
     * @example: To override the actions column
     *
     *      ->setFields(
     *          array(
     *             "field label 1" => 'x.field1',
     *             "field label 2" => 'x.field2',
     *             "_identifier_"  => 'x.id'
     *          )
     *      )
     *      ->setRenderers(
     *          array(
     *             2 => array(
     *               'view' => 'WaldoDatatableBundle:Renderers:_actions.html.twig',
     *               'params' => array(
     *                  'edit_route'    => 'matche_edit',
     *                  'delete_route'  => 'matche_delete',
     *                  'delete_form_prototype'   => $datatable->getPrototype('delete_form')
     *               ),
     *             ),
     *          )
     *       )
     *
     * @param array $renderers
     *
     * @return Datatable
     */
    public function setRenderers(array $renderers)
    {
        $this->renderers = $renderers;

        if (!empty($this->renderers)) {
            $this->rendererObj = $this->rendererEngine->build($this->renderers, $this->getFields());
        }

        return $this;
    }

    /**
     * set query where
     *
     * @param string $where
     * @param array  $params
     *
     * @return Datatable
     */
    public function setWhere($where, array $params = array())
    {
        $this->queryBuilder->setWhere($where, $params);
        return $this;
    }

    /**
     * set query group
     *
     * @param string $groupby
     *
     * @return Datatable
     */
    public function setGroupBy($groupby)
    {
        $this->queryBuilder->setGroupBy($groupby);
        return $this;
    }

    /**
     * set search
     *
     * @param bool $search
     *
     * @return Datatable
     */
    public function setSearch($search)
    {
        $this->search = $search;
        $this->queryBuilder->setSearch($search || $this->globalSearch);
        return $this;
    }

    /**
     * set global search
     *
     * @param bool $globalSearch
     *
     * @return Datatable
     */
    public function setGlobalSearch($globalSearch)
    {
        $this->globalSearch = $globalSearch;
        $this->queryBuilder->setSearch($globalSearch || $this->search);
        return $this;
    }

    /**
     * set datatable identifier
     *
     * @param string $id
     *
     * @return Datatable
     */
    public function setDatatableId($id)
    {
        if (!array_key_exists($id, self::$instances)) {
            self::$instances[$id] = $this;
        } else {
            throw new \Exception('Identifer already exists');
        }

        return $this;
    }

    /**
     * hasInstanceId
     *
     * @param strin $id
     */
    public function hasInstanceId($id)
    {
        return array_key_exists($id, self::$instances);
    }

    /**
     * get multiple
     *
     * @return array
     */
    public function getMultiple()
    {
        return $this->multiple;
    }

    /**
     * set multiple
     *
     * @example
     *
     *  ->setMultiple('delete' => array ('title' => "Delete", 'route' => 'route_to_delete'));
     *
     * @param array $multiple
     *
     * @return \Waldo\DatatableBundle\Util\Datatable
     */
    public function setMultiple(array $multiple)
    {
        $this->multiple = $multiple;

        if(count($this->multiple) > 0) {
            if(!in_array(0, $this->notFilterableFields)) {
                $this->notFilterableFields[] = 0;
            }
            if(!in_array(0, $this->notSortableFields)) {
                $this->notSortableFields[] = 0;
            }
        }

        return $this;
    }

    /**
     * get global configuration (read it from config.yml under datatable)
     *
     * @return array
     */
    public function getConfiguration()
    {
        return $this->config;
    }

    /**
     * get search field
     *
     * @return array
     */
    public function getSearchFields()
    {
        return $this->searchFields;
    }

    /**
     * set search fields
     *
     * @example
     *
     *      ->setSearchFields(array(0,2,5))
     *
     * @param array $searchFields
     *
     * @return \Waldo\DatatableBundle\Util\Datatable
     */
    public function setSearchFields(array $searchFields)
    {
        $this->searchFields = $searchFields;
        return $this;
    }

    /**
     * set not filterable fields
     *
     * @example
     *
     *      ->setNotFilterableFields(array(0,2,5))
     *
     * @param array $notFilterableFields
     *
     * @return \Waldo\DatatableBundle\Util\Datatable
     */
    public function setNotFilterableFields(array $notFilterableFields)
    {
        $this->notFilterableFields = $notFilterableFields;
        return $this;
    }

    /**
     * get not filterable field
     *
     * @return array
     */
    public function getNotFilterableFields()
    {
        return $this->notFilterableFields;
    }

    /**
     * set not sortable fields
     *
     * @example
     *
     *      ->setNotSortableFields(array(0,2,5))
     *
     * @param array $notSortableFields
     *
     * @return \Waldo\DatatableBundle\Util\Datatable
     */
    public function setNotSortableFields(array $notSortableFields)
    {
        $this->notSortableFields = $notSortableFields;
        return $this;
    }

    /**
     * get not sortable field
     *
     * @return array
     */
    public function getNotSortableFields()
    {
        return $this->notSortableFields;
    }

    /**
     * set hidden fields
     *
     * @example
     *
     *      ->setHiddenFields(array(0,2,5))
     *
     * @param array $hiddenFields
     *
     * @return \Waldo\DatatableBundle\Util\Datatable
     */
    public function setHiddenFields(array $hiddenFields)
    {
        $this->hiddenFields = $hiddenFields;
        return $this;
    }

    /**
     * get hidden field
     *
     * @return array
     */
    public function getHiddenFields()
    {
        return $this->hiddenFields;
    }

    /**
     * set filtering type
     * 's' strict
     * 'f' full => LIKE '%' . $value . '%'
     * 'b' begin => LIKE '%' . $value
     * 'e' end => LIKE $value . '%'
     *
     * @example
     *
     *      ->setFilteringType(array(0 => 's',2 => 'f',5 => 'b'))
     *
     * @param array $filteringType
     *
     * @return \Waldo\DatatableBundle\Util\Datatable
     */
    public function setFilteringType(array $filteringType)
    {
        $this->queryBuilder->setFilteringType($filteringType);
        return $this;
    }
}
