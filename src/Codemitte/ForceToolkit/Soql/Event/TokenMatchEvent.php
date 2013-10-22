<?php
/**
 * Created by JetBrains PhpStorm.
 * User: joshi
 * Date: 14.10.13
 * Time: 21:00
 * To change this template use File | Settings | File Templates.
 */

namespace Codemitte\ForceToolkit\Soql\Event;

use Codemitte\ForceToolkit\Soql\Tokenizer;
use Symfony\Component\EventDispatcher\Event;

final class TokenMatchEvent extends Event
{
    /**
     * @var Tokenizer
     */
    private $tokenizer;

    public function __construct(Tokenizer $tokenizer)
    {
        $this->tokenizer = $tokenizer;
    }

    public function getTokenizer()
    {
        return $this->tokenizer;
    }
}