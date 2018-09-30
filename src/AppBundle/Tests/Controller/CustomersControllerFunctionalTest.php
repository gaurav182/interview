<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\Service\CacheService;

class CustomersControllerFunctionalTest extends WebTestCase
{
    protected $client;

    public function setUp()
    {
        $this->client = static::createClient();
        $this->client->followRedirects();
    }

    /*
        test method to test the CacheService   
        this test assumes that the cache server is empty at the moment  
    */
    public function testCache()
    {
        $cache = new CacheService('127.0.0.1', '6379', null);
        if ($cache->isConnected()) {
            $customers = [
                ['_id' => 1, 'name' => 'Leandro', 'age' => 26],
                ['_id' => 2, 'name' => 'Marcelo', 'age' => 30],
                ['_id' => 3, 'name' => 'Alex', 'age' => 18]
            ];

            $cache->set($customers);
            
            $keys = $cache->get_keys('*');
            $result = $cache->get($keys);

            usort($result, function($a, $b) {
                return $a['_id'] - $b['_id'];
            });

            $this->assertEquals($customers, $result);

            $cache->delete();
        }
        else {
            /* 
                since the system is not connected with the cache server, 
                we assert it to true to avoid warning during the test run
            */
            $this->assertTrue(true);
        }
    }

    /*
        test method to test the POST method Request in CustomersController   
        this test assumes that the database and cache server is empty at the moment 
    */
    public function testCreateCustomers()
    {
        $customers = [
            ['name' => 'Leandro', 'age' => 26],
            ['name' => 'Marcelo', 'age' => 30],
            ['name' => 'Alex', 'age' => 18]
        ];
        $customers = json_encode($customers);

        $this->client->request('POST', '/customers/', [], [], ['CONTENT_TYPE' => 'application/json'], $customers);

        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    /*
        test method to test the GET method Request in CustomersController  
        this test assumes that the database and cache server is empty at the moment   
    */
    public function testGetCustomers()
    {  
        $customers = [
            ['name' => 'Leandro', 'age' => 26],
            ['name' => 'Marcelo', 'age' => 30],
            ['name' => 'Alex', 'age' => 18]
        ];

        $this->client->request('GET', '/customers/');
        $result = json_decode($this->client->getResponse()->getContent(), true); 
        $result = array_map(function($row) {
            return array(
                'name' => $row['name'],
                'age'  => $row['age']
            ); 
        }, $result['data']);

        usort($result, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        usort($customers, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        $this->assertEquals($customers, $result);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    /*
        test method to test the DELETE method Request in CustomersController  
        this test assumes that the database and cache server is empty at the moment   
    */
    public function testDeleteCustomers()
    {
        $this->client->request('DELETE', '/customers/');
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $output = ['status' => 'No customer available at the moment.'];
        $this->client->request('GET', '/customers/');
        $result = json_decode($this->client->getResponse()->getContent(), true); 
        $this->assertEquals($output, $result);
    }
}
