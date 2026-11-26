<?php

declare(strict_types=1);

namespace Bliptrace\Drupal;

use Bliptrace\Php\Config;

/**
 * Maps Drupal settings (or environment) to a runtime PHP {@see Config}.
 *
 * In a live Drupal site these values come from settings.php / config, e.g.:
 *
 *   $settings['bliptrace'] = [
 *     'enabled'     => TRUE,
 *     'key'         => \Bliptrace\Php\Env::get('KEY'), // BLIPTRACE_KEY
 *     'endpoint'    => 'https://ingest.bliptrace.com/v1/ingest',
 *     'environment' => 'production',
 *     'release'     => '1.0.0',
 *   ];
 *
 * NOTE: `project` is NEVER supplied — it is resolved server-side from the ingest
 * key. Only the key, endpoint and shaping options belong here.
 *
 * This class is framework-light: pass an array and get a Config back. The Drupal
 * service wiring (bliptrace.services.yml) is responsible for sourcing that array.
 */
final class BliptraceSettings
{
    /**
     * Build a Config from a plain settings array.
     *
     * @param  array<string,mixed>  $settings
     */
    public static function toConfig(array $settings): Config
    {
        return new Config(
            key: self::stringOrNull($settings['key'] ?? null),
            endpoint: (string) ($settings['endpoint'] ?? 'https://ingest.bliptrace.com/v1/ingest'),
            environment: (string) ($settings['environment'] ?? 'production'),
            release: self::stringOrNull($settings['release'] ?? null),
            timeout: (int) ($settings['timeout'] ?? 3),
            maxFrames: (int) ($settings['max_frames'] ?? $settings['maxFrames'] ?? 30),
            maxMessage: (int) ($settings['max_message'] ?? $settings['maxMessage'] ?? 2000),
            ignoreExceptions: self::stringList($settings['ignore_exceptions'] ?? $settings['ignoreExceptions'] ?? []),
            scrubKeys: self::stringList($settings['scrub_keys'] ?? $settings['scrubKeys'] ?? []),
            basePath: (string) ($settings['base_path'] ?? $settings['basePath'] ?? ''),
            enabled: (bool) ($settings['enabled'] ?? true),
            sdkName: 'bliptrace-drupal',
            sdkVersion: '1.0.0',
        );
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        return (string) $value;
    }

    /**
     * @param  mixed  $value
     * @return array<int,string>
     */
    private static function stringList($value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_map(static fn ($v): string => (string) $v, $value));
    }
}
