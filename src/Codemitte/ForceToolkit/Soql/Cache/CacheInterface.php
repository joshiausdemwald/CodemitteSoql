<?php
/**
 * Created by PhpStorm.
 * User: joshi
 * Date: 21.10.13
 * Time: 12:58
 */

namespace Codemitte\ForceToolkit\Soql\Cache;

use Codemitte\ForceToolkit\Soql\AST\Node;

/**
 * Class CacheInterface
 * @package Codemitte\ForceToolkit\Soql
 */
interface CacheInterface
{
    /**
     * @param $soql
     * @return Node|null
     */
    function get($soql);

    /**
     * @param $soql
     * @param Node $node
     * @return void
     */
    function set($soql, Node $node);
}