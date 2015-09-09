<?php

namespace Waldo\DatatableBundle\Tests\src\DatatableTest;

use Waldo\DatatableBundle\Tests\BaseClient;

use Waldo\DatatableBundle\Util\Datatable;

/**
 * Description of DatatableStaticTest
 *
 * @group DatatableStaticTest
 *
 * @author waldo
 */
class DatatableStaticTest extends BaseClient
{
    /**
     * @expectedException \Exception
     * @expectedExceptionMessage No instance found for datatable, you should set a datatable id in your action with "setDatatableId" using the id from your view
     */
    public function test_getInstanceWithoutInstance()
    {
        $i = Datatable::getInstance("fakes");
    }
}
