<?php
/**
 * Created by PhpStorm.
 * User: joshi
 * Date: 07.11.13
 * Time: 12:37
 */

namespace Phpforce\Query\AST;


abstract class LogicalUnit extends Node
{
    /**
     * @var LogicalUnit
     */
    public $next;

    /**
     * @var string
     */
    public $logical;
} 