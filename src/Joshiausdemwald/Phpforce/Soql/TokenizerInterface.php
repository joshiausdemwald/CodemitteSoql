<?php
namespace Joshiausdemwald\Phpforce\Soql;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

interface TokenizerInterface
{
    /**
     * @param EventSubscriberInterface $subscriber
     *
     * @return Tokenizer
     */
    function subscribe(EventSubscriberInterface $subscriber);

    /**
     * @param $listener
     *
     * @return Tokenizer
     */
    function listen($listener);

    /**
     * @param $soql
     * @return $this
     */
    function tokenize($soql);

    /**
     * @return array
     */
    function next();

    /**
     * @return string
     */
    function getValue();

    /**
     * @return string
     */
    function getType();

    /**
     * @return string
     */
    function getName();

    /**
     * @return int
     */
    public function getCol();

    /**
     * @return int
     */
    public function getLine();

    /**
     * @return Tokenizer
     */
    public function proceedSkip();

    /**
     * @param string $type
     * @param string|null $name
     * @return bool
     */
    public function expect($type, $name = null);

    /**
     * @param string $name
     * @return bool
     */
    public function expectKeyword($name);
    /**
     * @param string|array $type
     * @param string|null $name
     * @throws TokenizerException
     * @return Tokenizer
     */
    public function check($type, $name = null);

    /**
     * @param string $name
     * @throws TokenizerException
     * @return Tokenizer
     */
    public function checkKeyword($name);

    /**
     * @param string $name
     * @return boolean
     */
    public function is($type);

    /**
     * @param string $name
     * @return boolean
     */
    public function isKeyword($name);

    /**
     * @return string
     */
    public function getSoql();
} 