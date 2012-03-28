<?php

namespace Symfony\Component\Security\Tests\Http;

use Symfony\Component\Security\Http\Firewall;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class FirewallTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!class_exists('Symfony\Component\EventDispatcher\EventDispatcher')) {
            $this->markTestSkipped('The "EventDispatcher" component is not available');
        }

        if (!class_exists('Symfony\Component\HttpFoundation\Request')) {
            $this->markTestSkipped('The "HttpFoundation" component is not available');
        }

        if (!class_exists('Symfony\Component\HttpKernel\HttpKernel')) {
            $this->markTestSkipped('The "HttpKernel" component is not available');
        }
    }

    public function testOnKernelRequestRegistersExceptionListener()
    {
        $dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $listener = $this->getMock('Symfony\Component\Security\Http\Firewall\ExceptionListener', array(), array(), '', false);
        $listener
            ->expects($this->once())
            ->method('register')
            ->with($this->equalTo($dispatcher))
        ;

        $request = $this->getMock('Symfony\Component\HttpFoundation\Request', array(), array(), '', false, false);

        $map = $this->getMock('Symfony\Component\Security\Http\FirewallMapInterface');
        $map
            ->expects($this->once())
            ->method('getListeners')
            ->with($this->equalTo($request))
            ->will($this->returnValue(array(array(), $listener)))
        ;

        $event = new GetResponseEvent($this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface'), $request, HttpKernelInterface::MASTER_REQUEST);

        $firewall = new Firewall($map, $dispatcher);
        $firewall->onKernelRequest($event);
    }

    public function testOnKernelRequestStopsWhenThereIsAResponse()
    {
        $response = $this->getMock('Symfony\Component\HttpFoundation\Response');

        $first = $this->getMock('Symfony\Component\Security\Http\Firewall\ListenerInterface');
        $first
            ->expects($this->once())
            ->method('handle')
        ;

        $second = $this->getMock('Symfony\Component\Security\Http\Firewall\ListenerInterface');
        $second
            ->expects($this->never())
            ->method('handle')
        ;

        $map = $this->getMock('Symfony\Component\Security\Http\FirewallMapInterface');
        $map
            ->expects($this->once())
            ->method('getListeners')
            ->will($this->returnValue(array(array($first, $second), null)))
        ;

        $event = $this->getMock(
            'Symfony\Component\HttpKernel\Event\GetResponseEvent',
            array('hasResponse'),
            array(
                $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface'),
                $this->getMock('Symfony\Component\HttpFoundation\Request', array(), array(), '', false, false),
                HttpKernelInterface::MASTER_REQUEST
            )
        );
        $event
            ->expects($this->once())
            ->method('hasResponse')
            ->will($this->returnValue(true))
        ;

        $firewall = new Firewall($map, $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface'));
        $firewall->onKernelRequest($event);
    }

    public function testOnKernelRequestWithSubRequest()
    {
        $map = $this->getMock('Symfony\Component\Security\Http\FirewallMapInterface');
        $map
            ->expects($this->never())
            ->method('getListeners')
        ;

        $event = new GetResponseEvent(
            $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface'),
            $this->getMock('Symfony\Component\HttpFoundation\Request'),
            HttpKernelInterface::SUB_REQUEST
        );

        $firewall = new Firewall($map, $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface'));
        $firewall->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }
}
