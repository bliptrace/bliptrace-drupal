<?php

declare(strict_types=1);

use G8Scope\Drupal\G8ScopeSettings;
use G8Scope\Drupal\Reporter;
use G8Scope\Drupal\ReporterFactory;

it('maps a settings array to a Config', function (): void {
    $config = G8ScopeSettings::toConfig([
        'enabled' => true,
        'key' => 'abc',
        'endpoint' => 'https://ingest.test',
        'environment' => 'staging',
        'release' => '2.0.0',
        'timeout' => 5,
        'scrub_keys' => ['password', 'token'],
        'ignore_exceptions' => [LogicException::class],
    ]);

    expect($config->key)->toBe('abc')
        ->and($config->endpoint)->toBe('https://ingest.test')
        ->and($config->environment)->toBe('staging')
        ->and($config->release)->toBe('2.0.0')
        ->and($config->timeout)->toBe(5)
        ->and($config->scrubKeys)->toBe(['password', 'token'])
        ->and($config->ignoreExceptions)->toBe([LogicException::class])
        ->and($config->sdkName)->toBe('g8scope-drupal');
});

it('applies sensible defaults for an empty settings array', function (): void {
    $config = G8ScopeSettings::toConfig([]);

    expect($config->key)->toBeNull()
        ->and($config->enabled)->toBeTrue()
        ->and($config->environment)->toBe('production')
        ->and($config->endpoint)->toBe('https://ingest.bliptrace.com/v1/ingest');
});

it('builds a Reporter from a settings array', function (): void {
    $reporter = ReporterFactory::create(['key' => 'k', 'endpoint' => 'http://api.test']);

    expect($reporter)->toBeInstanceOf(Reporter::class);
});

it('reads g8scope from a Drupal-style settings object', function (): void {
    $settings = new class
    {
        public function get(string $name, $default = null)
        {
            return $name === 'g8scope'
                ? ['key' => 'from-drupal', 'endpoint' => 'http://api.test']
                : $default;
        }
    };

    $reporter = ReporterFactory::fromDrupalSettings($settings);

    expect($reporter)->toBeInstanceOf(Reporter::class);
});
