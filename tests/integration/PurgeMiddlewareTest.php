<?php

namespace Acpl\FlarumLSCache\Tests\integration;

use Acpl\FlarumLSCache\Event\LSCachePurging;
use Acpl\FlarumLSCache\Listener\DiscussionCachePurgeTrait;
use Acpl\FlarumLSCache\Middleware\PurgeCacheMiddleware;
use Acpl\FlarumLSCache\Utility\LSCachePurger;
use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Events\Dispatcher;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

class PurgeMiddlewareTest extends TestCase
{
    public function test_route_purge_formats_tag_once_and_passes_actor_to_event(): void
    {
        $settings = $this->createStub(SettingsRepositoryInterface::class);
        $events = new Dispatcher();
        $purger = new LSCachePurger($events, $this->createStub(Queue::class));
        $purger->clearPurgeData();
        $actor = new User();
        $dispatched = null;
        $events->listen(LSCachePurging::class, function (LSCachePurging $event) use (&$dispatched): void {
            $dispatched = $event;
        });
        $request = (new ServerRequest())
            ->withMethod('PATCH')
            ->withAttribute('routeName', 'examples.update')
            ->withAttribute('routeParameters', ['id' => 42]);
        $request = RequestUtil::withActor($request, $actor);
        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new EmptyResponse());

        try {
            $response = (new PurgeCacheMiddleware($settings, $purger, $events))->process($request, $handler);

            self::assertSame('tag=example_42', $response->getHeaderLine('X-LiteSpeed-Purge'));
            self::assertInstanceOf(LSCachePurging::class, $dispatched);
            self::assertSame($actor, $dispatched->actor);
            self::assertSame(['paths' => [], 'tags' => ['example_42']], $dispatched->data);
            self::assertSame(['paths' => [], 'tags' => []], $purger->getPurgeData());
        } finally {
            $purger->clearPurgeData();
        }
    }

    public function test_configured_discussion_tags_are_stored_without_purge_prefix(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->expects(self::once())->method('get')->with('acpl-lscache.purge_on_discussion_update')
            ->willReturn("/custom\ntag=custom\ntag=other");
        $purger = new LSCachePurger(new Dispatcher(), $this->createStub(Queue::class));
        $purger->clearPurgeData();
        $listener = new class($settings, $purger) {
            use DiscussionCachePurgeTrait {
                handleDiscussionRelatedPurge as public;
            }

            public function __construct(SettingsRepositoryInterface $settings, LSCachePurger $purger)
            {
                $this->settings = $settings;
                $this->purger = $purger;
            }
        };

        try {
            $listener->handleDiscussionRelatedPurge();

            self::assertSame([
                'paths' => ['/custom'],
                'tags' => ['default', 'index', 'discussions', 'custom', 'other'],
            ], $purger->getPurgeData());
        } finally {
            $purger->clearPurgeData();
        }
    }
}
