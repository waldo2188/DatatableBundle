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

    private $translator;

    private $formFactoryMock;

    protected function setUp()
    {
        $this->translator = $this->getMockBuilder("Symfony\Component\Translation\DataCollectorTranslator")
                ->disableOriginalConstructor()
                ->getMock();



        $this->extentsion = new DatatableExtension($this->translator);
    }

    public function testGetName()
    {
        $this->assertEquals("DatatableBundle", $this->extentsion->getName());
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
        $twig->expects($this->any())
                ->method("render")
                ->with($this->equalTo("WaldoDatatableBundle:Main:datatableJs.html.twig"))
                ->willReturnArgument(1);

        $configRow = array(
            "js" => array(
                'dom'=> "<'row'<'span6'fr>>t<'row'<'span7'il><'span5 align-right'p>>",
                'ajax'=> "urlDatatable"
            ),
            "action" => "",
            "action_twig" => "",
            "fields" => "",
            "delete_form" => "",
            "search" => "",
            "global_search" => "",
            "searchFields" => "",
            "multiple" => "",
            "sort" => ""
        );

        $res = $this->extentsion->datatableJs($twig, $configRow);


        $this->assertEquals("<'row'<'span6'fr>>t<'row'<'span7'il><'span5 align-right'p>>", $res['js']['dom']);
        $this->assertEquals("urlDatatable", $res['js']['ajax']['url']);
        $this->assertTrue($res['js']['paging']);

        $configRow["js"]['paging'] = false;

        $res = $this->extentsion->datatableJs($twig, $configRow);

        $this->assertFalse($res['js']['paging']);
    }

    public function testDatatableJsTranslation()
    {
        $dt = $this->getDatatable();
        $dt->setDatatableId("testDatatableJsTranslation");

        $twig = $this->getMock("\Twig_Environment");
        $twig->expects($this->once())
                ->method("render")
                ->with(
                        $this->equalTo("WaldoDatatableBundle:Main:datatableJs.html.twig"),
                        $this->callback(function($option){

                            return $option['js']['language']["searchPlaceholder"] === "find Me" &&
                                 $option['js']['language']["paginate"]["first"] === "coucou" &&
                                 array_key_exists("next", $option['js']['language']["paginate"])
                                 ;

                        })
                        )
                ->willReturn("OK");

        $res = $this->extentsion->datatableJs($twig, array(
            "js" => array("language" => array(
                "searchPlaceholder" => "find Me",
                "paginate" => array(
                    "first" => "coucou"
                )
            )),
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
                array("js" => array(
                    'paging' => true
                ))
                );
    }
}
