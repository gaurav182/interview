<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class CustomersController extends Controller
{
    /**
     * @Route("/customers/")
     * @Method("GET")
     */
    public function getAction()
    {
        try {

            /* 
                set the variable $customers to null
            */
            $customers = null;

            /* 
                establish connection to the cache server
            */
            $cacheService = $this->get('cache_service');

            /* 
                check the connection status with cache server 
            */
            if ($cacheService->isConnected()) { 

                /* 
                    get all the existing keys associated to the customers in the cache server 
                    and assign it to the variable $keys
                */
                $keys = $cacheService->get_keys('customer:*'); 

                /* 
                    if keys exist then get all the values from cache server that are associated with those keys 
                    and assign it to the variable $customers.
                    else then set the variable $customers to be null 
                */
                $customers = ($keys) ? $cacheService->get($keys) : null; 
            }

            /*
                check if the variable $customers is null 
                i.e. checking the existance of customers data in the cache server
                if the customers data in the cache server exists 
                then skip the steps of connecting and extracting the data from the database.
                and just return the customers data from the cache server
            */
            if (empty($customers)) {

                /* 
                    if the cache server does not have any customers data
                    then establish connection to the database
                    and get the data from database
                */
                $database = $this->get('database_service')->getDatabase();

                /* 
                    get all the customers data from database 
                    and assign it to the variable $customers
                */
                $customers = $database->customers->find();

                /*
                    check if the variable $customers is not null 
                    i.e. checking the existance of customers data in database  
                */
                if (!empty($customers)) { 

                    /*
                        format the customers data in the desired structure
                        and reassign it to the variable $customers  
                    */

                    $customers = iterator_to_array($customers); 
                    $customers = array_map(function($row) {
                        return array(
                            '_id'  => (string) $row['_id'],
                            'name' => $row['name'],
                            'age'  => $row['age']
                        ); 
                    }, $customers);

                    /* 
                        check the connection status with cache server 
                        since the cache server was empty before
                        so, the customers data from the database will be saved to the cache server for next GET request.
                    */
                    if ($cacheService->isConnected()) {

                        /*
                            the customers data is saved to the cache server 
                        */
                        $cacheService->set($customers);
                    }
                }
            }

            /*
                check if the variable $customers is empty
                if yes => send response with status 'No customer available at the moment.' in JSON format
                if not => send response with status 'OK' and the data of the variable $customers in JSON format.
            */
            if (empty($customers)) {
                return new JsonResponse(['status' => 'No customer available at the moment.']);
            }
            return new JsonResponse(['status' => 'OK', 'data' => $customers]);
            
        }
        catch (\Exception $e) {

            /*
                check for any exceptions, warnings or errors in the function
                If caught any exception 
                then send a user friendly exception response with status 'Oops, something went wrong.' in JSON format
            */
            return new JsonResponse(['status' => 'Oops, something went wrong.']);
        }
    }

    /**
     * @Route("/customers/")
     * @Method("POST")
     */
    public function postAction(Request $request)
    {
        try {

            /* 
                catch the POST variable in JSON format with $request->getContent() 
                and convert it to Array format with json_decode
                then assign it to the vairable $customers
            */
            $customers = json_decode($request->getContent(), true);

            /* 
                check if the variable $customers is a valid array to make sure that caught POST variable is in valid JSON format.
                if yes => skip the following step
                if not => send response with status 'Invalid data format. Required data format: JSON' in JSON format
            */
            if (!is_array($customers)) {
                return new JsonResponse(['status' => 'Invalid data format. Required data format: JSON'], 400);
            } 

            /* 
                check if the variable $customers is empty to check if the POST vaiable was empty.
                if yes => send response with status 'Empty data submission.' in JSON format
                if not => skip the following step
            */
            if (empty($customers)) {
                return new JsonResponse(['status' => 'Empty data submission.'], 400);
            }

            /* 
                set the variable $data to null
            */
            $data = null;

            /* 
                establish connection to the database
            */
            $database = $this->get('database_service')->getDatabase();
            
            /* 
                loop on the variable $customers to save the customers data into the database
                then store the auto generated inserted Id of each data to the vairable $oid
                then merge the value of $oid with the associated customer data 
                and store each customer data into the Array $data 
            */
            foreach ($customers as $customer) { 
                $return = $database->customers->insertOne($customer); 
                $oid = (string) $return->getInsertedId();
                $data[] = array_merge(['_id' => $oid], $customer);
            }

            /* 
                establish connection to the cache server
            */
            $cacheService = $this->get('cache_service');

            /* 
                check the connection status with cache server 
                and check if the Array $data is not empty
                if both true => save the value of the Array $data into the cache server for next GET request
            */
            if ($cacheService->isConnected() && !empty($data)) {

                /*
                    the customers data is saved to the cache server 
                */
                $cacheService->set($data);
            }

            /*
                send response with status 'Customers successfully created' in JSON format.
            */
            return new JsonResponse(['status' => 'Customers successfully created']);
        }
        catch (\Exception $e) {

            /*
                check for any exceptions, warnings or errors in the function
                If caught any exception 
                then send a user friendly exception response with status 'Oops, something went wrong.' in JSON format
            */
            return new JsonResponse(['status' => 'Oops, something went wrong.']);
        }
    }

    /**
     * @Route("/customers/")
     * @Method("DELETE")
     */
    public function deleteAction()
    {
        try {

            /* 
                establish connection to the database
            */
            $database = $this->get('database_service')->getDatabase();

            /* 
                 all the customers data is delete from the database
            */
            $database->customers->drop();

            /* 
                establish connection to the cache server
            */
            $cacheService = $this->get('cache_service');

            /* 
                check the connection status with cache server 
            */
            if ($cacheService->isConnected()) {

                /*
                    all the customers data is deleted from the cache server 
                */
                $cacheService->delete();
            }

            /*
                send response with status 'Customers successfully deleted' in JSON format.
            */
            return new JsonResponse(['status' => 'Customers successfully deleted']);
        }
        catch (\Exception $e) {

            /*
                check for any exceptions, warnings or errors in the function
                If caught any exception 
                then send a user friendly exception response with status 'Oops, something went wrong.' in JSON format
            */
            return new JsonResponse(['status' => 'Oops, something went wrong.']);
        }
    }
}

/*
curl http://127.0.0.1:8000/customers/ -X POST -d '[{"name":"Leandro", "age":21}, {"name":"Marcio", "age":22}, {"name":"Mike", "age":23}, {"name":"John", "age":24}, {"name":"Jenifer", "age":25}, {"name":"Micheal", "age":26}]'
*/