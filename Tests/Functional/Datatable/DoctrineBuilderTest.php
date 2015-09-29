<?php

namespace Waldo\DatatableBundle\Tests\src\DatatableTest;

use Symfony\Component\HttpFoundation\Request;

use Waldo\DatatableBundle\Util\Factory\Query\DoctrineBuilder;
use Waldo\DatatableBundle\Tests\BaseClient;
use Waldo\DatatableBundle\Util\Datatable;

/**
 * @group DoctrineBuilderTest
 * @author waldo
 */
class DoctrineBuilderTest extends BaseClient
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
        if (count($query) > 0) {
            unset($this->client);
            unset($this->datatable);
        }

        $this->client = static::createClient();
        $this->buildDatabase($this->client);

        // Inject a fake request
        $requestStack = $this->getMock("Symfony\Component\HttpFoundation\RequestStack");

        $requestStack
                ->expects($this->any())
                ->method('getCurrentRequest')
                ->willReturn(new Request($query));

        $this->client->getContainer()->set("request_stack", $requestStack);

        $this->em = $this->client->getContainer()->get('doctrine.orm.default_entity_manager');

        Datatable::clearInstance();

        $this->datatable = $this->client->getContainer()->get('datatable');
    }

    public function providerAddSearch()
    {
        return array(
            array("s", "Laptop"),
            array("f", "pto"),
            array("b", "ptop"),
            array("e", "Lap"),
            array(null, "Lap"),
        );
    }

    /**
     * @dataProvider providerAddSearch
     */
    public function test_addSearch($searchType, $searchString)
    {
        $query = array(
            "search" => array("regex" => "false", "value" => $searchString),
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
            )
        );

        $this->initDatatable($query);

        $requestStack = $this->getRequestStackMock();
        $requestStack->expects($this->any())
                ->method("getCurrentRequest")
                ->willReturn(new Request($query));

        $doctrineBuilder = new DoctrineBuilder($this->em, $requestStack);

        $doctrineBuilder->setFields(array(
                    "name" => "p.name",
                    "description" => "p.description",
                    "price" => "p.price",
                    "_identifier_" => "p.id"
                ))
                ->setEntity("Waldo\DatatableBundle\Tests\Functional\Entity\Product", "p")
                ->setSearch(true)
                ;

        if($searchType !== null) {
            $doctrineBuilder->setFilteringType(array(
                    0 => $searchType
                ));
        }

        $res = $doctrineBuilder->getData();

        $this->assertCount(1, $res[0]);
        $this->assertEquals("Laptop", $res[0][0][0]);
    }

    /**
     * @dataProvider providerAddSearch
     */
    public function test_addSearchWithoutColumns($searchType, $searchString)
    {
        $query = array(
            "search" => array("regex" => "false", "value" => $searchString)
            );

        $this->initDatatable($query);

        $requestStack = $this->getRequestStackMock();
        $requestStack->expects($this->any())
                ->method("getCurrentRequest")
                ->willReturn(new Request($query));

        $doctrineBuilder = new DoctrineBuilder($this->em, $requestStack);

        $doctrineBuilder->setFields(array(
                    "name" => "p.name",
                    "description" => "p.description",
                    "price" => "p.price",
                    "_identifier_" => "p.id"
                ))
                ->setEntity("Waldo\DatatableBundle\Tests\Functional\Entity\Product", "p")
                ->setSearch(true)
                ;

        if($searchType !== null) {
            $doctrineBuilder->setFilteringType(array(
                    0 => $searchType
                ));
        }

        $res = $doctrineBuilder->getData();

        $this->assertGreaterThan(1, $res[0]);
    }

    public function test_setDoctrineQueryBuilder()
    {
        $this->initDatatable();

        $requestStack = $this->getRequestStackMock();

        $doctrineBuilder = new DoctrineBuilder($this->em, $requestStack);

        $qbMock = $this->getMockBuilder("Doctrine\ORM\QueryBuilder")
                ->disableOriginalConstructor()
                ->getMock();

        $res = $doctrineBuilder->setDoctrineQueryBuilder($qbMock);

        $this->assertEquals($qbMock, $res->getDoctrineQueryBuilder());
    }

    private function getRequestStackMock()
    {
        return $this->getMockBuilder("Symfony\Component\HttpFoundation\RequestStack")
                        ->disableOriginalConstructor()
                        ->getMock();
    }

}
