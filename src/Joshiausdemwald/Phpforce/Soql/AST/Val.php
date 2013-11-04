<?php
namespace Joshiausdemwald\Phpforce\Soql\AST;


class Val extends Leaf
{
    public $type;

    public function __construct($value, $type)
    {
        parent::__construct($value);

        $this->type = $type;
    }
} 