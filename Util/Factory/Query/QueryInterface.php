<?php

namespace Waldo\DatatableBundle\Util\Factory\Query;

use Doctrine\ORM\Query\Expr\Join;

interface QueryInterface
{

    const DQL_ALIAS_PATTERN = "/([A-z]*\.[A-z]+)?\sas\s(.*)$/";

    /**
     * get total records
     *
     * @return integer
     */
    function getTotalRecords();

    /**
     * get data
     *
     * @return array
     */
    function getData();

    /**
     * set entity
     *
     * @param string $entity_name
     * @param string $entity_alias
     *
     * @return Datatable
     */
    function setEntity($entity_name, $entity_alias);

    /**
     * set fields
     *
     * @param array $fields
     *
     * @return Datatable
     */
    function setFields(array $fields);

    /**
     * get entity name
     *
     * @return string
     */
    function getEntityName();

    /**
     * get entity alias
     *
     * @return string
     */
    function getEntityAlias();

    /**
     * get fields
     *
     * @return array
     */
    function getFields();

    /**
     * get order field
     *
     * @return string
     */
    function getOrderField();

    /**
     * get order type
     *
     * @return string
     */
    function getOrderType();

    /**
     * set order
     *
     * @param string $order_field
     * @param string $order_type
     *
     * @return Datatable
     */
    function setOrder($order_field, $order_type);

    /**
     * set query where
     *
     * @param string $where
     * @param array  $params
     *
     * @return Datatable
     */
    function setWhere($where, array $params = array());

    /**
     * set search
     *
     * @param bool $search
     *
     * @return Datatable
     */
    function setSearch($search);

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
    public function addJoin($join, $alias, $type = Join::INNER_JOIN, $conditionType = null, $condition = null);

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
     * @return Datatable
     */
    function setFilteringType(array $filtering_type);

    /**
     * @return \Doctrine\ORM\QueryBuilder;
     */
    function getDoctrineQueryBuilder();

    function getFilteringType();
}
