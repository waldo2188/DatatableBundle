<?php

namespace Waldo\DatatableBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class WaldoDatatableExtension extends Extension
{

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $config = $this->applyDefaultConfig($config);


        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $container->setParameter('datatable', $config);
    }

    /**
     * Datatable options
     *
     * @see https://datatables.net/reference/option/
     * @param type $config
     */
    private function applyDefaultConfig($config)
    {
        $defaultJsConfig = array(
            "jQueryUI" => true,
            "pagingType" => "full_numbers",
            "lengthMenu" => [[10, 25, 50, -1], [10, 25, 50, "All"]],
            "pageLength" => 10,
            "serverSide" => true,
            "processing" => true,
            "paging" => true,
            "lengthChange" => true,
            "ordering" => true,
            "searching" => true,
            "autoWidth" => false,
            "order" => array()
        );

        $config['js'] = array_merge($defaultJsConfig, $config['js']);

        return $config;
    }

}
