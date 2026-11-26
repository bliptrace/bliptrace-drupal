<?php

declare(strict_types=1);

use Bliptrace\Drupal\Reporter;
use Bliptrace\Php\Client;
use Bliptrace\Php\Config;
use Bliptrace\Php\Transport\Transport;
use Symfony\Component\HttpFoundation\Request;

function reporterWith(FakeTransport $t, array $overrides = []): Reporter
{
    $config = new Config(
        key: 'test-key',
        endpoint: 'http://api.test',
        basePath: dirname(__DIR__),
        ignoreExceptions: $overrides['ignoreExceptions'] ?? [],
        enabled: $overrides['enabled'] ?? true,
        sdkName: 'bliptrace-drupal',
    );

    return new Reporter(new Client($config, $t));
}

it('builds and ships a valid envelope for a thrown exception', function (): void {
    $t = new FakeTransport;
    reporterWith($t)->capture(new RuntimeException('boom'));

    expect($t->sent)->toHaveCount(1);

    $env = $t->sent[0]['envelope'];
    expect($env['exception']['type'])->toBe('RuntimeException')
        ->and($env['exception']['message'])->toBe('boom')
        ->and($env['runtime']['name'])->toBe('php')
        ->and($env['sdk']['name'])->toBe('bliptrace-drupal')
        ->and($t->sent[0]['endpoint'])->toBe('http://api.test')
        ->and($t->sent[0]['key'])->toBe('test-key');
});

it('skips ignored exception types', function (): void {
    $t = new FakeTransport;
    reporterWith($t, ['ignoreExceptions' => [LogicException::class]])
        ->capture(new LogicException('nope'));

    expect($t->sent)->toBeEmpty();
});

it('sends nothing when disabled', function (): void {
    $t = new FakeTransport;
    reporterWith($t, ['enabled' => false])->capture(new Exception('x'));

    expect($t->sent)->toBeEmpty();
});

it('never throws even if the transport blows up', function (): void {
    $throwing = new class implements Transport
    {
        public function send(string $endpoint, string $key, array $envelope, int $timeout): void
        {
            throw new RuntimeException('transport exploded');
        }
    };

    $client = new Client(new Config(key: 'k', enabled: true), $throwing);
    $reporter = new Reporter($client);

    expect(fn () => $reporter->capture(new Exception('boom')))->not->toThrow(Throwable::class);
});

it('attaches request context when a request is supplied', function (): void {
    $t = new FakeTransport;

    $request = Request::create(
        'http://example.test/admin?x=1',
        'POST',
    );
    $request->headers->set('Authorization', 'Bearer secret');
    $request->headers->set('X-Custom', 'keepme');

    reporterWith($t)->capture(new RuntimeException('boom'), $request);

    $req = $t->sent[0]['envelope']['context']['request'];
    expect($req['method'])->toBe('POST')
        ->and($req['url'])->toContain('http://example.test/admin')
        ->and($req['headers'])->not->toHaveKey('authorization')
        ->and(array_change_key_case($req['headers']))->toHaveKey('x-custom');
});
