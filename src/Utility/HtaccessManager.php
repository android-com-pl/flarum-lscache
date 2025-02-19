<?php

namespace ACPL\FlarumLSCache\Utility;

use ACPL\FlarumLSCache\LSCache;
use Flarum\Foundation\Paths;
use Flarum\Http\CookieFactory;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class HtaccessManager
{
    private const BEGIN_LSCACHE = '# BEGIN LSCACHE';
    private const END_LSCACHE = '# END LSCACHE';

    private string $htaccessPath;
    private Filesystem $filesystem;

    public function __construct(
        Paths $paths,
        private readonly CookieFactory $cookie,
        private readonly SettingsRepositoryInterface $settings,
    ) {
        $this->filesystem = new Filesystem();
        $this->htaccessPath = $paths->public.'/.htaccess';
    }

    /**
     * Updates the .htaccess file with the new LSCache block content. If the LSCache block is already there, it replaces it. Otherwise, it appends it.
     * @throws FileNotFoundException
     */
    public function updateHtaccess(): void
    {
        $htaccessContent = $this->filesystem->get($this->htaccessPath);
        $newContent = $this->generateLsCacheBlock();

        if ($this->hasLsCacheBlock($htaccessContent)) {
            $htaccessContent = $this->replaceLsCacheBlock($htaccessContent, $newContent);
        } else {
            $htaccessContent = $this->prependLsCacheBlock($htaccessContent, $newContent);
        }

        $this->filesystem->put($this->htaccessPath, $htaccessContent);
    }

    /** Generates the content of the LSCache block to be inserted into the .htaccess file. */
    private function generateLsCacheBlock(): string
    {
        $block = self::BEGIN_LSCACHE." - Generated by LSCache extension. Do not manually edit the contents of this block!\n";
        $block .= '<IfModule LiteSpeed>'.
            $this->addLine('CacheLookup on').
            $this->addLine('RewriteEngine On').
            $this->addLine('RewriteCond %{REQUEST_METHOD} ^HEAD|GET$').
            $this->addLine(
                'RewriteRule .* - [E="Cache-Vary:'.implode(',', [
                    $this->cookie->getName(LSCache::VARY_COOKIE),
                    $this->cookie->getName('remember'),
                    'locale',
                ]).'"]',
            );

        // In the extend.php, there is an extender for default settings,
        // but it doesn't work in migration, that's why the default setting is also set here.
        $dropQs = Str::of($this->settings->get('acpl-lscache.drop_qs', implode("\n", LSCache::DEFAULT_DROP_QS)));
        if ($dropQs->isNotEmpty()) {
            $dropQsArr = $dropQs->explode("\n");
            $block .= $this->addLine('');

            foreach ($dropQsArr as $qs) {
                $qs = trim($qs);
                if (! empty($qs)) {
                    $block .= $this->addLine('CacheKeyModify -qs:'.$qs);
                }
            }
        }

        $block .= "\n</IfModule>";
        $block .= "\n".self::END_LSCACHE;

        return $block;
    }

    /** Helper method for generating a line of the LSCache block content */
    private function addLine(string $line): string
    {
        return "\n\t$line";
    }

    /** Checks if the LSCache block is present in the provided .htaccess content */
    private function hasLsCacheBlock(string $htaccessContent): bool
    {
        return str_contains($htaccessContent, self::BEGIN_LSCACHE);
    }

    /** Replaces the existing LSCache block in the .htaccess content with the new content */
    private function replaceLsCacheBlock(string $htaccessContent, string $newContent): string
    {
        $pattern = '/'.preg_quote(self::BEGIN_LSCACHE, '/').'.*'.preg_quote(self::END_LSCACHE, '/').'/s';

        return preg_replace($pattern, $newContent, $htaccessContent);
    }

    /** Prepends the new LSCache block to the .htaccess content */
    private function prependLsCacheBlock(string $htaccessContent, string $newContent): string
    {
        return "$newContent\n".$htaccessContent;
    }

    /**
     * Removes the LSCache block from the .htaccess file. If the block is not present, it does nothing.
     * @throws FileNotFoundException
     */
    public function removeLsCacheBlock(): void
    {
        $htaccessContent = $this->filesystem->get($this->htaccessPath);

        if (! $this->hasLsCacheBlock($htaccessContent)) {
            return;
        }

        $pattern = '/'.preg_quote(self::BEGIN_LSCACHE, '/').'.*'.preg_quote(self::END_LSCACHE, '/').'/s';
        $htaccessContent = preg_replace($pattern, '', $htaccessContent);

        $this->filesystem->put($this->htaccessPath, $htaccessContent);
    }
}
