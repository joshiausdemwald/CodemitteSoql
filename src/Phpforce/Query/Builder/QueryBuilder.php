<?php
namespace Phpforce\Query\Builder;

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
     * @param string    $soql
     * @param boolean   $all
     *
     * @return RecordIterator $result
     */
    public function query($soql, $all = false)
    {
        if($all)
        {
            return $this->client->queryAll($this->renderer->render($this->parser->parse($soql)));
        }
        return $this->client->query($this->renderer->render($this->parser->parse($soql)));
    }
}