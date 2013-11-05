<?php
namespace Phpforce\Query\Builder;

use Phpforce\Query\AST;
use Phpforce\Query\Parser;
use Phpforce\Query\Renderer\Renderer;
use Phpforce\SoapClient\ClientInterface;
use Phpforce\SoapClient\Result\RecordIterator;

class QueryBuilder
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * @var AST\Query|null
     */
    private $ast;

    /**
     * @param ClientInterface $client
     * @param Parser $parser
     */
    public function __construct(ClientInterface $client, Parser $parser, Renderer $renderer)
    {
        $this->client = $client;

        $this->parser = $parser;

        $this->renderer = $renderer;
    }

    /**
     * @return QueryBuilder
     */
    public function prepareStatement()
    {
        $this->ast = new AST\Query();

        return $this;
    }

    /**
     * @param string    $selectSoql
     *
     * @return QueryBuilder
     */
    public function select($selectSoql)
    {
        $this->parser->setSoql($selectSoql);

        $this->ast->select = new AST\Select($this->parser->parseSelectFieldList());

        return $this;
    }

    /**
     * @param $selectSoql
     *
     * @return QueryBuilder
     */
    public function addSelect($selectSoql)
    {
        $this->parser->setSoql($selectSoql);

        $this->ast->select->fields = array_merge($this->ast->select->fields, $this->parser->parseSelectFieldList());

        return $this;
    }

    /**
     * @return QueryBuilder
     */
    public function from($object)
    {
        $this->parser->setSoql($object);

        $this->ast->from = $this->parser->parseFromField();

        return $this;
    }

    /**
     * @param string $soql
     *
     * @return ConditionBuilder
     */
    public function where($soql = null)
    {
        $this->ast->where = new AST\Where($group = new AST\LogicalGroup());

        $conditionBuilder = new ConditionBuilder($this, $group, array($this->getParser(), 'parseWhere'), null);

        if($soql)
        {
            $conditionBuilder->condition($soql);
        }

        return $conditionBuilder;
    }

    /**
     * @param string $soql
     *
     * @return ConditionBuilder
     */
    public function with($soql = null)
    {
        $this->ast->with = new AST\With($group = new AST\LogicalGroup());

        $conditionBuilder = new ConditionBuilder($this, $group, array($this->getParser(), 'parseWith'), null);

        if($soql)
        {
            $conditionBuilder->condition($soql);
        }
        return $conditionBuilder;
    }

    /**
     * @return QueryBuilder
     */
    public function groupby($soql)
    {
        $this->parser->setSoql($soql);

        $this->ast->groupBy = $this->parser->parseGroupBy();

        return $this;
    }

    /**
     * @param string $soql
     *
     * @return ConditionBuilder
     */
    public function having($soql = null)
    {
        $this->ast->having = new AST\Having($group = new AST\LogicalGroup());

        $conditionBuilder = new ConditionBuilder($this, $group, array($this->getParser(), 'parseHaving'), null);

        if($soql)
        {
            $conditionBuilder->condition($soql);
        }

        return $conditionBuilder;
    }

    /**
     * @param $soql
     *
     * @return QueryBuilder
     */
    public function orderBy($soql)
    {
        $this->parser->setSoql($soql);

        $this->ast->orderBy = new AST\OrderBy($this->parser->parseOrderBy());

        return $this;
    }

    /**
     * @param int $offset
     *
     * @return QueryBuilder
     */
    public function offset($offset)
    {
        $this->ast->offset = $offset;

        return $this;
    }

    /**
     * @param int $offset
     *
     * @return QueryBuilder
     */
    public function limit($limit)
    {
        $this->ast->limit = $limit;

        return $this;
    }

    /**
     * @return QueryBuilder
     */
    public function forView()
    {
        $this->ast->for = 'VIEW';

        return $this;
    }

    /**
     * @return QueryBuilder
     */
    public function forReference()
    {
        $this->ast->for = 'REFERENCE';

        return $this;
    }

    /**
     * @return QueryBuilder
     */
    public function updateViewstat()
    {
        $this->ast->update = 'VIEWSTAT';

        return $this;
    }

    /**
     * @param array     $params
     * @param boolean   $all
     *
     * @return RecordIterator
     */
    public function execute(array $params = array(), $all = false)
    {
        if($all)
        {
            return $this->client->queryAll($this->renderer->render($this->ast));
        }
        return $this->client->query($this->renderer->render($this->ast));
    }

    /**
     * @return \Phpforce\Query\Parser
     */
    public function getParser()
    {
        return $this->parser;
    }

    /**
     * @return null|\Phpforce\Query\AST\Query
     */
    public function getAst()
    {
        return $this->ast;
    }

    /**
     * @return \Phpforce\SoapClient\ClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return \Phpforce\Query\Renderer\Renderer
     */
    public function getRenderer()
    {
        return $this->renderer;
    }
}