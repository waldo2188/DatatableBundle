<?php

namespace Waldo\DatatableBundle\Util\Formatter;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

class Renderer
{
    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @var array
     */
    protected $renderers;

    /**
     * @var array
     */
    protected $fields;

    /**
     *  @var int
     */
    protected $identifierIndex;

    /**
     * class constructor
     *
     * @param ContainerInterface $container
     * @param array $renderers
     * @param array $fields
     */
    public function __construct(EngineInterface $templating)
    {
        $this->templating = $templating;
    }

    /**
     * Build the renderer
     *
     * @param array $renderers
     * @param array $fields
     * @return \Waldo\DatatableBundle\Util\Formatter\Renderer
     */
    public function build(array $renderers, array $fields)
    {
        $this->renderers = $renderers;
        $this->fields = $fields;
        $this->prepare();

        return $this;
    }

    /**
     * return the rendered view using the given content
     *
     * @param string    $view_path
     * @param array     $params
     *
     * @return string
     */
    public function applyView($view_path, array $params)
    {
        $out = $this->templating
                ->render($view_path, $params);
        
        return html_entity_decode($out);
    }

    /**
     * prepare the renderer :
     *  - guess the identifier index
     *
     * @return void
     */
    protected function prepare()
    {
        $this->identifierIndex = array_search("_identifier_", array_keys($this->fields));
    }

    /**
     * apply foreach given cell content the given (if exists) view
     *
     * @param array $data
     * @param array $objects
     *
     * @return void
     */
    public function applyTo(array &$data, array $objects)
    {
        foreach ($data as $row_index => $row) {
            $identifier_raw = $data[$row_index][$this->identifierIndex];
            foreach ($row as $column_index => $column) {
                $params = array();
                if (array_key_exists($column_index, $this->renderers)) {
                    $view = $this->renderers[$column_index]['view'];
                    $params = isset($this->renderers[$column_index]['params']) ? $this->renderers[$column_index]['params'] : array();
                } else {
                    $view = 'WaldoDatatableBundle:Renderers:_default.html.twig';
                }
                $params = array_merge($params,
                        array(
                    'dt_obj' => $objects[$row_index],
                    'dt_item' => $data[$row_index][$column_index],
                    'dt_id' => $identifier_raw,
                    'dt_line' => $data[$row_index]
                        )
                );
                $data[$row_index][$column_index] = $this->applyView(
                        $view, $params
                );
            }
        }
    }

}
