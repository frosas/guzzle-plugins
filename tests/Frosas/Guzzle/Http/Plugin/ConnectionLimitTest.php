<?php

namespace Frosas\Guzzle\Http\Plugin;

use Guzzle\Http\Message\Response;
use Guzzle\Http\Plugin\MockPlugin;

class ConnectionLimitPluginTest extends \PHPUnit_Framework_TestCase
{
    function testRealRequest()
    {
        $client = new \Guzzle\Http\Client('http://www.iana.org/domains/example/');
        $client->getCurlMulti()->getEventDispatcher()->addSubscriber(new ConnectionLimit);
        $this->assertEquals(200, $client->get()->send()->getStatusCode());
    }

    function testMockResponse()
    {
        $client = new \Guzzle\Http\Client;
        $client->getCurlMulti()->getEventDispatcher()->addSubscriber(new ConnectionLimit);
        $response = $client->get()->setResponse(new Response(200), true)->send();
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    function testQueueing()
    {
        $client = new \Guzzle\Http\Client;
        $client->getCurlMulti()->getEventDispatcher()->addSubscriber(new ConnectionLimit(2));
        
        $queueings = 0;
        $client->getEventDispatcher()->addListener('request.queued', function($event) use (& $queueings) {
            $queueings++;
        });
        
        $dequeueings = 0;
        $client->getEventDispatcher()->addListener('request.dequeued', function($event) use (& $dequeueings) {
            $dequeueings++;
        });
        
        $requests = array(
            $client->get()->setResponse(new Response(200), true),
            $client->get()->setResponse(new Response(200), true),
            $client->get()->setResponse(new Response(200), true),
            $client->get()->setResponse(new Response(200), true));
        foreach ($client->send($requests) as $response) {
            $this->assertEquals(200, $response->getStatusCode(), "Status code");
        }
        
        $this->assertEquals(2, $queueings, "Queueings");
        $this->assertEquals(2, $dequeueings, "Dequeueings");
    }
}