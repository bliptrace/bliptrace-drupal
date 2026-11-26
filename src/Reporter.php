<?php

declare(strict_types=1);

namespace Bliptrace\Drupal;

use Bliptrace\Drupal\Context\RequestContext;
use Bliptrace\Php\Client;
use Throwable;

/**
 * Drupal reporter: enrich a Throwable with HTTP request context and ship it via
 * the runtime PHP client. Framework-light and directly testable — it accepts an
 * already-built Client and an optional request object (duck typed).
 *
 * Guarantee: capture() never throws. A monitoring failure must never break the
 * site.
 */
final class Reporter
{
    public function __construct(
        private readonly Client $client,
    ) {}

    /**
     * Capture a Throwable, enriching it with context from $request when present.
     *
     * @param  object|null  $request  A Symfony/Drupal Request, or null.
     */
    public function capture(Throwable $e, ?object $request = null): void
    {
        try {
            $context = $request !== null
                ? RequestContext::fromRequest($request)
                : RequestContext::empty();

            $this->client->capture($e, $context);
        } catch (Throwable) {
            // Never break the site.
        }
    }
}
