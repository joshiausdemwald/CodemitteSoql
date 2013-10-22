<?php
/**
 * Created by PhpStorm.
 * User: joshi
 * Date: 22.10.13
 * Time: 15:10
 */

namespace Codemitte\ForceToolkit\Soql\Builder\Type;

class Currency
{
    /**
     * @var string
     */
    private $amount;

    /**
     * @var string
     */
    private $currencyIsoCode;

    /**
     * @param float $amount
     * @param string $currencyIsoCode
     */
    public function __construct($amount, $currencyIsoCode)
    {
        $this->amount = $amount;

        $this->currencyIsoCode = $currencyIsoCode;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getCurrencyIsoCode()
    {
        return $this->currencyIsoCode;
    }
} 