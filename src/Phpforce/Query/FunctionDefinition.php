<?php
namespace Phpforce\Query;


class FunctionDefinition
{
    const
        TYPE_AGGREGATE = 'TYPE_AGGREGATE',
        TYPE_STRING = 'TYPE_STRING',
        TYPE_DATE = 'TYPE_DATE',
        TYPE_GROUPING = 'TYPE_GROUPING'
    ;
    private $scope = array();

    private $type;

    private $argumentsLengthMin = 0;
    private $argumentsLengthMax = 0;

    /**
     * @param string $type
     * @param array $scope
     * @param integer $argumentsLength
     */
    public function __construct($type, $scope = array(), $argumentsLengthMin, $argumentsLengthMax = null)
    {
        $this->type = $type;

        $this->scope = $scope;

        $this->argumentsLengthMin = $argumentsLengthMin;

        if(null === $argumentsLengthMax)
        {
            $this->argumentsLengthMax = $this->argumentsLengthMin;
        }
        else
        {
            $this->argumentsLengthMax = $argumentsLengthMax;
        }
    }

    /**
     * @return int
     */
    public function getArgumentsLengthMin()
    {
        return $this->argumentsLengthMin;
    }

    /**
     * @return int|null
     */
    public function getArgumentsLengthMax()
    {
        return $this->argumentsLengthMax;
    }

    /**
     * @return array
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }
} 