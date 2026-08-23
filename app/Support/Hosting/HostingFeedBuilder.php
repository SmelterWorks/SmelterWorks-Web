<?php

namespace App\Support\Hosting;

use Illuminate\Support\Carbon;

final class HostingFeedBuilder
{
    public function __construct(
        private readonly HostingCatalog $catalog,
        private readonly HostingStockService $stock,
    ) {}

    /**
     * @return array{
     *     title: string,
     *     link: string,
     *     description: string,
     *     language: string,
     *     last_build: Carbon,
     *     items: list<array{title: string, link: string, guid: string, description: string, pub_date: Carbon}>
     * }
     */
    public function build(): array
    {
        $comingSoon = (bool) config('smelterworks.hosting.coming_soon', false);
        $hosting = config('smelterworks.hosting');
        $inventory = [];

        if (! $comingSoon) {
            $this->stock->syncFromConfig();
            $inventory = $this->stock->inventorySnapshot();
        }

        $configPath = config_path('smelterworks/hosting.php');
        $updatedAt = Carbon::createFromTimestamp(
            is_file($configPath) ? (int) filemtime($configPath) : time(),
            'UTC',
        );

        $items = [];

        foreach ($this->catalog->plans() as $plan) {
            $stock = $inventory[$plan['slug']] ?? null;
            $isByos = $this->catalog->isByos($plan);

            if ($isByos) {
                $status = $comingSoon ? 'Coming soon' : 'Available';
                $availability = $comingSoon
                    ? 'Purchases are not open yet.'
                    : 'Unlimited panel slots on your own hardware.';

                $description = sprintf(
                    '%s $%s/mo or $%s/yr per daemon. Panel access only, not game-server RAM. Local backups included. Optional cloud backups from $3/mo (25GB) to $7/mo (100GB). Status: %s. %s',
                    $plan['blurb'],
                    number_format((float) $plan['price_monthly'], 2),
                    number_format((float) $plan['price_yearly'], 2),
                    $status,
                    $availability,
                );

                $fingerprint = implode('|', [
                    $plan['slug'],
                    $plan['price_monthly'],
                    $plan['price_yearly'],
                    $comingSoon ? 'soon' : 'open',
                    'byos',
                ]);

                $items[] = [
                    'title' => sprintf(
                        '%s: $%s/mo (%s)',
                        $plan['name'],
                        number_format((float) $plan['price_monthly'], 0),
                        $status,
                    ),
                    'link' => route('hosting'),
                    'guid' => 'smelterworks-hosting-'.$plan['slug'].'-'.substr(sha1($fingerprint), 0, 12),
                    'description' => $description,
                    'pub_date' => $updatedAt,
                ];

                continue;
            }

            $remaining = $stock['remaining'] ?? null;
            $status = $comingSoon
                ? 'Coming soon'
                : (($remaining !== null && $remaining < 1) ? 'Sold out' : 'Available');

            $availability = $comingSoon
                ? 'Purchases are not open yet.'
                : sprintf(
                    'Stock remaining: %d of %d.',
                    (int) ($stock['remaining'] ?? 0),
                    (int) ($stock['capacity'] ?? 0),
                );

            $description = sprintf(
                '%s. %d GB RAM, %d GB storage. $%s/mo or $%s/year. Status: %s. %s Regions: US and Germany.',
                $plan['blurb'],
                (int) $plan['ram_gb'],
                (int) $plan['storage_gb'],
                number_format((float) $plan['price_monthly'], 2),
                number_format((float) $plan['price_yearly'], 2),
                $status,
                $availability,
            );

            $fingerprint = implode('|', [
                $plan['slug'],
                $plan['price_monthly'],
                $plan['price_yearly'],
                $plan['ram_gb'],
                $plan['storage_gb'],
                $comingSoon ? 'soon' : 'open',
                $stock['remaining'] ?? 'n/a',
                $stock['capacity'] ?? 'n/a',
            ]);

            $items[] = [
                'title' => sprintf(
                    '%s: $%s/mo (%s)',
                    $plan['name'],
                    number_format((float) $plan['price_monthly'], 0),
                    $status,
                ),
                'link' => route('hosting'),
                'guid' => 'smelterworks-hosting-'.$plan['slug'].'-'.substr(sha1($fingerprint), 0, 12),
                'description' => $description,
                'pub_date' => $updatedAt,
            ];
        }

        return [
            'title' => config('app.name').' Hosting',
            'link' => route('hosting'),
            'description' => trim(($hosting['summary'] ?? '').' '.($hosting['tagline'] ?? '')),
            'language' => 'en-us',
            'last_build' => $updatedAt,
            'items' => $items,
        ];
    }
}
