<?php

namespace Waldo\DatatableBundle\Tests\Unit\Twig;

use Waldo\DatatableBundle\Twig\Extension\DatatableExtension;

/**
 * @group DatatableTwigExtensionTest
 */
class DatatableTwigExtensionTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var DatatableExtension
     */
    private $extentsion;

    private $formFactoryMock;

    protected function setUp()
    {
        $this->formFactoryMock = $this->getMockBuilder("Symfony\Component\Form\FormFactory")
                ->disableOriginalConstructor()
                ->setMethods(array("createBuilder", "add", "getForm", "createView"))
                ->getMock();

        $this->formFactoryMock->expects($this->any())
                ->method("createBuilder")
                ->willReturnSelf();

        $this->formFactoryMock->expects($this->any())
                ->method("add")
                ->willReturnSelf();

        $this->formFactoryMock->expects($this->any())
                ->method("getForm")
                ->willReturnSelf();

        $this->formFactoryMock->expects($this->any())
                ->method("createView")
                ->willReturnSelf();


        $this->extentsion = new DatatableExtension($this->formFactoryMock);
    }

    public function testGetName()
    {
        $this->assertEquals("DatatableBundle", $this->extentsion->getName());
    }

    public function testCreateFormBuilder()
    {
        $this->assertEquals($this->formFactoryMock, $this->extentsion->createFormBuilder());
    }

    public function testGetFunctions()
    {
        /* @var $functions array<\Twig_SimpleFunction> */
        $functions = $this->extentsion->getFunctions();

        $this->assertEquals("datatable", $functions[0]->getName());
        $this->assertEquals("datatable_html", $functions[1]->getName());
        $this->assertEquals("datatable_js", $functions[2]->getName());
    }

    public function testDatatable()
    {
        $dbMock = $this->getMockBuilder("Waldo\DatatableBundle\Util\Factory\Query\DoctrineBuilder")
                ->disableOriginalConstructor()
                ->getMock();

        $dbMock->expects($this->any())
                ->method("getOrderField")
                ->willReturn("oHYes");

        $dbMock->expects($this->any())
                ->method("getFields")
                ->willReturn(array());

        $dt = $this->getDatatable();
        $dt->setQueryBuilder($dbMock);
        $dt->setDatatableId("testDatatable");

        $twig = $this->getMock("\Twig_Environment");
        $twig->expects($this->once())
                ->method("render")
                ->with($this->equalTo("WaldoDatatableBundle:Main:index.html.twig"))
                ->willReturn("OK");

        $res = $this->extentsion->datatable($twig, array(
            "id" => "testDatatable",
            "js_conf" => "",
            "js" => array(),
            "action" => "",
            "action_twig" => "",
            "fields" => "",
            "delete_form" => "",
            "search" => "",
            "global_search" => "",
            "searchFields" => "",
            "multiple" => "",
            "sort" => ""
        ));

        $this->assertEquals("OK", $res);
    }

    public function testDatatableJs()
    {
        $dt = $this->getDatatable();
        $dt->setDatatableId("testDatatableJs");

        $twig = $this->getMock("\Twig_Environment");
        $twig->expects($this->once())
                ->method("render")
                ->with($this->equalTo("WaldoDatatableBundle:Main:datatableJs.html.twig"))
                ->willReturn("OK");

        $res = $this->extentsion->datatableJs($twig, array(
            "js_conf" => "",
            "js" => array(),
            "action" => "",
            "action_twig" => "",
            "fields" => "",
            "delete_form" => "",
            "search" => "",
            "global_search" => "",
            "searchFields" => "",
            "multiple" => "",
            "sort" => ""
        ));

        $this->assertEquals("OK", $res);
    }

    public function testDatatableHtml()
    {
        $dt = $this->getDatatable();
        $dt->setDatatableId("testDatatableHtml");

        $twig = $this->getMock("\Twig_Environment");
        $twig->expects($this->once())
                ->method("render")
                ->with($this->equalTo("myHtmlTemplate"))
                ->willReturn("OK");

        $res = $this->extentsion->datatableHtml($twig, array(
            "html_template" => "myHtmlTemplate",
            "js_conf" => "",
            "js" => array(),
            "action" => "",
            "action_twig" => "",
            "fields" => "",
            "delete_form" => "",
            "search" => "",
            "global_search" => "",
            "searchFields" => "",
            "multiple" => "",
            "sort" => ""
        ));

        $this->assertEquals("OK", $res);
    }

    private function getDatatable()
    {
        return new \Waldo\DatatableBundle\Util\Datatable(
                $this->getMockBuilder("Doctrine\ORM\EntityManager")->disableOriginalConstructor()->getMock(),
                $this->getMockBuilder("Symfony\Component\HttpFoundation\RequestStack")->disableOriginalConstructor()->getMock(),
                $this->getMockBuilder("Waldo\DatatableBundle\Util\Factory\Query\DoctrineBuilder")->disableOriginalConstructor()->getMock(),
                $this->getMockBuilder("Waldo\DatatableBundle\Util\Formatter\Renderer")->disableOriginalConstructor()->getMock(),
                array("js" => array())
                );
    }
}
