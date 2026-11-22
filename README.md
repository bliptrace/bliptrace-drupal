# g8scope for Drupal

A **thin** Drupal 10/11 module that auto-captures unhandled exceptions and ships
them to the [g8scope](https://scope.g8suite.com) ingest API. It is a light
adapter over the runtime PHP SDK, [`bliptrace/php`](../bliptrace-php) — all the
envelope building, scrubbing and transport live there; this module only wires
Drupal's exception event into the SDK.

Drupal is built on Symfony, so the natural hook is a kernel **exception event
subscriber**. The module subscribes to `KernelEvents::EXCEPTION`, enriches the
exception with request context, and hands it to the SDK client.

> **Never breaks the site.** Every step on the capture path is guarded. A
> misconfigured key, an unreachable ingest endpoint, or a slow network will
> never raise an error, stop event propagation, or alter the response — Drupal's
> own error handling continues exactly as before.

## How capture works

1. An unhandled `Throwable` triggers `KernelEvents::EXCEPTION`.
2. `ExceptionSubscriber` forwards it (at a low priority, so it only observes) to
   the `g8scope.reporter` service.
3. The `Reporter` builds request context (method, URL, headers — with
   `Authorization` and `Cookie` stripped), then asks the `bliptrace/php` client to
   build, scrub and ship the envelope.
4. The envelope is `POST`ed to `{endpoint}/v1/ingest` with an `X-Ingest-Key`
   header.

> **The project is never sent in the payload.** It is resolved **server-side**
> from the ingest key. Only the key, endpoint and shaping options are
> configured here.

## Install

Add the module via Composer:

```bash
composer require bliptrace/drupal
```

Then enable it:

```bash
drush en g8scope
# or via the UI: Admin → Extend → enable "g8scope"
```

## Configure

Set the ingest key (and any overrides) in your site's `settings.php`. Source the
key from the environment so it never lands in version control:

```php
// settings.php
$settings['g8scope'] = [
  'enabled'     => TRUE,
  'key'         => \G8Scope\Php\Env::get('KEY'),   // BLIPTRACE_KEY (required)
  'endpoint'    => 'https://ingest.scope.g8suite.com',
  'environment' => \G8Scope\Php\Env::get('ENVIRONMENT', 'production'),
  'release'     => \G8Scope\Php\Env::get('RELEASE'),
  'timeout'     => 3,
  // Optional shaping:
  'max_frames'        => 30,
  'max_message'       => 2000,
  'ignore_exceptions' => [],         // fully-qualified Throwable class names to skip
  'scrub_keys'        => ['password', 'token', 'secret'],
];
```

> **Env names:** `G8Scope\Php\Env::get('KEY')` (from `bliptrace/php` ^1.2) reads `BLIPTRACE_KEY` and
> falls back to the deprecated `G8SCOPE_KEY` when it is unset (fallback removed in 2.0). Rename your
> variables to `BLIPTRACE_*`; `G8SCOPE_ENV` becomes `BLIPTRACE_ENVIRONMENT`.

| Setting             | Maps to `G8Scope\Php\Config` | Default                              |
| ------------------- | ---------------------------- | ------------------------------------ |
| `enabled`           | `enabled`                    | `true`                               |
| `key`               | `key`                        | `null` (capture is off without it)   |
| `endpoint`          | `endpoint`                   | `https://ingest.scope.g8suite.com`   |
| `environment`       | `environment`                | `production`                         |
| `release`           | `release`                    | `null`                               |
| `timeout`           | `timeout`                    | `3`                                  |
| `max_frames`        | `maxFrames`                  | `30`                                 |
| `max_message`       | `maxMessage`                 | `2000`                               |
| `ignore_exceptions` | `ignoreExceptions`           | `[]`                                 |
| `scrub_keys`        | `scrubKeys`                  | `[]`                                 |

When `enabled` is `false` or `key` is empty, nothing is ever sent.

## Service wiring

`g8scope.services.yml` registers two services:

- `g8scope.reporter` — built by `ReporterFactory::fromDrupalSettings()` from the
  `g8scope` array in `settings.php`.
- `g8scope.exception_subscriber` — tagged `event_subscriber`, listens for
  `KernelEvents::EXCEPTION`.

## Development & tests

The test suite depends **only** on `bliptrace/php` (plus `symfony/http-kernel` to
load the subscriber) — it never pulls in `drupal/core`. The testable pieces
(`Reporter`, `RequestContext`, `G8ScopeSettings`, `ReporterFactory`) are
framework-light and live under the `G8Scope\Drupal\` namespace; the Drupal
service class lives under `Drupal\g8scope\`.

```bash
composer install
vendor/bin/pest
```

## Licence

MIT. See [LICENSE](LICENSE).
