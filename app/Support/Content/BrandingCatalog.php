<?php

namespace App\Support\Content;

final class BrandingCatalog
{
    /**
     * @return array{
     *     intro: string,
     *     groups: list<array{
     *         title: string,
     *         description: string,
     *         marks: list<array{
     *             label: string,
     *             width: int,
     *             height: int,
     *             preview: string,
     *             default_format: string,
     *             variants: array<string, array{
     *                 path: string,
     *                 url: string,
     *                 filename: string,
     *                 format: string,
     *                 bytes: int
     *             }>
     *         }>
     *     }>
     * }
     */
    public function forPage(): array
    {
        $config = config('smelterworks.branding');

        $groups = collect($config['groups'] ?? [])
            ->map(function (array $group): array {
                return [
                    'title' => (string) $group['title'],
                    'description' => (string) $group['description'],
                    'marks' => $this->resolveMarks($group),
                ];
            })
            ->filter(fn (array $group): bool => $group['marks'] !== [])
            ->values()
            ->all();

        return [
            'intro' => (string) ($config['intro'] ?? ''),
            'groups' => $groups,
        ];
    }

    /**
     * @return list<array{
     *     label: string,
     *     width: int,
     *     height: int,
     *     preview: string,
     *     default_format: string,
     *     variants: array<string, array{
     *         path: string,
     *         url: string,
     *         filename: string,
     *         format: string,
     *         bytes: int
     *     }>
     * }>
     */
    private function resolveMarks(array $group): array
    {
        $defaultPreview = (string) ($group['preview'] ?? 'solid');
        $definitions = [];

        if (isset($group['marks']) && is_array($group['marks'])) {
            foreach ($group['marks'] as $mark) {
                $definitions[] = [
                    'basename' => (string) $mark['basename'],
                    'size' => (int) $mark['size'],
                    'preview' => (string) ($mark['preview'] ?? $defaultPreview),
                ];
            }
        } else {
            $basename = (string) ($group['basename'] ?? '');

            foreach ($group['sizes'] ?? [] as $size) {
                $definitions[] = [
                    'basename' => $basename,
                    'size' => (int) $size,
                    'preview' => $defaultPreview,
                ];
            }
        }

        return collect($definitions)
            ->map(fn (array $definition): ?array => $this->buildMark(
                $definition['basename'],
                $definition['size'],
                $definition['preview'],
            ))
            ->filter()
            ->sortBy('width')
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     label: string,
     *     width: int,
     *     height: int,
     *     preview: string,
     *     default_format: string,
     *     variants: array<string, array{
     *         path: string,
     *         url: string,
     *         filename: string,
     *         format: string,
     *         bytes: int
     *     }>
     * }|null
     */
    private function buildMark(string $basename, int $size, string $preview): ?array
    {
        $variants = [];

        foreach (['png', 'webp'] as $extension) {
            $path = $this->assetPath($basename, $size, $extension);
            $absolute = public_path($path);

            if (! is_file($absolute)) {
                continue;
            }

            $imageSize = @getimagesize($absolute);

            if ($imageSize === false) {
                continue;
            }

            $variants[$extension] = [
                'path' => $path,
                'url' => asset($path),
                'filename' => basename($path),
                'format' => strtoupper($extension),
                'bytes' => filesize($absolute) ?: 0,
            ];
        }

        if ($variants === []) {
            return null;
        }

        $width = $size;
        $height = $size;

        if (isset($variants['png'])) {
            $pngSize = @getimagesize(public_path($variants['png']['path']));

            if ($pngSize !== false) {
                $width = (int) $pngSize[0];
                $height = (int) $pngSize[1];
            }
        }

        return [
            'label' => $width.'×'.$height.' px',
            'width' => $width,
            'height' => $height,
            'preview' => $preview,
            'default_format' => array_key_exists('png', $variants) ? 'png' : array_key_first($variants),
            'variants' => $variants,
        ];
    }

    private function assetPath(string $basename, int $size, string $extension): string
    {
        if ($size >= 1024) {
            return 'images/brand/'.$basename.'.'.$extension;
        }

        return 'images/brand/'.$basename.'-'.$size.'.'.$extension;
    }
}
