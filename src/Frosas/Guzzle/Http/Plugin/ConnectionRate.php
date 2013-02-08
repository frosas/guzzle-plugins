<?php

namespace Frosas\Guzzle\Http\Plugin;

use Guzzle\Http\Message\Request;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Guzzle\Http\Curl\CurlMultiInterface;
use Guzzle\Common\Event;

/**
 * Limits the rate at which the connections are made by delaying them
 *
 * It has to be subscribed to CurlMulti event dispatcher:
 * 
 *     $client->getCurlMulti()->getEventDispatcher()->addSubscriber(new ConnectionRate);
 */
class ConnectionRate implements EventSubscriberInterface
{
    private $connectionsPerSecond;

    static function getSubscribedEvents()
    {
        return array(
            CurlMultiInterface::ADD_REQUEST => 'onAddRequest',
            CurlMultiInterface::REMOVE_REQUEST => 'onRemoveRequest'
        );
    }

    function __construct($connectionsPerSecond = 10)
    {
        $this->connectionsPerSecond = $connectionsPerSecond;
    }
    
    function onAddRequest(Event $event)
    {
        $event['request']->getEventDispatcher()
            ->addListener('request.before_send', array($this, 'beforeSendRequest'));
    }

    function onRemoveRequest(Event $event)
    {
        $event['request']->getEventDispatcher()
            ->removeListener('request.before_send', array($this, 'beforeSendRequest'));
    }

    function beforeSendRequest(Event $event)
    {
        $secondsPerConnection = 1 / $this->connectionsPerSecond;
        $secondsPerConnection *= 2 * rand() / getrandmax(); // Distribute parallel connections
        usleep($secondsPerConnection * 1000000);
    }
}
