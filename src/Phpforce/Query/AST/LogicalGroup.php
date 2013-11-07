<?php
namespace Phpforce\Query\AST;

class LogicalGroup extends LogicalUnit
{
    /**
     * @var string
     */
    public $type;

    /**
     * @var LogicalUnit
     */
    public $firstChild;
}