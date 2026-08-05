<?php

namespace App\Support\Seo;

final class JsonLdBuilder
{
    /**
     * @param  list<array<string, mixed>>  $extra
     */
    public function encode(array $extra = []): string
    {
        $payload = [
            '@context' => 'https://schema.org',
            '@graph' => $this->graph($extra),
        ];

        return json_encode(
            $payload,
            JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $extra
     * @return list<array<string, mixed>>
     */
    public function graph(array $extra = []): array
    {
        $base = rtrim((string) config('app.url'), '/');
        $name = (string) config('app.name');
        $orgId = $base.'/#organization';
        $siteId = $base.'/#website';

        $sameAs = array_values(array_filter([
            config('smelterworks.links.forgejo'),
            config('smelterworks.links.github'),
            config('smelterworks.donate.kofi_url'),
            config('smelterworks.links.fluxer'),
            config('smelterworks.links.wiki'),
        ], fn (mixed $url): bool => filled($url)));

        $organization = [
            '@type' => 'Organization',
            '@id' => $orgId,
            'name' => $name,
            'url' => $base.'/',
            'logo' => [
                '@type' => 'ImageObject',
                'url' => asset('images/brand/SmelterWorks-512.png'),
            ],
            'description' => (string) config('smelterworks.mission'),
        ];

        if ($sameAs !== []) {
            $organization['sameAs'] = $sameAs;
        }

        $email = config('smelterworks.contact.email');
        if (filled($email)) {
            $organization['email'] = (string) $email;
        }

        $website = [
            '@type' => 'WebSite',
            '@id' => $siteId,
            'name' => $name,
            'url' => $base.'/',
            'description' => (string) config('smelterworks.mission'),
            'publisher' => ['@id' => $orgId],
            'inLanguage' => str_replace('_', '-', (string) config('app.locale', 'en')),
        ];

        return array_values(array_merge([$organization, $website], $extra));
    }
}
