<?php

declare(strict_types=1);

namespace G8Scope\Drupal;

use G8Scope\Php\Client;

/**
 * Builds a {@see Reporter} from a settings array.
 *
 * Used by the Drupal container as a service factory (see g8scope.services.yml):
 * Drupal's `settings` service yields the `g8scope` array, this turns it into a
 * configured Client + Reporter. Framework-light, so it is unit-testable too.
 */
final class ReporterFactory
{
    /**
     * @param  array<string,mixed>  $settings
     */
    public static function create(array $settings): Reporter
    {
        $config = G8ScopeSettings::toConfig($settings);

        return new Reporter(new Client($config));
    }

    /**
     * Drupal container factory entry point.
     *
     * Expects the Drupal `settings` service (\Drupal\Core\Site\Settings),
     * duck typed so this file stays autoload-safe without Drupal core.
     */
    public static function fromDrupalSettings(mixed $settings): Reporter
    {
        $g8 = [];
        if (is_object($settings) && method_exists($settings, 'get')) {
            $g8 = $settings->get('g8scope', []);
        }

        return self::create(is_array($g8) ? $g8 : []);
    }
}
