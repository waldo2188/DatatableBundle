<?php

namespace Waldo\DatatableBundle\Util\Factory\Query;

use Symfony\Component\HttpFoundation\RequestStack;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

class DoctrineBuilder implements QueryInterface
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var RequestStack
     */
    protected $request;

    /**
     * @var \Doctrine\ORM\QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var string
     */
    protected $entityAlias;

    /**
     * @var array
     */
    protected $fields;

    /**
     * @var string
     */
    protected $orderField;

    /**
     * @var string
     */
    protected $orderType = "asc";

    /**
     * @var string
     */
    protected $where;

    /**
     * @var array
     */
    protected $joins = array();

    /**
     * @var boolean
     */
    protected $hasAction = true;

    /**
     * @var closure
     */
    protected $renderer;

    /**
     * @var boolean
     */
    protected $search = FALSE;

    /**
     * @var array
     */
    protected $filteringType = array();

    /**
     * class constructor
     *
     * @param EntityManager $entityManager
     * @param RequestStack $request
     */
    public function __construct(EntityManager $entityManager, RequestStack $request)
    {
        $this->em = $entityManager;
        $this->request = $request;

        $this->queryBuilder = $this->em->createQueryBuilder();
    }

    /**
     * get the search DQL
     *
     * @return string
     */
    protected function addSearch(QueryBuilder $queryBuilder)
    {
        if ($this->search !== true) {
            return;
        }

        $request = $this->request->getCurrentRequest();

        $columns = $request->get('columns', array());

        $searchFields = array_intersect_key(array_values($this->fields), $columns);

        $globalSearch = $request->get('search');

        $orExpr = $queryBuilder->expr()->orX();

        $filteringType = $this->getFilteringType();

        foreach ($searchFields as $i => $searchField) {

            $searchField = $this->getSearchField($searchField);

            // Global filtering
            if ((!empty($globalSearch) || $globalSearch['value'] == '0') && $columns[$i]['searchable'] === "true") {

                $qbParam = "sSearch_global_" . $i;

                if ($this->isStringDQLQuery($searchField)) {

                    $orExpr->add(
                            $queryBuilder->expr()->eq($searchField, ':' . $qbParam)
                    );
                    $queryBuilder->setParameter($qbParam, $globalSearch['value']);

                } else {

                    $orExpr->add($queryBuilder->expr()->like($searchField, ":" . $qbParam));
                    $queryBuilder->setParameter($qbParam, "%" . $globalSearch['value'] . "%");

                }
            }

            // Individual filtering
            $searchName = "sSearch_" . $i;

            if($columns[$i]['searchable'] === "true" && $columns[$i]['search']['value'] != "") {

                $queryBuilder->andWhere($queryBuilder->expr()->like($searchField, ":" . $searchName));

                if (array_key_exists($i, $filteringType)) {
                    switch ($filteringType[$i]) {
                        case 's':
                            $queryBuilder->setParameter($searchName, $columns[$i]['search']['value']);
                            break;
                        case 'f':
                            $queryBuilder->setParameter($searchName, sprintf("%%%s%%", $columns[$i]['search']['value']));
                            break;
                        case 'b':
                            $queryBuilder->setParameter($searchName, sprintf("%%%s", $columns[$i]['search']['value']));
                            break;
                        case 'e':
                            $queryBuilder->setParameter($searchName, sprintf("%s%%", $columns[$i]['search']['value']));
                            break;
                    }
                } else {
                    $queryBuilder->setParameter($searchName, sprintf("%%%s%%", $columns[$i]['search']['value']));
                }
            }
        }

        if ((!empty($globalSearch) || $globalSearch == '0') && $orExpr->count() > 0) {
            $queryBuilder->andWhere($orExpr);
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
        if($type === Join::INNER_JOIN) {
            $this->queryBuilder->innerJoin($join, $alias, $conditionType, $condition);
        } elseif($type === Join::LEFT_JOIN) {
            $this->queryBuilder->leftJoin($join, $alias, $conditionType, $condition);
        }

        $this->joins[] = array($join, $alias, $type, $conditionType, $condition);

        return $this;
    }

    /**
     * get total records
     *
     * @return integer
     */
    public function getTotalRecords()
    {
        $qb = clone $this->queryBuilder;
        $qb->resetDQLPart('orderBy');

        $gb = $qb->getDQLPart('groupBy');

        if (empty($gb) || !in_array($this->fields['_identifier_'], $gb)) {
            $qb->select(
                    $qb->expr()->count($this->fields['_identifier_'])
                    );

            return $qb->getQuery()->getSingleScalarResult();
        } else {
            $qb->resetDQLPart('groupBy');
            $qb->select(
                    $qb->expr()->countDistinct($this->fields['_identifier_'])
                    );

            return $qb->getQuery()->getSingleScalarResult();
        }
    }

    /**
     * get total records after filtering
     *
     * @return integer
     */
    public function getTotalDisplayRecords()
    {
        $qb = clone $this->queryBuilder;

        $this->addSearch($qb);

        $qb->resetDQLPart('orderBy');

        $gb = $qb->getDQLPart('groupBy');

        if (empty($gb) || !in_array($this->fields['_identifier_'], $gb)) {
            $qb->select(
                    $qb->expr()->count($this->fields['_identifier_'])
                       );

            return $qb->getQuery()->getSingleScalarResult();

        } else {
            $qb->resetDQLPart('groupBy');
            $qb->select(
                    $qb->expr()->countDistinct($this->fields['_identifier_'])
                    );

            return $qb->getQuery()->getSingleScalarResult();
        }
    }

    /**
     * get data
     *
     * @return array
     */
    public function getData()
    {
        $request = $this->request->getCurrentRequest();
        $order = $request->get('order', array());

        $dqlFields = array_values($this->fields);

        $qb = clone $this->queryBuilder;

        // add sorting
        if (array_key_exists(0, $order)) {
            $orderField = explode(' as ', $dqlFields[$order[0]['column']]);
            end($orderField);

            $qb->orderBy(current($orderField), $order[0]['dir']);
        } elseif($this->orderField == null) {
            $qb->resetDQLPart('orderBy');
        }

        // extract alias selectors
        $select = array($this->entityAlias);
        foreach ($this->joins as $join) {
            $select[] = $join[1];
        }

        foreach ($this->fields as $key => $field) {
            if (stripos($field, " as ") !== false || stripos($field, "(") !== false) {
                $select[] = $field;
            }
        }

        $qb->select(implode(',', $select));

        // add search
        $this->addSearch($qb);

        // get results and process data formatting
        $query = $qb->getQuery();
        $length = (int) $request->get('length', 0);

        if ($length > 0) {
            $query->setMaxResults($length)
                    ->setFirstResult((int) $request->get('start'));
        }

        $objects = $query->getResult(Query::HYDRATE_OBJECT);
        $maps = $query->getResult(Query::HYDRATE_SCALAR);
        $data = array();

        $aliasPattern = self::DQL_ALIAS_PATTERN;

        $get_scalar_key = function($field) use($aliasPattern) {

            $has_alias = (bool) preg_match_all($aliasPattern, $field, $matches);
            $_f = ( $has_alias === true ) ? $matches[2][0] : $field;
            $_f = str_replace('.', '_', $_f);

            return $_f;
        };

        $fields = array();

        foreach ($this->fields as $field) {
            $fields[] = $get_scalar_key($field);
        }

        foreach ($maps as $map) {
            $item = array();
            foreach ($fields as $_field) {
                $item[] = $map[$_field];
            }
            $data[] = $item;
        }

        return array($data, $objects);
    }

    /**
     * get entity name
     *
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * get entity alias
     *
     * @return string
     */
    public function getEntityAlias()
    {
        return $this->entityAlias;
    }

    /**
     * get fields
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * get order field
     *
     * @return string
     */
    public function getOrderField()
    {
        return $this->orderField;
    }

    /**
     * get order type
     *
     * @return string
     */
    public function getOrderType()
    {
        return $this->orderType;
    }

    /**
     * get doctrine query builder
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getDoctrineQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * set entity
     *
     * @param type $entity_name
     * @param type $entity_alias
     *
     * @return Datatable
     */
    public function setEntity($entity_name, $entity_alias)
    {
        $this->entityName = $entity_name;
        $this->entityAlias = $entity_alias;
        $this->queryBuilder->from($entity_name, $entity_alias);

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
        $this->fields = $fields;
        $this->queryBuilder->select(implode(', ', $fields));

        return $this;
    }

    /**
     * set order
     *
     * @param type $order_field
     * @param type $order_type
     *
     * @return Datatable
     */
    public function setOrder($order_field, $order_type)
    {
        $this->orderField = $order_field;
        $this->orderType = $order_type;
        $this->queryBuilder->orderBy($order_field, $order_type);

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
        $this->queryBuilder->where($where);
        $this->queryBuilder->setParameters($params);
        return $this;
    }

    /**
     * set query group
     *
     * @param string $group
     *
     * @return Datatable
     */
    public function setGroupBy($group)
    {
        $this->queryBuilder->groupBy($group);
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
        return $this;
    }

    /**
     * set doctrine query builder
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     *
     * @return DoctrineBuilder
     */
    public function setDoctrineQueryBuilder(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
        return $this;
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
     * @param array $filtering_type
     *
     * @return DoctrineBuilder
     */
    public function setFilteringType(array $filtering_type)
    {
        $this->filteringType = $filtering_type;
        return $this;
    }

    public function getFilteringType()
    {
        return $this->filteringType;
    }

    /**
     * The most of time $search_field is a string that represent the name of a field in data base.
     * But some times, $search_field is a DQL subquery
     *
     * @param string $field
     * @return string
     */
    private function getSearchField($field)
    {
        if ($this->isStringDQLQuery($field)) {

            $dqlQuery = $field;

            $lexer = new Query\Lexer($field);

            // We have to rename some identifier or the execution will crash
            do {
                $lexer->moveNext();

                if ($this->isTheIdentifierILookingFor($lexer)) {

                    $replacement = sprintf("$1%s_%d$3", $lexer->lookahead['value'], mt_rand());
                    $pattern = sprintf("/([\(\s])(%s)([\s\.])/", $lexer->lookahead['value']);

                    $dqlQuery = preg_replace($pattern, $replacement, $dqlQuery);
                }

            } while($lexer->lookahead !== null);

            $dqlQuery = substr($dqlQuery, 0, strripos($dqlQuery, ")") + 1);

            return $dqlQuery;
        }

        $field = explode(' ', trim($field));

        return $field[0];
    }

    /**
     * Check if it's the lexer part is the identifier I looking for
     *
     * @param \Doctrine\ORM\Query\Lexer $lexer
     * @return boolean
     */
    private function isTheIdentifierILookingFor(Query\Lexer $lexer)
    {
        if ($lexer->token['type'] === Query\Lexer::T_IDENTIFIER && $lexer->isNextToken(Query\Lexer::T_IDENTIFIER)) {
            return true;
        }

        if ($lexer->token['type'] === Query\Lexer::T_IDENTIFIER && $lexer->isNextToken(Query\Lexer::T_AS)) {

            $lexer->moveNext();

            if ($lexer->lookahead['type'] === Query\Lexer::T_IDENTIFIER) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a sring is a DQL query
     *
     * @param string $value
     * @return boolean
     */
    private function isStringDQLQuery($value)
    {
        $keysWord = array(
            "SELECT ",
            " FROM ",
            " WHERE "
        );

        foreach ($keysWord as $keyWord) {
            if (stripos($value, $keyWord) !== false) {
                return true;
            }
        }

        return false;
    }
}
