<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\SEOBundle\Async\GenerateSitemapByWebsiteAndTypeProcessor;
use Oro\Bundle\SEOBundle\Topic\GenerateSitemapByWebsiteAndTypeTopic;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\SEO\Tools\SitemapDumperInterface;
use Psr\Log\LoggerInterface;

class GenerateSitemapByWebsiteAndTypeProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var JobRunner|\PHPUnit\Framework\MockObject\MockObject */
    private $jobRunner;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var SitemapDumperInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $sitemapDumper;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var GenerateSitemapByWebsiteAndTypeProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->sitemapDumper = $this->createMock(SitemapDumperInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new GenerateSitemapByWebsiteAndTypeProcessor(
            $this->jobRunner,
            $this->doctrine,
            $this->sitemapDumper,
            $this->logger
        );
    }

    private function getSession(): SessionInterface
    {
        return $this->createMock(SessionInterface::class);
    }

    private function getMessage(array $body): MessageInterface
    {
        $message = new Message();
        $message->setBody($body);

        return $message;
    }

    public function testGetSubscribedTopics()
    {
        self::assertEquals(
            [GenerateSitemapByWebsiteAndTypeTopic::getName()],
            GenerateSitemapByWebsiteAndTypeProcessor::getSubscribedTopics()
        );
    }

    public function testProcessWhenWebsiteNotFound()
    {
        $jobId = 100;
        $websiteId = 123;
        $message = $this->getMessage([
            'jobId'     => $jobId,
            'version'   => 1,
            'websiteId' => $websiteId,
            'type'      => 'test_type'
        ]);

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->with($jobId)
            ->willReturnCallback(function (int $jobId, \Closure $callback) {
                return $callback($this->jobRunner, new Job());
            });

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('find')
            ->with(Website::class, $websiteId)
            ->willReturn(null);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during generating a sitemap of a specific type for a website.',
                ['exception' => new \RuntimeException('The website does not exist.')]
            );

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testProcessWhenDumpFailed()
    {
        $jobId = 100;
        $version = 1;
        $websiteId = 123;
        $type = 'test_type';
        $message = $this->getMessage([
            'jobId'     => $jobId,
            'version'   => $version,
            'websiteId' => $websiteId,
            'type'      => $type
        ]);

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->with($jobId)
            ->willReturnCallback(function (int $jobId, \Closure $callback) {
                return $callback($this->jobRunner, new Job());
            });

        $website = $this->createMock(Website::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('find')
            ->with(Website::class, $websiteId)
            ->willReturn($website);

        $exception = new \Exception('some error');
        $this->sitemapDumper->expects(self::once())
            ->method('dump')
            ->with(self::identicalTo($website), $version, $type)
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during generating a sitemap of a specific type for a website.',
                ['exception' => $exception]
            );

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testProcess()
    {
        $jobId = 100;
        $version = 1;
        $websiteId = 123;
        $type = 'test_type';
        $message = $this->getMessage([
            'jobId'     => $jobId,
            'version'   => $version,
            'websiteId' => $websiteId,
            'type'      => $type
        ]);

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->with($jobId)
            ->willReturnCallback(function (int $jobId, \Closure $callback) {
                return $callback($this->jobRunner, new Job());
            });

        $website = $this->createMock(Website::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('find')
            ->with(Website::class, $websiteId)
            ->willReturn($website);

        $this->sitemapDumper->expects(self::once())
            ->method('dump')
            ->with(self::identicalTo($website), $version, $type);

        $this->logger->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->getSession())
        );
    }
}
