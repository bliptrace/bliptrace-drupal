<?php

declare(strict_types=1);

use Drupal\bliptrace\EventSubscriber\ExceptionSubscriber;
use Bliptrace\Drupal\Reporter;
use Bliptrace\Php\Client;
use Bliptrace\Php\Config;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

it('is loadable and subscribes to the kernel exception event', function (): void {
    $events = ExceptionSubscriber::getSubscribedEvents();

    expect($events)->toHaveKey(KernelEvents::EXCEPTION);
});

it('forwards a thrown exception from the event to the reporter', function (): void {
    $t = new FakeTransport;
    $client = new Client(new Config(key: 'k', endpoint: 'http://api.test', enabled: true), $t);
    $subscriber = new ExceptionSubscriber(new Reporter($client));

    $kernel = new class implements HttpKernelInterface
    {
        public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
        {
            return new Response;
        }
    };
    $request = Request::create('http://example.test/x', 'GET');
    $event = new ExceptionEvent(
        $kernel,
        $request,
        HttpKernelInterface::MAIN_REQUEST,
        new RuntimeException('kaboom'),
    );

    $subscriber->onException($event);

    expect($t->sent)->toHaveCount(1)
        ->and($t->sent[0]['envelope']['exception']['type'])->toBe('RuntimeException')
        ->and($t->sent[0]['envelope']['exception']['message'])->toBe('kaboom');
})->skip(! interface_exists(HttpKernelInterface::class), 'symfony/http-kernel not installed');
