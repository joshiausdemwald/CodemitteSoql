<?php
namespace Phpforce\Query\Soql\Builder\Type;

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