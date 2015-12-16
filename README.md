DatatableBundle
===============

Fork of [AliHichem/DatatableBundle](https://github.com/AliHichem/DatatableBundle), this bundle will add some great features
and evolve in a different way than it source.

[![Build Status](https://travis-ci.org/waldo2188/DatatableBundle.svg?branch=master)](https://travis-ci.org/waldo2188/DatatableBundle)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/bb7b64f6-4203-45ca-b99a-2d15c4d272ec/small.png)](https://insight.sensiolabs.com/projects/bb7b64f6-4203-45ca-b99a-2d15c4d272ec)

> **Warning**: The [jQuery Datatable plugin](http://datatables.net/) has evolved (version 1.10) with a all new API and option.  
> You **MUST** use the version **2** of DatatableBundle with the jQuery Datatable plugin version lower than 1.10.  
> You **MUST** use the version **3** of DatatableBundle with the jQuery Datatable plugin version equal or greater than 1.10.

The Datatable bundle for symfony2 allow for easily integration of the [jQuery Datatable plugin](http://datatables.net/) with
the doctrine2 entities.
This bundle provides a way to make a projection of a doctrine2 entity to a powerful jquery datagrid. It mainly includes:

 * datatable service container: to manage the datatable as a service.
 * twig extension: for view integration.
 * dynamic pager handler : no need to set your pager.
 * support doctrine2 association.
 * support of Doctrine Query Builder.
 * support of doctrine subquery.
 * support of column search.
 * support of custom twig/phpClosure renderers.

<div style="text-align:center"><img alt="Screenshot" src="/Resources/doc/images/sample_01.png"></div>

-------------------------------------
## Summary
- [Installation](#installation)
  1. [Download DatatableBundle using Composer](#Download-DatatableBundle-using-Composer)
  2. [Enable the Bundle](#Enable-the-Bundle)
  3. [Configure the Bundle](#Configure-the-Bundle)
- [How to use DatatableBundle ?](#how-to-use-datatablebundle-)
- [Rendering inside Twig](#rendering-inside-twig)
- [Advanced Use of DatatableBundle](#advanced-use-of-DatatableBundle)
- [Use of search filters](#use-of-search-filters)
  - [Activate search globally](#activate-search-globally)
  - [Set search fields](#set-search-fields)
- [Multiple actions, how to had checkbox for each row ?](#Multiple-actions-how-to-had-checkbox-for-each-row-)
- [Custom renderer for cells](#custom-renderer-for-cells)
- [Datatable Callbacks options](#datatable-callbacks-options)
- [Translation](#translation)
- [Doctrine Query Builder](#doctrine-query-builder)
- [Multiple datatable in the same view](#multiple-datatable-in-the-same-view)
- [Use specific jQuery Datatable options](#Use-specific-jQuery-Datatable-options)
- [Launch the test suite](/Resources/doc/test.md)

---------------------------------------

## Installation

Installation is a quick (I promise!) 3 step process:
  1. [Download DatatableBundle using Composer](#Download-DatatableBundle-using-Composer)
  2. [Enable the Bundle](#Enable-the-Bundle)
  3. [Configure the Bundle](#Configure-the-Bundle)

### Download DatatableBundle using Composer

#### Using Composer

Add DatatableBundle in your composer.json as below, and update with Composer :
```js
"require": {
    ...
    "waldo/datatable-bundle": "~3.0"
}
```
Or, update/install with this command : `composer require waldo/datatable-bundle ~3.0`

Generate the assets symlinks : 
```bash
php app/console assets:install --symlink web
```

### Enable the Bundle

Add the bundle to the `AppKernel.php`
```php
$bundles = array(
    \\...
    new Waldo\DatatableBundle\WaldoDatatableBundle(),
    )
```

### Configure the Bundle

In this section you can put the global config that you want to set for all the instance of DataTable in your project.

#### To keep it to default

```
# app/config/config.yml
waldo_datatable:
    all:    ~
    js:     ~
```

The `js` config will be applied to DataTable exactly like you do with `$().datatable({ your config });` in a javascript part.
> Note: all your js config have to be string typed, make sure to use (") as delimiters.

#### Config sample

```
waldo_datatable:
    all:
        search:           false
    js:
        pageLength: "10"
        lengthMenu: [[5,10, 25, 50, -1], [5,10, 25, 50, 'All']]
        dom: '<"clearfix"lf>rtip'
        jQueryUI: "false"
```

## How to use DatatableBundle ?

Assuming for example that you need a grid in your "index" action, create in your controller method as below :
```php
/**
 * set datatable configs
 * @return \Waldo\DatatableBundle\Util\Datatable
 */
private function datatable() {
    return $this->get('datatable')
                ->setEntity("XXXMyBundle:Entity", "x")                          // replace "XXXMyBundle:Entity" by your entity
                ->setFields(
                        array(
                            "Name"          => 'x.name',                        // Declaration for fields:
                            "Address"       => 'x.address',                    //      "label" => "alias.field_attribute_for_dql"
                            "Total"         => 'COUNT(x.people) as total',      // Use SQL commands, you must always define an alias
                            "Sub"           => '(SELECT i FROM ... ) as sub',   // you can set sub DQL request, you MUST ALWAYS define an alias
                            "_identifier_"  => 'x.id')                          // you have to put the identifier field without label. Do not replace the "_identifier_"
                        )
                ->setWhere(                                                     // set your dql where statement
                     'x.address = :address',
                     array('address' => 'Paris')
                )
                ->setOrder("x.created", "desc");                                // it's also possible to set the default order
}


/**
 * Grid action
 * @Route("/", name="datatable")
 * @return Response
 */
public function gridAction()
{
    return $this->datatable()->execute();                                      // call the "execute" method in your grid action
}

/**
 * Lists all entities.
 * @Route("/list", name="datatable_list")
 * @return Response
 */
public function indexAction()
{
    $this->datatable();                                                         // call the datatable config initializer
    return $this->render('XXXMyBundle:Module:index.html.twig');                 // replace "XXXMyBundle:Module:index.html.twig" by yours
}
```

## Rendering inside Twig

You have the choice, you can render the HTML table part and Javascript part in just one time with the Twig function `datatable`,
like below.

```twig
<!-- XXX\MyBundle\Resources\views\Module\index.html.twig -->

<!-- include the assets -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/r/dt/dt-1.10.9/datatables.min.css"/>
<script type="text/javascript" src="//code.jquery.com/jquery-2.1.4.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/r/dt/dt-1.10.9/datatables.min.js"></script>

{{ datatable({
        'js' : {
            'ajax' : path('route_for_your_datatable_action')
        }
    })
}}
```

Or, render each part separatly.

`datatable_html` is the Twig function for the HTML part.  
`datatable_js` is the Twig function for the Javascript part.

```twig
{% block body %}
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/r/dt/dt-1.10.9/datatables.min.css"/>
    {{ datatable_html({
            'id' : 'dta-offres'
        })
    }}

{% endblock %}

{% block javascripts %}
<script type="text/javascript" charset="utf8" src="//code.jquery.com/jquery-1.10.2.min.js"></script>
<script type="text/javascript" charset="utf8" src="//cdn.datatables.net/1.10.9/js/jquery.dataTables.js"></script>
{{ datatable_js({
        'id' : 'dta-offres',
        'js' : {
            'dom': '<"clearfix"lf>rtip',
            'ajax': path('route_for_your_datatable_action'),
        }
    })
}}
{% endblock javascripts %}
```

## Advanced Use of DatatableBundle

### Advanced php config

Assuming the example above, you can add your joins and where statements.

```php
/**
 * set datatable configs
 *
 * @return \Waldo\DatatableBundle\Util\Datatable
 */
private function datatable()
{
    return $this->get('datatable')
                ->setEntity("XXXMyBundle:Entity", "x")      // replace "XXXMyBundle:Entity" by your entity
                ->setFields(
                        array(
                            "Name"          => 'x.name',    // Declaration for fields:
                            "Address"       => 'x.address', // "label" => "alias.field_attribute_for_dql"
                            "Group"         => 'g.name',
                            "Team"          => 't.name',
                            "_identifier_"  => 'x.id')      // you have to put the identifier field without label. Do not replace the "_identifier_"
                        )
                ->addJoin('x.group', 'g', \Doctrine\ORM\Query\Expr\Join::INNER_JOIN)
                ->addJoin('x.team', 't', \Doctrine\ORM\Query\Expr\Join::LEFT_JOIN)
                ->addJoin('x.something', 's', \Doctrine\ORM\Query\Expr\Join::LEFT_JOIN, \Doctrine\ORM\Query\Expr\Join::WITH, 's.id = :someId')
                ->setWhere(                                // set your dql where statement
                     'x.address = :address',
                     array('address' => 'Paris')
                )
                ->setOrder("x.created", "desc")            // it's also possible to set the default order.
                ->setParameter('someId', 12)
                ;
}
```

### Use of search filter
*  [Activate search globally](#activate-search-globally)
*  [Set search fields](#set-search-fields)

#### Activate search globally

The searching functionality that is very useful for quickly search through the information from the database.
This bundle provide two way of searching, who can be used together : **global search** and **individual column search**.

By default the filtering functionality is disabled, to get it working you just need to activate it from your configuration method like this :

```php
private function datatable()
{
    return $this->get('datatable')
                //...
                ->setSearch(true); // for individual column search
                // or
                ->setGlobalSearch(true);
}
```
#### Set search fields

You can set fields where you want to enable your search.
Let say you want search to be active only for "field 1" and "field3 ", you just need to activate search for the approriate column key
and your datatable config should be :

```php
/**
 * set datatable configs
 *
 * @return \Waldo\DatatableBundle\Util\Datatable
 */
private function datatable()
{
    $datatable = $this->get('datatable');
    return $datatable->setEntity("XXXMyBundle:Entity", "x")
                    ->setFields(
                            array(
                                "label of field 1" => 'x.field1',   // column key 0
                                "label of field 2" => 'x.field2',   // column key 1
                                "label of field 3" => 'x.field3',   // column key 2
                                "_identifier_" => 'x.id')          // column key 3
                    )
                    ->setSearch(true)
                    ->setSearchFields(array(0,2))
    ;
}
```

### Multiple actions, how to had checkbox for each row ?

Sometimes, it's good to be able to do the same action on multiple records like deleting, activating, moving ...
Well this is very easy to add to your datatable: all what you need is to declare your multiple action as follow.

```php
/**
 * set datatable configs
 * @return \Waldo\DatatableBundle\Util\Datatable
 */
private function datatable()
{
    $datatable = $this->get('datatable');
    return $datatable->setEntity("XXXMyBundle:Entity", "x")
                    ->setFields(
                            array(
                                "label of field1" => 'x.field1',   // column key 0
                                "label of field2" => 'x.field2',   // column key 1
                                "_identifier_" => 'x.id')          // column key 2
                    )
                    ->setMultiple(
                                array(
                                    'delete' => array(
                                        'title' => 'Delete',
                                        'route' => 'multiple_delete_route' // path to multiple delete route action
                                    ),
                                    'move' => array(
                                        'title' => 'Move',
                                        'route' => 'multiple_move_route' // path to multiple move route action
                                    ),
                                )
                        )
    ;
}
```

Then all what you have to do is to add the necessary logic in your "multiple_delete_route" (or whatever your route is for).
In that action, you can get the selected ids by :

```php
$data = $this->getRequest()->get('dataTables');
$ids  = $data['actions'];
```

### Custom renderer for cells

#### Twig renderers

To set your own column structure, you can use a custom twig renderer as below :
In this example you can find how to set the use of the default twig renderer for action fields which you can override as
your own needs.

```php
/**
 * set datatable configs
 * @return \Waldo\DatatableBundle\Util\Datatable
 */
private function datatable()
{
    $datatable = $this->get('datatable');
    return $datatable->setEntity("XXXMyBundle:Entity", "x")
                    ->setFields(
                            array(
                                "label of field1" => 'x.field1',
                                "label of field2" => 'x.field2',
                                "_identifier_" => 'x.id')
                    )
                    ->setRenderers(
                            array(
                                2 => array(
                                    'view' => 'XXXMyBundle:Renderers:_actions.html.twig', // Path to the template
                                    'params' => array( // All the parameters you need (same as a twig template)
                                            'edit_route'    => 'route_edit',
                                            'delete_route'  => 'route_delete'
                                        ),
                                ),
                            )
                    );
}
```

In a twig renderer you can have access the the field value using `dt_item` variable,
```
// XXXMyBundle:Renderers:_actions.html.twig
{{ dt_item }}
```
or access the entire entity object using `dt_obj` variable.
```
// XXXMyBundle:Renderers:_actions.html.twig
<a href="{{ path('route_to_user_edit',{ 'user_id' : dt_obj.id }) }}">{{ dt_obj.username }}</a>
```

> NOTE: be careful of Doctrine's LAZY LOADING when using dt_obj !

#### PHP Closures

Assuming the example above, you can set your custom fields renderer using [PHP Closures](http://php.net/manual/en/class.closure.php).

```php
/**
 * set datatable configs
 * @return \Waldo\DatatableBundle\Util\Datatable
 */
private function datatable() {

    $controller_instance = $this;
    return $this->get('datatable')
                ->setEntity("XXXMyBundle:Entity", "x")          // replace "XXXMyBundle:Entity" by your entity
                ->setFields(
                        array(
                            "Name"          => 'x.name',        // Declaration for fields:
                            "Address"        => 'x.address',    // "label" => "alias.field_attribute_for_dql"
                            "_identifier_"  => 'x.id')          // you have to put the identifier field without label. Do not replace the "_identifier_"
                        )
                ->setRenderer(
                    function(&$data) use ($controller_instance) {
                        foreach ($data as $key => $value) {
                            if ($key == 1) {            // 1 => address field
                                $data[$key] = $controller_instance
                                        ->get('templating')
                                        ->render(
                                               'XXXMyBundle:Module:_grid_entity.html.twig',
                                               array('data' => $value)
                                        );
                            }
                        }
                    }
                );
}
```

### DataTable Callbacks options
If you need to put some Javascript Callbacks like [`drawCallback`](http://datatables.net/reference/option/drawCallback), you can
do it localy with the Datatable `js` option. See the two examples below:

```twig
// XXXMyBundle:Welcome:list.html.twig
{{ datatable_js({
        'id' : 'datable-id',
        'js' : {
            'ajax': "/some/path",
            'createdRow': 'function(){console.log("Do something useful here");}'
        }
    })
}}

{# or #}

{{ datatable_js({
        'id' : 'datable-id',
        'js' : {
            'ajax': "/some/path",
            'createdRow': 'myUsefullThing'
        }
    })
}}
<script type="text/javascript">
var myUsefullThing = function() {
    // Do something here
};
</script>
```

You can also define a Callback globally by setting it up in the `config.yml` like below :

```yaml
waldo_datatable:
    js:
        createdRow: |
            function(){console.log("Do something useful here");}

```


### Translation

You can set your own translated labels by adding in your translation catalog entries as define in `Resources/translations/messages.en.yml`

You can also get the translated labels from *official DataTable translation* repository, by configuring the bundle like below :
```yaml
waldo_datatable:
    all:    ~
    js:
        language:
            url: "//cdn.datatables.net/plug-ins/1.10.9/i18n/Chinese.json"
```

This bundle includes nine translation catalogs: Arabic, Chinese, Dutch, English, Spanish, French, Italian, Polish, Russian and Turkish
To get more translated entries, you can follow the [official DataTable translation](https://datatables.net/manual/i18n)


### Doctrine Query Builder

To use your own query object to supply to the datatable object, you can perform this action using your own
"Doctrine Query object": DatatableBundle allow to manipulate the query object provider which is now a Doctrine Query Builder object,
you can use it to update the query in all its components except of course in the selected field part.

This is a classic config before using the Doctrine Query Builder:

```php
private function datatable()
{
    $datatable = $this->get('datatable')
                ->setEntity("XXXBundle:Entity", "e")
                ->setFields(
                        array(
                            "column1 label" => 'e.column1',
                            "_identifier_" => 'e.id')
                        )
                ->setWhere(
                    'e.column1 = :column1',
                    array('column1' => '1' )
                )
                ->setOrder("e.created", "desc");

     $qb = $datatable->getQueryBuilder()->getDoctrineQueryBuilder();
     // This is the Doctrine Query Builder object, you can
     // retrieve it and include your own change

     return $datatable;
}
```

This is a config that uses a Doctrine Query object a query builder :

```php
private function datatable()
{
    $qb = $this->getDoctrine()->getEntityManager()->createQueryBuilder();
    $qb->from("XXXBundle:Entity", "e")
       ->where('e.column1 = :column1')
       ->setParameters(array('column1' = 0))
       ->orderBy("e.created", "desc");

    $datatable = $this->get('datatable')
                ->setFields(
                        array(
                            "Column 1 label" => 'e.column1',
                            "_identifier_" => 'e.id')
                        );

    $datatable->getQueryBuilder()->setDoctrineQueryBuilder($qb);

    return $datatable;
}
```

### Multiple DataTable in the same view

To declare multiple DataTables in the same view, you have to set the datatable identifier in you controller with `setDatatableId` :
Each of your DataTable config methods ( datatable() , datatable_1() .. datatable_n() ) needs to set the same identifier used in your view:

#### In the controller

```php
protected function datatable()
{
    // ...
    return $this->get('datatable')
                ->setDatatableId('dta-unique-id_1')
                ->setEntity("XXXMyBundle:Entity", "x")
    // ...
}

protected function datatableSecond()
{
    // ...
    return $this->get('datatable')
                ->setDatatableId('dta-unique-id_2')
                ->setEntity("YYYMyBundle:Entity", "y")
    // ...
}
```

#### In the view

```js
{{
    datatable({
        'id' : 'dta-unique-id_1',
        ...
            'js' : {
            'ajax' : path('route_for_your_datatable_action_1')
            }
    })
}}

{{
    datatable({
        'id' : 'dta-unique-id_2',
        ...
        'js' : {
            'ajax' : path('route_for_your_datatable_action_2')
        }
    })
}}
```

## Use specific jQuery DataTable options

Some time we need to apply some specific options to a grid, like a specific width for the second column.
DataTable comes with a [lot a feature](http://datatables.net/reference/option/) that you can always use, even with this bundle.

In the code below, we use the `columnDefs` option to fix the width of the second column.
```js
{{
    datatable({
        'id' : 'dta-id',
        'js' : {
            'ajax' : path('route_for_your_datatable_action'),
            'columnDefs': [
                { "width": "15%", "targets": 1 }
            ]
        }
    })
}}
```

You really can play with all the DataTable's options.

