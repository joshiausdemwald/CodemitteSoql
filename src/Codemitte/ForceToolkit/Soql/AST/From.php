<?php
/**
 * Created by PhpStorm.
 * User: joshi
 * Date: 17.10.13
 * Time: 12:11
 */

namespace Codemitte\ForceToolkit\Soql\AST;


class From extends Leaf
{
    /**
     * @var Alias
     */
    public $alias;

    /**
     * @param string $value
     * @param null|Alias $alias
     */
    public function __construct($value, $alias = null)
    {
        parent::__construct($value);

        $this->alias = $alias;
    }
} 