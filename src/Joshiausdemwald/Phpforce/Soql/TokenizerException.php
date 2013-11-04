<?php
namespace Joshiausdemwald\Phpforce\Soql;


class TokenizerException extends \Exception
{
    /**
     * @param string $message
     */
    public function __construct($message, Tokenizer $tokenizer)
    {
        $rows = preg_split('#$\R?^#m', $tokenizer->getSoql());

        $max = 0;

        foreach($rows AS $row)
        {
            $len = strlen($row);

            if($len > $max)
            {
                $max = $len;
            }
        }

        $cnt = strlen(strval(count($rows)));;

        $codes = array();

        foreach($rows AS $i => $row)
        {
            $len = strlen($row);

            if($max < $len)
            {
                $max = $len;
            }

            $codes[] = '[' . sprintf('%0' . $cnt . 'd', $i) . '] ' . $row;

            if($i == $tokenizer->getLine())
            {
                $codes[] = str_repeat('-', $tokenizer->getCol() + $cnt + 2) . '^';
            }
        }

        parent::__construct($message . sprintf(" (Error near line %u, col %u of SOQL query)\r\n\r\n", $tokenizer->getLine(), $tokenizer->getCol()) . implode("\r\n", $codes));
    }

    public function getCol()
    {
        return $this->col;
    }
}