<?php
namespace Phpforce\Query\Soql;

class TokenDefinition
{
    const
        T_BOQ = 'T_BOQ',
        T_EOQ = 'T_EOQ',
        T_KEYWORD = 'T_KEYWORD',
        T_LINEBREAK = 'T_LINEBREAK',
        T_WHITESPACE = 'T_WHITESPACE',
        T_DOT = 'T_DOT',
        T_COMMA = 'T_COMMA',
        T_LEFT_PAREN = 'T_LEFT_PAREN',
        T_RIGHT_PAREN = 'T_RIGHT_PAREN',
        T_AND = 'T_AND',
        T_OR = 'T_OR',
        T_OPERATOR = 'T_OPERATOR',
        T_LOGICAL_OPERATOR = 'T_LOGICAL_OPERATOR',

        T_DATE_LITERAL = 'T_DATE_LITERAL',
        T_DATE_FORMAT = 'T_DATE_FORMAT',
        T_DATETIME_FORMAT = 'T_DATETIME_FORMAT',
        T_NUMBER = 'T_NUMBER',
        T_FLOAT = 'T_FLOAT',
        T_STRING = 'T_STRING',
        T_COMMENT = 'T_COMMENT',
        T_VARIABLE =  'T_VARIABLE',
        T_EXPRESSION = 'T_EXPRESSION',
        T_CURRENCY_NUMBER = 'T_CURRENCY_NUMBER',
        T_NULL = 'T_NULL',
        T_FALSE = 'T_FALSE',
        T_TRUE  = 'T_TRUE'
    ;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $value;

    /**
     * @var string
     */
    public $pattern;

    /**
     * @var string
     */
    public $type;

    /**
     * @param string $name
     * @param string $type
     * @param string|null $value
     * @param string|null $pattern
     */
    private function __construct($name, $type, $value = null, $pattern = null)
    {
        $this->name = $name;

        $this->value = $value;

        $this->pattern = $pattern;

        $this->type = $type;
    }

    /**
     * @param string $name
     * @param string $type
     * @return TokenDefinition
     */
    public static function simple($name, $type)
    {
        return new TokenDefinition($name, $type, $name, null);
    }

    /**
     * @param string $name
     * @param string $value
     * @param string $type
     * @return TokenDefinition
     */
    public static function simpleValue($name, $value, $type)
    {
        return new TokenDefinition($name, $type, $value, null);
    }

    /**
     * @param string $name
     * @param string $pattern
     * @param string $type
     * @return TokenDefinition
     */
    public static function regex($name, $pattern, $type)
    {
        return new TokenDefinition($name, $type, null, $pattern);
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
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }
}