<?php

namespace Waldo\DatatableBundle\Twig\Extension;

use Symfony\Component\Form\FormFactoryInterface;
use Waldo\DatatableBundle\Util\Datatable;

class DatatableExtension extends \Twig_Extension
{

    /**
     * @var EngineInterface
     */
    protected $formFactory;

    /**
     * class constructor
     *
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('datatable', array($this, 'datatable'),
                    array("is_safe" => array("html"), 'needs_environment' => true)),
            new \Twig_SimpleFunction('datatable_html', array($this, 'datatableHtml'),
                    array("is_safe" => array("html"), 'needs_environment' => true)),
            new \Twig_SimpleFunction('datatable_js', array($this, 'datatableJs'),
                    array("is_safe" => array("html"), 'needs_environment' => true))
        );
    }

    /**
     * Converts a string to time
     *
     * @param string $string
     * @return int
     */
    public function datatable(\Twig_Environment $twig, $options)
    {
        $options = $this->buildDatatableTemplate($options);

        $mainTemplate = array_key_exists('main_template', $options) ? $options['main_template'] : 'WaldoDatatableBundle:Main:index.html.twig';

        return $twig->render($mainTemplate, $options);
    }

    /**
     * Converts a string to time
     *
     * @param string $string
     * @return int
     */
    public function datatableJs(\Twig_Environment $twig, $options)
    {
        $options = $this->buildDatatableTemplate($options, "js");

        $mainTemplate = array_key_exists('main_template', $options) ? $options['js_template'] : 'WaldoDatatableBundle:Main:datatableJs.html.twig';

        return $twig->render($mainTemplate, $options);
    }

    /**
     * Converts a string to time
     *
     * @param string $string
     * @return int
     */
    public function datatableHtml(\Twig_Environment $twig, $options)
    {
        if (!isset($options['id'])) {
            $options['id'] = 'ali-dta_' . md5(mt_rand(1, 100));
        }
        $dt = Datatable::getInstance($options['id']);

        $options['action'] = $dt->getHasAction();
        $options['action_twig'] = $dt->getHasRendererAction();
        $options['fields'] = $dt->getFields();
        $options['search'] = $dt->getSearch();
        $options['search_fields'] = $dt->getSearchFields();
        $options['multiple'] = $dt->getMultiple();

        $mainTemplate = 'WaldoDatatableBundle:Main:datatableHtml.html.twig';

        if (isset($options['html_template'])) {
            $mainTemplate = $options['html_template'];
        }

        return $twig->render($mainTemplate, $options);
    }

    private function buildDatatableTemplate($options, $type = null)
    {
        if (!isset($options['id'])) {
            $options['id'] = 'ali-dta_' . md5(mt_rand(1, 100));
        }

        $dt = Datatable::getInstance($options['id']);
        $config = $dt->getConfiguration();
        $options['js_conf'] = json_encode($config['js']);
        $options['js'] = json_encode($options['js']);
        $options['action'] = $dt->getHasAction();
        $options['action_twig'] = $dt->getHasRendererAction();
        $options['fields'] = $dt->getFields();
        $options['delete_form'] = $this->createDeleteForm('_id_')->createView();
        $options['search'] = $dt->getSearch();
        $options['global_search'] = $dt->getGlobalSearch();
        $options['multiple'] = $dt->getMultiple();
        $options['sort'] = $dt->getOrderField() === null ? null : array(
            array_search($dt->getOrderField(), array_values($dt->getFields())),
            $dt->getOrderType()
        );

        if ($type == "js") {
            $options['not_filterable_fields'] = $dt->getNotFilterableFields();
            $options['not_sortable_fields'] = $dt->getNotSortableFields();
            $options['hidden_fields'] = $dt->getHiddenFields();
        }

        return $options;
    }

    /**
     * create delete form
     *
     * @param type $id
     * @return type
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
                        ->add('id', 'hidden')
                        ->getForm();
    }

    /**
     * create form builder
     *
     * @param type $data
     * @param array $options
     * @return type
     */
    public function createFormBuilder($data = null, array $options = array())
    {
        return $this->formFactory->createBuilder('form', $data, $options);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'DatatableBundle';
    }

}
