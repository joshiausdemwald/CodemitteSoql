<?php
namespace Joshiausdemwald\Phpforce\Soql\AST;


class OrderByField extends Leaf
{
    /**
     * @var null|string
     */
    public $direction;

    /**
     * @var null|string
     */
    public $nulls;

    /**
     * @param Field|SoqlFunction $field
     * @param null|string $direction
     * @param null|string $nulls
     */
    public function __construct($field, $direction = null, $nulls = null)
    {
        parent::__construct($field);

        $this->direction = $direction;

        $this->nulls = $nulls;
    }
}