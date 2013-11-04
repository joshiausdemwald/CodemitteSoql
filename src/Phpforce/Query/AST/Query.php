<?php
namespace Phpforce\Query\Soql\AST;


class Query extends Node
{
    /**
     * @var Typeof[]|Field[]|Subquery[]
     */
    public $select = array();

    /**
     * @var FromPart
     */
    public $from;

    /**
     * @var LogicalGroup[]
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