<?php
/**
 * Created by PhpStorm.
 * User: joshi
 * Date: 21.10.13
 * Time: 23:28
 */

namespace Codemitte\ForceToolkit\Soql\Renderer;


use Codemitte\ForceToolkit\Soql\AST\Field;
use Codemitte\ForceToolkit\Soql\AST\From;
use Codemitte\ForceToolkit\Soql\AST\GroupBy;
use Codemitte\ForceToolkit\Soql\AST\Having;
use Codemitte\ForceToolkit\Soql\AST\LogicalCondition;
use Codemitte\ForceToolkit\Soql\AST\LogicalGroup;
use Codemitte\ForceToolkit\Soql\AST\Query;
use Codemitte\ForceToolkit\Soql\AST\SoqlFunction;
use Codemitte\ForceToolkit\Soql\AST\Typeof;
use Codemitte\ForceToolkit\Soql\AST\Val;
use Codemitte\ForceToolkit\Soql\AST\Where;
use Codemitte\ForceToolkit\Soql\AST\With;
use Codemitte\ForceToolkit\Soql\Builder\Type\Currency;
use Codemitte\ForceToolkit\Soql\Builder\Type\Date;
use Codemitte\ForceToolkit\Soql\TokenDefinition;

class Renderer
{
    /**
     * @var string
     */
    private $output;

    /**
     * @var array
     */
    private $variables = array();

    /**
     * @param Query $query
     * @param array $variables
     */
    public function render(Query $query, array $variables = array())
    {
        $this->output = '';

        $this->variables = $variables;

        $this->renderQuery($query);

        return $this->output;
    }

    /**
     * @param Query $query
     * @param array $variables
     *
     * @return string $output
     */
    public function renderQuery(Query $query)
    {
        $this->renderSelect($query->select);

        $this->renderFrom($query->from);

        $this->renderWhere($query->where);

        $this->renderWith($query->with);

        $this->renderHaving($query->having);

        $this->renderGroupBy($query->groupBy);

        $this->renderLimit($query->limit);

        $this->renderOffset($query->offset);

        $this->renderFor($query->for);

        $this->renderUpdate($query->update);
    }

    /**
     * @param array $fieldsn
     */
    public function renderSelect(array $fields)
    {
        $this->output .= 'SELECT ';

        for($i = 0; $len = count($fields), $i < $len; $i ++)
        {
            $field = $fields[$i];

            if($field instanceof Field)
            {
                $this->renderField($field);
            }
            elseif($field instanceof Query)
            {
                $this->output .= '(';
                $this->renderQuery($field);
                $this->output .= ')';
            }
            elseif($field instanceof Typeof)
            {
                $this->renderTypeof($field);
            }
            elseif($field instanceof SoqlFunction)
            {
                $this->renderFunction($field);
            }

            if($i < $len - 1)
            {
                $this->output .= ', ';
            }
        };
    }

    /**
     * @param Field|array<Field> $fields
     */
    public function renderFields($fields = null)
    {
        $fields = (array)$fields;

        if(count($fields) > 1)
        {
            $this->output .= '(';

            for($i = 0; $len = count($fields), $i < $len; $i++)
            {
                $this->renderField($fields[$i]);

                if($i < $len -1)
                {
                    $this->output .= ', ';
                }
            }
            $this->output .= ')';
        }
        else
        {
            $this->renderField($fields[0]);
        }
    }

    /**
     * @param Field $field
     */
    private function renderField(Field $field)
    {
        $this->output .= $field->value;

        if($field->alias)
        {
            $this->output .= ' AS ' . $field->alias;
        }
    }

    /**
     * @param SoqlFunction $function
     */
    public function renderFunction(SoqlFunction $function)
    {
        $this->output .= $function->name . '(';
        $this->renderArguments($function->arguments);
        $this->output .= ')';
        if($function->alias)
        {
            $this->output .= ' AS ' . $function->alias;
        }
    }

    /**
     * @param array $arguments
     */
    public function renderArguments(array $arguments)
    {
        $n = 0;
        $size = count($arguments);
        foreach($arguments AS $argument)
        {
            if($argument instanceof SoqlFunction)
            {
                $this->renderFunction($argument);
            }
            elseif($argument instanceof Field)
            {
                $this->renderField($argument);
            }
            if($n < $size - 1)
            {
                $this->output .= ', ';
            }
            $n ++;
        }
    }

    /**
     * @param Typeof $typeof
     */
    public function renderTypeof(Typeof $typeof)
    {
        $this->output .= 'TYPEOF ' . $typeof->fieldname;

        foreach($typeof->whens AS $when)
        {
            $this->output .= ' WHEN ' . $when->when;

            $this->output .= ' THEN ';

            $this->renderFields($when->then);
        }

        if($typeof->else)
        {
            $this->output .= ' ELSE ';
            $this->renderFields($typeof->else);
        }

        $this->output .= ' END';
    }

    /**
     * @param From $from
     */
    public function renderFrom(From $from)
    {
        $this->output .= ' FROM ' . $from->value;
    }

    /**
     * @param Where $where
     */
    public function renderWhere(Where $where = null)
    {
        if(null === $where) return;

        $this->output .= ' WHERE ';

        $this->renderLogicalGroup($where->logicalGroup);
    }

    /**
     * @param With $with
     */
    public function renderWith(With $with = null)
    {
        if(null === $with) return;

        $this->output .= ' WITH ' . $with->what . ' ';

        $this->renderLogicalGroup($with->logicalGroup);
    }

    /**
     * @param LogicalGroup $group
     */
    public function renderLogicalGroup(LogicalGroup $group)
    {
        foreach($group->conditions AS $condition)
        {
            if($condition->logical)
            {
                $this->output .= ' ' . $condition->logical . ' ';
            }

            if($condition instanceof LogicalGroup)
            {
                $this->output .= '(';
                $this->renderLogicalGroup($condition);
                $this->output .= ')';
            }
            else
            {
                $this->renderLeftOperand($condition->left);

                $this->output .= ' ' . $condition->operator . ' ';

                $this->renderRightOperand($condition->right);
            }
        }
    }

    /**
     * @param SoqlFunction|Field $operand
     */
    public function renderLeftOperand($operand)
    {
        if($operand instanceof SoqlFunction)
        {
            $this->renderFunction($operand);
        }
        else
        {
            $this->renderField($operand);
        }
    }

    /**
     * @param Val $val
     */
    public function renderRightOperand(Val $val)
    {
        switch($val->type)
        {
            case 'SUBQUERY':
                $this->output .= '(';
                $this->renderQuery($val->value);
                $this->output .= ')';
                break;

            case 'LIST':
                $this->output .= '(';
                for($i = 0; $len = count($val->value), $i < $len; $i++)
                {
                    $this->renderRightOperand($val->value[$i]);
                    if($i < $len - 1)
                    {
                        $this->output .= ', ';
                    }
                }
                $this->output .= ')';
                break;

            case 'STRING':
                $this->output .= '\'' . $val->value . '\'';
                break;

            case 'VARIABLE':
                $this->renderRightOperand($this->convertVariable($this->lookupVariable($val->value)));
                break;

            case 'CATEGORYNAME':
            case 'DATETIME_FORMAT':
            case 'DATE_FORMAT':
            case 'DATE_LITERAL':
            case 'NUMBER':
            case 'FLOAT':
            case 'CURRENCY_NUMBER':
            case 'NULL':
            case 'FALSE':
            case 'TRUE':
                $this->output .= $val->value;
                break;
        }
    }

    /**
     * @param Having $having
     */
    public function renderHaving(Having $having = null)
    {
        if(null === $having) return;

        $this->output .= ' HAVING ';

        $this->renderLogicalGroup($having->logicalGroup);
    }

    /**
     * @param GroupBy $groupBy
     */
    public function renderGroupBy(GroupBy $groupBy = null)
    {
        if(null === $groupBy) return;

        $this->output .= ' GROUP BY ';

        if($groupBy->type)
        {
            $this->output .= $groupBy->type;
        }

        $this->renderFields($groupBy->fields);
    }

    public function renderLimit($limit = null)
    {
        if($limit === null) return;

        $this->output .= ' LIMIT ' . $limit;
    }

    public function renderOffset($offset = null)
    {
        if($offset === null) return;

        $this->output .= ' OFFSET ' . $offset;
    }

    public function renderFor($for = null)
    {
        if(null === $for) return;

        $this->output .= ' FOR ' . $for;
    }

    public function renderUpdate($update = null)
    {
        if(null === $update) return;

        $this->output .= ' UPDATE ' . $update;
    }

    /**
     * @param $variables
     * @throws \InvalidArgumentException
     */
    public function setVariables($variables)
    {
        $this->variables = array_merge($this->variables, $variables);
    }
    /**
     * @param $key
     */
    private function lookupVariable($key)
    {
        if(isset($this->variables[$key]))
        {
            return $this->variables[$key];
        }
        throw new \InvalidArgumentException(sprintf('Variable with key "%s" was never bound to the query.', $key));
    }

    /**
     * @param mixed $val
     *
     * @return Val
     */
    private function convertVariable($variable)
    {
        $val = null;

        if(is_array($variable))
        {
            $list = array();

            foreach($variable AS $var)
            {
                $list[] = $this->convertVariable($var);
            }
            $val = new Val($list, 'LIST');
        }
        elseif(null === $variable)
        {
            $val = new Val('NULL', 'NULL');
        }
        elseif(true === $variable)
        {
            $val = new Val('TRUE', 'TRUE');
        }
        elseif(false === $variable)
        {
            $val = new Val('FALSE', 'FALSE');
        }
        elseif(is_string($variable))
        {
            $val = new Val($variable, 'STRING');
        }
        elseif(is_float($variable))
        {
            $val = new Val($variable, 'FLOAT');
        }
        elseif(is_float($variable))
        {
            $val = new Val($variable, 'NUMBER');
        }
        // WHAT IS WITH DATE????
        elseif($variable instanceof \DateTime)
        {
            $val = new Val($variable->format(\DateTime::ISO8601), 'DATETIME_FORMAT');
        }
        elseif($variable instanceof Date)
        {
            $val = new Val($variable->format('Y-m-d'), 'DATE_FORMAT');
        }
        elseif($variable instanceof Currency)
        {
            $val = new Val($variable->getCurrencyIsoCode() . strval(abs($variable->getAmount())), 'CURRENCY_NUMBER');
        }
        else
        {
            throw new \InvalidArgumentException(sprintf('Unsupported parameter type "%s"', gettype($variable)));
        }
        return $val;
    }
}