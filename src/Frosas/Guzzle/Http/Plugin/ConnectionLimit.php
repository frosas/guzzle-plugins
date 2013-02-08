<?php

namespace Frosas\Guzzle\Http\Plugin;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Guzzle\Http\Curl\CurlMultiInterface;
use Guzzle\Common\Event;

/**
 * Limits the amount of active (parallel) connections
 * 
 * It has to be subscribed to CurlMulti event dispatcher:
 * 
 *     $client->getCurlMulti()->getEventDispatcher()->addSubscriber(new ConnectionLimit);
 * 
 * TODO Catch exceptions and throw them only when there are no more requests in the queue. Right 
 * now it doesn't seem possible without modifying CurlMulti.
 */
class ConnectionLimit implements EventSubscriberInterface
{
    private $max;
    private $queued = array();
    private $removingActiveRequest = false;
    
    static function getSubscribedEvents()
    {
        return array(
            CurlMultiInterface::ADD_REQUEST => 'onCurlAddRequest',
            CurlMultiInterface::REMOVE_REQUEST => 'onCurlRemoveRequest'
        );
    }
    
    function __construct($max = 10)
    {
        $this->max = $max;
    }
    
    function onCurlAddRequest(Event $event)
    {
        $request = $event['request'];
        if ($this->tooManyRequests($request->getClient())) {
            $this->queueActiveRequest($request);
        }
    }
    
    function onCurlRemoveRequest(Event $event)
    {
        if ($this->removingActiveRequest) return;
        
        $this->dequeueNextRequest();
    }
    
    private function tooManyRequests($client)
    {
        return count($client->getCurlMulti()) > $this->max;
    }
    
    private function queueActiveRequest($request)
    {
        $this->removeActiveRequest($request);
        $this->queued[] = $request;
        $request->dispatch('request.queued', array('request' => $request));
    }
    
    private function removeActiveRequest($request)
    {
        if ($this->removingActiveRequest) throw new \Exception("Already removing a request");
        
        $queuedResponse = $request->getParams()->get('queued_response');
        $this->removingActiveRequest = true;
        $request->getClient()->getCurlMulti()->remove($request);
        $this->removingActiveRequest = false;
        $request->getParams()->set('queued_response', $queuedResponse);
    }
    
    private function dequeueNextRequest()
    {
        if ($request = array_shift($this->queued)) {
            $request->getClient()->getCurlMulti()->add($request, true);
            $request->dispatch('request.dequeued', array('request' => $request));
        }
    }
}