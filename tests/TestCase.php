<?php

namespace BluedotBd\LaravelBulksms\Tests;

use BluedotBd\LaravelBulksms\LaravelBulksmsServiceProvider;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * Storage path used by the tests, removed again in tearDown().
     *
     * @var string
     */
    protected $storagePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storagePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bulksms-tests-' . uniqid();

        if (! is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0775, true);
        }

        $this->app->useStoragePath($this->storagePath);
    }

    protected function tearDown(): void
    {
        if ($this->storagePath && is_dir($this->storagePath)) {
            File::deleteDirectory($this->storagePath);
        }

        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [LaravelBulksmsServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'LaravelBulksms' => \BluedotBd\LaravelBulksms\LaravelBulksmsFacade::class,
        ];
    }

    /**
     * Store a dry-run gateway config so nothing is sent over the network.
     *
     * @param  string $name
     * @param  array  $overrides
     * @return array  the saved config
     */
    protected function saveDryConfig($name = 'test', array $overrides = [])
    {
        $url = 'https://api.example.test/sendtext?apikey=secret&callerID=LKEY&toUser=##NUMBER##&messageContent=##SMS##';

        $params = array_merge([
            'api_mode'       => 'dry',
            'send_method'    => 'GET',
            'send_url'       => $url,
            'send_header'    => '',
            'send_success'   => '',
            'send_error'     => '',
            'balance_url'    => '',
            'balance_method' => 'GET',
            'balance_header' => '',
            'balance_key'    => '',
        ], $overrides);

        (new \BluedotBd\LaravelBulksms\LaravelBulksms())->checkAndSave($params, $url, $name);

        return json_decode(file_get_contents(storage_path('magic-sms/' . $name)), true);
    }
}
