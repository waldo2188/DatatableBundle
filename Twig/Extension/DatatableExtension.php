<?php

namespace Waldo\DatatableBundle\Twig\Extension;

use Symfony\Component\Form\FormFactoryInterface;
use Waldo\DatatableBundle\Util\Datatable;

class DatatableExtension extends \Twig_Extension
{

    /**
     * Any value referenced here are Datatable's options giving the value is an object or an array
     *
     * @see https://datatables.net/reference/option/
     * @var array
     */
    private $datatableObjectValuedOption = array(
            "lengthMenu"
        );

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
                    array("is_safe" => array("html"), 'needs_environment' => true)),
            new \Twig_SimpleFunction('datatable_string_option', array($this, 'datatableStringOption'),
                    array("is_safe" => array("html")))
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

        $options['fields'] = $dt->getFields();
        $options['search'] = $dt->getSearch();
        $options['searchFields'] = $dt->getSearchFields();
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

        $options['js'] = array_merge($options['js'], $config['js']);
        $options['fields'] = $dt->getFields();
        $options['delete_form'] = $this->createDeleteForm('_id_')->createView();
        $options['search'] = $dt->getSearch();
        $options['global_search'] = $dt->getGlobalSearch();
        $options['multiple'] = $dt->getMultiple();
        $options['searchFields'] = $dt->getSearchFields();
        $options['sort'] = $dt->getOrderField() === null ? null : array(
            array_search($dt->getOrderField(), array_values($dt->getFields())),
            $dt->getOrderType()
        );



        if ($type == "js") {
            $options['fieldsOptions'] = array(
                array(
                    "type" => "visible",
                    "value" => "false",
                    "target" => $dt->getHiddenFields()
                ),
                array(
                    "type" => "orderable",
                    "value" => "false",
                    "target" => $dt->getNotSortableFields()
                ),
                array(
                    "type" => "searchable",
                    "value" => "false",
                    "target" => $dt->getNotFilterableFields()
                )
            );
        }

        return $options;
    }

    /**
     * Some Datatable's option need to be surronded by an apostrophe an other need to be print as raw, like an array.
     *
     * @param string $optionName
     * @param mix $value
     * @return string
     */
    public function datatableStringOption($optionName, $value)
    {
        if(is_bool($value)) {
            if($value === true) {
                return 'true';
            } else {
                return 'false';
            }
        }

        if(in_array($optionName, $this->datatableObjectValuedOption) || is_int($value)) {
            return $value;
        }

        return sprintf("'%s'", $value);
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
