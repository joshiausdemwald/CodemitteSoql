<?php
namespace Phpforce\Query\Builder;

use Codemitte\ForceToolkit\Soql\Tokenizer\Tokenizer;
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
        $this->parser->enterScope(Parser::SCOPE_SELECT);

        $this->parser->setSoql($selectSoql);

        $this->ast->select[] = $this->parser->parseSelectFieldList();

        return $this;
    }

    /**
     * @return QueryBuilder
     */
    public function from($object)
    {
        $this->parser->enterScope(Parser::SCOPE_FROM);

        $this->parser->setSoql($object);

        $this->ast->from = $this->parser->parseFrom();

        return $this;
    }

    /**
     * @param AST\LogicalGroup|string
     *
     * @return QueryBuilder
     */
    public function where($where)
    {
        $this->parser->enterScope(Parser::SCOPE_WHERE);

        $this->ast->where = new AST\Where($this->buildLogicalGroup($where));

        return $this;
    }

    /**
     * @param AST\LogicalGroup|string
     *
     * @return QueryBuilder
     */
    public function with($with)
    {
        $this->parser->enterScope(Parser::SCOPE_WITH);

        if($with instanceof AST\LogicalGroup)
        {
            $this->ast->with = new AST\With($this->getConditionBuilder($with)->end());
        }
        else
        {
            $this->parser->setSoql($with);

            $this->ast->with = $this->parser->parseWith();
        }
        print_r($this->ast->with);

        return $this;
    }

    public function groupby()
    {

    }

    public function having()
    {

    }

    public function offset()
    {

    }

    public function limit()
    {

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
     * @param AST\LogicalGroup|string $soql
     *
     * @return AST\LogicalGroup $logicalGroup
     */
    protected function buildLogicalGroup($soql, $logical = null)
    {
        $group = null;

        if($soql instanceof AST\LogicalGroup)
        {
            $group = $soql;
        }
        else
        {
            $group = $this->getConditionBuilder($soql)->end();
        }

        $group->logical = $logical;

        return $group;
    }

    /**
     * @param string $soql
     *
     * @return ConditionBuilder
     */
    public function getConditionBuilder($soql)
    {
        $conditionBuilder = new ConditionBuilder($this);

        return $conditionBuilder->set($soql);
    }

    /**
     * @return \Phpforce\Query\Parser
     */
    public function getParser()
    {
        return $this->parser;
    }
}