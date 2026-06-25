<?php

declare(strict_types=1);

use G8Scope\Drupal\Context\RequestContext;
use Symfony\Component\HttpFoundation\Request;

it('strips authorization and cookie headers and yields method/url', function (): void {
    $ctx = RequestContext::fromArrays(
        method: 'GET',
        url: 'https://example.test/path',
        headers: [
            'Authorization' => 'Bearer secret',
            'Cookie' => 'session=abc',
            'X-Trace' => 'keep',
            'Accept' => ['application/json'],
        ],
    );

    expect($ctx['request']['method'])->toBe('GET')
        ->and($ctx['request']['url'])->toBe('https://example.test/path')
        ->and($ctx['request']['headers'])->toHaveKey('X-Trace')
        ->and($ctx['request']['headers']['X-Trace'])->toBe('keep')
        ->and($ctx['request']['headers']['Accept'])->toBe('application/json')
        ->and($ctx['request']['headers'])->not->toHaveKey('Authorization')
        ->and($ctx['request']['headers'])->not->toHaveKey('Cookie')
        ->and($ctx['custom'])->toBe([]);
});

it('builds context from a Symfony request', function (): void {
    $request = Request::create('https://example.test/x', 'DELETE');
    $request->headers->set('Cookie', 'a=b');
    $request->headers->set('X-Keep', 'yes');

    $ctx = RequestContext::fromRequest($request);

    expect($ctx['request']['method'])->toBe('DELETE')
        ->and($ctx['request']['url'])->toContain('https://example.test/x')
        ->and(array_change_key_case($ctx['request']['headers']))->not->toHaveKey('cookie')
        ->and(array_change_key_case($ctx['request']['headers']))->toHaveKey('x-keep');
});

it('returns only custom when there is no request', function (): void {
    expect(RequestContext::fromRequest(null))->toBe(['custom' => []])
        ->and(RequestContext::empty())->toBe(['custom' => []]);
});
