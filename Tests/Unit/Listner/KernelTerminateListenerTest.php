<?php

namespace Waldo\DatatableBundle\Tests\Unit\Listner;

use Waldo\DatatableBundle\Listener\KernelTerminateListener;

/**
 * @group KernelTerminateListenerTest
 */
class KernelTerminateListenerTest extends \PHPUnit_Framework_TestCase
{

    public function testOnKernelTerminate()
    {

        $dt = new \Waldo\DatatableBundle\Util\Datatable(
                $this->getMockBuilder("Doctrine\ORM\EntityManager")->disableOriginalConstructor()->getMock(),
                $this->getMockBuilder("Symfony\Component\HttpFoundation\RequestStack")->disableOriginalConstructor()->getMock(),
                $this->getMockBuilder("Waldo\DatatableBundle\Util\Factory\Query\DoctrineBuilder")->disableOriginalConstructor()->getMock(),
                $this->getMockBuilder("Waldo\DatatableBundle\Util\Formatter\Renderer")->disableOriginalConstructor()->getMock(),
                array("js" => array())
                );

        $dt->setDatatableId("testOnKernelTerminate");

        $listner = new KernelTerminateListener();

        $listner->onKernelTerminate();

        $this->assertFalse($dt->hasInstanceId("testOnKernelTerminate"));
    }

}
