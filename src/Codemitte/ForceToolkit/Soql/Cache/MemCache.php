<?php
/**
 * Created by PhpStorm.
 * User: joshi
 * Date: 21.10.13
 * Time: 13:00
 */

namespace Codemitte\ForceToolkit\Soql\Cache;

use Codemitte\ForceToolkit\Soql\AST\Node;
use \Memcache AS MC;

/**
 * Class QueryCache
 * @package Codemitte\ForceToolkit\Soql
 */
class MemCache implements CacheInterface
{
    /**
     * @var \Memcache
     */
    private $mc;

    /**
     * @var int
     */
    private $ttl;

    /**
     * @param int $ttl
     */
    public function __construct($ttl = 3600, $port = 11211)
    {
        $this->ttl = $ttl;

        $this->mc = new MC;

        $this->mc->pconnect('127.0.0.1', 11211);
    }

    /**
     * @param $soql
     * @return Node|null
     */
    public function get($soql)
    {
        $hashCode = $this->hashCode($soql);

        if(false === ($node = $this->mc->get($hashCode)))
        {
            return null;
        }
        return $node;
    }

    /**
     * @param $soql
     * @param Node $node
     * @return void
     */
    public function set($soql, Node $node)
    {
        $this->mc->set($this->hashCode($soql), $node, MEMCACHE_COMPRESSED, $this->ttl);
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