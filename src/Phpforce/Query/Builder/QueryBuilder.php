<?php
namespace Phpforce\Query\Builder;

use Phpforce\Query\AST;
use Phpforce\Query\Parser;
use Phpforce\Query\Renderer\Renderer;
use Phpforce\SoapClient\ClientInterface;
use Phpforce\SoapClient\Result;
use Phpforce\SoapClient\Soap\SoapConnection;

class QueryBuilder implements ClientInterface
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
    public function prepareStatement($soql = null)
    {
        if($soql)
        {
            return $this->query($soql);
        }

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

        $this->ast->select = $this->parser->parseSelect();

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

        $this->ast->select->fields = array_merge($this->ast->select->fields, $this->parser->parseSelect());

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
        $conditionBuilder = new ConditionBuilder($this, array($this->getParser(), 'parseWhere'));

        $this->ast->where = new AST\Where($conditionBuilder->getAST());

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
        if($this->ast->withDataCategory)
        {
            throw new QueryBuilderException('Only WITH or WITH DATA CATEGORY statements allowed..');
        }

        $conditionBuilder = new ConditionBuilder($this, array($this->getParser(), 'parseWith'));

        $this->ast->with = new AST\With($conditionBuilder->getAST());

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
    public function withDataCategory($soql = null)
    {
        if($this->ast->with)
        {
            throw new QueryBuilderException('Only WITH or WITH DATA CATEGORY statements allowed..');
        }
        $conditionBuilder = new ConditionBuilder($this, array($this->getParser(), 'parseWithDataCategory'));

        $this->ast->withDataCategory = new AST\WithDataCategory($conditionBuilder->getAST());

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
        $conditionBuilder = new ConditionBuilder($this, array($this->getParser(), 'parseHaving'));

        $this->ast->having = new AST\Having($conditionBuilder->getAST());

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

    /**
     * @param string $soql
     *
     * @return Result\RecordIterator
     */
    public function query($soql = null)
    {
        return $this->client->query($this->renderer->render($this->buildQuery($soql)));
    }

    /**
     * {@inheritdoc}
     */
    public function queryAll($soql = null)
    {
        return $this->client->queryAll($this->renderer->render($this->buildQuery($soql)));
    }

    /**
     * {@inheritdoc}
     */
    public function queryMore($queryLocator)
    {
        return $this->client->queryMore($queryLocator);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve(array $fields, array $ids, $objectType)
    {
        return $this->client->retrieve($fields, $ids, $objectType);
    }

    /**
     * {@inheritdoc}
     */
    private function buildQuery($soql = null)
    {
        if(null !== $soql)
        {
            $this->parser->setSoql($soql);

            $this->ast = $this->parser->parseQuery();
        }
        return $this->ast;
    }

    /**
     * {@inheritdoc}
     */
    public function convertLead(array $leadConverts)
    {
        return $this->client->convertLead($leadConverts);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $objects, $objectType)
    {
        return $this->client->create($objects, $objectType);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(array $ids)
    {
        return $this->client->delete($ids);
    }

    /**
     * {@inheritdoc}
     */
    public function describeGlobal()
    {
        return $this->client->describeGlobal();
    }

    /**
     * {@inheritdoc}
     */
    public function describeSObjects(array $objectNames)
    {
        return $this->client->describeSObjects($objectNames);
    }

    /**
     * {@inheritdoc}
     */
    public function describeTabs()
    {
        return $this->client->describeTabs();
    }

    /**
     * {@inheritdoc}
     */
    public function emptyRecycleBin(array $ids)
    {
        return $this->client->emptyRecycleBin($ids);
    }

    /**
     * {@inheritdoc}
     */
    public function getDeleted($objectType, \DateTime $startDate, \DateTime $endDate)
    {
        return $this->client->getDeleted($objectType, $startDate, $endDate);
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdated($objectType, \DateTime $startDate, \DateTime $endDate)
    {
        return $this->client->getUpdated($objectType, $startDate, $endDate);
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateSessions(array $sessionIds)
    {
        return $this->invalidateSessions($sessionIds);
    }

    /**
     * {@inheritdoc}
     */
    public function login($username, $password, $token)
    {
        return $this->client->login($username, $password, $token);
    }

    /**
     * {@inheritdoc}
     */
    public function logout()
    {
        return $this->client->logout;
    }

    /**
     * {@inheritdoc}
     */
    public function merge(array $mergeRequests, $objectType)
    {
        return $this->client->merge($mergeRequests, $objectType);
    }

    /**
     * {@inheritdoc}
     */
    public function process(array $processRequests)
    {
        return $this->client->process($processRequests);
    }

    /**
     * {@inheritdoc}
     */
    public function search($searchString)
    {
        return $this->client->search($searchString);
    }

    /**
     * {@inheritdoc}
     */
    public function undelete(array $ids)
    {
        return $this->client->undelete($ids);
    }

    /**
     * {@inheritdoc}
     */
    public function update(array $objects, $objectType)
    {
        return $this->client->update($objectType, $objectType);
    }

    /**
     * {@inheritdoc}
     */
    public function upsert($externalFieldName, array $objects, $objectType)
    {
        return $this->client->upsert($externalFieldName, $objectType, $objectType);
    }

    /**
     * {@inheritdoc}
     */
    public function getServerTimestamp()
    {
        return $this->client->getServerTimestamp();
    }

    /**
     * {@inheritdoc}
     */
    public function getUserInfo()
    {
        return $this->client->getUserInfo();
    }

    /**
     * {@inheritdoc}
     */
    public function resetPassword($userId)
    {
        return $this->client->resetPassword($userId);
    }

    /**
     * {@inheritdoc}
     */
    public function sendEmail(array $emails)
    {
        return $this->client->sendEmail($emails);
    }

    /**
     * {@inheritdoc}
     */
    public function setPassword($userId, $password)
    {
        return $this->client->setPassword($userId, $password);
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection()
    {
        return $this->client->getConnection();
    }

    /**
     * {@inheritdoc}
     */
    public function setConnection(SoapConnection $connection)
    {
        return $this->client->setConnection($connection);
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(array(
            'client'    =>     serialize($this->client),
            'parser'    =>     serialize($this->parser),
            'renderer'  =>     serialize($this->renderer),
            'ast'       =>     serialize($this->ast)
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $unserialized = unserialize($serialized);

        $this->client       = unserialize($unserialized['client']);
        $this->parser       = unserialize($unserialized['parser']);
        $this->renderer     = unserialize($unserialized['renderer']);
        $this->ast          = unserialize($unserialized['ast']);
    }
}