<?php
namespace Phpforce\Test\Query;

use Doctrine\Common\Cache\ArrayCache;
use Phpforce\Query\AST\LogicalGroup;
use Phpforce\Query\AST\LogicalCondition;
use Phpforce\Query\AST\Query;
use Phpforce\Query\AST\Val;
use Phpforce\Query\AST\Where;
use Phpforce\Query\AST\Field;
use Phpforce\Query\Builder\Type\Currency;
use Phpforce\Query\Parser;
use Phpforce\Query\Renderer\Renderer;
use Phpforce\Query\Tokenizer;
use Monolog\Logger;
use Phpforce\SoapClient\Metadata\CacheWarmer;
use Phpforce\SoapClient\ClientFactory;
use Phpforce\SoapClient\Soap\WSDL\Wsdl;
use Symfony\Component\EventDispatcher\EventDispatcher;

class TokenizerTest extends \PHPUnit_Framework_TestCase
{
    public function newParser($soql)
    {
        $parser = new Parser(new Tokenizer(new EventDispatcher()), new ArrayCache(__DIR__ . '/../../../../cache/', 'query'));

        $parser->setSoql($soql);

        return $parser;
    }

    protected function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub

        date_default_timezone_set('Europe/Berlin');
    }


    public function testRenderer()
    {
        $ast = $this->newParser(
            "SELECT hour_in_day(convertTimezone(dingsda)), dings, COUNT(hanswurst) AS nn,
            (SELECT Id FROM hanswurst WHERE Pimgs IN :pomsg),
            (SELECT COUNT() FROM account WHERE DAY_IN_MONTH(name) = 2),
            (SELECT Id AS aadf, Account.Id, Account.Name FROM dings__R WHERE dings__r > EUR2141.3),
pums, ping p, ping__c AS pong,
TYPEOF
    hans WHEN Account THEN hans.ping AS p
    WHEN Dings THEN hans.pong WHEN kases THEN pingpong, putz, wutz
    ELSE hans.pusle
END,
dings__c,
TYPEOF
  dings__c WHEN Account THEN ping END,
TYPEOF
  kasese WHEN Account THEN ping,pong ELSE pum END
FROM
  dingsds
WHERE
 dings IN ('kaesespaetzle', 'wursti') AND DAY_IN_MONTH(mydatefield__c) = 'sadg' AND
    DINGDONG = 2.3214 AND
    (gans < 3 OR ganz > 5) AND
 dings__c INCLUDES ('hans', 'hans;wurst', 'käses') AND
    (PING = :pong OR ping__c = :pung) AND
 LONGLONG IN (SELECT Id FROM Hanswurst WHERE ping = NULL AND pong.Type = 'einzzweidrei') AND
 hans IN :pungpung AND
    PING__c>=1 AND
    Date__c = LAST_N_DAYS:90

WITH DATA CATEGORY Geography__c ABOVE usa__c AND
    Product__c ABOVE_OR_BELOW mobile_phones__c AND
    Geography AT (usa__c, uk__c) AND Geography__c AT usa__c
 GROUP BY ROLLUP(hans, wurst, kaese)
 HAVING hans = :b AND Dings > 3.3 AND bing IN :pingong

 ORDER BY hanswi__c DESC NULLS LAST, pupsi__c ASC NULLS FIRST, dingsda

 LIMIT 3 OFFSET 5 FOR VIEW
    UPDATE VIEWSTAT
                      ")->parseQuery();

        $renderer = new Renderer();

        $renderer->render($ast, array(
            'pomsg' => 'Dingsda',
            'pong' => new Currency(1000.03, 'CNG'),
            'pung' => new \DateTime('now'),
            'b' => 124.4,
            'pungpung' => array('dings', 'bums', 'woing')
        ));
    }

    public function testPositive1()
    {
        $this->newParser(
            "SELECT hour_in_day(convertTimezone(dingsda)), dings, COUNT(hanswurst) AS nn,
            (SELECT Id FROM hanswurst WHERE Pimgs IN :pomsg),
            (SELECT COUNT() FROM account WHERE DAY_IN_MONTH(name) = 2),
            (SELECT Id AS aadf, Account.Id, Account.Name FROM dings__R WHERE dings__r > EUR2141.3),
pums, ping p, ping__c AS pong,
TYPEOF
    hans WHEN Account THEN hans.ping AS p
    WHEN Dings THEN hans.pong WHEN kases THEN pingpong, putz, wutz
    ELSE hans.pusle
END,
dings__c,
TYPEOF
  dings__c WHEN Account THEN ping END,
TYPEOF
  kasese WHEN Account THEN ping,pong ELSE pum END
FROM
  dingsds
WHERE
 dings IN ('kaesespaetzle', 'wursti') AND DAY_IN_MONTH(mydatefield__c) = 'sadg' AND
 DINGDONG = 2.3214 AND
 dings__c INCLUDES ('hans', 'hans;wurst', 'käses') AND
 (PING = :pong OR ping__c = :pung) AND
 LONGLONG IN (SELECT Id FROM Hanswurst WHERE ping = NULL AND pong.Type = 'einzzweidrei') AND
 hans IN :pungpung AND
 PING__c>=1 AND
 Date__c = LAST_N_DAYS:90

WITH DATA CATEGORY Geography__c ABOVE usa__c AND
 Product__c ABOVE_OR_BELOW mobile_phones__c AND
 Geography AT (usa__c, uk__c) AND Geography__c AT usa__c
 GROUP BY ROLLUP(hans, wurst, kaese)
 HAVING hans = :b AND Dings > 3.3 AND bing IN :pingong

 ORDER BY hanswi__c DESC NULLS LAST, pupsi__c ASC NULLS FIRST, dingsda

 LIMIT 3 OFFSET 5 FOR VIEW
 "
        )->parseQuery();
    }

    public function testArbitraryQueries()
    {
        $this->newParser("SELECT Id, Name
            FROM Account
            WHERE Name = 'Sandy'")->parseQuery();

        $this->newParser("SELECT count()
            FROM Contact c
            WHERE a.name = 'MyriadPubs'");

        $this->newParser("SELECT Id FROM Account WHERE Name LIKE 'Ter%'")->parseQuery();
        $this->newParser("SELECT Id FROM Account WHERE Name LIKE 'Ter\%'")->parseQuery();
        $this->newParser("SELECT Id FROM Account WHERE Name LIKE 'Ter\%%'")->parseQuery();

        $this->newParser("SELECT Id
                FROM Account
                WHERE Name LIKE 'Bob\'s BBQ'")->parseQuery();

        $this->newParser("SELECT Name FROM Account WHERE Name like 'A%'")->parseQuery();
        $this->newParser("SELECT Id FROM Contact WHERE Name LIKE 'A%' AND MailingCity='California'")->parseQuery();
        $this->newParser("SELECT Name FROM Account WHERE CreatedDate > 2011-04-26T10:00:00-08:00")->parseQuery();
        $this->newParser("SELECT Amount FROM Opportunity WHERE CALENDAR_YEAR(CreatedDate) = 2011")->parseQuery();
        $this->newParser("SELECT Id
        FROM Case
        WHERE Contact.LastName = null")->parseQuery();
        $this->newParser("SELECT AccountId
FROM Event
WHERE ActivityDate != null")->parseQuery();

        $this->newParser("SELECT Company, toLabel(Recordtype.Name) FROM Lead")->parseQuery();
        $this->newParser("SELECT Company, toLabel(Status)
FROM Lead
WHERE toLabel(Status) = 'le Draft'")->parseQuery();

        $this->newParser("SELECT Id, MSP1__c FROM CustObj__c WHERE MSP1__c = 'AAA;BBB'")->parseQuery();
        $this->newParser("SELECT Id, MSP1__c from CustObj__c WHERE MSP1__c includes ('AAA;BBB','CCC')")->parseQuery();
        $this->newParser("SELECT Id
FROM Event
WHERE What.Type IN ('Account', 'Opportunity')")->parseQuery();

        $this->newParser("SELECT Name FROM Account
WHERE BillingState IN ('California', 'New York')")->parseQuery();

        $this->newParser("SELECT Id, Name
FROM Account
WHERE Id IN
  ( SELECT AccountId
    FROM Opportunity
    WHERE StageName = 'Closed Lost'
  )")->parseQuery();

        $this->newParser("SELECT Id
FROM Task
WHERE WhoId IN
  (
    SELECT Id
    FROM Contact
    WHERE MailingCity = 'Twin Falls'
  )")->parseQuery();

        $this->newParser("SELECT Id
FROM Account
WHERE Id NOT IN
  (
    SELECT AccountId
    FROM Opportunity
    WHERE IsClosed = false
  )")->parseQuery();

        $this->newParser("SELECT Id
FROM Opportunity
WHERE AccountId NOT IN
  (
    SELECT AccountId
    FROM Contact
    WHERE LeadSource = 'Web'
  )")->parseQuery();

        $this->newParser("SELECT Id, Name
FROM Account
WHERE Id IN
  (
    SELECT AccountId
    FROM Contact
    WHERE LastName LIKE 'apple%'
  )
  AND Id IN
  (
    SELECT AccountId
    FROM Opportunity
    WHERE isClosed = false
  )")->parseQuery();

        $this->newParser("SELECT Id, (SELECT Id from OpportunityLineItems)
FROM Opportunity
WHERE Id IN
  (
    SELECT OpportunityId
    FROM OpportunityLineItem
    WHERE totalPrice > 10000
  )")->parseQuery();

        $ast = $this->newParser("SELECT Id
 FROM Idea
 WHERE (Id IN (SELECT ParentId FROM Vote WHERE CreatedDate > LAST_WEEK AND Parent.Type='Idea'))")->parseQuery();

        // RENDERING FAILS, CHECK AST INTEGRITY
        $this->assertInstanceOf(Query::class, $ast);
        $wherePart = $ast->where;
        $this->assertInstanceOf(Where::class, $wherePart);
        $logicalGroup1 = $ast->where->logicalGroup;
        $this->assertInstanceOf(LogicalGroup::class, $logicalGroup1);
        $condition1 = $logicalGroup1->firstChild;
        $this->assertInstanceOf(LogicalCondition::class, $condition1);
        $this->assertInstanceOf(Field::class, $condition1->left);
        $this->assertEquals('IN', $condition1->operator);
        $this->assertInstanceOf(Val::class, $condition1->right);
        $this->assertInstanceOf(Query::class, $condition1->right->value);
        // AST INTEGRITY OF THE WHERE PART SEEMS TO BE VALID

        $this->newParser("SELECT Id, Name
FROM Account
WHERE Id IN
  (
    SELECT AccountId
    FROM Contact
    WHERE LastName LIKE 'Brown_%'
  )")->parseQuery();

        $this->newParser("SELECT Id, Name
FROM Account
WHERE Id IN
  (
    SELECT ParentId
    FROM Account
    WHERE Name = 'myaccount'
  )")->parseQuery();

        $this->newParser("SELECT Id, Name
FROM Account
WHERE Parent.Name = 'myaccount'")->parseQuery();

        $this->newParser("SELECT Id
 FROM Idea
 WHERE (Idea.Title LIKE 'Vacation%')
AND (Idea.LastCommentDate > YESTERDAY)
AND (Id IN (SELECT ParentId FROM Vote
            WHERE CreatedById = '005x0000000sMgYAAU'
             AND Parent.Type='Idea'))")->parseQuery();

        $this->newParser("SELECT Id
 FROM Idea
 WHERE
  ((Idea.Title LIKE 'Vacation%')
  AND (CreatedDate > YESTERDAY)
  AND (Id IN (SELECT ParentId FROM Vote
              WHERE CreatedById = '005x0000000sMgYAAU'
               AND Parent.Type='Idea')
  )
  OR (Idea.Title like 'ExcellentIdea%'))")->parseQuery();

        $this->newParser("SELECT Name
FROM Account
ORDER BY Name DESC NULLS LAST")->parseQuery();

        $this->newParser("SELECT Id, CaseNumber, Account.Id, Account.Name
FROM Case
ORDER BY Account.Name")->parseQuery();

        $this->newParser("SELECT Name
FROM Account
WHERE industry = 'media'
ORDER BY BillingPostalCode ASC NULLS LAST LIMIT 125")->parseQuery();

        $this->newParser("SELECT Name
FROM Account
WHERE Industry = 'Media' LIMIT 125")->parseQuery();

        $this->newParser("SELECT MAX(CreatedDate)
FROM Account LIMIT 1")->parseQuery();

        $this->newParser("SELECT Name
FROM Merchandise__c
WHERE Price__c > 5.0
ORDER BY Name
LIMIT 100
OFFSET 10")->parseQuery();

        $this->newParser("SELECT Name, Id
FROM Merchandise__c
ORDER BY Name
LIMIT 100
OFFSET 0")->parseQuery();

        $this->newParser("SELECT Name, Id
FROM Merchandise__c
ORDER BY Name
LIMIT 100
OFFSET 100")->parseQuery();

        $this->newParser("SELECT Name
FROM Merchandise__c
ORDER BY Name
OFFSET 10")->parseQuery();

        $this->newParser("SELECT Title FROM KnowledgeArticleVersion WHERE PublishStatus='online' WITH DATA CATEGORY Geography__c ABOVE usa__c")->parseQuery();

        $this->newParser("SELECT Id FROM UserProfileFeed WITH UserId='005D0000001AamR' ORDER BY CreatedDate DESC, Id DESC LIMIT 20")->parseQuery();

        $this->newParser("SELECT Title FROM KnowledgeArticleVersion WHERE PublishStatus='online' WITH DATA CATEGORY Geography__c ABOVE usa__c")->parseQuery();

        $this->newParser("SELECT Title FROM Question WHERE LastReplyDate > 2005-10-08T01:02:03Z WITH DATA CATEGORY Geography__c AT (usa__c, uk__c)")->parseQuery();

        $this->newParser("SELECT UrlName FROM KnowledgeArticleVersion WHERE PublishStatus='draft' WITH DATA CATEGORY Geography__c AT usa__c AND Product__c ABOVE_OR_BELOW mobile_phones__c")->parseQuery();

        $this->newParser("SELECT Title FROM Question WHERE LastReplyDate < 2005-10-08T01:02:03Z WITH DATA CATEGORY Product__c AT mobile_phones__c")->parseQuery();
        $this->newParser("SELECT Title, Summary FROM KnowledgeArticleVersion WHERE PublishStatus='Online' AND Language = 'en_US' WITH DATA CATEGORY Geography__c ABOVE_OR_BELOW europe__c AND Product__c BELOW All__c")->parseQuery();
        $this->newParser("SELECT Id, Title FROM Offer__kav WHERE PublishStatus='Draft' AND Language = 'en_US' WITH DATA CATEGORY Geography__c AT (france__c,usa__c) AND Product__c ABOVE dsl__c")->parseQuery();

        $this->newParser("SELECT LeadSource, COUNT(Name)
FROM Lead
GROUP BY LeadSource")->parseQuery();

        $this->newParser("SELECT LeadSource
FROM Lead
GROUP BY LeadSource")->parseQuery();

        $this->newParser("SELECT Name, Max(CreatedDate)
FROM Account
GROUP BY Name
LIMIT 5")->parseQuery();

        $this->newParser("SELECT Name n, MAX(Amount) max
FROM Opportunity
GROUP BY Name")->parseQuery();

        $this->newParser("SELECT Name, MAX(Amount), MIN(Amount)
FROM Opportunity
GROUP BY Name")->parseQuery();

        $this->newParser("SELECT Name, MAX(Amount), MIN(Amount) min, SUM(Amount)
FROM Opportunity
GROUP BY Name")->parseQuery();

        $this->newParser("SELECT LeadSource, COUNT(Name) cnt
FROM Lead
GROUP BY ROLLUP(LeadSource)")->parseQuery();

        $this->newParser("SELECT Status, LeadSource, COUNT(Name) cnt
FROM Lead
GROUP BY ROLLUP(Status, LeadSource)
")->parseQuery();

        $this->newParser("SELECT LeadSource, Rating,
    GROUPING(LeadSource) grpLS, GROUPING(Rating) grpRating,
    COUNT(Name) cnt
FROM Lead
GROUP BY ROLLUP(LeadSource, Rating)")->parseQuery();

        $this->newParser("SELECT Type, BillingCountry,
    GROUPING(Type) grpType, GROUPING(BillingCountry) grpCty,
    COUNT(id) accts
FROM Account
GROUP BY CUBE(Type, BillingCountry)
ORDER BY GROUPING(Type), GROUPING(BillingCountry)")->parseQuery();

        $this->newParser("SELECT LeadSource, COUNT(Name)
FROM Lead
GROUP BY LeadSource")->parseQuery();

        $this->newParser("SELECT LeadSource, COUNT(Name)
FROM Lead
GROUP BY LeadSource
HAVING COUNT(Name) > 100")->parseQuery();

        $this->newParser("SELECT Name, Count(Id)
FROM Account
GROUP BY Name
HAVING Count(Id) > 1")->parseQuery();

        $this->newParser("SELECT LeadSource, COUNT(Name)
FROM Lead
GROUP BY LeadSource
HAVING COUNT(Name) > 100 and LeadSource > 'Phone'
")->parseQuery();

        $this->newParser("SELECT Name FROM Account
WHERE CreatedById IN
    (
    SELECT
        TYPEOF Owner
            WHEN User THEN Id
            WHEN Group THEN CreatedById
        END
    FROM CASE
    )")->parseQuery();

        $this->newParser("SELECT
    TYPEOF What
        WHEN Account THEN Phone
        ELSE Name
    END
FROM Event
WHERE CreatedById IN
    (
    SELECT CreatedById
    FROM Case
    )")->parseQuery();

        $this->newParser("SELECT
  TYPEOF What
    WHEN Account THEN Phone, NumberOfEmployees
    WHEN Opportunity THEN Amount, CloseDate
    ELSE Name, Email
  END
FROM Event")->parseQuery();

        $this->newParser("SELECT AVG(Amount)
FROM Opportunity")->parseQuery();

        $this->newParser("SELECT CampaignId, AVG(Amount)
FROM Opportunity
GROUP BY CampaignId")->parseQuery();

        $this->newParser("SELECT CampaignId, AVG(Amount)
FROM Opportunity
GROUP BY CampaignId")->parseQuery();

        $this->newParser("SELECT COUNT()
FROM Account
WHERE Name LIKE 'a%'")->parseQuery();

        $this->newParser("SELECT COUNT(Id)
FROM Account
WHERE Name LIKE 'a%'")->parseQuery();

        $this->newParser("SELECT COUNT_DISTINCT(Company)
FROM Lead")->parseQuery();

        $this->newParser("SELECT MIN(CreatedDate), FirstName, LastName
FROM Contact
GROUP BY FirstName, LastName")->parseQuery();

        $this->newParser("SELECT Name, MAX(BudgetedCost)
FROM Campaign
GROUP BY Name")->parseQuery();

        $this->newParser("SELECT SUM(Amount)
FROM Opportunity
WHERE IsClosed = false AND Probability > 60")->parseQuery();

        $this->newParser("SELECT COUNT()
FROM Account
WHERE Name LIKE 'a%'")->parseQuery();

        $this->newParser("SELECT COUNT()
FROM Contact
WHERE Account.Name = 'MyriadPubs'")->parseQuery();

        $this->newParser("SELECT COUNT(Id)
FROM Account
WHERE Name LIKE 'a%'")->parseQuery();

        $this->newParser("SELECT COUNT()
FROM Account
WHERE Name LIKE 'a%'")->parseQuery();

        $this->newParser("SELECT COUNT(Id)
FROM Account
WHERE Name LIKE 'a%'")->parseQuery();

        $this->newParser("SELECT COUNT(Id), COUNT(CampaignId)
FROM Opportunity")->parseQuery();

        $this->newParser("SELECT LeadSource, COUNT(Name)
FROM Lead
GROUP BY LeadSource")->parseQuery();

        $this->newParser("SELECT CALENDAR_YEAR(CreatedDate), SUM(Amount)
FROM Opportunity
GROUP BY CALENDAR_YEAR(CreatedDate)")->parseQuery();

        $this->newParser("SELECT CreatedDate, Amount
FROM Opportunity
WHERE CALENDAR_YEAR(CreatedDate) = 2009")->parseQuery();

        $this->newParser("SELECT CALENDAR_YEAR(CloseDate)
FROM Opportunity
GROUP BY CALENDAR_YEAR(CloseDate)")->parseQuery();

        $this->newParser("SELECT HOUR_IN_DAY(convertTimezone(CreatedDate)), SUM(Amount)
FROM Opportunity
GROUP BY HOUR_IN_DAY(convertTimezone(CreatedDate))")->parseQuery();

        $this->newParser("SELECT Id, convertCurrency(AnnualRevenue)
FROM Account")->parseQuery();

        $this->newParser("SELECT Id, Name
FROM Opportunity
WHERE Amount > USD5000")->parseQuery();

        $this->newParser("SELECT Name, MAX(Amount)
FROM Opportunity
GROUP BY Name
HAVING MAX(Amount) > 10000")->parseQuery();
    }

    public function testClientPositive1()
    {
        $builder = new ClientFactory(
            $wsdl = new Wsdl(__DIR__ . '/../../../../fixtures/partner.wsdl.xml'),
            SF_USERNAME,
            SF_PASSWORD,
            SF_SECURITY_TOKEN
        );

        $client = $builder
            ->withCache(new ArrayCache(__DIR__ . '/../../../../cache/', 'metadata'))
            ->withLog(new Logger('phpforce'))
            ->getInstance()
        ;

        $accouts = $client->query('SELECT Id, name,
        BillingStreet__c, OwnerId, Owner.Id, Owner.Name, createdDate,
        ParentId, Parent.Name, Parent.Id, IgnoreSiretDuplicateCheck__c, TaxCode1__c,
        (SELECT Id, FirstName FROM Contacts LIMIT 2) FROM Account LIMIT 3');

        $cacheWarmer = new CacheWarmer($client, true);

        // $cacheWarmer->warmup();

        foreach($accouts AS $account)
        {
        }

        $accouts->rewind();

        $acc = $accouts->current();
    }
}
