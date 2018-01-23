<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\EventListener\BypassMaintenanceListener;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the BypassMaintenanceListener class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class BypassMaintenanceListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $listener = new BypassMaintenanceListener($this->mockSession(), false);

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\BypassMaintenanceListener', $listener);
    }

    /**
     * Tests adding the request attribute.
     */
    public function testAddsTheRequestAttribute()
    {
        $request = new Request();
        $request->cookies->set('BE_USER_AUTH', 'da6c1abd61155f4ce98c6b5f1fbbf0ebeb43638e');

        $event = new GetResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $listener = new BypassMaintenanceListener($this->mockSession(), false);
        $listener->onKernelRequest($event);

        $this->assertTrue($event->getRequest()->attributes->get('_bypass_maintenance'));
    }

    /**
     * Tests that the request attribute is not set if there is no back end user.
     */
    public function testDoesNotAddTheRequestAttributeIfThereIsNoBackEndUser()
    {
        $event = new GetResponseEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST
        );

        $listener = new BypassMaintenanceListener($this->mockSession(), false);
        $listener->onKernelRequest($event);

        $this->assertFalse($event->getRequest()->attributes->has('_bypass_maintenance'));
    }
}
