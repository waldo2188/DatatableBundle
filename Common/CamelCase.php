<?php

namespace Waldo\DatatableBundle\Common;

/**
 * Convert String to camelCase String
 *
 * hello_wolrd => helloWorld
 * heLLo wolrd => helloWorld
 *
 * @author waldo
 */
trait CamelCase
{

    public function camelCase($str)
    {
        return lcfirst(str_replace(" ", "", ucwords(strtr($str, "_-", "  "))));

    }

}
