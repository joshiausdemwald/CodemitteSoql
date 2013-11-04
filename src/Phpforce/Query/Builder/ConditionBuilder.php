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
     * @var AST\LogicalGroup
     */
    private $logicalGroup;

    /**
     * @param QueryBuilder $queryBuilder
     */
    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @param string $soql
     *
     * @return ConditionBuilder
     */
    public function set($soql)
    {
        if($soql instanceof AST\LogicalGroup)
        {
            $this->logicalGroup = $soql;
        }
        else
        {
            $this->queryBuilder->getParser()->setSoql($soql);

            $this->logicalGroup = $this->queryBuilder->getParser()->parseLogicalGroup();
        }
        return $this;
    }

    /**
     * @param LogicalGroup[]|string $soql
     *
     * @return ConditionBuilder
     */
    public function aand($soql)
    {
        $group = null;

        if($soql instanceof AST\LogicalGroup)
        {
            $group = $soql;
        }
        else
        {
            $this->queryBuilder->getParser()->setSoql($soql);

            $group = $this->queryBuilder->getParser()->parseLogicalGroup('AND');
        }

        $group->logical = 'AND';

        $this->logicalGroup->conditions[] = $group;

        return $this;
    }

    /**
     * @param string $soql
     *
     * @return ConditionBuilder
     */
    public function oor($soql)
    {
        $group = null;

        if($soql instanceof AST\LogicalGroup)
        {
            $group = $soql;
        }
        else
        {
            $this->queryBuilder->getParser()->setSoql($soql);

            $group = $this->queryBuilder->getParser()->parseLogicalGroup('AND');
        }

        $group->logical = 'OR';

        $this->logicalGroup->conditions[] = $group;

        return $this;
    }

    /**
     * @return AST\LogicalGroup $logicalGroup
     */
    public function end()
    {
        return $this->logicalGroup;
    }

} 