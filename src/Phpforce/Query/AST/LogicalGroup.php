<?php
namespace Phpforce\Query\AST;

class LogicalGroup extends LogicalUnit
{
    /**
     * @var LogicalUnit
     */
    public $firstChild;
}