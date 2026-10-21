# Laravel Bulk SMS

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bluedot-bd/laravel-bulk-sms.svg?style=flat-square)](https://packagist.org/packages/bluedot-bd/laravel-bulk-sms)
[![Total Downloads](https://img.shields.io/packagist/dt/bluedot-bd/laravel-bulk-sms.svg?style=flat-square)](https://packagist.org/packages/bluedot-bd/laravel-bulk-sms)

Send SMS through **any HTTP-based gateway** from a Laravel app: describe your
provider once, then send through a builder, a facade, a queued job or a
notification channel. Switching gateways (BulksmsBD, smsd.one, Infobip, your own
endpoint…) is configuration, not code.

- **Requirements:** PHP 7.3 – 8.5, Laravel 8 – 13, `ext-curl`
- **No hard-coded provider:** a gateway is a URL, a method and an optional
  success check
- **Optional balance lookup** when the provider exposes one

## Installation

```bash
composer require bluedot-bd/laravel-bulk-sms
```

The service provider and the `LaravelBulksms` alias are registered automatically.

Publish the config if you want to change the defaults:

```bash
php artisan vendor:publish --tag=laravel-bulksms-config
```

## How it works

Every gateway is a JSON file in `storage/magic-sms/<name>`:

```
storage/magic-sms/lkey
```

The package writes that file for you. Hand it your provider's send URL — with the
recipient and the message as real parameters — and it detects which parameters
carry the number and the text:

```php
use BluedotBd\LaravelBulksms\LaravelBulksms;

$url = 'https://api.smsd.one/sendtext?apikey=YOUR_KEY&callerID=LKEY'
     . '&toUser=8801700000000&messageContent=hello';

$saved = (new LaravelBulksms())->checkAndSave([
    'api_mode'       => 'dry',     // dry = log the request, live = call the gateway
    'send_method'    => 'GET',     // GET or POST
    'send_url'       => $url,
    'send_header'    => '',        // comma separated request headers
    'send_success'   => '',        // regex for "sent", e.g. 'status.*success'
    'send_error'     => '',        // regex for "failed" (informational)
    'balance_url'    => '',        // optional
    'balance_method' => 'GET',
    'balance_header' => '',
    'balance_key'    => '',        // JSON key holding the balance
], $url, 'lkey');
```

`checkAndSave()` returns the parsed URL:

```php
[
    'status'           => true,
    'url'              => 'https://…&toUser=##NUMBER##&messageContent=##SMS##',
    'send_key_mobile'  => 'toUser',
    'send_key_message' => 'messageContent',
]
```

The number and message parameters are recognised by name, so `contacts`/`msg`,
`to`/`text`, `number`/`message`, `mobile`/`body`, `toUser`/`messageContent` and
similar pairs all work. A URL without both throws `Invalid Send URL`.

> `api_mode` is your safety net: keep it on `dry` while wiring things up — the
> package then only logs the outgoing request and never calls the gateway.

## Sending

### Builder

```php
use BluedotBd\LaravelBulksms\LaravelBulksms;

try {
    (new LaravelBulksms('lkey'))
        ->to('8801700000000')
        ->message('Your order #123 is on the way')   // or ->line('…')->line('…')
        ->send();
} catch (Exception $e) {
    // gateway unreachable, rejected by send_success, empty recipient/message…
}
```

### Facade

The facade resolves the configured gateway, so no config name is needed — set
`BULK_SMS_CONFIG` in `.env` (or `config` in the published config file):

```php
use LaravelBulksms;

LaravelBulksms::to('8801700000000')->message('Hello from the facade')->send();
```

### Queued job

```php
use BluedotBd\LaravelBulksms\Jobs\SendSMS;

dispatch(new SendSMS('lkey', $number, $message));
dispatch((new SendSMS('lkey', $number, $message))->onQueue('sms'));
```

### Notification channel

```php
use BluedotBd\LaravelBulksms\LaravelBulksms;
use BluedotBd\LaravelBulksms\SmsChannel;
use Illuminate\Notifications\Notification;

class OrderShipped extends Notification
{
    public function via($notifiable)
    {
        return [SmsChannel::class];
    }

    public function toSms($notifiable)
    {
        return (new LaravelBulksms('lkey'))
            ->to($notifiable->mobile)
            ->line('Your order has shipped');
    }
}
```

## Balance

```php
$balance = (new LaravelBulksms('lkey'))->balance(); // float, 0.0 when unavailable
```

Requires `balance_url` (account credentials in its query string), `balance_method`
and the `balance_key` of the JSON field holding the amount.

## Configuration

`config/laravel-bulksms.php`:

| Key / env | Default | Description |
| --- | --- | --- |
| `config` / `BULK_SMS_CONFIG` | `null` | Gateway name the facade (and `app('laravel-bulksms')`) sends with |
| `timeout` / `BULK_SMS_TIMEOUT` | `15` | Seconds to wait for the gateway |

## Errors

Every failure is an `Exception`, so it can be caught at the call site:

| Message | When |
| --- | --- |
| `Magic SMS Config File Not Found!` | The gateway name was never saved with `checkAndSave()` |
| `Invalid Send URL` | The URL has no query string, or no number/message parameter |
| `SMS config is not loaded…` | Sending through an instance created without a config name |
| `SMS recipient number is empty` / `SMS message is empty` | Nothing to send |
| `SMS Sending Failed! …` | The response did not match `send_success` |

## Testing

```bash
composer test
```

The suite uses `api_mode = dry`, so it never calls a real gateway. It is written
for PHP 7.3 – 8.5 and Laravel 8 – 13 (testbench resolves the matching version).

## Changelog

See [CHANGELOG](CHANGELOG.md) for what changed recently.

## Contributing

See [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover a security issue, email <saiful@bluedot.ltd> instead of using the
issue tracker.

## Credits

- [Bluedot Technology Ltd.](https://github.com/bluedot-bd)
- [All Contributors](../../contributors)

## License

MIT — see [LICENSE.md](LICENSE.md).
