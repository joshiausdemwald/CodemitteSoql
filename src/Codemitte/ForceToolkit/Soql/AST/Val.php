<?php
/**
 * Created by PhpStorm.
 * User: joshi
 * Date: 17.10.13
 * Time: 12:08
 */

namespace Codemitte\ForceToolkit\Soql\AST;


class Val extends Leaf
{
    public $type;

    public function __construct($value, $type)
    {
        parent::__construct($value);

        $this->type = $type;
    }
} 