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
        try {

            /*
                create an object of Predis Client Class
            */
            $this->redisCache = new Predis\Client([
                'scheme' => 'tcp',
                'host'   => $host,
                'port'   => $port,
                'prefix' => $prefix
            ]);

            /*
                request to connect to the cache server
                if connected => set the protected variable $_connect to True
                if not => set the protected variable $_connect to False
            */
            $this->redisCache->connect();
            $this->_connect = true;
        } 
        catch (Predis\Connection\ConnectionException $exception) {
            // We could not connect to Redis! Your handling code goes here.
            $this->_connect = false; 
        }
    }

    /*
        method built to check the connection status to the cache server
        it return boolean variable
        if True => cache server connection is open
        if False => cache server connection is close
    */
    public function isConnected()
    {
        return $this->_connect;
    }

    /*
        method to extract all the keys that exists in the cache server 
        that follow the given pattern defined in the variable $key
    */
    public function get_keys($key)
    {
        return $this->redisCache->keys($key);
    }

    /*
        method to delete all keys and values in the current cache server database
    */
    public function delete()
    {
        return $this->redisCache->flushdb();
    }

    /*
        method to save all keys and values pairs into the cache server
        'customer:' prefix is added to each database inserted Id as a key to each customer data
        each cache item is set to expire after 86400 seconds (1 day)
    */
    public function set($data)
    {
        $this->values = $data; 
        return $this->redisCache->pipeline(function ($pipe) {
            foreach($this->values as $row) {
                $pipe->hmset('customer:'.$row['_id'], $row)->expire('customer:'.$row['_id'], 86400);
            }
            $this->values = null;
        });
    }

    /*
        method to retrieve all values associated with the keys defined in the parameter $data from the cache server
    */
    public function get($data)
    {
        $this->values = $data; 
        return $this->redisCache->pipeline(function ($pipe) {
            foreach($this->values as $key) {
                $pipe->hgetall($key);
            }
            $this->values = null;
        });
    }
}
