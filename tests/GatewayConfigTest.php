<?php

namespace BluedotBd\LaravelBulksms\Tests;

use BluedotBd\LaravelBulksms\LaravelBulksms;
use Exception;

class GatewayConfigTest extends TestCase
{
    public function test_it_detects_the_number_and_message_parameters(): void
    {
        $result = (new LaravelBulksms())->checkAndFormatUrl(
            'https://api.example.test/sendtext?apikey=secret&callerID=LKEY&toUser=8801700000000&messageContent=hello'
        );

        $this->assertTrue($result['status']);
        $this->assertSame('toUser', $result['send_key_mobile']);
        $this->assertSame('messageContent', $result['send_key_message']);
        $this->assertStringContainsString('toUser=##NUMBER##', $result['url']);
        $this->assertStringContainsString('messageContent=##SMS##', $result['url']);
        $this->assertStringContainsString('apikey=secret', $result['url']);
    }

    public function test_it_detects_other_common_parameter_names(): void
    {
        $result = (new LaravelBulksms())->checkAndFormatUrl(
            'http://msg.example.test/smsapi?api_key=k&type=text&contacts=8801700000000&senderid=8809612436583&msg=hello'
        );

        $this->assertTrue($result['status']);
        $this->assertSame('contacts', $result['send_key_mobile']);
        $this->assertSame('msg', $result['send_key_message']);
    }

    public function test_a_url_without_a_query_string_is_rejected(): void
    {
        $result = (new LaravelBulksms())->checkAndFormatUrl('https://api.example.test/sendtext');

        $this->assertFalse($result['status']);
        $this->assertSame('', $result['send_key_mobile']);
    }

    public function test_a_url_without_a_message_parameter_is_rejected(): void
    {
        $result = (new LaravelBulksms())->checkAndFormatUrl('https://api.example.test/send?toUser=8801700000000');

        $this->assertFalse($result['status']);
    }

    public function test_check_and_save_writes_the_config_with_the_detected_keys(): void
    {
        $saved = $this->saveDryConfig('saved');

        $this->assertFileExists(storage_path('magic-sms/saved'));
        $this->assertSame('dry', $saved['api_mode']);
        $this->assertSame('GET', $saved['send_method']);
        $this->assertSame('toUser', $saved['send_key_mobile']);
        $this->assertSame('messageContent', $saved['send_key_message']);
        $this->assertStringContainsString('##SMS##', $saved['send_url']);
    }

    public function test_check_and_save_rejects_an_invalid_url(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid Send URL');

        (new LaravelBulksms())->checkAndSave([], 'https://api.example.test/sendtext', 'broken');
    }

    public function test_an_unknown_config_name_throws(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Magic SMS Config File Not Found!');

        new LaravelBulksms('does-not-exist');
    }
}
