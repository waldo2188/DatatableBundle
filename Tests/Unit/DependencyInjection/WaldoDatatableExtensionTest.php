<?php

namespace Waldo\DatatableBundle\Tests\Unit\DependencyInjection;

use Waldo\DatatableBundle\DependencyInjection\WaldoDatatableExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @group WaldoDatatableExtensionTest
 */
class WaldoDatatableExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WaldoDatatableExtension
     */
    private $extension;

    /**
     * @var ContainerBuilder
     */
    private $container;

    protected function setUp()
    {
        $this->extension = new WaldoDatatableExtension();

        $this->container = new ContainerBuilder();
        $this->container->registerExtension($this->extension);
    }

    public function testWithoutConfiguration()
    {
        $this->container->loadFromExtension($this->extension->getAlias());
        $this->extension->load(array(), $this->container);

        $this->assertTrue($this->container->hasDefinition("datatable"));
        $this->assertTrue($this->container->hasDefinition("datatable.renderer"));
        $this->assertTrue($this->container->hasDefinition("datatable.twig.extension"));
        $this->assertTrue($this->container->hasDefinition("datatable.kernel.listener.terminate"));
        $this->assertArrayHasKey("datatable", $this->container->getParameterBag()->all());
    }

    public function testWithYamlConfig()
    {
        $configYaml = <<<YAML
waldo_datatable:
    all:
        action:           true
        search:           false
    js:
        iDisplayLength: "10"
        aLengthMenu: "[[5,10, 25, 50, -1], [5,10, 25, 50, 'All']]"
        bJQueryUI: "false"
        fnPreDrawCallback: |
            function( e ) {
                // you custom code goes here
            }
YAML;

        $yamlParser = new \Symfony\Component\Yaml\Parser();

        $config = $yamlParser->parse($configYaml);

        $this->container->loadFromExtension($this->extension->getAlias());
        $this->extension->load($config, $this->container);

        $this->assertArrayHasKey("datatable", $this->container->getParameterBag()->all());

        $parseConfig = $this->container->getParameterBag()->get("datatable");

        $this->assertEquals("[[5,10, 25, 50, -1], [5,10, 25, 50, 'All']]", $parseConfig['js']['aLengthMenu']);
    }

}
