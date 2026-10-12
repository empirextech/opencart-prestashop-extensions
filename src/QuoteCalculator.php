<?php

declare(strict_types=1);

namespace EmpireX\MarketplaceKit;

final class QuoteCalculator
{
    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $address
     * @return array{code:string,title:string,quote:array<string, array<string, mixed>>}|null
     */
    public function quote(array $config, array $address, float $subtotal, float $weightKg): ?array
    {
        if (empty($config['status'])) {
            return null;
        }

        $country = strtoupper((string) ($address['iso_code_2'] ?? $address['iso_code'] ?? $address['country'] ?? ''));
        $city = mb_strtolower(trim((string) ($address['city'] ?? '')));
        $postcode = preg_replace('/\s+/', '', (string) ($address['postcode'] ?? '')) ?? '';

        $blocked = array_filter(array_map('trim', explode(',', (string) ($config['blocked_postcode_prefixes'] ?? ''))));
        foreach ($blocked as $prefix) {
            if ($prefix !== '' && str_starts_with($postcode, $prefix)) {
                return null;
            }
        }

        $freeFrom = (float) ($config['free_from'] ?? 0);
        if ($freeFrom > 0 && $subtotal >= $freeFrom) {
            return $this->wrap((string) $config['title'], 0.0, 'free');
        }

        $cityRates = $this->parseCityRates((string) ($config['city_rates'] ?? ''));
        if ($city !== '' && array_key_exists($city, $cityRates)) {
            return $this->wrap((string) $config['title'] . ' (' . $address['city'] . ')', $cityRates[$city], 'city');
        }

        $weightSurcharge = 0.0;
        $threshold = (float) ($config['heavy_weight_kg'] ?? 0);
        if ($threshold > 0 && $weightKg >= $threshold) {
            $weightSurcharge = (float) ($config['heavy_surcharge'] ?? 0);
        }

        $geoRates = $this->parseGeoRates((string) ($config['country_rates'] ?? ''));
        $base = $geoRates[$country] ?? (float) ($config['default_cost'] ?? 0);
        if ($base <= 0 && $weightSurcharge <= 0) {
            return null;
        }

        return $this->wrap((string) $config['title'], $base + $weightSurcharge, 'geo');
    }

    /**
     * @return array<string, float>
     */
    public function parseCityRates(string $raw): array
    {
        $out = [];
        foreach (preg_split('/\r\n|\n/', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || !str_contains($line, '=')) {
                continue;
            }
            [$city, $rate] = array_map('trim', explode('=', $line, 2));
            if ($city !== '' && is_numeric($rate)) {
                $out[mb_strtolower($city)] = (float) $rate;
            }
        }

        return $out;
    }

    /**
     * @return array<string, float>
     */
    public function parseGeoRates(string $raw): array
    {
        $out = [];
        foreach (preg_split('/\r\n|\n/', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || !str_contains($line, '=')) {
                continue;
            }
            [$code, $rate] = array_map('trim', explode('=', $line, 2));
            if (strlen($code) === 2 && is_numeric($rate)) {
                $out[strtoupper($code)] = (float) $rate;
            }
        }

        return $out;
    }

    /**
     * @return array{code:string,title:string,quote:array<string, array<string, mixed>>}
     */
    private function wrap(string $title, float $cost, string $suffix): array
    {
        return [
            'code' => 'empirex_rules',
            'title' => $title,
            'quote' => [
                'empirex_rules_' . $suffix => [
                    'code' => 'empirex_rules.empirex_rules_' . $suffix,
                    'title' => $title,
                    'cost' => round($cost, 2),
                    'tax_class_id' => 0,
                    'text' => number_format($cost, 2, '.', ''),
                ],
            ],
        ];
    }
}
