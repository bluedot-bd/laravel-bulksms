<?php

namespace BluedotBd\LaravelBulksms\Jobs;

use BluedotBd\LaravelBulksms\LaravelBulksms;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSMS implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $config, $number, $message;

    /**
     * Create a new job instance.
     *
     * @param string $config  config file name
     * @param string $number  recipient number
     * @param string $message message text
     */
    public function __construct($config, $number, $message)
    {
        $this->config  = $config;
        $this->number  = $number;
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        (new LaravelBulksms($this->config))
            ->to($this->number)
            ->message($this->message)
            ->send();
    }
}
