<?php

declare(strict_types=1);

namespace G8Scope\Drupal\Context;

/**
 * Builds the envelope context from an HTTP request.
 *
 * Framework-light by design: it accepts a Symfony/Drupal Request object (duck
 * typed) OR plain arrays, so it can be exercised in tests without Drupal core.
 * Sensitive headers (authorization, cookie) are stripped before anything leaves
 * the process. Returns `['custom' => []]` when there is no request to describe.
 */
final class RequestContext
{
    /**
     * Build context from a Symfony/Drupal Request (\Symfony\Component\HttpFoundation\Request).
     *
     * @return array<string,mixed>
     */
    public static function fromRequest(mixed $request): array
    {
        if ($request === null || ! is_object($request)) {
            return ['custom' => []];
        }

        if (! method_exists($request, 'getMethod') || ! method_exists($request, 'getUri')) {
            return ['custom' => []];
        }

        $headers = [];
        if (isset($request->headers) && method_exists($request->headers, 'all')) {
            $headers = $request->headers->all();
        }

        return self::fromArrays(
            method: (string) $request->getMethod(),
            url: (string) $request->getUri(),
            headers: is_array($headers) ? $headers : [],
        );
    }

    /**
     * Build context from plain values (testable without any framework).
     *
     * @param  array<string,mixed>  $headers
     * @return array<string,mixed>
     */
    public static function fromArrays(string $method, string $url, array $headers = []): array
    {
        $clean = [];
        foreach ($headers as $name => $value) {
            if (in_array(strtolower((string) $name), ['authorization', 'cookie'], true)) {
                continue;
            }
            $clean[$name] = is_array($value) ? implode(',', $value) : (string) $value;
        }

        return [
            'request' => [
                'method' => $method,
                'url' => $url,
                'headers' => $clean,
            ],
            'custom' => [],
        ];
    }

    /** @return array<string,mixed> */
    public static function empty(): array
    {
        return ['custom' => []];
    }
}
