<?php
namespace Phpforce\Query\Builder;

use Phpforce\Query\AST;
use Phpforce\Query\Parser;

class ConditionBuilder
{
    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var ConditionBuilder
     */
    private $parent;

    /**
     * @var callable
     */
    private $method;

    /**
     * @var AST\LogicalGroup
     */
    private $ast;

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $method
     * @param ConditionBuilder|null $parent
     * @param string|null $class
     */
    public function __construct(QueryBuilder $queryBuilder, AST\LogicalGroup $ast, array $method, ConditionBuilder $parent = null, $class = null)
    {
        $this->queryBuilder = $queryBuilder;

        $this->ast          = $ast;

        $this->method = $method;

        $this->parent = $parent;
    }

    /**
     * @return ConditionBuilder
     */
    public function condition($soql, $logical = null)
    {
        $this->queryBuilder->getParser()->setSoql($soql);

        $group = \call_user_func($this->method);

        if(null === $logical)
        {
            $this->ast->conditions = $group->conditions;
        }
        else
        {
            $group->logical = $logical;

            $this->ast->conditions[] = $group;
        }
        return $this;
    }

    /**
     * @param $soql
     *
     * @return ConditionBuilder
     */
    public function andCondition($soql)
    {
        return $this->condition($soql, 'AND');
    }

    /**
     * @param $soql
     *
     * @return ConditionBuilder
     */
    public function orCondition($soql)
    {
        return $this->condition($soql, 'OR');
    }

    /**
     * @return ConditionBuilder
     */
    public function group($logical = null)
    {
        $childBuilder = new ConditionBuilder($this->queryBuilder, new AST\LogicalGroup($logical), $this->method, $this);

        return $childBuilder;
    }

    /**
     * @return ConditionBuilder
     */
    public function andGroup()
    {
        return $this->group('AND');
    }

    /**
     * @return ConditionBuilder
     */
    public function orGroup()
    {
        return $this->group('OR');
    }

    /**
     * @return ConditionBuilder
     */
    public function endGroup()
    {
        $this->parent->ast->conditions[] = $this->ast;

        return $this->parent;
    }

    /**
     * @return QueryBuilder
     */
    public function end()
    {
        return $this->queryBuilder;
    }
}