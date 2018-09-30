<?php

namespace AppBundle\Service;

use Predis;

/**
* Here you have to implement a CacheService with the operations above.
* It should contain a failover, which means that if you cannot retrieve
* data you have to hit the Database.
**/
class CacheService
{
    protected $redisCache, $values, $_connect;

    public function __construct($host, $port, $prefix)
    {
        $this->redisCache = new Predis\Client([
            'scheme' => 'tcp',
            'host'   => $host,
            'port'   => $port,
            'prefix' => $prefix
        ]);

        try {
            $this->redisCache->connect();  
            $this->_connect = true; 
        } 
        catch (Predis\Connection\ConnectionException $exception) {
            // We could not connect to Redis! Your handling code goes here.
            $this->_connect = false; 
        }
    }

    public function isConnected()
    {
        return $this->_connect;
    }

    public function exists($key)
    {
        return $this->redisCache->exists($key);
    }

    public function keys($key)
    {
        return $this->redisCache->keys($key);
    }

    public function flushdb()
    {
        return $this->redisCache->flushdb();
    }

    public function flushall()
    {
        return $this->redisCache->flushall();
    }

    public function pipelineset($data)
    {
        $this->values = $data;
        return $this->redisCache->pipeline(function ($pipe) {
            foreach($this->values as $row) {
                $pipe->hmset('customer:'.$row['_id'], $row)->expire('customer:'.$row['_id'], 60);
            }
        });
    }

    public function pipelineget($data)
    {
        $this->values = $data;
        return $this->redisCache->pipeline(function ($pipe) {
            foreach($this->values as $key) {
                $pipe->hgetall($key);
            }
        });
    }

    /* not in use, but for future reference */

    public function get($key)
    {
        return $this->redisCache->get($key);
    }

    public function set($key, $value)
    {
        return $this->redisCache->set($key, $value);
    }

    public function del($key)
    {
        return $this->redisCache->del($key);
    }

    public function hmset($key, $data)
    {
        return $this->redisCache->hmset($key, $data);
    }

    public function hmget($key, $fields)
    {
        return $this->redisCache->hmget($key, $fields);
    }

    public function hdel($key)
    {
        return $this->redisCache->hdel($key);
    }

    public function hgetall($key)
    {
        return $this->redisCache->hgetall($key);
    }
    
    /* not in use, but for future reference */
}
