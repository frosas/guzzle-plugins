<?php

namespace Frosas\Guzzle\Http\Plugin;

use Guzzle\Http\Exception\HttpException;
use Guzzle\Http\Message\Response;

class FailFastTest extends \PHPUnit_Framework_TestCase
{
    private $client;
    
    function setUp()
    {
        $this->client = new \Guzzle\Http\Client;
        $this->client->getCurlMulti()->getEventDispatcher()->addSubscriber(new FailFast);
    }
    
    function testASingleRequestExceptionIsThrown()
    {
        $this->setExpectedException('Guzzle\Http\Exception\HttpException');
        $this->client->send(array(
            $this->client->get()->setResponse(new Response(400), true)
        ));
    }
    
    function testSendingStopsImmediatelly()
    {
        $requests = array(
            $this->client->get()->setResponse(new Response(400), true),
            $this->client->get()->setResponse(new Response(200), true));
        try {
            $this->client->send($requests);
            $this->fail("An exception was expected");
        } catch (HttpException $exception) {
            $this->assertInstanceOf('Guzzle\Http\Message\Response', $requests[0]->getResponse());
            $this->assertNull($requests[1]->getResponse());
        } catch (\Exception $e) {
            $this->fail("An HttpException was expected");
        }
    }
}