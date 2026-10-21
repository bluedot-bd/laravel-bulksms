<?php

namespace BluedotBd\LaravelBulksms;

use Exception;
use Illuminate\Support\Facades\Log;

class LaravelBulksms
{
    /**
     * SMS API details loaded from storage/magic-sms/<config>.
     *
     * @var array
     */
    protected $config = [];

    /**
     * The phone number the message should be sent to.
     *
     * @var string|null
     */
    protected $to;

    /**
     * Full message.
     *
     * @var string|null
     */
    protected $message;

    /**
     * Message lines.
     *
     * @var array
     */
    protected $lines = [];

    /**
     * Create a new LaravelBulksms instance.
     *
     * @param string|null $config config file name
     */
    public function __construct($config = null)
    {
        $this->ensureConfigDirectory();

        if ($config) {
            $this->loadConfig($config);
        }
    }

    /**
     * SMS to number.
     *
     * @param string $to
     */
    public function to($to): self
    {
        $this->to = $to;

        return $this;
    }

    /**
     * SMS text.
     *
     * @param string $message
     */
    public function message($message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Add a new line.
     *
     * @param string $line
     */
    public function line($line = ''): self
    {
        $this->lines[] = $line;

        return $this;
    }

    /**
     * Load config from the storage path.
     *
     * @param  string $config
     * @return void
     */
    public function loadConfig($config)
    {
        $file = $this->configPath($config);

        if (! file_exists($file)) {
            throw new Exception('Magic SMS Config File Not Found!', 1);
        }

        $this->config = json_decode(file_get_contents($file), true) ?: [];
    }

    /**
     * Export config as array.
     *
     * @return array
     */
    public function config()
    {
        return $this->config;
    }

    /**
     * Check SMS balance.
     *
     * @return float SMS balance
     */
    public function balance()
    {
        $this->ensureConfigLoaded();

        $url = explode('?', (string) $this->getConfig('balance_url'));
        if (count($url) <= 1) {
            return 0.00;
        }

        $headers = [];
        if ($this->getConfig('balance_header')) {
            $headers = explode(',', $this->getConfig('balance_header'));
        }

        parse_str($url[1], $data);

        if (preg_match('/get/i', (string) $this->getConfig('balance_method', 'GET'))) {
            $response = $this->httpGet($url[0], $data, $headers);
        } else {
            $response = $this->httpPost($url[0], $data, $headers);
        }

        $balance = [];
        if (is_array($response) || is_object($response)) {
            $key = $this->getConfig('balance_key');

            array_walk_recursive($response, function ($value, $name) use (&$balance, $key) {
                if ($name === $key) {
                    $balance[] = $value;
                }
            });
        }

        $balance = isset($balance[0]) ? $balance[0] : 0;

        return (float) $balance;
    }

    /**
     * Send the SMS.
     *
     * @return object|string SMS API response
     * @throws \Exception
     */
    public function send()
    {
        $this->ensureConfigLoaded();

        if (empty($this->to)) {
            throw new Exception('SMS recipient number is empty', 1);
        }

        if (empty($this->message) && empty($this->lines)) {
            throw new Exception('SMS message is empty', 1);
        }

        $mobileNumber = $this->to;
        $message      = (empty($this->lines)) ? $this->message : implode("\n", $this->lines);
        $url          = explode('?', (string) $this->getConfig('send_url'));

        if (count($url) <= 1) {
            throw new Exception('Invalid Send URL', 1);
        }

        parse_str($url[1], $data);

        $headers = [];
        if ($this->getConfig('send_header')) {
            $headers = explode(',', $this->getConfig('send_header'));
        }

        $data[$this->getConfig('send_key_mobile')]  = $mobileNumber;
        $data[$this->getConfig('send_key_message')] = $message;

        $response = [];

        if (preg_match('/get/i', (string) $this->getConfig('send_method', 'GET'))) {
            if ($this->getConfig('api_mode') == 'dry') {
                Log::info(json_encode(['method' => 'GET', 'url' => $url[0], 'params' => $data, 'headers' => $headers]));
            } else {
                $response = $this->httpGet($url[0], $data, $headers);
            }
        } else {
            if ($this->getConfig('api_mode') == 'dry') {
                Log::info(json_encode(['method' => 'POST', 'url' => $url[0], 'params' => $data, 'headers' => $headers]));
            } else {
                $response = $this->httpPost($url[0], $data, $headers);
            }
        }

        if ($this->getConfig('send_success')) {
            if (! preg_match('/' . $this->getConfig('send_success') . '/i', json_encode($response))) {
                Log::error(json_encode($response));

                throw new Exception("SMS Sending Failed!\nAPI Response:" . json_encode($response), 1);
            }
        }

        return $response;
    }

    /**
     * Check the API url and save it as JSON in the storage path.
     *
     * @param  array  $params params
     * @param  string $url    API url (send)
     * @param  string $config config name
     * @return array  formatted data
     * @throws \Exception
     */
    public function checkAndSave($params, $url, $config)
    {
        $url = htmlspecialchars_decode(urldecode((string) $url));

        $params['send_url'] = htmlspecialchars_decode(urldecode((string) ($params['send_url'] ?? $url)));

        if (array_key_exists('balance_url', $params)) {
            $params['balance_url'] = htmlspecialchars_decode(urldecode((string) $params['balance_url']));
        }

        $formattedUrl = $this->checkAndFormatUrl($url);

        if (! $formattedUrl || ! $formattedUrl['status']) {
            throw new Exception('Invalid Send URL', 1);
        }

        $params['send_url']         = $formattedUrl['url'];
        $params['send_key_mobile']  = $formattedUrl['send_key_mobile'];
        $params['send_key_message'] = $formattedUrl['send_key_message'];

        $this->ensureConfigDirectory();

        file_put_contents(
            $this->configPath($config),
            json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        return $formattedUrl;
    }

    /**
     * Check and format the API url (send).
     *
     * @param  string $BulkSMS_url API url (send)
     * @return array
     */
    public function checkAndFormatUrl($BulkSMS_url)
    {
        $match            = 0;
        $url              = explode('?', (string) $BulkSMS_url);
        $send_key_mobile  = '';
        $send_key_message = '';
        $return           = ['status' => false, 'url' => '', 'send_key_mobile' => '', 'send_key_message' => ''];

        if (count($url) == 2) {
            $params = [];

            parse_str($url[1], $searchParams);

            foreach ($searchParams as $key => $value) {
                if (preg_match('/message_type/', $key)) {
                    $params[$key] = $value;
                } elseif (preg_match('/contacts|number|mobile|^to$|toUser|toCustomer|(^user$)|msisdn|receiver|recipient/i', $key)) {
                    $match += 1;
                    $params[$key]    = '##NUMBER##';
                    $send_key_mobile = $key;
                } elseif (preg_match('/msg|message|text|Body|(^sms$)/i', $key)) {
                    $match += 1;
                    $params[$key]     = '##SMS##';
                    $send_key_message = $key;
                } elseif (! preg_match('/schedule|delay/i', $key)) {
                    $params[$key] = $value;
                }
            }

            if ($match == 2) {
                $BulkSMS_url = $url[0] . '?' . http_build_query($params);
                $BulkSMS_url = urldecode($BulkSMS_url);
                $return      = ['status' => true, 'url' => $BulkSMS_url, 'send_key_mobile' => $send_key_mobile, 'send_key_message' => $send_key_message];
            }
        }

        return $return;
    }

    /**
     * Full path of a gateway config file.
     *
     * @param  string $config
     * @return string
     */
    protected function configPath($config)
    {
        return storage_path('magic-sms' . DIRECTORY_SEPARATOR . $config);
    }

    /**
     * @return void
     */
    protected function ensureConfigDirectory()
    {
        $directory = storage_path('magic-sms');

        if (! is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }
    }

    /**
     * Fail with a pointer instead of "Invalid Send URL" when the instance was
     * created without a config name (e.g. the facade without
     * laravel-bulksms.config set).
     *
     * @return void
     * @throws \Exception
     */
    protected function ensureConfigLoaded()
    {
        if (empty($this->config)) {
            throw new Exception(
                'SMS config is not loaded. Pass the config name to the constructor or set laravel-bulksms.config.',
                1
            );
        }
    }

    /**
     * Read a gateway setting without raising undefined-index errors.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    protected function getConfig($key, $default = '')
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * HTTP GET request.
     *
     * @param  string $url     api url
     * @param  array  $data    params
     * @param  array  $headers headers
     * @return object|string GET response
     */
    private function httpGet($url, $data, $headers = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, (int) config('laravel-bulksms.timeout', 15));
        if (! empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false && $error) {
            Log::error('BulkSMS GET failed: ' . $error);
        }

        $json = json_decode((string) $response);

        return ($json) ? $json : $response;
    }

    /**
     * HTTP POST request.
     *
     * @param  string $url     api url
     * @param  array  $data    params
     * @param  array  $headers headers
     * @return object|string POST response
     */
    private function httpPost($url, $data, $headers = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_TIMEOUT, (int) config('laravel-bulksms.timeout', 15));
        if (! empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false && $error) {
            Log::error('BulkSMS POST failed: ' . $error);
        }

        $json = json_decode((string) $response);

        return ($json) ? $json : $response;
    }
}
