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
     * @var AST\LogicalUnit
     */
    private $ast;

    /**
     * @var AST\LogicalGroup
     */
    private $root;

    /**
     * @var string
     */
    private $logical;

    /**
     * @param QueryBuilder          $queryBuilder
     * @param callable              $method
     * @param ConditionBuilder      $parent
     * @param string|null           $logical
     */
    public function __construct(QueryBuilder $queryBuilder, array $method, ConditionBuilder $parent = null, $logical = null)
    {
        $this->queryBuilder = $queryBuilder;

        $this->method       = $method;

        $this->parent       = $parent;

        $this->logical      = $logical;

        $this->root         = $this->ast = new AST\LogicalGroup();

        $this->root->logical = $logical;
    }

    /**
     * @param string    $soql
     * @param string    $logical
     *
     * @return ConditionBuilder
     */
    public function condition($soql)
    {
        $this->ast = $this->root->firstChild = $this->buildCondition($soql);

        return $this;
    }

    /**
     * @param $soql
     *
     * @return ConditionBuilder
     */
    public function andCondition($soql)
    {
        $this->ast = $this->ast->next = $this->buildCondition($soql, 'AND');

        return $this;
    }

    /**
     * @param $soql
     *
     * @return ConditionBuilder
     */
    public function orCondition($soql)
    {
        $this->ast = $this->ast->next = $this->buildCondition($soql, 'OR');

        return $this;
    }

    /**
     * @param $soql
     *
     * @return ConditionBuilder
     */
    public function notCondition($soql)
    {
        $this->ast = $this->ast->next = $this->buildCondition($soql, 'NOT');

        return $this;
    }

    /**
     * @param string $soql
     * @param string|null $logical
     *
     * @return LogicalUnit
     */
    private function buildCondition($soql, $logical = null)
    {
        $this->queryBuilder->getParser()->setSoql($soql);
        $unit = \call_user_func($this->method);

        // e.g. "NOT"-PRÄFIX
        if($logical && $unit->logical)
        {
            $group = new AST\LogicalGroup();
            $group->firstChild = $unit;
            $unit = $group;
        }
        $unit->logical = $logical;

        return $unit;
    }

    /**
     * @param  string $logical
     *
     * @return ConditionBuilder
     */
    public function group($soql = null)
    {
        return $this->getChildBuilder($soql);
    }

    /**
     * @return ConditionBuilder
     */
    public function andGroup($soql = null)
    {
        return $this->getChildBuilder($soql, 'AND');
    }

    /**
     * @return ConditionBuilder
     */
    public function orGroup($soql = null)
    {
        return $this->getChildBuilder($soql, 'OR');
    }

    /**
     * @return ConditionBuilder
     */
    public function notGroup($soql = null)
    {
        return $this->getChildBuilder($soql, 'NOT');
    }

    /**
     * @param string|null   $soql
     * @param string|null   $logical
     *
     * @return ConditionBuilder
     */
    private function getChildBuilder($soql = null, $logical = null)
    {
        $builder = new ConditionBuilder($this->queryBuilder, $this->method, $this, $logical);

        if($soql)
        {
            $builder->condition($soql);
        }
        return $builder;
    }

    /**
     * @return ConditionBuilder
     */
    public function endGroup()
    {
        $this->parent->ast = $this->parent->ast->next = $this->root;

        return $this->parent;
    }

    /**
     * @return QueryBuilder
     */
    public function end()
    {
        return $this->queryBuilder;
    }

    /**
     * @return AST\LogicalUnit
     */
    public function getAST()
    {
        return $this->root;
    }
}