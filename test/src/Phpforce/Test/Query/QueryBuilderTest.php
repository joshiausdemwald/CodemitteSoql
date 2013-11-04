<?php
namespace Phpforce\Test\Query;

use Monolog\Logger;
use Doctrine\Common\Cache\FilesystemCache;
use Phpforce\Query\Builder\QueryBuilder;
use Phpforce\Query\Renderer\Renderer;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Phpforce\SoapClient\ClientBuilder;
use Phpforce\SoapClient\Soap\WSDL\Wsdl;
use Phpforce\Query\Parser;
use Phpforce\Query\Tokenizer;
use Phpforce\SoapClient\ClientInterface;

class QueryBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return Parser
     */
    public function newParser()
    {
        return new Parser(new Tokenizer(new EventDispatcher()), new FilesystemCache(__DIR__ . '/../../../../cache/', 'query'));
    }

    /**
     * @return ClientInterface
     */
    public function newClient()
    {
        $builder = new ClientBuilder(
            $wsdl = new Wsdl(__DIR__ . '/../../../../fixtures/partner.wsdl.xml'),
            SF_USERNAME,
            SF_PASSWORD,
            SF_SECURITY_TOKEN
        );

        return $builder
            ->withCache(new FilesystemCache(__DIR__ . '/../../../../cache/', 'metadata'))
            ->withLog(new Logger('phpforce'))
            ->build()
        ;
    }

    /**
     * @return Renderer
     */
    public function newRenderer()
    {
        return new Renderer();
    }

    /**
     * @return QueryBuilder
     */
    public function newQueryBuilder()
    {
        return new QueryBuilder($this->newClient(), $this->newParser(), $this->newRenderer());
    }

    /**
     * @test
     *
     * @return void
     */
    public function testQuery()
    {
        $queryBuilder = $this->newQueryBuilder();

        $queryBuilder
            ->prepareStatement()
                ->select('Id, dings, (select dingsbums from account limit 1)')
                ->select('TYPEOF Account WHEN dings THEN bums END')
                ->select('dingsbums')
                ->select('kloing')
                ->from('Account a')
                ->where($queryBuilder->getConditionBuilder
                    ('witz > 3')
                    ->aand($queryBuilder->getConditionBuilder
                        ('dings > 3')
                        ->oor('bims < 4')
                        ->end()
                    )
                    ->end()
                )
                ->with($queryBuilder->getConditionBuilder
                    ("DATA CATEGORY nase BELOW wurst")
                    ->aand("DATA CATEGORY wurst ABOVE hans")
                    ->end()
                )
        ;
    }
} 