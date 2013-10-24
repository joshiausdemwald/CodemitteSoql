<?php
namespace Codemitte\ForceToolkit\Soql;


use Codemitte\ForceToolkit\Soql\AST as AST;
use Codemitte\ForceToolkit\Soql\Cache\CacheInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Yaml\Exception\ParseException;

class Parser
{
    const
        SCOPE_SELECT = 'SCOPE_SELECT',
        SCOPE_FROM = 'SCOPE_FROM',
        SCOPE_WHERE = 'SCOPE_WHERE',
        SCOPE_WITH = 'SCOPE_WITH',
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
     * @var CacheInterface
     */
    private $cache;

    /**
     * @param Tokenizer $tokenizer
     * @param boolean $debug
     */
    public function __construct(TokenizerInterface $tokenizer, CacheInterface $cache)
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
        if(null === ($this->query = $this->cache->get($soql)))
        {
            $this->tokenizer->tokenize($soql);

            $this->tokenizer->proceedSkip();

            $this->cache->set($soql, ($this->query = $this->parseQuery()));
        }
        return $this->query;
    }

    /**
     * @return AST\Query
     */
    public function parseQuery()
    {
        $this->tokenizer->checkKeyword('SELECT');

        $this->scope = static::SCOPE_SELECT;

        $query = new AST\Query();

        $query->select = $this->parseSelectFieldList();

        $this->tokenizer->checkKeyword('FROM');

        $this->scope = static::SCOPE_FROM;

        $this->tokenizer->expect(TokenDefinition::T_EXPRESSION);

        $from = $this->tokenizer->getValue();

        $this->tokenizer->proceedSkip();

        $alias = $this->parseAlias();

        $query->from = new AST\From($from, $alias);

        $this->tokenizer->check(array(
            TokenDefinition::T_KEYWORD,
            TokenDefinition::T_EOQ,
            TokenDefinition::T_RIGHT_PAREN
        ));

        if($this->tokenizer->isKeyword('WHERE'))
        {
            $this->scope = static::SCOPE_WHERE;

            $this->tokenizer->proceedSkip();

            $query->where = new AST\Where($this->parseLogicalGroup());
        }

        $this->tokenizer->check(array(
            TokenDefinition::T_KEYWORD,
            TokenDefinition::T_EOQ,
            TokenDefinition::T_RIGHT_PAREN
        ));

        if($this->tokenizer->isKeyword('WITH'))
        {
            $this->scope = static::SCOPE_WITH;

            $this->tokenizer->proceedSkip();

            if($this->tokenizer->isKeyword('DATA'))
            {
                $this->tokenizer->expectKeyword('CATEGORY');

                $query->with = new AST\With($this->parseWithDataCategory(), AST\With::DATA_CATEGORY);


            }
            else
            {
                $query->with = new AST\With($this->parseLogicalGroup());
            }
        }

        $this->tokenizer->check(array(
            TokenDefinition::T_KEYWORD,
            TokenDefinition::T_EOQ,
            TokenDefinition::T_RIGHT_PAREN
        ));

        if($this->tokenizer->isKeyword('GROUP'))
        {
            $this->scope = static::SCOPE_GROUPBY;

            $this->tokenizer->expectKeyword('BY');

            $this->tokenizer->proceedSkip();

            $query->groupBy = $this->parseGroupBy();

            $this->tokenizer->check(array(
                TokenDefinition::T_KEYWORD,
                TokenDefinition::T_EOQ,
                TokenDefinition::T_RIGHT_PAREN
            ));

            if($this->tokenizer->isKeyword('HAVING'))
            {
                $this->scope = static::SCOPE_HAVING;

                $this->tokenizer->proceedSkip();

                $query->having = new AST\Having($this->parseLogicalGroup());
            }
        }

        $this->tokenizer->check(array(
            TokenDefinition::T_KEYWORD,
            TokenDefinition::T_EOQ,
            TokenDefinition::T_RIGHT_PAREN
        ));

        if($this->tokenizer->isKeyword('ORDER'))
        {
            $this->scope = self::SCOPE_ORDERBY;

            $this->tokenizer->expectKeyword('BY');

            $query->orderBy = new AST\OrderBy($this->parseOrderBy());
        }

        $this->tokenizer->check(array(
            TokenDefinition::T_KEYWORD,
            TokenDefinition::T_EOQ,
            TokenDefinition::T_RIGHT_PAREN
        ));

        $this->scope = null;

        if($this->tokenizer->isKeyword('LIMIT'))
        {
            $this->tokenizer->expect(TokenDefinition::T_NUMBER);

            $query->limit = $this->tokenizer->getValue();

            $this->tokenizer->proceedSkip();
        }

        $this->tokenizer->check(array(
            TokenDefinition::T_KEYWORD,
            TokenDefinition::T_EOQ,
            TokenDefinition::T_RIGHT_PAREN
        ));

        if($this->tokenizer->isKeyword('OFFSET'))
        {
            $this->tokenizer->expect(TokenDefinition::T_NUMBER);

            $query->offset = $this->tokenizer->getValue();

            $this->tokenizer->proceedSkip();
        }

        $this->tokenizer->check(array(
            TokenDefinition::T_KEYWORD,
            TokenDefinition::T_EOQ,
            TokenDefinition::T_RIGHT_PAREN
        ));

        if($this->tokenizer->isKeyword('FOR'))
        {
            $this->tokenizer->expectKeyword(array('VIEW', 'REFERENCE'));

            $query->for = $this->tokenizer->getValue();

            $this->tokenizer->proceedSkip();
        }

        $this->tokenizer->check(array(
            TokenDefinition::T_KEYWORD,
            TokenDefinition::T_EOQ,
            TokenDefinition::T_RIGHT_PAREN
        ));

        if($this->tokenizer->isKeyword('UPDATE'))
        {
            $this->tokenizer->expectKeyword('VIEWSTAT');

            $query->update = $this->tokenizer->getValue();
        }

        return $query;
    }

    /**
     * @return Field[]|Query[]|Typeof[]
     */
    public function parseSelectFieldList()
    {
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
            $this->tokenizer->proceedSkip();

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
     * @return AST\LogicalGroup
     */
    public function parseLogicalGroup($groupLogical = null, $level = 0)
    {
        $group = new AST\LogicalGroup($groupLogical);

        $n = 0;

        while(true)
        {
            $logical = null;
            $right = null;
            $op = null;
            $left = null;
            $not  = false;

            // LOGICAL OPERATOR (AND/OR)
            if($n > 0)
            {
                $this->tokenizer->check(TokenDefinition::T_LOGICAL_OPERATOR);

                $logical = $this->tokenizer->getValue();

                $this->tokenizer->proceedSkip();
            }

            // SUB-GROUP
            if($this->tokenizer->is(TokenDefinition::T_LEFT_PAREN))
            {
                if($level > 0 && ! $logical)
                {
                    $this->throwError('Missing logical operator "AND", "OR" before logical group.');
                }

                $this->tokenizer->proceedSkip();

                $group->conditions[] = $this->parseLogicalGroup($logical, $level++);

                $this->tokenizer->check(TokenDefinition::T_RIGHT_PAREN);

                $this->tokenizer->proceedSkip();
            }

            // Condition, followed by "AND"/"OR"
            elseif($this->tokenizer->is(TokenDefinition::T_EXPRESSION))
            {
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

                // NOT IN?
                if($this->tokenizer->is(TokenDefinition::T_LOGICAL_OPERATOR))
                {
                    $this->tokenizer->check(TokenDefinition::T_LOGICAL_OPERATOR, 'NOT');

                    $not = true;

                    $this->tokenizer->proceedSkip();

                    $this->tokenizer->check(TokenDefinition::T_OPERATOR, 'IN');
                }

                $this->tokenizer->check(TokenDefinition::T_OPERATOR, array(
                    '=', '!=', '>=', '<=', '<', '>', 'IN', 'INCLUDES', 'EXCLUDES', 'LIKE'
                ));

                $operator = $this->tokenizer->getValue();

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

                // COMPLEX OR SUBQUERY
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

                        $this->tokenizer->proceedSkip();
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

                    $this->tokenizer->proceedSkip();
                }
                $group->conditions[] = new AST\LogicalCondition($left, $operator, $right, $logical);
            }

            if( ! $this->tokenizer->is(TokenDefinition::T_LOGICAL_OPERATOR))
            {
                break;
            }

            $n ++;
        }
        return $group;
    }

    /**
     * @return AST\LogicalGroup
     */
    public function parseWithDataCategory()
    {
        $group = new AST\LogicalGroup();

        while(true)
        {
            // dataCategoryGroupName
            $this->tokenizer->expect(TokenDefinition::T_EXPRESSION);

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

            $group->conditions[] = new AST\LogicalCondition($groupName, $filteringSelector, $categoryName);

            $this->tokenizer->proceedSkip();

            if($this->tokenizer->is(TokenDefinition::T_LOGICAL_OPERATOR))
            {
                $this->tokenizer->check(TokenDefinition::T_LOGICAL_OPERATOR, 'AND');
            }
            else
            {
                break;
            }
        }
        return $group;
    }

    /**
     * @return AST\GroupBy
     */
    public function parseGroupBy()
    {
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

        return new AST\GroupBy($fields, $funcname);
    }

    /**
     * @return OrderByField[]
     */
    public function parseOrderBy()
    {
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
}
