<?php

namespace BluedotBd\LaravelBulksms\Tests;

use BluedotBd\LaravelBulksms\Jobs\SendSMS;
use BluedotBd\LaravelBulksms\LaravelBulksms;
use BluedotBd\LaravelBulksms\SmsChannel;
use Exception;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SendSmsTest extends TestCase
{
    public function test_it_sends_in_dry_mode_and_logs_the_request(): void
    {
        $this->saveDryConfig('dry');
        Log::spy();

        $response = (new LaravelBulksms('dry'))->to('8801700000000')->message('Hello')->send();

        $this->assertSame([], $response);
        Log::shouldHaveReceived('info')->once();
    }

    public function test_lines_are_joined_and_win_over_the_message(): void
    {
        $this->saveDryConfig('lines');
        Log::spy();

        (new LaravelBulksms('lines'))->to('8801700000000')->message('ignored')->line('one')->line('two')->send();

        Log::shouldHaveReceived('info')->withArgs(function ($payload) {
            return strpos($payload, 'one\n' . 'two') !== false;
        })->once();
    }

    public function test_it_refuses_to_send_without_a_recipient(): void
    {
        $this->saveDryConfig('dry');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('SMS recipient number is empty');

        (new LaravelBulksms('dry'))->message('Hello')->send();
    }

    public function test_it_refuses_to_send_without_a_message(): void
    {
        $this->saveDryConfig('dry');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('SMS message is empty');

        (new LaravelBulksms('dry'))->to('8801700000000')->send();
    }

    public function test_it_reports_a_missing_config_instead_of_an_invalid_url(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('SMS config is not loaded');

        (new LaravelBulksms())->to('8801700000000')->message('Hello')->send();
    }

    public function test_the_queued_job_sends_the_message(): void
    {
        $this->saveDryConfig('queued');
        Log::spy();

        dispatch(new SendSMS('queued', '8801700000000', 'From the job'));

        Log::shouldHaveReceived('info')->once();
    }

    public function test_the_notification_channel_sends_the_message(): void
    {
        $this->saveDryConfig('channel');
        Log::spy();

        (new SmsChannel())->send(
            (object) ['mobile' => '8801700000000'],
            new class('channel') extends Notification {
                private $config;

                public function __construct($config)
                {
                    $this->config = $config;
                }

                public function via($notifiable)
                {
                    return [SmsChannel::class];
                }

                public function toSms($notifiable)
                {
                    return (new LaravelBulksms($this->config))
                        ->to($notifiable->mobile)
                        ->line('From the channel');
                }
            }
        );

        Log::shouldHaveReceived('info')->once();
    }

    public function test_balance_returns_zero_without_a_balance_url(): void
    {
        $this->saveDryConfig('dry');

        $this->assertSame(0.0, (new LaravelBulksms('dry'))->balance());
    }
}
