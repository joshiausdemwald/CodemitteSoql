<?php
namespace Codemitte\Test;

use Codemitte\ForceToolkit\Soql\AST\LogicalGroup;
use Codemitte\ForceToolkit\Soql\AST\Query;
use Codemitte\ForceToolkit\Soql\AST\Val;
use Codemitte\ForceToolkit\Soql\AST\Where;
use Codemitte\ForceToolkit\Soql\AST\Field;
use Codemitte\ForceToolkit\Soql\Builder\Type\Currency;
use Codemitte\ForceToolkit\Soql\Cache\ArrayQueryCache;
use Codemitte\ForceToolkit\Soql\Cache\MemCache;
use Codemitte\ForceToolkit\Soql\Event\TokenMatchEvent;
use Codemitte\ForceToolkit\Soql\Parser;
use Codemitte\ForceToolkit\Soql\Renderer\Renderer;
use Codemitte\ForceToolkit\Soql\TokenDefinition;
use Codemitte\ForceToolkit\Soql\Tokenizer;
use Symfony\Component\EventDispatcher\EventDispatcher;

class TokenizerTest extends \PHPUnit_Framework_TestCase
{
    public function newParser()
    {
        return new Parser(new Tokenizer(new EventDispatcher()), new MemCache());
    }

    /**
     * @test
     */
    public function testRenderer()
    {
        $ast = $this->newParser()->parse(
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
    FOR VIEW
    UPDATE VIEWSTAT

WITH DATA CATEGORY Geography__c ABOVE usa__c AND
    Product__c ABOVE_OR_BELOW mobile_phones__c AND
    Geography AT (usa__c, uk__c) AND Geography__c AT usa__c
 GROUP BY ROLLUP(hans, wurst, kaese)
 HAVING hans = :b AND Dings > 3.3 AND bing IN :pingong

 ORDER BY hanswi__c DESC NULLS LAST, pupsi__c ASC NULLS FIRST, dingsda

 LIMIT 3 OFFSET 5 FOR VIEW
                      ");

        $renderer = new Renderer();

        $renderer->render($ast, array(
            'pomsg' => 'Dingsda',
            'pong' => new Currency(1000.03, 'CNG'),
            'pung' => new \DateTime('now'),
            'b' => 124.4,
            'pungpung' => array('dings', 'bums', 'woing')
        ));
    }

    /**
     * @test
     */
    public function testPositive1()
    {
        $tokenizer = new Tokenizer(new EventDispatcher());
        $parser = $this->newParser();

        $parser->parse(
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
        );
    }

    /**
     * @test
     */
    public function testArbitraryQueries()
    {
        $this->newParser()->parse("SELECT Id, Name
            FROM Account
            WHERE Name = 'Sandy'");

        $this->newParser()->parse("SELECT count()
            FROM Contact c
            WHERE a.name = 'MyriadPubs'");

        $this->newParser()->parse("SELECT Id FROM Account WHERE Name LIKE 'Ter%'");
        $this->newParser()->parse("SELECT Id FROM Account WHERE Name LIKE 'Ter\%'");
        $this->newParser()->parse("SELECT Id FROM Account WHERE Name LIKE 'Ter\%%'");

        $this->newParser()->parse("SELECT Id
                FROM Account
                WHERE Name LIKE 'Bob\'s BBQ'");

        $this->newParser()->parse("SELECT Name FROM Account WHERE Name like 'A%'");
        $this->newParser()->parse("SELECT Id FROM Contact WHERE Name LIKE 'A%' AND MailingCity='California'");
        $this->newParser()->parse("SELECT Name FROM Account WHERE CreatedDate > 2011-04-26T10:00:00-08:00");
        $this->newParser()->parse("SELECT Amount FROM Opportunity WHERE CALENDAR_YEAR(CreatedDate) = 2011");
        $this->newParser()->parse("SELECT Id
        FROM Case
        WHERE Contact.LastName = null");
        $this->newParser()->parse("SELECT AccountId
FROM Event
WHERE ActivityDate != null");

        $this->newParser()->parse("SELECT Company, toLabel(Recordtype.Name) FROM Lead");
        $this->newParser()->parse("SELECT Company, toLabel(Status)
FROM Lead
WHERE toLabel(Status) = 'le Draft'");

        $this->newParser()->parse("SELECT Id, MSP1__c FROM CustObj__c WHERE MSP1__c = 'AAA;BBB'");
        $this->newParser()->parse("SELECT Id, MSP1__c from CustObj__c WHERE MSP1__c includes ('AAA;BBB','CCC')");
        $this->newParser()->parse("SELECT Id
FROM Event
WHERE What.Type IN ('Account', 'Opportunity')");

        $this->newParser()->parse("SELECT Name FROM Account
WHERE BillingState IN ('California', 'New York')");

        $this->newParser()->parse("SELECT Id, Name
FROM Account
WHERE Id IN
  ( SELECT AccountId
    FROM Opportunity
    WHERE StageName = 'Closed Lost'
  )");

        $this->newParser()->parse("SELECT Id
FROM Task
WHERE WhoId IN
  (
    SELECT Id
    FROM Contact
    WHERE MailingCity = 'Twin Falls'
  )");

        $this->newParser()->parse("SELECT Id
FROM Account
WHERE Id NOT IN
  (
    SELECT AccountId
    FROM Opportunity
    WHERE IsClosed = false
  )");

        $this->newParser()->parse("SELECT Id
FROM Opportunity
WHERE AccountId NOT IN
  (
    SELECT AccountId
    FROM Contact
    WHERE LeadSource = 'Web'
  )");

        $this->newParser()->parse("SELECT Id, Name
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
  )");

        $this->newParser()->parse("SELECT Id, (SELECT Id from OpportunityLineItems)
FROM Opportunity
WHERE Id IN
  (
    SELECT OpportunityId
    FROM OpportunityLineItem
    WHERE totalPrice > 10000
  )");

        $ast = $this->newParser()->parse("SELECT Id
 FROM Idea
 WHERE (Id IN (SELECT ParentId FROM Vote WHERE CreatedDate > LAST_WEEK AND Parent.Type='Idea'))");

        // RENDERING FAILS, CHECK AST INTEGRITY
        $this->assertInstanceOf(Query::class, $ast);
        $wherePart = $ast->where;
        $this->assertInstanceOf(Where::class, $wherePart);
        $logicalGroup1 = $ast->where->logicalGroup;
        $this->assertInstanceOf(LogicalGroup::class, $logicalGroup1);
        $conditions1 = $logicalGroup1->conditions;
        $this->assertCount(1, $conditions1);
        $logicalGroup11 = $conditions1[0];
        $this->assertInstanceOf(LogicalGroup::class, $logicalGroup11);
        $this->assertCount(1, $logicalGroup11->conditions);
        $condition11 = $logicalGroup11->conditions[0];
        $this->assertInstanceOf(Field::class, $condition11->left);
        $this->assertEquals('IN', $condition11->operator);
        $this->assertInstanceOf(Val::class, $condition11->right);
        $this->assertInstanceOf(Query::class, $condition11->right->value);
        // AST INTEGRITY OF THE WHERE PART SEEMS TO BE VALID

        $this->newParser()->parse("SELECT Id, Name
FROM Account
WHERE Id IN
  (
    SELECT AccountId
    FROM Contact
    WHERE LastName LIKE 'Brown_%'
  )");

        $this->newParser()->parse("SELECT Id, Name
FROM Account
WHERE Id IN
  (
    SELECT ParentId
    FROM Account
    WHERE Name = 'myaccount'
  )");

        $this->newParser()->parse("SELECT Id, Name
FROM Account
WHERE Parent.Name = 'myaccount'");

        $this->newParser()->parse("SELECT Id
 FROM Idea
 WHERE (Idea.Title LIKE 'Vacation%')
AND (Idea.LastCommentDate > YESTERDAY)
AND (Id IN (SELECT ParentId FROM Vote
            WHERE CreatedById = '005x0000000sMgYAAU'
             AND Parent.Type='Idea'))");

        $this->newParser()->parse("SELECT Id
 FROM Idea
 WHERE
  ((Idea.Title LIKE 'Vacation%')
  AND (CreatedDate > YESTERDAY)
  AND (Id IN (SELECT ParentId FROM Vote
              WHERE CreatedById = '005x0000000sMgYAAU'
               AND Parent.Type='Idea')
  )
  OR (Idea.Title like 'ExcellentIdea%'))");

        $this->newParser()->parse("SELECT Name
FROM Account
ORDER BY Name DESC NULLS LAST");

        $this->newParser()->parse("SELECT Id, CaseNumber, Account.Id, Account.Name
FROM Case
ORDER BY Account.Name");

        $this->newParser()->parse("SELECT Name
FROM Account
WHERE industry = 'media'
ORDER BY BillingPostalCode ASC NULLS LAST LIMIT 125");

        $this->newParser()->parse("SELECT Name
FROM Account
WHERE Industry = 'Media' LIMIT 125");

        $this->newParser()->parse("SELECT MAX(CreatedDate)
FROM Account LIMIT 1");

        $this->newParser()->parse("SELECT Name
FROM Merchandise__c
WHERE Price__c > 5.0
ORDER BY Name
LIMIT 100
OFFSET 10");

        $this->newParser()->parse("SELECT Name, Id
FROM Merchandise__c
ORDER BY Name
LIMIT 100
OFFSET 0");

        $this->newParser()->parse("SELECT Name, Id
FROM Merchandise__c
ORDER BY Name
LIMIT 100
OFFSET 100");

        $this->newParser()->parse("SELECT Name
FROM Merchandise__c
ORDER BY Name
OFFSET 10");

        $this->newParser()->parse("SELECT Title FROM KnowledgeArticleVersion WHERE PublishStatus='online' WITH DATA CATEGORY Geography__c ABOVE usa__c");

        $this->newParser()->parse("SELECT Id FROM UserProfileFeed WITH UserId='005D0000001AamR' ORDER BY CreatedDate DESC, Id DESC LIMIT 20");

        $this->newParser()->parse("SELECT Title FROM KnowledgeArticleVersion WHERE PublishStatus='online' WITH DATA CATEGORY Geography__c ABOVE usa__c");

        $this->newParser()->parse("SELECT Title FROM Question WHERE LastReplyDate > 2005-10-08T01:02:03Z WITH DATA CATEGORY Geography__c AT (usa__c, uk__c)");

        $this->newParser()->parse("SELECT UrlName FROM KnowledgeArticleVersion WHERE PublishStatus='draft' WITH DATA CATEGORY Geography__c AT usa__c AND Product__c ABOVE_OR_BELOW mobile_phones__c");

        $this->newParser()->parse("SELECT Title FROM Question WHERE LastReplyDate < 2005-10-08T01:02:03Z WITH DATA CATEGORY Product__c AT mobile_phones__c");
        $this->newParser()->parse("SELECT Title, Summary FROM KnowledgeArticleVersion WHERE PublishStatus='Online' AND Language = 'en_US' WITH DATA CATEGORY Geography__c ABOVE_OR_BELOW europe__c AND Product__c BELOW All__c");
        $this->newParser()->parse("SELECT Id, Title FROM Offer__kav WHERE PublishStatus='Draft' AND Language = 'en_US' WITH DATA CATEGORY Geography__c AT (france__c,usa__c) AND Product__c ABOVE dsl__c");

        $this->newParser()->parse("SELECT LeadSource, COUNT(Name)
FROM Lead
GROUP BY LeadSource");

        $this->newParser()->parse("SELECT LeadSource
FROM Lead
GROUP BY LeadSource");

        $this->newParser()->parse("SELECT Name, Max(CreatedDate)
FROM Account
GROUP BY Name
LIMIT 5");

        $this->newParser()->parse("SELECT Name n, MAX(Amount) max
FROM Opportunity
GROUP BY Name");

        $this->newParser()->parse("SELECT Name, MAX(Amount), MIN(Amount)
FROM Opportunity
GROUP BY Name");

        $this->newParser()->parse("SELECT Name, MAX(Amount), MIN(Amount) min, SUM(Amount)
FROM Opportunity
GROUP BY Name");

        $this->newParser()->parse("SELECT LeadSource, COUNT(Name) cnt
FROM Lead
GROUP BY ROLLUP(LeadSource)");

        $this->newParser()->parse("SELECT Status, LeadSource, COUNT(Name) cnt
FROM Lead
GROUP BY ROLLUP(Status, LeadSource)
");

        $this->newParser()->parse("SELECT LeadSource, Rating,
    GROUPING(LeadSource) grpLS, GROUPING(Rating) grpRating,
    COUNT(Name) cnt
FROM Lead
GROUP BY ROLLUP(LeadSource, Rating)");

        $this->newParser()->parse("SELECT Type, BillingCountry,
    GROUPING(Type) grpType, GROUPING(BillingCountry) grpCty,
    COUNT(id) accts
FROM Account
GROUP BY CUBE(Type, BillingCountry)
ORDER BY GROUPING(Type), GROUPING(BillingCountry)");

        $this->newParser()->parse("SELECT LeadSource, COUNT(Name)
FROM Lead
GROUP BY LeadSource");

        $this->newParser()->parse("SELECT LeadSource, COUNT(Name)
FROM Lead
GROUP BY LeadSource
HAVING COUNT(Name) > 100");

        $this->newParser()->parse("SELECT Name, Count(Id)
FROM Account
GROUP BY Name
HAVING Count(Id) > 1");

        $this->newParser()->parse("SELECT LeadSource, COUNT(Name)
FROM Lead
GROUP BY LeadSource
HAVING COUNT(Name) > 100 and LeadSource > 'Phone'
");

        $this->newParser()->parse("SELECT Name FROM Account
WHERE CreatedById IN
    (
    SELECT
        TYPEOF Owner
            WHEN User THEN Id
            WHEN Group THEN CreatedById
        END
    FROM CASE
    )");

        $this->newParser()->parse("SELECT
    TYPEOF What
        WHEN Account THEN Phone
        ELSE Name
    END
FROM Event
WHERE CreatedById IN
    (
    SELECT CreatedById
    FROM Case
    )");

        $this->newParser()->parse("SELECT
  TYPEOF What
    WHEN Account THEN Phone, NumberOfEmployees
    WHEN Opportunity THEN Amount, CloseDate
    ELSE Name, Email
  END
FROM Event");

        $this->newParser()->parse("SELECT AVG(Amount)
FROM Opportunity");

        $this->newParser()->parse("SELECT CampaignId, AVG(Amount)
FROM Opportunity
GROUP BY CampaignId");

        $this->newParser()->parse("SELECT CampaignId, AVG(Amount)
FROM Opportunity
GROUP BY CampaignId");

        $this->newParser()->parse("SELECT COUNT()
FROM Account
WHERE Name LIKE 'a%'");

        $this->newParser()->parse("SELECT COUNT(Id)
FROM Account
WHERE Name LIKE 'a%'");

        $this->newParser()->parse("SELECT COUNT_DISTINCT(Company)
FROM Lead");

        $this->newParser()->parse("SELECT MIN(CreatedDate), FirstName, LastName
FROM Contact
GROUP BY FirstName, LastName");

        $this->newParser()->parse("SELECT Name, MAX(BudgetedCost)
FROM Campaign
GROUP BY Name");

        $this->newParser()->parse("SELECT SUM(Amount)
FROM Opportunity
WHERE IsClosed = false AND Probability > 60");

        $this->newParser()->parse("SELECT COUNT()
FROM Account
WHERE Name LIKE 'a%'");

        $this->newParser()->parse("SELECT COUNT()
FROM Contact
WHERE Account.Name = 'MyriadPubs'");

        $this->newParser()->parse("SELECT COUNT(Id)
FROM Account
WHERE Name LIKE 'a%'");

        $this->newParser()->parse("SELECT COUNT()
FROM Account
WHERE Name LIKE 'a%'");

        $this->newParser()->parse("SELECT COUNT(Id)
FROM Account
WHERE Name LIKE 'a%'");

        $this->newParser()->parse("SELECT COUNT(Id), COUNT(CampaignId)
FROM Opportunity");

        $this->newParser()->parse("SELECT LeadSource, COUNT(Name)
FROM Lead
GROUP BY LeadSource");

        $this->newParser()->parse("SELECT CALENDAR_YEAR(CreatedDate), SUM(Amount)
FROM Opportunity
GROUP BY CALENDAR_YEAR(CreatedDate)");

        $this->newParser()->parse("SELECT CreatedDate, Amount
FROM Opportunity
WHERE CALENDAR_YEAR(CreatedDate) = 2009");

        $this->newParser()->parse("SELECT CALENDAR_YEAR(CloseDate)
FROM Opportunity
GROUP BY CALENDAR_YEAR(CloseDate)");

        $this->newParser()->parse("SELECT HOUR_IN_DAY(convertTimezone(CreatedDate)), SUM(Amount)
FROM Opportunity
GROUP BY HOUR_IN_DAY(convertTimezone(CreatedDate))");

        $this->newParser()->parse("SELECT Id, convertCurrency(AnnualRevenue)
FROM Account");

        $this->newParser()->parse("SELECT Id, Name
FROM Opportunity
WHERE Amount > USD5000");

        $this->newParser()->parse("SELECT Name, MAX(Amount)
FROM Opportunity
GROUP BY Name
HAVING MAX(Amount) > 10000");
    }
}