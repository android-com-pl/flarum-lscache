# LiteSpeed Cache for Flarum


[![Latest Stable Version](https://img.shields.io/packagist/v/acpl/flarum-lscache)](https://packagist.org/packages/acpl/flarum-lscache) [![Total Downloads](https://img.shields.io/packagist/dt/acpl/flarum-lscache.svg)](https://packagist.org/packages/acpl/flarum-lscache) [![GitHub Sponsors](https://img.shields.io/badge/Donate-%E2%9D%A4-%23db61a2.svg?&logo=github&logoColor=white&labelColor=181717)](https://github.com/android-com-pl/flarum-lscache?sponsor=1)

A [Flarum](http://flarum.org) extension. Integrates [LSCache](https://lscache.io/) with your forum.

Requires a LiteSpeed Web Server or OpenLiteSpeed.

# Installation

### Install with composer:

```sh
composer require acpl/flarum-lscache
```

Upon initial activation, the extension will add its configurations to the `.htaccess` file.
It's recommended to back up your `.htaccess` file before installing the extension.

### Updating

```sh
composer update acpl/flarum-lscache
php flarum migrate
php flarum cache:clear
```

### Cache Management

This extension smartly manages the cache by purging it only where needed. For instance, when a new post is added in a discussion, the cache for that specific discussion, its tags, and the homepage are purged.

When you clear the Flarum cache, the LSCache is also cleared automatically unless you disable this feature in the settings.

You can clear the LSCache without clearing the Flarum cache via the admin panel. This option is available under the standard Flarum cache clearing option. There is also the `php flarum lscache:clear` command. The command supports the `--path` argument. For example, `php flarum lscache:clear --path=/tags --path=/d/1-test`. You can use this if you only want to purge specific paths instead of the entire cache.

## How to Contribute

We welcome any contributions to the development of LiteSpeed Cache for Flarum!
If you'd like to contribute, feel free to fork [this repository](https://github.com/android-com-pl/flarum-lscache) and submit a pull request.
You can also [open an issue](https://github.com/android-com-pl/flarum-lscache/issues) if you want to suggest improvements or report a problem.

### Support This Project

This project is open source and maintained by a single developer.
If you find it useful and would like to ensure its continued development, please consider supporting it through [GitHub Sponsors](https://github.com/android-com-pl/flarum-lscache?sponsor=1).
Your support is greatly appreciated!

## Links

- [Packagist](https://packagist.org/packages/acpl/flarum-lscache)
- [GitHub](https://github.com/android-com-pl/flarum-lscache)
- [Discuss](https://discuss.flarum.org/d/29475)
