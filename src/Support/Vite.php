<?php declare(strict_types=1);

namespace App\Support;

/**
 * Generates HTML tags for assets compiled by Vite.
 */
final class Vite
{
    private const DEFAULT_ENTRIES = [
        'resources/scss/app.scss',
        'resources/js/app.ts',
    ];

    private string $buildPath;
    private string $buildUrl;
    private string $hotFile;
    private string $manifestPath;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $manifest = null;

    public function __construct(
        ?string $buildPath = null,
        string $buildUrl = '/build'
    ) {
        $this->buildPath = rtrim($buildPath ?? PROJECT_ROOT . '/public/build', '/\\');
        $this->buildUrl = '/' . trim($buildUrl, '/');
        $this->hotFile = $this->buildPath . '/hot';
        $this->manifestPath = $this->buildPath . '/.vite/manifest.json';
    }

    /**
     * Render Vite script and stylesheet tags for one or more entrypoints.
     *
     * @param string|array<int, string> $entries
     * @return string
     */
    public function tags(string|array $entries = self::DEFAULT_ENTRIES): string
    {
        $entries = is_array($entries) ? $entries : [$entries];
        $tags = [];

        if ($this->isRunningHot()) {
            $devServer = $this->devServerUrl();
            $stylesheetTags = [];
            $scriptTags = [$this->scriptTag($devServer . '/@vite/client')];

            foreach ($entries as $entry) {
                $url = $devServer . '/' . ltrim($entry, '/');

                if ($this->isStylesheet($entry)) {
                    $stylesheetTags[] = $this->stylesheetTag($url);
                    continue;
                }

                $scriptTags[] = $this->scriptTag($url);
            }

            return implode(PHP_EOL, [...$stylesheetTags, ...$scriptTags]);
        }

        foreach ($entries as $entry) {
            foreach ($this->productionTags($entry) as $tag) {
                $tags[] = $tag;
            }
        }

        return implode(PHP_EOL, array_values(array_unique($tags)));
    }

    /**
     * @return array<int, string>
     */
    private function productionTags(string $entry): array
    {
        $manifest = $this->manifest();

        if (!isset($manifest[$entry]) || !is_array($manifest[$entry])) {
            throw new \RuntimeException("Vite entry [{$entry}] was not found in the manifest.");
        }

        $chunk = $manifest[$entry];
        $tags = [];

        foreach ($this->collectCss($chunk, $manifest) as $cssFile) {
            $tags[] = $this->stylesheetTag($this->assetUrl($cssFile));
        }

        if (!isset($chunk['file']) || !is_string($chunk['file'])) {
            throw new \RuntimeException("Vite entry [{$entry}] is missing its output file.");
        }

        $tags[] = $this->isStylesheet($chunk['file'])
            ? $this->stylesheetTag($this->assetUrl($chunk['file']))
            : $this->scriptTag($this->assetUrl($chunk['file']));

        return $tags;
    }

    /**
     * @param array<string, mixed> $chunk
     * @param array<string, mixed> $manifest
     * @return array<int, string>
     */
    private function collectCss(array $chunk, array $manifest): array
    {
        $css = [];

        foreach (($chunk['imports'] ?? []) as $import) {
            if (is_string($import) && isset($manifest[$import]) && is_array($manifest[$import])) {
                $css = array_merge($css, $this->collectCss($manifest[$import], $manifest));
            }
        }

        foreach (($chunk['css'] ?? []) as $file) {
            if (is_string($file)) {
                $css[] = $file;
            }
        }

        return array_values(array_unique($css));
    }

    /**
     * @return array<string, mixed>
     */
    private function manifest(): array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }

        if (!is_file($this->manifestPath)) {
            throw new \RuntimeException('Vite manifest does not exist. Run `npm run build` or start `npm run dev`.');
        }

        $contents = file_get_contents($this->manifestPath);

        if ($contents === false) {
            throw new \RuntimeException('Vite manifest could not be read.');
        }

        $manifest = json_decode($contents, true);

        if (!is_array($manifest)) {
            throw new \RuntimeException('Vite manifest contains invalid JSON.');
        }

        $this->manifest = $manifest;

        return $this->manifest;
    }

    private function isRunningHot(): bool
    {
        return is_file($this->hotFile);
    }

    private function devServerUrl(): string
    {
        $url = trim((string) file_get_contents($this->hotFile));

        if ($url === '') {
            throw new \RuntimeException('Vite hot file is empty.');
        }

        return rtrim($url, '/');
    }

    private function assetUrl(string $file): string
    {
        return $this->buildUrl . '/' . ltrim($file, '/');
    }

    private function isStylesheet(string $file): bool
    {
        return preg_match('/\.(css|scss|sass|less|styl|stylus)(\?.*)?$/', $file) === 1;
    }

    private function scriptTag(string $src): string
    {
        return '<script type="module" src="' . s($src) . '"></script>';
    }

    private function stylesheetTag(string $href): string
    {
        return '<link rel="stylesheet" href="' . s($href) . '">';
    }
}
