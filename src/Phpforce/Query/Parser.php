<?php
namespace Phpforce\Query;

use Phpforce\Query\AST as AST;
use Doctrine\Common\Cache\Cache;
use Symfony\Component\Yaml\Exception\ParseException;

class Parser
{
    const
        SCOPE_SELECT = 'SCOPE_SELECT',
        SCOPE_FROM = 'SCOPE_FROM',
        SCOPE_WHERE = 'SCOPE_WHERE',
        SCOPE_WITH = 'SCOPE_WITH',
        SCOPE_WITH_DATA_CATEGORY = 'SCOPE_WITH_DATA_CATEGORY',
        SCOPE_GROUPBY = 'SCOPE_GROUPBY',
        SCOPE_HAVING = 'SCOPE_HAVING',
        SCOPE_ORDERBY = 'ORDERBY'
    ;

    /**
     * @var Tokenizer
     */
    private $tokenizer;

    /**
     * @var string
     */
    private $scope;

    /**
     * @var FunctionDefinition[]
     */
    private static $availableFunctions;

    /**
     * @var bool
     */
    private $isSubquery = false;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @param \Phpforce\Query\TokenizerInterface $tokenizer
     * @param Cache $cache
     */
    public function __construct(TokenizerInterface $tokenizer, Cache $cache)
    {
        $this->cache = $cache;

        $this->tokenizer = $tokenizer;

        static::$availableFunctions = array
        (
            'TOLABEL' => new FunctionDefinition(FunctionDefinition::TYPE_STRING, array(static::SCOPE_SELECT, static::SCOPE_WHERE), 1),
            'CONVERTCURRENCY' => new FunctionDefinition(FunctionDefinition::TYPE_STRING, array(static::SCOPE_SELECT), 1),

            'CONVERTTIMEZONE' => new FunctionDefinition(FunctionDefinition::TYPE_DATE, array(static::SCOPE_SELECT, static::SCOPE_GROUPBY, static::SCOPE_ORDERBY, static::SCOPE_WHERE, static::SCOPE_HAVING), 1),

            'CUBE' => new FunctionDefinition(FunctionDefinition::TYPE_GROUPING, array(static::SCOPE_GROUPBY), 1, -1),
            'ROLLUP' => new FunctionDefinition(FunctionDefinition::TYPE_GROUPING, array(static::SCOPE_GROUPBY), 1, -1),

            'GROUPING' => new FunctionDefinition(FunctionDefinition::TYPE_AGGREGATE, array(static::SCOPE_SELECT, static::SCOPE_ORDERBY, static::SCOPE_HAVING), 1),
            'COUNT' => new FunctionDefinition(FunctionDefinition::TYPE_AGGREGATE, array(static::SCOPE_SELECT, static::SCOPE_ORDERBY, static::SCOPE_HAVING), 0, 1),
            'AVG' => new FunctionDefinition(FunctionDefinition::TYPE_AGGREGATE, array(static::SCOPE_SELECT, static::SCOPE_ORDERBY, static::SCOPE_HAVING), 1),
            'COUNT_DISTINCT'=> new FunctionDefinition(FunctionDefinition::TYPE_AGGREGATE, array(static::SCOPE_SELECT, static::SCOPE_ORDERBY, static::SCOPE_HAVING), 1),
            'MIN'=> new FunctionDefinition(FunctionDefinition::TYPE_AGGREGATE, array(static::SCOPE_SELECT, static::SCOPE_ORDERBY, static::SCOPE_HAVING), 1),
            'MAX'=> new FunctionDefinition(FunctionDefinition::TYPE_AGGREGATE, array(static::SCOPE_SELECT, static::SCOPE_ORDERBY, static::SCOPE_HAVING), 1),
            'SUM'=> new FunctionDefinition(FunctionDefinition::TYPE_AGGREGATE, array(static::SCOPE_SELECT, static::SCOPE_ORDERBY, static::SCOPE_HAVING), 1),

            'CALENDAR_MONTH'=> new FunctionDefinition(FunctionDefinition::TYPE_DATE, array(static::SCOPE_SELECT, static::SCOPE_GROUPBY, static::SCOPE_ORDERBY, static::SCOPE_WHERE, static::SCOPE_HAVING), 1),
            'CALENDAR_QUARTER'=> new FunctionDefinition(FunctionDefinition::TYPE_DATE, array(static::SCOPE_SELECT, static::SCOPE_GROUPBY, static::SCOPE_ORDERBY, static::SCOPE_WHERE, static::SCOPE_HAVING), 1),
            'CALENDAR_YEAR'=> new FunctionDefinition(FunctionDefinition::TYPE_DATE, array(static::SCOPE_SELECT, static::SCOPE_GROUPBY, static::SCOPE_ORDERBY, static::SCOPE_WHERE, static::SCOPE_HAVING), 1),
            'DAY_IN_MONTH'=> new FunctionDefinition(FunctionDefinition::TYPE_DATE, array(static::SCOPE_SELECT, static::SCOPE_GROUPBY, static::SCOPE_ORDERBY, static::SCOPE_WHERE, static::SCOPE_HAVING), 1),
            'DAY_IN_WEEK'=> new FunctionDefinition(FunctionDefinition::TYPE_DATE, array(static::SCOPE_SELECT, static::SCOPE_GROUPBY, static::SCOPE_ORDERBY, static::SCOPE_WHERE, static::SCOPE_HAVING), 1),
            'DAY_IN_YEAR'=> new FunctionDefinition(FunctionDefinition::TYPE_DATE, array(static::SCOPE_SELECT, static::SCOPE_GROUPBY, static::SCOPE_ORDERBY, static::SCOPE_WHERE, static::SCOPE_HAVING), 1),
            'DAY_ONLY'=> new FunctionDefinition(FunctionDefinition::TYPE_DATE, array(static::SCOPE_SELECT, static::SCOPE_GROUPBY, static::SCOPE_ORDERBY, static::SCOPE_WHERE, static::SCOPE_HAVING), 1),
            'FISCAL_MONTH'=> new FunctionDefinition(FunctionDefinition::TYPE_DATE, array(static::SCOPE_SELECT, static::SCOPE_GROUPBY, static::SCOPE_ORDERBY, static::SCOPE_WHERE, static::SCOPE_HAVING), 1),
            'FISCAL_QUARTER'=> new FunctionDefinition(FunctionDefinition::TYPE_DATE, array(static::SCOPE_SELECT, static::SCOPE_GROUPBY, static::SCOPE_ORDERBY, static::SCOPE_WHERE, static::SCOPE_HAVING), 1),
            'FISCAL_YEAR'=> new FunctionDefinition(FunctionDefinition::TYPE_DATE, array(static::SCOPE_SELECT, static::SCOPE_GROUPBY, static::SCOPE_ORDERBY, static::SCOPE_WHERE, static::SCOPE_HAVING), 1),
            'HOUR_IN_DAY'=> new FunctionDefinition(FunctionDefinition::TYPE_DATE, array(static::SCOPE_SELECT, static::SCOPE_GROUPBY, static::SCOPE_ORDERBY, static::SCOPE_WHERE, static::SCOPE_HAVING), 1),
            'WEEK_IN_MONTH'=> new FunctionDefinition(FunctionDefinition::TYPE_DATE, array(static::SCOPE_SELECT, static::SCOPE_GROUPBY, static::SCOPE_ORDERBY, static::SCOPE_WHERE, static::SCOPE_HAVING), 1),
            'WEEK_IN_YEAR'=> new FunctionDefinition(FunctionDefinition::TYPE_DATE, array(static::SCOPE_SELECT, static::SCOPE_GROUPBY, static::SCOPE_ORDERBY, static::SCOPE_WHERE, static::SCOPE_HAVING), 1)
        );
    }

    /**
     * @param $soql
     * @return AST\Node|AST\Query|null
     */
    public function parse($soql)
    {
        $id = hash('sha1', $soql);

        if($this->cache->contains($id))
        {
            $this->query = $this->cache->fetch($id);
        }
        else
        {
            $this->tokenizer->tokenize($soql);

            $this->query = $this->parseQuery();

            $this->cache->save($id, $this->query);
        }
        return $this->query;
    }

    /**
     * @return AST\Query
     */
    public function parseQuery()
    {
        $query = new AST\Query();

        $query->select = $this->parseSelect();

        $query->from = $this->parseFrom();

        if($this->tokenizer->isKeyword('WHERE'))
        {
            $this->enterScope(static::SCOPE_WHERE);

            $query->where = new AST\Where($this->parseWhere());
        }

        if($this->tokenizer->isKeyword('WITH') && ($with = $this->parseWith()))
        {
            $query->with = new AST\With($this->parseWith());
        }

        elseif($this->tokenizer->isKeyword('DATA'))
        {
            $this->tokenizer->expectKeyword('CATEGORY');

            $query->withDataCategory = new AST\WithDataCategory($this->parseWithDataCategory());
        }

        if($this->tokenizer->isKeyword('GROUP'))
        {
            $this->tokenizer->expectKeyword('BY');

            $query->groupBy = $this->parseGroupBy();

            if($this->tokenizer->isKeyword('HAVING'))
            {
                $query->having = new AST\Having($this->parseHaving());
            }
        }

        if($this->tokenizer->isKeyword('ORDER'))
        {
            $this->tokenizer->expectKeyword('BY');

            $query->orderBy = new AST\OrderBy($this->parseOrderBy());
        }

        $this->clearScope();

        if($this->tokenizer->isKeyword('LIMIT'))
        {
            $query->limit = $this->parseLimit();
        }

        if($this->tokenizer->isKeyword('OFFSET'))
        {
            $query->offset = $this->parseOffset();
        }

        if($this->tokenizer->isKeyword('FOR'))
        {
            $query->for = $this->parseFor();
        }

        if($this->tokenizer->isKeyword('UPDATE'))
        {
            $query->update = $this->parseUpdate();
        }

        $this->checkVeryEnd();

        return $query;
    }

    /**
     * @return AST\Select
     */
    public function parseSelect()
    {
        $this->tokenizer->expectKeyword('SELECT');

        return new AST\Select($this->parseSelectFieldList());
    }

    /**
     * @return Field[]|Query[]|Typeof[]
     */
    public function parseSelectFieldList()
    {
        $this->enterScope(static::SCOPE_SELECT);

        $fields = array();

        $withCount = false;

        while(true)
        {
            $field = $this->parseSelectField();

            // SONDERLOCKE COUNT()
            if(
                ! $withCount &&
                $field instanceof AST\SoqlFunction &&
                $field->name == 'COUNT' &&
                count($field->arguments) === 0
            ) {
                $withCount = true;
            }

            $fields[] = $field;

            if($withCount && count($fields) > 1)
            {
                $this->throwError('COUNT() must be the only field in SELECT part.');
            }

            if( ! $this->tokenizer->is(TokenDefinition::T_COMMA))
            {
                break;
            }
        }
        return $fields;
    }

    /**
     * Simple field, compound field
     * COUNT()
     * toLabel()
     * subquery()
     *
     * @return AST\Field
     */
    public function parseSelectField()
    {
        $this->tokenizer->proceedSkip();

        // SINGLE FIELD
        if($this->tokenizer->is(TokenDefinition::T_EXPRESSION))
        {
            return $this->parseSimpleSelectField();
        }

        // TYPEOF
        elseif($this->tokenizer->isKeyword('TYPEOF'))
        {
            return $this->parseSelectTypeof();
        }

        // SUBQUERY
        elseif($this->tokenizer->is(TokenDefinition::T_LEFT_PAREN))
        {
            return $this->parseSubquery();
        }
        else
        {
            $this->throwError(sprintf('Unexpected "%s", expected one of "T_EXPRESSION", "T_KEYWORD", "T_LEFT_PAREN"', $this->tokenizer->getType()));
        }
    }

    /**
     * @return AST\Query
     */
    public function parseSubquery()
    {
        if($this->isSubquery)
        {
            $this->throwError('Multi-level subqueries are not allowed.');
        }

        if($this->scope === static::SCOPE_HAVING)
        {
            $this->throwError('No subquery filters allowed in HAVING clause');
        }

        $this->isSubquery = true;

        $query = $this->parseQuery();

        $this->isSubquery = false;

        $this->tokenizer->check(TokenDefinition::T_RIGHT_PAREN);

        // ADVANCE TO READ NEXT FIELD
        $this->tokenizer->proceedSkip();

        return $query;
    }

    /**
     * @return AST\Field
     */
    public function parseSimpleSelectField()
    {
        $value = $this->tokenizer->getValue();

        $this->tokenizer->proceedSkip();

        if($this->tokenizer->is(TokenDefinition::T_LEFT_PAREN))
        {
            $function = $this->parseFunction($value);
            $function->alias = $this->parseAlias();
            return $function;

        }
        return new AST\Field($value, $this->parseAlias());
    }

    /**
     * @param $name
     * @return AST\SoqlFunction
     */
    public function parseFunction($name)
    {
        $funcname = strtoupper($name);

        /** @var FunctionDefinition $functionDefinition */
        $functionDefinition;

        if(isset(static::$availableFunctions[$funcname]))
        {
            $functionDefinition = static::$availableFunctions[$funcname];

            if( ! in_array($this->scope, $functionDefinition->getScope()))
            {
                $this->throwError(sprintf('Function %s "%s" not allowed within scope "%s" (allowed scopes "%s").', $functionDefinition->getType(), $name, $this->scope, implode('", "', $functionDefinition->getScope())));
            }
        }
        else
        {
            $this->throwError(sprintf('Unknown function "%s".', $name));
        }
        $function = new AST\SoqlFunction($funcname);
        $function->arguments = $this->parseFunctionArguments();

        if(
            count($function->arguments) < $functionDefinition->getArgumentsLengthMin() ||
            $functionDefinition->getArgumentsLengthMax() > 0 && count($function->arguments) > $functionDefinition->getArgumentsLengthMax()
        ) {
            $this->throwError(sprintf('Function %s "%s" does only allow at least %u, at max %u arguments.', $functionDefinition->getType(), $name, $functionDefinition->getArgumentsLengthMin(), $functionDefinition->getArgumentsLengthMax()));
        }
        return $function;
    }

    /**
     * Pointer: COUNT(
     * --------------^-
     *
     * @return Field[]|SoqlFunction[]
     */
    public function parseFunctionArguments()
    {
        $arguments = array();

        while(true)
        {
            $this->tokenizer->proceedSkip();

            if($this->tokenizer->is(TokenDefinition::T_EXPRESSION))
            {
                $arguments[] = $this->parseFunctionArgument();
            }

            if( ! $this->tokenizer->is(TokenDefinition::T_COMMA))
            {
                break;
            }
        }

        $this->tokenizer->check(TokenDefinition::T_RIGHT_PAREN);

        $this->tokenizer->proceedSkip();

        return $arguments;
    }

    /**
     * Starts at COUNT(argument, pingpong)
     * ----------------^------------------
     *
     * * Starts at COUNT(argument, pingpong())
     * ----------------------------^----------
     * @return AST\Field|AST\SoqlFunction
     */
    public function parseFunctionArgument()
    {
        $value = $this->tokenizer->getValue();

        $this->tokenizer->proceedSkip();

        if($this->tokenizer->is(TokenDefinition::T_LEFT_PAREN))
        {
            return $this->parseFunction($value); // NO ALIAS
        }
        return new AST\Field($value);
    }

    /**
     * @return AST\Typeof
     */
    public function parseSelectTypeof()
    {
        $this->tokenizer->expect(TokenDefinition::T_EXPRESSION);

        $typeof = new AST\Typeof($this->tokenizer->getValue());

        $this->tokenizer->expectKeyword('WHEN');

        // ALLOW e.g. "GROUP" AS FIELDNAME AS FIELDNAME
        $this->tokenizer->expect(array(TokenDefinition::T_KEYWORD, TokenDefinition::T_EXPRESSION));

        $when = new AST\TypeofCondition($this->tokenizer->getValue());

        $this->tokenizer->expectKeyword('THEN');

        // FIELDS OF FIRST THEN
        while(true)
        {
            $this->tokenizer->expect(TokenDefinition::T_EXPRESSION);

            $when->then[] = $this->parseSimpleSelectField();

            if( ! $this->tokenizer->is(TokenDefinition::T_COMMA))
            {
                break;
            }
        }

        $typeof->whens[] = $when;

        while(true)
        {
            if($this->tokenizer->isKeyword('WHEN'))
            {
                $this->tokenizer->expect(array(TokenDefinition::T_KEYWORD, TokenDefinition::T_EXPRESSION));

                $when = new AST\TypeofCondition($this->tokenizer->getValue());

                $this->tokenizer->expectKeyword('THEN');

                // FIELDS
                while(true)
                {
                    $this->tokenizer->expect(TokenDefinition::T_EXPRESSION);

                    $when->then[] = $this->parseSimpleSelectField();

                    if( ! $this->tokenizer->is(TokenDefinition::T_COMMA))
                    {
                        break 1;
                    }
                }

                $typeof->whens[] = $when;
            }
            elseif($this->tokenizer->isKeyword('ELSE'))
            {
                // FIELDS
                while(true)
                {
                    $this->tokenizer->expect(TokenDefinition::T_EXPRESSION);

                    $typeof->else[] = $this->parseSimpleSelectField();

                    if( ! $this->tokenizer->is(TokenDefinition::T_COMMA))
                    {
                        break 1;
                    }
                }
            }
            elseif($this->tokenizer->isKeyword('END'))
            {
                $this->tokenizer->proceedSkip();

                break 1;
            }
            else
            {
                $this->tokenizer->check(TokenDefinition::T_KEYWORD);
            }
        }
        return $typeof;
    }

    /**
     * Start: COUNT() cnt
     * ---------------′--
     *
     * Start: COUNT() as cnt
     * ---------------′--
     *
     * @return string
     */
    public function parseAlias()
    {
        if($this->tokenizer->isKeyword('AS'))
        {
            $this->tokenizer->expect(TokenDefinition::T_EXPRESSION);
        }

        if($this->tokenizer->is(TokenDefinition::T_EXPRESSION))
        {
            $alias = $this->tokenizer->getValue();

            $this->tokenizer->proceedSkip();

            return $alias;
        }
    }

    /**
     * @return AST\From
     */
    public function parseFrom()
    {
        $this->tokenizer->checkKeyword('FROM');

        $from = $this->parseFromField();

        $this->checkEOQ();

        return $from;
    }

    /**
     * @return AST\From
     */
    public function parseFromField()
    {
        $this->enterScope(static::SCOPE_FROM);

        $this->tokenizer->expect(TokenDefinition::T_EXPRESSION);

        $from = $this->tokenizer->getValue();

        $this->tokenizer->proceedSkip();

        $alias = $this->parseAlias();

        return new AST\From($from, $alias);
    }

    /**
     * @return AST\LogicalUnit
     */
    public function parseWhere()
    {
        $this->enterScope(static::SCOPE_WHERE);

        $this->tokenizer->proceedSkip();

        $group = $this->parseWhereLogicalGroup();

        $this->checkEOQ();

        return $group;
    }

    /**
     * @return AST\LogicalUnit
     */
    public function parseWith()
    {
        $this->tokenizer->proceedSkip();

        if($this->tokenizer->isKeyword('DATA'))
        {
            return;
        }
        else
        {
            $this->enterScope(static::SCOPE_WITH);

            $unit = $this->parseWithLogicalGroup();
        }

        $this->checkEOQ();

        return $unit;
    }

    /**
     * @return AST\LogicalUnit
     */
    public function parseWithDataCategory()
    {
        $this->enterScope(static::SCOPE_WITH_DATA_CATEGORY);

        $this->tokenizer->proceedSkip();

        $unit = $this->parseWithDataCategoryLogicalGroup();

        $this->checkEOQ();

        return $unit;
    }

    /**
     * @param string $parentLogical
     *
     * @return AST\LogicalUnit
     */
    public function parseWhereLogicalGroup()
    {
        $condition = null;

        // SUB-GROUP
        if($this->tokenizer->is(TokenDefinition::T_LEFT_PAREN))
        {
            $condition = new AST\LogicalGroup();

            $this->tokenizer->proceedSkip();

            $condition->firstChild = $this->parseWhereLogicalGroup();

            $this->tokenizer->check(TokenDefinition::T_RIGHT_PAREN);

            $this->tokenizer->proceedSkip();

            $condition->next = $this->parseWhereLogicalGroup();
        }

        // Condition, [followed by "AND"/"OR" condition]
        elseif($this->tokenizer->is(TokenDefinition::T_EXPRESSION))
        {
            $right      = null;

            $op         = null;

            $left       = null;

            $value = $this->tokenizer->getValue();

            $this->tokenizer->proceedSkip();

            if($this->tokenizer->is(TokenDefinition::T_LEFT_PAREN))
            {
                $left = $this->parseFunction($value);
            }
            else
            {
                $left = new AST\Field($value);
            }

            // OPERATOR [including NOT IN]
            if($this->tokenizer->is(TokenDefinition::T_LOGICAL_OPERATOR))
            {
                $this->tokenizer->check(TokenDefinition::T_LOGICAL_OPERATOR, 'NOT');

                $this->tokenizer->proceedSkip();

                $this->tokenizer->check(TokenDefinition::T_OPERATOR, 'IN');

                $operator = 'NOT IN';
            }
            else
            {
                $this->tokenizer->check(TokenDefinition::T_OPERATOR, array(
                    '=', '!=', '>=', '<=', '<', '>', 'IN', 'INCLUDES', 'EXCLUDES', 'LIKE'
                ));
                $operator = $this->tokenizer->getValue();
            }

            // DOUBLE CHECK OPERATOR
            if(in_array($operator, array('IN', 'INCLUDES', 'EXCLUDES')))
            {
                $this->tokenizer->expect(array(
                    TokenDefinition::T_VARIABLE,
                    TokenDefinition::T_LEFT_PAREN
                ));
            }
            else
            {
                $this->tokenizer->expect(array(
                    TokenDefinition::T_DATETIME_FORMAT,
                    TokenDefinition::T_DATE_FORMAT,
                    TokenDefinition::T_DATE_LITERAL,
                    TokenDefinition::T_STRING,
                    TokenDefinition::T_VARIABLE,
                    TokenDefinition::T_NUMBER,
                    TokenDefinition::T_FLOAT,
                    TokenDefinition::T_CURRENCY_NUMBER,
                    TokenDefinition::T_NULL,
                    TokenDefinition::T_FALSE,
                    TokenDefinition::T_TRUE
                ));
            }

            // RIGHT PART, COMPLEX VALUE OR SUBQUERY FIRST
            if($this->tokenizer->is(TokenDefinition::T_LEFT_PAREN))
            {
                $this->tokenizer->proceedSkip();

                // SUBQUERY
                if($this->tokenizer->isKeyword('SELECT'))
                {
                    $right = new AST\Val($this->parseSubquery(), 'SUBQUERY');
                }
                else
                {
                    $this->tokenizer->check(array(
                        TokenDefinition::T_DATETIME_FORMAT,
                        TokenDefinition::T_KEYWORD,
                        TokenDefinition::T_DATE_FORMAT,
                        TokenDefinition::T_DATE_LITERAL,
                        TokenDefinition::T_STRING,
                        TokenDefinition::T_VARIABLE,
                        TokenDefinition::T_NUMBER,
                        TokenDefinition::T_FLOAT,
                        TokenDefinition::T_CURRENCY_NUMBER,
                        TokenDefinition::T_NULL,
                        TokenDefinition::T_FALSE,
                        TokenDefinition::T_TRUE
                    ));

                    $list = array();

                    while(true)
                    {
                        $this->tokenizer->proceedSkip();

                        if( ! $this->tokenizer->is(TokenDefinition::T_COMMA))
                        {
                            break;
                        }

                        $this->tokenizer->proceedSkip();

                        $list[] = new AST\Val($this->tokenizer->getValue(), $this->tokenizer->getName());
                    }
                    $right = new AST\Val($list, 'LIST');

                    $this->tokenizer->check(TokenDefinition::T_RIGHT_PAREN);
                }
            }
            else
            {
                $this->tokenizer->check(array(
                    TokenDefinition::T_DATETIME_FORMAT,
                    TokenDefinition::T_KEYWORD,
                    TokenDefinition::T_DATE_FORMAT,
                    TokenDefinition::T_DATE_LITERAL,
                    TokenDefinition::T_STRING,
                    TokenDefinition::T_VARIABLE,
                    TokenDefinition::T_NUMBER,
                    TokenDefinition::T_FLOAT,
                    TokenDefinition::T_CURRENCY_NUMBER,
                    TokenDefinition::T_NULL,
                    TokenDefinition::T_FALSE,
                    TokenDefinition::T_TRUE
                ));
                $right = new AST\Val($this->tokenizer->getValue(), $this->tokenizer->getName());
            }
            $condition = new AST\LogicalCondition($left, $operator, $right);

            $this->tokenizer->proceedSkip();

            $condition->next = $this->parseWhereLogicalGroup();
        }

        // WEITER GEHT's, VERKNÜPFUNG MIT "AND" o.Ä.
        elseif($this->tokenizer->is(TokenDefinition::T_LOGICAL_OPERATOR))
        {
            $logical              = $this->tokenizer->getValue();

            $this->tokenizer->proceedSkip();

            $condition            = $this->parseWhereLogicalGroup();

            if($condition->logical)
            {
                $this->throwError(sprintf('Unexpected "%s"', $condition->logical));
            }
            $condition->logical   = $logical;
        }
        return $condition;
    }

    /**
     * Example: WITH UserId='005D0000001AamR'
     *          Only equal sign operator allowed.
     *          No logical operators allowed
     *          No grouping by parenthesis allowed.
     *
     * @param string $parentLogical
     *
     * @return AST\LogicalUnit
     */
    public function parseWithLogicalGroup($parentLogical = null)
    {
        $right      = null;
        $op         = null;

        $this->tokenizer->check(TokenDefinition::T_EXPRESSION);

        $left = new AST\Field($this->tokenizer->getValue());

        $this->tokenizer->expect(TokenDefinition::T_OPERATOR, '=');

        $operator = $this->tokenizer->getValue();

        $this->tokenizer->expect(array(
            TokenDefinition::T_DATETIME_FORMAT,
            TokenDefinition::T_DATE_FORMAT,
            TokenDefinition::T_DATE_LITERAL,
            TokenDefinition::T_STRING,
            TokenDefinition::T_VARIABLE
        ));

        $right = new AST\Val($this->tokenizer->getValue(), $this->tokenizer->getName());

        $this->tokenizer->proceedSkip();

        return new AST\LogicalCondition($left, $operator, $right);
    }

    /**
     * WITH DATA CATEGORY field__c OPERATOR field__c
     *
     * @return AST\LogicalGroup
     */
    public function parseWithDataCategoryLogicalGroup()
    {
        if($this->tokenizer->is(TokenDefinition::T_EXPRESSION))
        {
            // dataCategoryGroupName
            $this->tokenizer->check(TokenDefinition::T_EXPRESSION);

            $groupName = new AST\Field($this->tokenizer->getValue());

            // filteringSelector
            $this->tokenizer->expect(TokenDefinition::T_OPERATOR, array(
                'ABOVE', 'BELOW', 'ABOVE_OR_BELOW', 'AT'
            ));

            $filteringSelector = $this->tokenizer->getValue();

            $this->tokenizer->proceedSkip();

            $categoryName = null;

            // LIST
            if($this->tokenizer->is(TokenDefinition::T_LEFT_PAREN))
            {
                $categoryNames = array();

                while(true)
                {
                    $this->tokenizer->expect(TokenDefinition::T_EXPRESSION);

                    $categoryNames[] = new AST\Val($this->tokenizer->getValue(), 'CATEGORYNAME');

                    $this->tokenizer->proceedSkip();

                    if(! $this->tokenizer->is(TokenDefinition::T_COMMA))
                    {
                        break;
                    }
                }
                $categoryName = new AST\Val($categoryNames, 'LIST');

                $this->tokenizer->check(TokenDefinition::T_RIGHT_PAREN);
            }
            else
            {
                $this->tokenizer->check(TokenDefinition::T_EXPRESSION);

                $categoryName = new AST\Val($this->tokenizer->getValue(), 'CATEGORYNAME');
            }

            $condition = new AST\LogicalCondition($groupName, $filteringSelector, $categoryName);

            $group->conditions[] = $condition;

            $this->tokenizer->proceedSkip();
        }
        elseif($this->tokenizer->is(TokenDefinition::T_LOGICAL_OPERATOR))
        {
            $this->tokenizer->check(TokenDefinition::T_LOGICAL_OPERATOR, 'AND');

            $condition = $this->parseWithDataCategoryLogicalGroup();

            if($condition->logical)
            {
                $this->throwError(sprintf('Unexpected "%s"', $condition->logical));
            }
            $condition->logical = 'AND';
        }
        return $condition;
    }

    /**
     * @return AST\GroupBy
     */
    public function parseGroupBy()
    {
        $this->tokenizer->proceedSkip();

        $this->enterScope(static::SCOPE_GROUPBY);

        $fields = array();

        $funcname = null;

        // FIRST
        $this->tokenizer->check(TokenDefinition::T_EXPRESSION);

        if(in_array(($name = $this->tokenizer->getName()), array('CUBE', 'ROLLUP')))
        {
            $funcname = $name;

            $this->tokenizer->expect(TokenDefinition::T_LEFT_PAREN);

            $fields = $this->parseFunction($name)->arguments;
        }

        else
        {
            $value = $this->tokenizer->getValue();

            $this->tokenizer->proceedSkip();

            $field = null;

            if($this->tokenizer->is(TokenDefinition::T_LEFT_PAREN))
            {
                $field = $this->parseFunction($value);
            }
            else
            {
                $field = new AST\Field($value);
            }

            $fields[] = $field;

            while(true)
            {
                if( ! $this->tokenizer->is(TokenDefinition::T_COMMA))
                {
                    break;
                }

                $this->tokenizer->expect(TokenDefinition::T_EXPRESSION);

                $value = $this->tokenizer->getValue();

                $this->tokenizer->proceedSkip();

                $field = null;

                if($this->tokenizer->is(TokenDefinition::T_LEFT_PAREN))
                {
                    $field = $this->parseFunction($value);
                }
                else
                {
                    $field = new AST\Field($value);
                }

                $fields[] = $field;
            }
        }

        $this->checkEOQ();

        return new AST\GroupBy($fields, $funcname);
    }

    /**
     * @return AST\LogicalGroup
     */
    public function parseHaving()
    {
        $this->enterScope(static::SCOPE_HAVING);

        $having = $this->parseHavingLogicalGroup();

        $this->checkEOQ();

        return $having;
    }

    /**
     * @return $integer
     */
    public function parseLimit()
    {
        $this->tokenizer->expect(TokenDefinition::T_NUMBER);

        $limit = $this->tokenizer->getValue();

        $this->tokenizer->proceedSkip();

        $this->checkEOQ();

        return $limit;
    }

    /**
     * @return int
     */
    public function parseOffset()
    {
        $this->tokenizer->expect(TokenDefinition::T_NUMBER);

        $offset = $this->tokenizer->getValue();

        $this->tokenizer->proceedSkip();

        $this->checkEOQ();

        return $offset;
    }

    /**
     * @return string
     */
    public function parseFor()
    {
        $this->tokenizer->expectKeyword(array('VIEW', 'REFERENCE'));

        $for = $this->tokenizer->getValue();

        $this->tokenizer->proceedSkip();

        $this->checkEOQ();

        return $for;
    }

    /**
     * @return string
     */
    public function parseUpdate()
    {
        $this->tokenizer->expectKeyword('VIEWSTAT');

        $value = $this->tokenizer->getValue();

        $this->tokenizer->proceedSkip();

        return $value;
    }

    /**
     * @return OrderByField[]
     */
    public function parseOrderBy()
    {
        $this->enterScope(self::SCOPE_ORDERBY);

        $retVal = array();

        while(true)
        {
            $this->tokenizer->expect(TokenDefinition::T_EXPRESSION);

            $value = $this->tokenizer->getValue();

            $this->tokenizer->proceedSkip();

            $field = null;

            if($this->tokenizer->is(TokenDefinition::T_LEFT_PAREN))
            {
                $field = $this->parseFunction($value);
            }
            else
            {
                $field = new AST\Field($value);
            }

            $sort = null;

            $nulls = null; // first/last

            if(
                $this->tokenizer->is(TokenDefinition::T_KEYWORD) &&
                in_array($this->tokenizer->getName(), array('DESC', 'ASC')))
            {
                $sort = $this->tokenizer->getName();

                $this->tokenizer->proceedSkip();
            }

            if($this->tokenizer->isKeyword('NULLS'))
            {
                $this->tokenizer->proceedSkip();

                $this->tokenizer->checkKeyword(array('FIRST', 'LAST'));

                $nulls = $this->tokenizer->getValue();

                $this->tokenizer->proceedSkip();
            }

            $retVal[] = new AST\OrderByField($field, $sort, $nulls);

            if( ! $this->tokenizer->is(TokenDefinition::T_COMMA))
            {
                break;
            }
        }

        $this->checkEOQ();

        return $retVal;
    }

    /**
     * @param $message
     * @throws TokenizerException
     */
    public function throwError($message)
    {
        throw new TokenizerException($message, $this->tokenizer);
    }

    /**
     * @param string $soql
     */
    public function setSoql($soql)
    {
        $this->isSubquery = false;

        $this->tokenizer->tokenize($soql);
    }

    /**
     * @param string $scope
     */
    public function enterScope($scope)
    {
        $this->scope = $scope;
    }

    /**
     * void
     */
    public function clearScope()
    {
        $this->scope = null;
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @return \Phpforce\Query\Tokenizer
     */
    public function getTokenizer()
    {
        return $this->tokenizer;
    }

    private function checkEOQ()
    {
        if($this->isSubquery)
        {
            $this->tokenizer->check(array(
                TokenDefinition::T_KEYWORD,
                TokenDefinition::T_RIGHT_PAREN
            ));
        }
        else
        {
            $this->tokenizer->check(array(
                TokenDefinition::T_KEYWORD,
                TokenDefinition::T_EOQ
            ));
        }
    }

    private function checkVeryEnd()
    {
        if($this->isSubquery)
        {
            $this->tokenizer->check(TokenDefinition::T_RIGHT_PAREN);
        }
        else
        {
            $this->tokenizer->check(TokenDefinition::T_EOQ);
        }
    }
}
