<?php
namespace Phpforce\Query\AST;

class Query extends Node
{
    /**
     * @var Select
     */
    public $select;

    /**
     * @var From
     */
    public $from;

    /**
     * @var Where
     */
    public $where;

    /**
     * @var With
     */
    public $with;

    /**
     * @var WithDataCategory
     */
    public $withDataCategory;

    /**
     * @var GroupBy
     */
    public $groupBy;

    /**
     * @var Having
     */
    public $having;

    /**
     * @var int
     */
    public $limit;

    /**
     * @var int
     */
    public $offset;

    /**
     * @var string
     */
    public $for;

    /**
     * @var string
     */
    public $update;
} 