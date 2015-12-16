<?php

namespace Waldo\DatatableBundle\Tests\src\DatatableTest;

use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Request;
use Waldo\DatatableBundle\Tests\BaseClient;
use Waldo\DatatableBundle\Util\Datatable;

/**
 * @group DatatableTest
 * @author waldo
 */
class DatatableTest extends BaseClient
{

    /**
     * @var Symfony\Bundle\FrameworkBundle\Client
     */
    private $client;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Datatable
     */
    private $datatable;

    protected function setUp()
    {
        $this->initDatatable();
    }

    private function initDatatable($query = array())
    {
        if(count($query) > 0) {
            unset($this->client);
            unset($this->datatable);
        }

        $this->client = static::createClient();
        $this->buildDatabase($this->client);

        // Inject a fake request
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');

        $requestStack
        ->expects($this->any())
        ->method('getCurrentRequest')
        ->willReturn(new Request($query));

        $this->client->getContainer()->set("request_stack", $requestStack);

        $this->em = $this->client->getContainer()->get('doctrine.orm.default_entity_manager');

        Datatable::clearInstance();

        $this->datatable = $this->client->getContainer()->get('datatable');
    }

    public function test_chainingClassBehavior()
    {
        $this->assertInstanceOf(
                '\Waldo\DatatableBundle\Util\Datatable',
                $this->datatable->setEntity('$entity_name', '$entity_alias')
                );

        $this->assertInstanceOf('\Waldo\DatatableBundle\Util\Datatable', $this->datatable->setFields(array()));
        $this->assertInstanceOf('\Waldo\DatatableBundle\Util\Datatable', $this->datatable->setFixedData('$data'));
        $this->assertInstanceOf('\Waldo\DatatableBundle\Util\Datatable', $this->datatable->setOrder('$order_field', '$order_type'));

        $this->assertInstanceOf('\Waldo\DatatableBundle\Util\Datatable',
                $this->datatable->setRenderer(function($value, $key) {
                    return true;
                }));
    }

    public function test_addJoin()
    {
        $this->datatable
                ->setEntity('Waldo\DatatableBundle\Tests\Functional\Entity\Product', 'p')
                ->addJoin('p.features', 'f');

        /* @var $qb \Doctrine\ORM\QueryBuilder */
        $qb = $this->datatable->getQueryBuilder()->getDoctrineQueryBuilder();
        $parts = $qb->getDQLParts();
        $this->assertNotEmpty($parts['join']);
        $this->assertTrue(array_key_exists('p', $parts['join']));
    }

    public function test_addJoinWithCondition()
    {
        $this->datatable
                ->setEntity('Waldo\DatatableBundle\Tests\Functional\Entity\Product', 'p')
                ->addJoin('p.features', 'f', \Doctrine\ORM\Query\Expr\Join::INNER_JOIN, 'p.id = 1');

        /* @var $qb \Doctrine\ORM\QueryBuilder */
        $qb = $this->datatable->getQueryBuilder()->getDoctrineQueryBuilder();
        $parts = $qb->getDQLParts();
        $this->assertNotEmpty($parts['join']);
        $this->assertTrue(array_key_exists('p', $parts['join']));
    }


    public function test_execute()
    {
        $r = $this->datatable
                ->setEntity('Waldo\DatatableBundle\Tests\Functional\Entity\Product', 'p')
                ->setFields(
                        array(
                            "title"        => 'p.name',
                            "_identifier_" => 'p.id')
                )
                ->execute();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $r);
    }

    public function test_executeWithGroupBy()
    {
        $r = $this->datatable
                ->setEntity('Waldo\DatatableBundle\Tests\Functional\Entity\Product', 'p')
                ->setFields(
                        array(
                            "title"        => 'p.name',
                            "_identifier_" => 'p.id')
                )
                ->setGroupBy("p.id")
                ->execute();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $r);
    }

    public function test_executeWithFixedData()
    {
        $r = $this->datatable
                ->setEntity('Waldo\DatatableBundle\Tests\Functional\Entity\Product', 'p')
                ->setFields(
                        array(
                            "title"        => 'p.name',
                            "_identifier_" => 'p.id')
                )
                ->setFixedData(array("plop"))
                ->execute();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $r);
    }

    public function test_executeWithMultiple()
    {
        $r = $this->datatable
                ->setEntity('Waldo\DatatableBundle\Tests\Functional\Entity\Product', 'p')
                ->setFields(
                        array(
                            "title"        => 'p.name',
                            "_identifier_" => 'p.id')
                )
                ->setMultiple(array('delete' => array ('title' => "Delete", 'route' => 'route_to_delete')))
                ->execute();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $r);
    }

    public function test_getInstance()
    {
        $this->datatable
                ->setDatatableId('test')
                ->setEntity('Waldo\DatatableBundle\Tests\Functional\Entity\Product', 'p')
                ->setFields(
                        array(
                            "title"        => 'p.name',
                            "_identifier_" => 'p.id')
        );
        $i = $this->datatable->getInstance('test');
        $this->assertInstanceOf('\Waldo\DatatableBundle\Util\Datatable', $i);
        $this->assertEquals('p', $i->getEntityAlias());
    }

    public function test_getInstanceWithFaketId()
    {
        $this->datatable

                ->setEntity('Waldo\DatatableBundle\Tests\Functional\Entity\Product', 'p')
                ->setFields(
                        array(
                            "title"        => 'p.name',
                            "_identifier_" => 'p.id')
        );

        $i = $this->datatable->getInstance("fake");

        $this->assertInstanceOf('\Waldo\DatatableBundle\Util\Datatable', $i);
        $this->assertEquals('p', $i->getEntityAlias());
    }

    public function test_clearInstance()
    {
        $this->datatable->setDatatableId('fake1')
                ->setEntity('Waldo\DatatableBundle\Tests\Functional\Entity\Product', 'p')
                ->setFields(
                        array(
                            "title"        => 'p.name',
                            "_identifier_" => 'p.id')
        );

        Datatable::clearInstance();
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Identifer already exists
     */
    public function test_setDatatableIdException()
    {
        $this->datatable->setDatatableId('fake1');
        $this->datatable->setDatatableId('fake1');
    }

    public function test_queryBuilder()
    {
        $qb = $this->getMockBuilder("Waldo\DatatableBundle\Util\Factory\Query\QueryInterface")->getMock();


        $this->datatable->setQueryBuilder($qb);

        $qbGet = $this->datatable->getQueryBuilder();

        $this->assertEquals($qb, $qbGet);
    }

    public function test_getEntityName()
    {
        $this->datatable
                ->setEntity('Waldo\DatatableBundle\Tests\Functional\Entity\Product', 'p')
                ->setFields(
                        array(
                            "title"        => 'p.name',
                            "_identifier_" => 'p.id')
        );
        $this->assertEquals('Waldo\DatatableBundle\Tests\Functional\Entity\Product', $this->datatable->getEntityName());
    }

    public function test_getEntityAlias()
    {
        $this->datatable
                ->setEntity('Waldo\DatatableBundle\Tests\Functional\Entity\Product', 'p')
                ->setFields(
                        array(
                            "title"        => 'p.name',
                            "_identifier_" => 'p.id')
        );
        $this->assertEquals('p', $this->datatable->getEntityAlias());
    }

    public function test_getFields()
    {
        $this->datatable
                ->setEntity('Waldo\DatatableBundle\Tests\Functional\Entity\Product', 'p')
                ->setFields(
                        array(
                            "title"        => 'p.name',
                            "_identifier_" => 'p.id')
        );
        $this->assertInternalType('array', $this->datatable->getFields());
    }

    public function test_getOrderField()
    {
        $this->datatable
                ->setEntity('Waldo\DatatableBundle\Tests\Functional\Entity\Product', 'p')
                ->setFields(
                        array(
                            "title"        => 'p.name',
                            "_identifier_" => 'p.id'))
                ->setOrder('p.id', 'asc')
        ;
        $this->assertInternalType('string', $this->datatable->getOrderField());
    }

    public function test_getOrderFieldWithAlias()
    {
        $this->initDatatable(array("iSortCol_0" => 0));


        $data = $this->datatable
                ->setEntity('Waldo\DatatableBundle\Tests\Functional\Entity\Product', 'p')
                ->setFields(
                        array(
                            "title"        => "(SELECT Product.name
                                              FROM \Waldo\DatatableBundle\Tests\Functional\Entity\Product as Product
                                              WHERE Product.id = 1) as someAliasName",
                            "_identifier_" => 'p.id'))
                ->getQueryBuilder()->getData(null);

        $this->assertEquals("Laptop", $data[0][0][0]);
    }

    public function test_getOrderType()
    {
        $this->datatable
                ->setEntity('Waldo\DatatableBundle\Tests\Functional\Entity\Product', 'p')
                ->setFields(
                        array(
                            "title"        => 'p.name',
                            "_identifier_" => 'p.id'))
                ->setOrder('p.id', 'asc')
        ;
        $this->assertInternalType('string', $this->datatable->getOrderType());
    }

    public function test_getQueryBuilder()
    {
        $this->datatable
                ->setEntity('Waldo\DatatableBundle\Tests\Functional\Entity\Product', 'p')
                ->setFields(
                        array(
                            "title"        => 'p.name',
                            "_identifier_" => 'p.id'))
                ->setOrder('p.id', 'asc')
        ;
        $this->assertInstanceOf('Waldo\DatatableBundle\Util\Factory\Query\DoctrineBuilder', $this->datatable->getQueryBuilder());
    }

    public function test_alias()
    {
        $r = $this->datatable
                ->setEntity('Waldo\DatatableBundle\Tests\Functional\Entity\Product', 'p')
                ->setFields(
                        array(
                            "title"        => 'p.name as someAliasName',
                            "_identifier_" => 'p.id')
                )->getQueryBuilder()->getData(null);


        $this->assertArrayHasKey("someAliasName", $r[1][0]);
    }

    public function test_multipleAlias()
    {
        $r = $this->datatable
                ->setEntity('Waldo\DatatableBundle\Tests\Functional\Entity\Product', 'p')
                ->setFields(
                        array(
                            "title"        => "(SELECT Product.name
                                              FROM \Waldo\DatatableBundle\Tests\Functional\Entity\Product as Product
                                              WHERE Product.id = 1) as someAliasName",
                            "_identifier_" => 'p.id')
                )->getQueryBuilder()->getData(null);


        $this->assertArrayHasKey("someAliasName", $r[1][0]);
    }

    public function test_setWhere()
    {
        $r = $this->datatable
                ->setEntity('Waldo\DatatableBundle\Tests\Functional\Entity\Product', 'p')
                ->setFields(
                        array(
                            "title"        => "(SELECT Product.name
                                              FROM \Waldo\DatatableBundle\Tests\Functional\Entity\Product as Product
                                              WHERE Product.id = 1) as someAliasName",
                            "_identifier_" => 'p.id')
                )
                ->setWhere("thisIsAWhere")
                ->getQueryBuilder()->getDoctrineQueryBuilder()->getDQL();

        $this->assertContains("thisIsAWhere", $r);
    }

    public function test_setGroupBy()
    {
        $r = $this->datatable
                ->setEntity('Waldo\DatatableBundle\Tests\Functional\Entity\Product', 'p')
                ->setFields(
                        array(
                            "title"        => "(SELECT Product.name
                                              FROM \Waldo\DatatableBundle\Tests\Functional\Entity\Product as Product
                                              WHERE Product.id = 1) as someAliasName",
                            "_identifier_" => 'p.id')
                )
                ->setGroupBy("thisIsAGroupBy")
                ->getQueryBuilder()->getDoctrineQueryBuilder()->getDQL();

        $this->assertContains("thisIsAGroupBy", $r);
    }

    public function test_multiple()
    {
        $expectedArray = array(
                        'add' => array('title' => "Add", 'route' => 'route_to_add'),
                        'delete' => array('title' => "Delete", 'route' => 'route_to_delete'),
                        );

        $this->datatable
            ->setMultiple($expectedArray);


        $this->assertEquals($expectedArray, $this->datatable->getMultiple());
    }

    public function test_getConfig()
    {
        $c = $this->datatable->getConfiguration();

        $this->assertTrue(is_array($c));
    }

    public function test_searchFields()
    {
        $expectedArray = array("field 1", "field 2");

        $this->datatable->setSearchFields($expectedArray);

        $this->assertEquals($expectedArray, $this->datatable->getSearchFields());
    }

    public function test_notFilterableFields()
    {
        $expectedArray = array("field 1", "field 2");

        $this->datatable->setNotFilterableFields($expectedArray);

        $this->assertEquals($expectedArray, $this->datatable->getNotFilterableFields());
    }

    public function test_notSortableFields()
    {
        $expectedArray = array("field 1", "field 2");

        $this->datatable->setNotSortableFields($expectedArray);

        $this->assertEquals($expectedArray, $this->datatable->getNotSortableFields());
    }

    public function test_hiddenFields()
    {
        $expectedArray = array("field 1", "field 2");

        $this->datatable->setHiddenFields($expectedArray);

        $this->assertEquals($expectedArray, $this->datatable->getHiddenFields());
    }

    public function test_filteringType()
    {
        $this->initDatatable(array(
            "search" => array("regex" => "false", "value" => "desktop"),
            "columns" => array(
                0 => array(
                    "searchable" => "true",
                    "search" => array("regex" => "false", "value" => "")
                    ),
                1 => array(
                    "searchable" => "true",
                    "search" => array("regex" => "false", "value" => "")
                    ),
                2 => array(
                    "searchable" => "true",
                    "search" => array("regex" => "false", "value" => "")
                    ),
                3 => array(
                    "searchable" => "true",
                    "search" => array("regex" => "false", "value" => "")
                    )
            )
        ));

        /* @var $res \Symfony\Component\HttpFoundation\JsonResponse */
        $res = $this->datatable
                ->setEntity('Waldo\DatatableBundle\Tests\Functional\Entity\Product', 'p')
                ->setFields(array(
                    "name" => "p.name",
                    "price" => "p.price",
                    "description" => "p.description",
                    "_identifier_" => "p.id"
                ))
                ->setFilteringType(array(
                    0 => "s",
                    1 => "f",
                    2 => "b",
                ))
                ->setSearch(true)
                ->execute();

        $res = json_decode($res->getContent());

        $this->assertEquals(1, $res->recordsFiltered);
        $this->assertEquals(2, $res->recordsTotal);
        $this->assertEquals("Desktop", $res->data[0][0]);
    }

    public function test_SQLCommandInFields()
    {
        $datatable = $this->datatable
                ->setEntity('Waldo\DatatableBundle\Tests\Functional\Entity\Product', 'p')
                ->setFields(
                        array(
                            "total"        => 'COUNT(p.id) as total',
                            "_identifier_" => 'p.id')
                );

        /* @var $qb \Doctrine\ORM\QueryBuilder */
        $qb = $datatable->getQueryBuilder()->getDoctrineQueryBuilder();
        $qb->groupBy('p.id');

        $r = $datatable->getQueryBuilder()->getData(null);

        $this->assertArrayHasKey("total", $r[1][0]);
        $this->assertEquals(1, $r[0][0][1]);
        $this->assertEquals(1, $r[1][0]['total']);
    }

    public function test_getSearch()
    {
        $this->datatable
                ->setEntity('Waldo\DatatableBundle\Tests\Functional\Entity\Product', 'p')
                ->setFields(
                        array(
                            "title"        => 'p.name',
                            "_identifier_" => 'p.id'))
                ->setOrder('p.id', 'asc')
        ;

        $this->assertInternalType('boolean', $this->datatable->getSearch());
    }

    public function test_getSearchWithSubQuery()
    {
        $this->initDatatable(array(
            "search" => array("regex" => "false", "value" => "Laptop"),
            "columns" => array(
                0 => array(
                    "searchable" => "true",
                    "search" => array("regex" => "false", "value" => "")
                    ),
                1 => array(
                    "searchable" => "true",
                    "search" => array("regex" => "false", "value" => "")
                    ),
                2 => array(
                    "searchable" => "true",
                    "search" => array("regex" => "false", "value" => "")
                    ),
                3 => array(
                    "searchable" => "true",
                    "search" => array("regex" => "false", "value" => "")
                    )
            )
        ));

        $this->datatable
                ->setEntity('Waldo\DatatableBundle\Tests\Functional\Entity\Product', 'p')
                ->setFields(
                        array(
                            "title"        => "(SELECT Product.name
                                              FROM Waldo\DatatableBundle\Tests\Functional\Entity\Product as Product
                                              WHERE Product.id = p.id) as someAliasName",
                            "id"            => 'p.id',
                            "_identifier_" => 'p.id')
                )
                ->setSearch(true)
                ;

        $data = $this->datatable->execute();

        $this->assertEquals('{"draw":0,"recordsTotal":"2","recordsFiltered":"1","data":[["Laptop",1,1]]}', $data->getContent());
    }

    public function test_setRenderders()
    {
        $tpl = new \SplFileInfo(__DIR__ . '/../../app/Resources/views/Renderers/_actions.html.twig');

        $out  = $this->datatable
                ->setEntity('\Waldo\DatatableBundle\Tests\Functional\Entity\Feature', 'f')
                ->setFields(
                        array(
                            "title"        => 'f.name',
                            "_identifier_" => 'f.id')
                )
                ->setRenderers(
                        array(
                            1 => array(
                                'view'   => $tpl->getRealPath(),
                                'params' => array(
                                    'edit_route'            => '_edit',
                                    'delete_route'          => '_delete'
                                ),
                            ),
                        )
                )
                ->execute()
        ;
        $json = (array) json_decode($out->getContent());
        $this->assertContains('form', $json['data'][0][1]);
    }

    public function test_setRenderer()
    {
        $datatable  = $this->datatable;

        $templating = $this->client->getContainer()->get('templating');
        $out        = $datatable
                ->setEntity('\Waldo\DatatableBundle\Tests\Functional\Entity\Feature', 'f')
                ->setFields(
                        array(
                            "title"        => 'f.name',
                            "_identifier_" => 'f.id')
                )
                ->setRenderer(
                        function(&$data) use ($templating, $datatable) {

                            $tpl = new \SplFileInfo(__DIR__ . '/../../app/Resources/views/Renderers/_actions.html.twig');

                            foreach ($data as $key => $value)
                            {
                                if ($key == 1)                                      // 1 => adress field
                                {
                                    $data[$key] = $templating
                                            ->render($tpl->getRealPath(), array(
                                        'edit_route'            => '_edit',
                                        'delete_route'          => '_delete'
                                            )
                                    );
                                }
                            }
                        }
                )
                ->execute()
        ;
        $json = (array) json_decode($out->getContent());
        $this->assertContains('form', $json['data'][0][1]);
    }

    public function test_globalSearch()
    {
        $this->datatable->setGlobalSearch(true);
        $this->assertTrue($this->datatable->getGlobalSearch());
    }

}
