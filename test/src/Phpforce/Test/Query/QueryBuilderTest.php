<?php
namespace Phpforce\Test\Query;

use Doctrine\Common\Cache\ArrayCache;
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
        //return new Parser(new Tokenizer(new EventDispatcher()), new FilesystemCache(__DIR__ . '/../../../../cache/', 'query'));
        return new Parser(new Tokenizer(new EventDispatcher()), new ArrayCache());
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
            //->withCache(new FilesystemCache(__DIR__ . '/../../../../cache/', 'metadata'))
            ->withCache(new ArrayCache())
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

        /*->with() // ConditionBuilder
            ->add('ding = nums') ConditionBuilder
            ->addAnd('ping = nums') ConditionBuilder
            ->addAndGroup() ChildConditionBuilder
                ->add('ping = nums') ChildConditionBuilder
                ->addOr('ping = nums') ChildConditionBuilder
                ->addOrGroup() ChildChildConditionBuilder
                    ->add('lala = lulul')->addOr('lili = lala') ChildChildConditionBuilder
                ->end() ChildConditionBuilder
            ->end() ConditionBuilder
        ->end() QueryBuilder*/

        $queryBuilder
            ->prepareStatement()
                ->select('Id, GROUPING(trallala), dings, (SELECT dingsbums FROM account LIMIT 1)')
                ->addSelect('TYPEOF Account WHEN dings THEN bums END')
                ->addSelect('dingsbums')
                ->addSelect('kloing')
                ->from('Account a')
                ->where
                    ('NOT fjord1 = 4')
                    ->andCondition('(NOT Dings = 3) AND (NOT dings=5) AND (hans < 7 OR hans > 9)')
                    ->andCondition('fond = 1')
                    ->andGroup
                        ('ping = "pong"')
                        ->orCondition('ding = "tong"')
                    ->endGroup()
                ->end()
                ->withDataCategory
                    ('dings1 ABOVE bums__c')
                    ->andCondition('dings2 AT(usa__c, russia__c)')
                ->end()

                // ->end()
/*                ->groupby('Dings, nbums')
                ->having('COUNT(id) > 10')
                ->end()
                ->offset(10)
                ->orderBy('hans, COUNT(wurst)')
                ->limit(3)
                ->forReference()
                ->forView()*/
        ;


        print_r($queryBuilder->getAst());
        print_r($queryBuilder->execute());
    }
} 