<?php
/**
 * Created by PhpStorm.
 * User: joshi
 * Date: 21.10.13
 * Time: 13:00
 */

namespace Codemitte\ForceToolkit\Soql\Cache;

use Codemitte\ForceToolkit\Soql\AST\Node;

/**
 * Class QueryCache
 * @package Codemitte\ForceToolkit\Soql
 */
class ArrayQueryCache implements CacheInterface
{
    /**
     * @var array
     */
    private $cache = array();

    /**
     * @var array
     */
    private $validUntil = array();

    /**
     * @var int
     */
    private $ttl;

    /**
     * @param int $ttl
     */
    public function __construct($ttl = 3600)
    {
        $this->ttl = $ttl;
    }

    /**
     * @param $soql
     * @return Node|null
     */
    public function get($soql)
    {
        $hashCode = $this->hashCode($soql);

        if($this->_has($soql, $hashCode))
        {
            return $this->cache[$hashCode];
        }
        return null;
    }

    /**
     * @param $soql
     * @param Node $node
     * @return void
     */
    public function set($soql, Node $node)
    {
        $hashCode = $this->hashCode($soql);

        $this->validUntil[$hashCode] = time() + $this->ttl;

        $this->cache[$hashCode] = $node;
    }

    private function _has($soql, $hashCode = null)
    {
        if(null === $hashCode)
        {
            $hashCode = $this->hashCode($soql);
        }
        return isset($this->cache[$hashCode]) && $this->validUntil[$hashCode] >= time();
    }

    /**
     * @param string $soql
     * @return string
     */
    private function hashCode($soql)
    {
        return hash('sha1', $soql);
    }
} 