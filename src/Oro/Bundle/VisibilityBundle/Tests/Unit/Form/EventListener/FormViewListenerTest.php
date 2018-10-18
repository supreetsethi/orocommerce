<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\VisibilityBundle\Form\EventListener\FormViewListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

class FormViewListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FormViewListener
     */
    protected $listener;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|RequestStack
     */
    protected $requestStack;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface $translator */
        $translator = $this->createMock('Symfony\Component\Translation\TranslatorInterface');

        $this->doctrineHelper = $this->getDoctrineHelper();

        $this->requestStack = $this->createMock('Symfony\Component\HttpFoundation\RequestStack');

        $listener = new FormViewListener($translator, $this->doctrineHelper, $this->requestStack);
        $this->listener = $listener;
    }

    public function testOnCategoryEditNoRequest()
    {
        $event = $this->getBeforeListRenderEvent();
        $event->expects($this->never())
            ->method('getScrollData');

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityReference');

        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn(null);
        $this->listener->onCategoryEdit($event);
    }

    public function testOnCategoryEdit()
    {
        $event = $this->getBeforeListRenderEvent();
        $event->expects($this->once())
            ->method('getScrollData')
            ->willReturn($this->getScrollData());

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroCatalogBundle:Category')
            ->willReturn(new Category());

        /** @var \PHPUnit\Framework\MockObject\MockObject|\Twig_Environment $env */
        $env = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $env->expects($this->once())
            ->method('render')
            ->willReturn('');
        $event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($this->getRequest());
        $this->listener->onCategoryEdit($event);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper
     */
    protected function getDoctrineHelper()
    {
        $helper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        return $helper;
    }

    /**
     * @return BeforeListRenderEvent|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getBeforeListRenderEvent()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|BeforeListRenderEvent $event */
        $event = $this->getMockBuilder('Oro\Bundle\UIBundle\Event\BeforeListRenderEvent')
            ->disableOriginalConstructor()
            ->getMock();

        return $event;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ScrollData
     */
    protected function getScrollData()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ScrollData $scrollData */
        $scrollData = $this->createMock('Oro\Bundle\UIBundle\View\ScrollData');

        $scrollData->expects($this->once())
            ->method('addBlock');

        $scrollData->expects($this->once())
            ->method('addSubBlock');

        $scrollData->expects($this->once())
            ->method('addSubBlockData');

        return $scrollData;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Request
     */
    protected function getRequest()
    {
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        return $request;
    }
}
