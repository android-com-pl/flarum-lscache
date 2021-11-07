# [WIP] LiteSpeed Cache for Flarum


[comment]: <> (![License]&#40;https://img.shields.io/badge/license-MIT-blue.svg&#41; [![Latest Stable Version]&#40;https://img.shields.io/packagist/v/acpl/flarum-lscache.svg&#41;]&#40;https://packagist.org/packages/acpl/flarum-lscache&#41; [![Total Downloads]&#40;https://img.shields.io/packagist/dt/acpl/flarum-lscache.svg&#41;]&#40;https://packagist.org/packages/acpl/flarum-lscache&#41;)

A [Flarum](http://flarum.org) extension. Integrates LsCache with your forum.

Requires a LiteSpeed server.

> 🚨 This extension is not yet ready - do not install it on production. If you are interested, you are welcome to contribute.

TODO:
- Handling logged-in cookies
- CSRF support

# Installation

- Add this to your `.htaccess` file:

```apacheconf
<IfModule LiteSpeed>
    CacheLookup on
    RewriteRule .* - [E=Cache-Vary:flarum_remember,flarum_lscache_vary]
</IfModule>
```


[comment]: <> (Install with composer:)

[comment]: <> (```sh)

[comment]: <> (composer require acpl/flarum-lscache:"*")

[comment]: <> (```)

[comment]: <> (## Updating)

[comment]: <> (```sh)

[comment]: <> (composer update acpl/flarum-lscache:"*")

[comment]: <> (php flarum migrate)

[comment]: <> (php flarum cache:clear)

[comment]: <> (```)

[comment]: <> (## Links)

[comment]: <> (- [Packagist]&#40;https://packagist.org/packages/acpl/flarum-lscache&#41;)

[comment]: <> (- [GitHub]&#40;https://github.com/android-com-pl/flarum-lscache&#41;)

[comment]: <> (- [Discuss]&#40;https://discuss.flarum.org/d/PUT_DISCUSS_SLUG_HERE&#41;)
