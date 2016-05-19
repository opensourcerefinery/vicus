<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Vicus\Listener;

/**
 * Description of DatabaseConnectionListener
 *
 * @author Michael Koert <mkoert at bluebikeproductions.com>
 */
class DatabaseConnectionListener implements EventSubscriberInterface
{
    public function onConnection(ResponseEvent $event)
    {
//        $response = $event->getResponse();
//        $headers = $response->headers;
// 
//        if (!$headers->has('Content-Length') && !$headers->has('Transfer-Encoding')) {
//            $headers->set('Content-Length', strlen($response->getContent()));
//        }
		
		
    }
	
	public static function getSubscribedEvents()
    {
        return array('database.connection' => array('onConnection', -255));
    }
}
