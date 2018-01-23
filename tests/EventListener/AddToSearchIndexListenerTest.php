<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\EventListener\AddToSearchIndexListener;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Routing\ScopeMatcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Tests the AddToSearchIndexListener class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class AddToSearchIndexListenerTest extends TestCase
{
    /**
     * @var ContaoFrameworkInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $framework;

    /**
     * @var ScopeMatcher|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeMatcher;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->framework = $this->createMock(ContaoFrameworkInterface::class);

        $frontendAdapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['indexPageIfApplicable'])
            ->getMock()
        ;

        $frontendAdapter
            ->method('indexPageIfApplicable')
            ->willReturn(null)
        ;

        $this->framework
            ->method('getAdapter')
            ->willReturn($frontendAdapter)
        ;

        $this->scopeMatcher = $this->createMock(ScopeMatcher::class);
    }

    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $listener = new AddToSearchIndexListener($this->framework, $this->scopeMatcher);

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\AddToSearchIndexListener', $listener);
    }

    /**
     * Tests that the listener does use the response if the Contao framework is booted.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testIndexesTheResponse()
    {
        $this->framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $this->scopeMatcher
            ->method('isFrontendMasterRequest')
            ->willReturn(true)
        ;

        $event = $this->mockPostResponseEvent();

        $event
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn(new Response())
        ;

        $listener = new AddToSearchIndexListener($this->framework, $this->scopeMatcher);
        $listener->onKernelTerminate($event);
    }

    /**
     * Tests that the listener does nothing if the Contao framework is not booted.
     */
    public function testDoesNotIndexTheResponseIfTheContaoFrameworkIsNotInitialized()
    {
        $this->framework
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $this->scopeMatcher
            ->expects($this->never())
            ->method('isFrontendMasterRequest')
        ;

        $event = $this->mockPostResponseEvent();

        $event
            ->expects($this->never())
            ->method('getResponse')
        ;

        $listener = new AddToSearchIndexListener($this->framework, $this->scopeMatcher);
        $listener->onKernelTerminate($event);
    }

    /**
     * Tests that the listener does nothing if not a Contao front end master request.
     */
    public function testDoesNotIndexTheResponseIfNotAContaoFrontendMasterRequest()
    {
        $this->framework
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $this->scopeMatcher
            ->method('isFrontendMasterRequest')
            ->willReturn(false)
        ;

        $event = $this->mockPostResponseEvent();

        $event
            ->expects($this->never())
            ->method('getResponse')
        ;

        $listener = new AddToSearchIndexListener($this->framework, $this->scopeMatcher);
        $listener->onKernelTerminate($event);
    }

    /**
     * Tests that the listener does nothing if the request method is not GET.
     */
    public function testDoesNotIndexTheResponseIfTheRequestMethodIsNotGet()
    {
        $this->framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $this->scopeMatcher
            ->method('isFrontendMasterRequest')
            ->willReturn(true)
        ;

        $event = $this->mockPostResponseEvent(null, Request::METHOD_POST);

        $event
            ->expects($this->never())
            ->method('getResponse')
        ;

        $listener = new AddToSearchIndexListener($this->framework, $this->scopeMatcher);
        $listener->onKernelTerminate($event);
    }

    /**
     * Tests that the listener does nothing if the request is a fragment.
     */
    public function testDoesNotIndexTheResponseUponFragmentRequests()
    {
        $this->framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $this->scopeMatcher
            ->method('isFrontendMasterRequest')
            ->willReturn(true)
        ;

        $event = $this->mockPostResponseEvent('_fragment/foo/bar');

        $event
            ->expects($this->never())
            ->method('getResponse')
        ;

        $listener = new AddToSearchIndexListener($this->framework, $this->scopeMatcher);
        $listener->onKernelTerminate($event);
    }

    /**
     * Returns a PostResponseEvent mock object.
     *
     * @param string|null $requestUri
     * @param string      $requestMethod
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|PostResponseEvent
     */
    private function mockPostResponseEvent($requestUri = null, $requestMethod = Request::METHOD_GET)
    {
        $request = new Request();
        $request->setMethod($requestMethod);

        if (null !== $requestUri) {
            $request->server->set('REQUEST_URI', $requestUri);
        }

        return $this
            ->getMockBuilder(PostResponseEvent::class)
            ->setConstructorArgs([
                $this->createMock(KernelInterface::class),
                $request,
                new Response(),
            ])
            ->setMethods(['getResponse'])
            ->getMock()
        ;
    }
}
