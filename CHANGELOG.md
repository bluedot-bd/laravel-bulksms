# Changelog

All notable changes to `laravel-bulksms` will be documented in this file

## 1.0.8 - 2026-10-21

- no runtime changes: only the development dependencies and the Composer policy
  were adjusted so the test matrix can resolve on PHP 7.3 - 8.5 (the older
  entries install the end-of-life framework versions the tests run against)

## 1.0.7 - 2026-10-21

- fixed: the implementation class `BluedotBd\LaravelBulksms\LaravelBulksms` was never shipped, so
  `new LaravelBulksms(...)`, `Jobs\SendSMS` and the `LaravelBulksms` alias all failed with
  "class not found"
- fixed: `LaravelBulksmsFacade` contained the whole implementation, so the documented static usage
  (`LaravelBulksms::to()`) threw "Non-static method cannot be called statically". It is now a real
  facade resolving `LaravelBulksms::class`, and `to()`/`message()`/`line()` return the builder, so
  `LaravelBulksms::to($number)->message($text)->send()` works as documented
- fixed: the service provider bound the missing class; it now registers `LaravelBulksms` as a
  singleton and keeps the `laravel-bulksms` alias for backwards compatibility
- fixed: `line()` and the optional config keys (`send_header`, `send_method`, `send_success`,
  `api_mode`, `balance_*`) no longer raise undefined-property/index warnings on PHP 8
- fixed: `send()` reports an empty recipient or message instead of sending a blank request
- added `ext-curl` requirement and an `illuminate/support` version range (8 – 13)

## 1.0.0 - 2023-02-19

- initial release
