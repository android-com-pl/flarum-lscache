# LiteSpeed Cache for Flarum


[comment]: <> (![License]&#40;https://img.shields.io/badge/license-MIT-blue.svg&#41; [![Latest Stable Version]&#40;https://img.shields.io/packagist/v/acpl/flarum-lscache.svg&#41;]&#40;https://packagist.org/packages/acpl/flarum-lscache&#41; [![Total Downloads]&#40;https://img.shields.io/packagist/dt/acpl/flarum-lscache.svg&#41;]&#40;https://packagist.org/packages/acpl/flarum-lscache&#41;)

A [Flarum](http://flarum.org) extension. Integrates LSCache with your forum.

Requires a LiteSpeed Web Server.

# Installation

### Add this to your `.htaccess` file:

```apacheconf
<IfModule LiteSpeed>
    CacheLookup on
    RewriteEngine On
    RewriteCond %{REQUEST_METHOD} ^HEAD|GET$
    # Detection of logged-in user.
    RewriteRule .* - [E="Cache-Vary:flarum_remember,flarum_lscache_vary"]
    # If you have a non-default path to the admin panel, change "admin" to match.
    RewriteCond %{ORG_REQ_URI} !/admin
    # Enable private cache for admin panel. If it causes problems replace "private" with "no-cache".
    RewriteRule .* - [E=Cache-Control:private]
</IfModule>
```
Rules in `.htaccess` have a higher priority than those added by the extension. For example, you can exclude specific URLs from the cache or change their expiration time. For more options, see here: https://docs.litespeedtech.com/lscache/noplugin/settings/#rewrite-rules


### Install with composer:

```sh

composer require acpl/flarum-lscache:"*"

```

### Updating

```sh

composer update acpl/flarum-lscache:"*"

php flarum migrate

php flarum cache:clear

```

[comment]: <> (## Links)

[comment]: <> (- [Packagist]&#40;https://packagist.org/packages/acpl/flarum-lscache&#41;)

[comment]: <> (- [GitHub]&#40;https://github.com/android-com-pl/flarum-lscache&#41;)

[comment]: <> (- [Discuss]&#40;https://discuss.flarum.org/d/PUT_DISCUSS_SLUG_HERE&#41;)
