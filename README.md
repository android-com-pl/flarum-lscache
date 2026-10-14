# ⚡ LiteSpeed Cache for Flarum

[![Latest Stable Version](https://img.shields.io/packagist/v/acpl/flarum-lscache)](https://packagist.org/packages/acpl/flarum-lscache) [![Total Downloads](https://img.shields.io/packagist/dt/acpl/flarum-lscache.svg)](https://packagist.org/packages/acpl/flarum-lscache) [![GitHub Sponsors](https://img.shields.io/badge/Donate-%E2%9D%A4-%23db61a2.svg?&logo=github&logoColor=white&labelColor=181717)](https://github.com/android-com-pl/flarum-lscache?sponsor=1)

Full-page [LiteSpeed caching](https://lscache.io/) for [Flarum](https://flarum.org).

If your forum runs on LiteSpeed, this is a must-have for heavy guest and bot traffic. The first request generates a response; LiteSpeed serves later requests for the same cached page directly, without running PHP or querying the database again. This can substantially reduce server load.

Requires LiteSpeed Web Server or OpenLiteSpeed with LSCache enabled.

## 📥 Installation

Install with Composer:

```sh
composer require acpl/flarum-lscache
```

Back up your `.htaccess` before enabling the extension. It adds its cache rules to that file on activation.

### 🔄 Updating

```sh
composer update acpl/flarum-lscache
php flarum migrate
php flarum cache:clear
```

## 🧹 Cache Management

Changes invalidate the affected cache entries. For example, a new public post clears the cache for its discussion, tag pages, and the homepage.

Clearing Flarum's cache also clears LSCache, unless you disable this in the extension settings.

To clear only LSCache, use the admin panel's cache menu or the CLI:

```sh
php flarum lscache:clear
php flarum lscache:clear --path=/tags --path=/d/1-test
```

## ❓ FAQ

### Does it cache pages for logged-in users?

No. Forum pages are cached for guests only. Logged-in users can see different content, permissions, notifications, and read states. Sharing their cached pages would be unsafe.

A separate cache for each user would still need to generate each page on that user's first visit, then rebuild it whenever their content changes. That means fewer cache hits and more invalidation work. Guest caching lets many visitors reuse the same response.

### Does it work on Apache or nginx?

No. It requires the LSCache engine in LiteSpeed Web Server or OpenLiteSpeed.

## 🧑🏻‍💻 For Developers

> [!NOTE]
> [Cache tags](https://docs.litespeedtech.com/lscache/devguide/controls/#x-litespeed-tag) are labels used to invalidate cached responses. They are separate from the discussion tags provided by `flarum/tags`.

### 🏷️ How the Extension Tags Paths

Cache tags come from route names. For example, an extension could register these API routes:

```php
(new Extend\Routes('api'))
    ->get('/examples', 'examples.index', ExamplesListController::class)
    ->get('/examples/{id}', 'examples.show', ExamplesShowController::class)
    ->post('/examples', 'examples.create', ExamplesCreateController::class);
```

For GET and HEAD responses:

- `.index` routes get the resource tag: `examples.index` → `examples`.
- Other routes get their full name: `examples.show` → `examples.show`.
- An `id` or `slug` route parameter adds a tag using the singular resource name: `example_1` or `example_example-slug`.

> [!TIP]
> To see the cache tags added for a given request, check the value of the `X-LiteSpeed-Tag` header in the Network tab of DevTools.

### 🗑️ Purging Cache

Successful POST, PUT, PATCH, and DELETE requests to `.create`, `.update`, or `.delete` routes purge the resource's ID tag when an `id` route parameter is present. A collection's `.create` route usually has no `id`, so use events to invalidate its list cache.

For event-based purging, register the singular resource name in `$resourcesSupportedByEvent` to disable automatic ID purging. Keep this registration inside the condition so your extension also works when LSCache is disabled or absent:

```php
// extend.php
use Acpl\FlarumLSCache\Utility\LSCachePurger;
use Flarum\Extend;

return [
    // ... your current extenders
    (new Extend\Conditional)
        ->whenExtensionEnabled('acpl-lscache', function () {
            LSCachePurger::$resourcesSupportedByEvent[] = 'example';

            return [
                (new Extend\Event)->listen(ExampleUpdated::class, ExampleUpdatedListener::class),
            ];
        }),
];
```

```php
// ExampleUpdatedListener.php
use Acpl\FlarumLSCache\Listener\AbstractCachePurgeListener;

/** @extends AbstractCachePurgeListener<ExampleUpdated> */
class ExampleUpdatedListener extends AbstractCachePurgeListener
{
    protected function addPurgeData(object $event): void
    {
        $this->purger->addPurgeTags([
            'examples',
            "example_{$event->example->id}",
        ]);
    }
}
```

> [!TIP]
> Prefer tags: they also invalidate cached URL variants, including query strings.

For one tag, use `addPurgeTag('examples')`. To purge URLs directly, use `addPurgePath('/api/examples')` or `addPurgePaths(['/api/examples', '/api/examples/1'])`.

To handle multiple events in one class, use a subscriber instead:

```php
// extend.php
use Acpl\FlarumLSCache\Utility\LSCachePurger;
use Flarum\Extend;

return [
    // ... your current extenders
    (new Extend\Conditional)
        ->whenExtensionEnabled('acpl-lscache', function () {
            LSCachePurger::$resourcesSupportedByEvent[] = 'example';

            return [
                (new Extend\Event)->subscribe(ExampleEventSubscriber::class),
            ];
        }),
];
```

```php
// ExampleEventSubscriber.php
use Acpl\FlarumLSCache\Listener\AbstractCachePurgeSubscriber;
use Illuminate\Contracts\Events\Dispatcher;

class ExampleEventSubscriber extends AbstractCachePurgeSubscriber
{
    public function subscribe(Dispatcher $events): void
    {
        $this->addPurgeListener($events, ExampleUpdated::class, $this->handleExampleUpdated(...));
        // ... rest of listeners
    }

    public function handleExampleUpdated(ExampleUpdated $event): void
    {
        $this->purger->addPurgeTags([
            'examples',
            "example_{$event->example->id}",
        ]);
    }
    // ... rest of methods
}
```

## 🔗 Links

- [Packagist](https://packagist.org/packages/acpl/flarum-lscache)
- [GitHub](https://github.com/android-com-pl/flarum-lscache)
- [Donate](https://github.com/android-com-pl/flarum-lscache?sponsor=1)
- [Discuss](https://discuss.flarum.org/d/29475)
