<?php

namespace Frosas\Guzzle\Http\Plugin;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Guzzle\Http\Curl\CurlMultiInterface;
use Guzzle\Common\Event;

/**
 * Instead of getting an ExceptionCollection when Client::send() fails you'll get the first 
 * request exception as soon as it occurs.
 * 
 * It has to be subscribed to CurlMulti event dispatcher:
 * 
 *     $client->getCurlMulti()->getEventDispatcher()->addSubscriber(new FailFast);
 */
class FailFast implements EventSubscriberInterface
{
    static function getSubscribedEvents()
    {
        return array(CurlMultiInterface::MULTI_EXCEPTION => 'onRequestException');
    }
    
    function onRequestException(Event $event)
    {
        throw $event['exception'];
    }
}