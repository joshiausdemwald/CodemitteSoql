<?php
/**
 * Created by PhpStorm.
 * User: joshi
 * Date: 17.10.13
 * Time: 12:04
 */

namespace Codemitte\ForceToolkit\Soql\AST;


class Query extends Node
{
    /**
     * @var array<Typeof|Field|Subquery>
     */
    public $select = array();

    /**
     * @var FromPart
     */
    public $from;

    /**
     * @var array<LogicalGroup>
     */
    public $where;

    public $with;

    public $groupBy;

    public $having;

    public $limit;

    public $offset;

    public $for;

    public $update;
} 