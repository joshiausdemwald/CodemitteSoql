<?php
namespace Phpforce\Query;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Tokenizer implements TokenizerInterface
{
    const ON_TOKEN_MATCH = 'token.match';

    private static $RIGHT_DELIMS = array(
        ' ', "\t", "\b", "\f", "\r", "\n", ',', ')', '/'
    );

    private static $LOGICAL_OPERATORS = array('AND', 'OR', 'NOT');

    private static $EXPRESSION_OPERATORS = array(
        'INCLUDES', 'LIKE', 'IN', /*'NOT IN', */'EXCLUDES',  // WHERE
        'AT', 'ABOVE', 'BELOW', 'ABOVE_OR_BELOW'); // WITH


    private static $DATE_LITERALS = array(
        'YESTERDAY',
        'TODAY',
        'TOMORROW',
        'LAST_WEEK',
        'THIS_WEEK',
        'NEXT_WEEK',
        'LAST_MONTH',
        'THIS_MONTH',
        'NEXT_MONTH',
        'LAST_90_DAYS',
        'NEXT_90_DAYS',
        'LAST_QUARTER',
        'NEXT_QUARTER',
        'THIS_YEAR',
        'LAST_YEAR',
        'NEXT_YEAR',
        'THIS_FISCAL_QUARTER',
        'LAST_FISCAL_QUARTER',
        'NEXT_FISCAL_QUARTER',
        'THIS_FISCAL_YEAR',
        'LAST_FISCAL_YEAR',
        'NEXT_FISCAL_YEAR'
    );

    private static $DYNAMIC_DATE_LITERALS = array(
        'NEXT_N_DAYS',
        'LAST_N_DAYS',
        'NEXT_N_WEEKS',
        'LAST_N_WEEKS',
        'NEXT_N_MONTHS',
        'LAST_N_MONTHS',
        'NEXT_N_QUARTERS',
        'LAST_N_QUARTERS',
        'NEXT_N_YEARS',
        'LAST_N_YEARS',
        'NEXT_N_FISCAL_​QUARTERS',
        'LAST_N_FISCAL_​QUARTERS',
        'NEXT_N_FISCAL_YEARS',
        'LAST_N_FISCAL_YEARS'
    );

    /**
     * @var array
     */
    private static $KEYWORDS= array(
        'SELECT',
        'TYPEOF',
        'FROM',
        'WHERE',
        'WITH',
        'GROUP',
        'HAVING',
        'ORDER',
        'LIMIT',
        'FOR',
        'OFFSET',
        'UPDATE',
        'USING',
        'AS',
        'BY',
        'DATA',
        'CATEGORY',
        'DESC',
        'ASC',
        'NULLS',
        'FIRST',
        'LAST',
        'WHEN',
        'THEN',
        'ELSE',
        'END',
        'ALL',
        'ROWS',
        'VIEWSTAT',
        'VIEW',
        'REFERENCE'
    );

    public static $ISO_CODES = array(
        'AED',
        'AFN',
        'ALL',
        'AMD',
        'ANG',
        'AOA',
        'ARS',
        'AUD',
        'AWG',
        'AZN',
        'BAM',
        'BBD',
        'BDT',
        'BGN',
        'BHD',
        'BIF',
        'BMD',
        'BND',
        'BOB',
        'BRL',
        'BSD',
        'BTN',
        'BWP',
        'BYR',
        'BZD',
        'CAD',
        'CDF',
        'CHF',
        'CLP',
        'CNY',
        'COP',
        'CRC',
        'CUC',
        'CUP',
        'CVE',
        'CZK',
        'DJF',
        'DKK',
        'DOP',
        'DZD',
        'EGP',
        'ERN',
        'ETB',
        'EUR',
        'FJD',
        'FKP',
        'GBP',
        'GEL',
        'GGP',
        'GHS',
        'GIP',
        'GMD',
        'GNF',
        'GTQ',
        'GYD',
        'HKD',
        'HNL',
        'HRK',
        'HTG',
        'HUF',
        'IDR',
        'ILS',
        'IMP',
        'INR',
        'IQD',
        'IRR',
        'ISK',
        'JEP',
        'JMD',
        'JOD',
        'JPY',
        'KES',
        'KGS',
        'KHR',
        'KMF',
        'KPW',
        'KRW',
        'KWD',
        'KYD',
        'KZT',
        'LAK',
        'LBP',
        'LKR',
        'LRD',
        'LSL',
        'LTL',
        'LVL',
        'LYD',
        'MAD',
        'MDL',
        'MGA',
        'MKD',
        'MMK',
        'MNT',
        'MOP',
        'MRO',
        'MUR',
        'MVR',
        'MWK',
        'MXN',
        'MYR',
        'MZN',
        'NAD',
        'NGN',
        'NIO',
        'NOK',
        'NPR',
        'NZD',
        'OMR',
        'PAB',
        'PEN',
        'PGK',
        'PHP',
        'PKR',
        'PLN',
        'PYG',
        'QAR',
        'RON',
        'RSD',
        'RUB',
        'RWF',
        'SAR',
        'SBD',
        'SCR',
        'SDG',
        'SEK',
        'SGD',
        'SHP',
        'SLL',
        'SOS',
        'SPL',
        'SRD',
        'STD',
        'SVC',
        'SYP',
        'SZL',
        'THB',
        'TJS',
        'TMT',
        'TND',
        'TOP',
        'TRY',
        'TTD',
        'TVD',
        'TWD',
        'TZS',
        'UAH',
        'UGX',
        'USD',
        'UYU',
        'UZS',
        'VEF',
        'VND',
        'VUV',
        'WST',
        'XAF',
        'XCD',
        'XDR',
        'XOF',
        'XPF',
        'YER',
        'ZAR',
        'ZMW',
        'ZWD'
    );

    /**
     * @var string
     */
    private $value;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $name;

    /**
     * @var integer
     */
    private $col;

    /**
     * @var integer
     */
    private $index;

    /**
     * @var integer
     */
    private $line;

    /**
     * @var string
     */
    private $soql;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param EventSubscriberInterface $subscriber
     *
     * @return Tokenizer
     */
    public function subscribe(EventSubscriberInterface $subscriber)
    {
        $this->eventDispatcher->addSubscriber($subscriber);

        return $this;
    }

    /**
     * @param $listener
     *
     * @return Tokenizer
     */
    public function listen($listener)
    {
        $this->eventDispatcher->addListener(self::ON_TOKEN_MATCH, $listener);

        return $this;
    }

    /**
     * @param $soql
     * @return $this
     */
    public function tokenize($soql)
    {
        $this->soql = $soql;

        $this->col  = $this->index = 0;

        $this->line = 0;

        return $this;
    }

    /**
     * @return array
     */
    public function next()
    {
        $this->value = '';
        $this->name  = null;
        $this->type  = null;

        $char = $this->char();

        // LINEBREAK
        if(in_array($char, array("\r", "\n")))
        {
            $this->type = 'T_LINEBREAK';
            $this->name = 'LINEBREAK';
            $this->value = PHP_EOL;
            $this->line++;
            $this->col = 0;

            $next = $this->nextChar();

            if("\r" === $char && "\n" === $next)
            {
                $this->nextChar();
            }
        }

        // WHITESPACE
        elseif(in_array($char, array(' ', "\b", "\f", "\t")))
        {
            $this->type = 'T_WHITESPACE';
            $this->name = 'WHITESPACE';
            $this->value = ' ';

            if(in_array($this->nextChar(), array(' ', "\b", "\f", "\t")))
            {
                $this->nextChar();
            }
        }

        // PARENTHESIS
        elseif($char === '(')
        {
            $this->type = 'T_LEFT_PAREN';
            $this->name = 'LEFT_PAREN';
            $this->value = '(';
            $this->nextChar();
        }
        elseif($char === ')')
        {
            $this->type = 'T_RIGHT_PAREN';
            $this->name = 'RIGHT_PAREN';
            $this->value = ')';
            $this->nextChar();
        }

        // COMMA
        elseif($char === ',')
        {
            $this->type = 'T_COMMA';
            $this->name = 'COMMA';
            $this->value = ',';
            $this->nextChar();
        }

        // VARIABLE
        elseif($char === ':')
        {
            $this->tokenizeVariable();
        }

        // STRING
        elseif(in_array($char, array('"', '\'')))
        {
            $this->tokenizeString();
        }

        elseif(in_array($char, array('<', '>', '!', '=')))
        {
            $this->tokenizeOperator();
        }

        // DATE_FORMAT
        // DATETIME_FORMAT
        // NUMBER
        elseif(ctype_digit($char) || in_array($char, array('+', '-')))
        {
            $this->tokenizeDigit();
        }

        elseif($char == '/')
        {
            $this->tokenizeComment();
        }

        // COMPLEX VALUES:
        // KEYWORDS
        // FIELDNAMES
        // FUNCTIONS
        elseif(ctype_alpha($char))
        {
            $this->tokenizeExpression();
        }

        // EOF?
        elseif(false === $char)
        {
            if($this->type === 'T_EOQ')
            {
                throw new TokenizerException('End of query already reached.', $this);
            }
            $this->type = 'T_EOQ';
            $this->name = 'EOQ';
            $this->value = "\0";
        }
        else
        {
            throw new TokenizerException(sprintf('Syntax error: unexpected "%s".', $char), $this);
        }
        return $this;
    }

    /**
     * @return string
     */
    private function char()
    {
        return isset($this->soql[$this->index]) ? $this->soql[$this->index] : false;
    }

    /**
     * @return string
     */
    private function nextChar()
    {
        $this->index++;

        $this->col++;

        return $this->char();
    }

    private function tokenizeString()
    {
        $delim = $this->char();

        $this->type = 'T_STRING';
        $this->name = 'STRING';

        $ok = false;

        while(false !== ($next = $this->nextChar()))
        {
            if($next === '\\')
            {
                if(false === ($nn = $this->nextChar()))
                {
                    break;
                }
                $next .= $nn;
            }

            // END
            elseif($next === $delim)
            {
                $ok = true;

                $this->nextChar();

                break;
            }
            $this->value .= $next;
        }

        if(!$ok)
        {
            throw new TokenizerException('Syntax error: unterminated string literal.');
        }
    }

    private function tokenizeVariable()
    {
        $this->value = '';
        $this->type = 'T_VARIABLE';
        $this->name = 'VARIABLE';

        while(false !== ($next = $this->nextChar()))
        {
            // READY
            if(in_array($next, self::$RIGHT_DELIMS))
            {
                break;
            }

            $this->value .= $next;
        }

        if( ! preg_match('#[a-zA-Z][a-zA-Z0-9_]*?#', $this->value))
        {
            throw new TokenizerException('Invalid variable expression. Variable must not begin with a digits and may contain only alphanumeric characters and "_".', $this);
        }
    }

    private function tokenizeOperator()
    {
        static $names = null;

        if(null === $names)
        {
            $names = array(
                '<=' => 'LTE',
                '<' => 'LT',
                '>=' => 'GTE',
                '>' => 'GT',
                '!=' => 'NE',
                '=' => 'EQ'
            );
        }

        $this->type = 'T_OPERATOR';

        $this->value = $this->char();

        $op = $this->value . $this->nextChar();

        if(in_array($op, array('!=', '<=', '>=')))
        {
            $this->value = $op;

            $this->nextChar();
        }
        $this->name = $names[$this->value];
    }

    private function tokenizeDigit()
    {
        $this->value = $this->char();

        while(false !== ($next = $this->nextChar()))
        {
            // READY
            if(in_array($next, self::$RIGHT_DELIMS))
            {
                break;
            }

            $this->value .= $next;
        }

        $date_pattern  = '[0-9]{4}\\-(?:(?:0[1-9])|(?:1[0-2]))\\-(?:(?:0[1-9])|(?:[1-2][0-9])|(?:3[0-1]))';

        $datetime_pattern = $date_pattern . 'T(?:(?:[0-1][0-9])|(?:2[0-3])):[0-5][0-9]:(?:(?:[0-5][0-9])|(?:60))(?:Z|(?:[+\-](?:(?:[0-1][0-9])|(?:2[0-3])):[0-5][0-9]))';

        if(is_numeric($this->value))
        {
            $this->type = 'T_NUMBER';
            $this->name = 'NUMBER';

            if(is_float(($this->value*1)))
            {
                $this->type = 'T_FLOAT';
                $this->name = 'FLOAT';
            }
        }
        elseif(19 < strlen($this->value) && preg_match('#^' . $datetime_pattern . '$#', $this->value))
        {
            $this->type = 'T_DATETIME_FORMAT';
            $this->name = 'DATETIME_FORMAT';
        }
        elseif(10 === strlen($this->value) && preg_match('#^' . $date_pattern . '$#', $this->value))
        {
            $this->type = 'T_DATE_FORMAT';
            $this->name = 'DATE_FORMAT';
        }
        else
        {
            throw new TokenizerException(sprintf('Syntax error: unexpected "%s".', $this->value), $this);
        }
    }

    private function tokenizeComment()
    {
        $this->type = 'T_COMMENT';
        $this->name = 'COMMENT';
        $this->value = '';

        $next = $this->nextChar();

        // MULTILINE
        if('*' === $next)
        {
            $ok = false;

            while(false !== ($next = $this->nextChar()))
            {
                if(false === $next)
                {
                    break;
                }

                if($next === '*' && '/' === $this->nextChar())
                {
                    $ok = true;
                    $this->nextChar();
                    break;
                }
                $this->value .= $next;
            }

            if( ! $ok)
            {
                throw new TokenizerException('Unterminated comment!', $this);
            }
        }

        // SINGLELINE
        elseif('/' === $next)
        {
            while(false !== ($next = $this->nextChar()) && ! in_array($next, array("\r", "\n")))
            {
                $this->value .= $next;
            }
        }
        else
        {
            throw new TokenizerException(sprintf('Syntax error: unexpected "%s".', $next), $this);
        }
    }

    /**
     * @throws TokenizerException
     */
    private function tokenizeExpression()
    {
        static $dynamicDateLiteralPattern = null;

        if(null === $dynamicDateLiteralPattern)
        {
            $dynamicDateLiteralPattern = '#^(?:' . implode('|', self::$DYNAMIC_DATE_LITERALS) . ')\:[1-9][0-9]*$#';
        }

        $this->value = $this->char();

        if(ctype_alpha($this->value))
        {
            while(false !== ($next = $this->nextChar()))
            {
                if( ! ctype_alnum($next) && ! in_array($next, array('.', '_', ':')))
                {
                    break;
                }
                $this->value .= $next;
            }
        }

        $uppercaseValue = strtoupper($this->value);

        if('NULL' === $uppercaseValue)
        {
            $this->name = $this->value = 'NULL';
            $this->type = 'T_NULL';
        }
        elseif('FALSE' === $uppercaseValue)
        {
            $this->name = $this->value = 'FALSE';
            $this->type = 'T_FALSE';
        }
        elseif('TRUE' === $uppercaseValue)
        {
            $this->name = $this->value = 'TRUE';
            $this->type = 'T_TRUE';
        }
        elseif(in_array($uppercaseValue, self::$KEYWORDS))
        {
            $this->name = 'KEYWORD';
            $this->type = 'T_KEYWORD';
            $this->value = $uppercaseValue;
        }

        elseif(in_array($uppercaseValue, self::$LOGICAL_OPERATORS))
        {
            $this->name = 'LOGICAL_OPERATOR';
            $this->type = 'T_LOGICAL_OPERATOR';
            $this->value = $uppercaseValue;
        }

        elseif(in_array($uppercaseValue, self::$EXPRESSION_OPERATORS))
        {
            $this->name = 'OPERATOR';
            $this->type = 'T_OPERATOR';
            $this->value = $uppercaseValue;
        }

        elseif(in_array($uppercaseValue, self::$DATE_LITERALS) || preg_match($dynamicDateLiteralPattern, $uppercaseValue))
        {
            $this->type = 'T_DATE_LITERAL';
            $this->name = 'DATE_LITERAL';
        }
        elseif(ctype_alpha($code = substr($uppercaseValue, 0, 3)) && in_array($code, self::$ISO_CODES) && is_numeric(substr($uppercaseValue, 3)))
        {
            $this->type = 'T_CURRENCY_NUMBER';
            $this->name = 'CURRENCY_NUMBER';
        }
        elseif(strlen($uppercaseValue) > 0)
        {
            $this->type = 'T_EXPRESSION';
            $this->name = 'EXPRESSION';
        }
        else
        {
            throw new TokenizerException('Syntax error.', $this);
        }
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getCol()
    {
        return $this->col;
    }

    /**
     * @return int
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * @return Tokenizer
     */
    public function proceedSkip()
    {
        do {
            $this->next();
        }
        while(in_array($this->type, array(
            TokenDefinition::T_WHITESPACE,
            TokenDefinition::T_LINEBREAK,
            TokenDefinition::T_COMMENT
        )));
        return $this;
    }

    /**
     * @param string $type
     * @param string|null $name
     *
     * @return bool
     */
    public function expect($type, $name = null)
    {
        $this->proceedSkip();

        return $this->check($type, $name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function expectKeyword($name)
    {
        $this->proceedSkip();

        return $this->checkKeyword($name) ;
    }

    /**
     * @param string|array $type
     * @param string| null $name
     * @throws TokenizerException
     * @return Tokenizer
     */
    public function check($type, $names = null)
    {
        if( ! $this->is($type) || (null !== $names && ! in_array($this->value, (array)$names)))
        {
            throw new TokenizerException(sprintf('Parse error! Unexpected "%s %s" with value "%s".', $this->type, $this->name, $this->value), $this);
        }

        return $this;
    }

    /**
     * @param string $name
     * @throws TokenizerException
     * @return Tokenizer
     */
    public function checkKeyword($name)
    {
        if( ! $this->check(TokenDefinition::T_KEYWORD, $name))
        {
            throw new TokenizerException(sprintf('Parse error! Unexpected %s %s with value "%s" (expected T_KEYWORD "%s").', $this->type, $this->name, $this->value, $name), $this);
        }
        return $this;
    }

    /**
     * @param string $name
     * @return boolean
     */
    public function is($type)
    {
        return in_array($this->type, (array)$type);
    }

    /**
     * @param string $name
     * @return boolean
     */
    public function isKeyword($name)
    {
        return $this->is(TokenDefinition::T_KEYWORD) && $name === $this->value;
    }

    /**
     * @return string
     */
    public function getSoql()
    {
        return $this->soql;
    }
}