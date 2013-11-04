<?php
namespace Joshiausdemwald\Phpforce\Soql\Event;

use Joshiausdemwald\Phpforce\Soql\Tokenizer;
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