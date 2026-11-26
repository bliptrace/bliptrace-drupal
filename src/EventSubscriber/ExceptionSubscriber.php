<?php

declare(strict_types=1);

namespace Drupal\bliptrace\EventSubscriber;

use Bliptrace\Drupal\Reporter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

/**
 * Subscribes to the kernel EXCEPTION event and forwards the Throwable to the
 * Bliptrace Reporter. Drupal is Symfony-based, so this is the natural hook for
 * capturing unhandled exceptions across the whole site.
 *
 * NEVER breaks the site: the listener swallows every error from the capture
 * path and NEVER stops propagation or alters the response — Drupal's own error
 * handling continues exactly as before.
 *
 * Wired via bliptrace.services.yml (tagged `event_subscriber`). Loaded only inside
 * a running Drupal/Symfony kernel; the Symfony classes it references are present
 * there (and in this package's dev dependencies for the test loader).
 */
final class ExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Reporter $reporter,
    ) {}

    /**
     * @return array<string,array<int,array<int,int|string>>>
     */
    public static function getSubscribedEvents(): array
    {
        // Low priority so framework/site listeners run first; we only observe.
        return [
            KernelEvents::EXCEPTION => [['onException', -256]],
        ];
    }

    public function onException(ExceptionEvent $event): void
    {
        try {
            $throwable = $event->getThrowable();
            $request = method_exists($event, 'getRequest') ? $event->getRequest() : null;

            $this->reporter->capture($throwable, $request);
        } catch (Throwable) {
            // Never break the site.
        }
    }
}
