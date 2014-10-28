<?php
/**
 * Client class file.
 * @copyright (c) 2014, Galament
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace bariew\finblack;

/**
 * Description.
 *
 * Usage:
 *
 * $client = new \bariew\finblack\Client([
        'baseUrl' => 'http://blacklist.dev',
        'username' => 'pt',
        'apiKey' => 123123
    ]);
    print_r($client->request('index', ['full_name' => 'asdf']));
 *
 * @author Pavel Bariev <bariew@yandex.ru>
 */

class Client
{
    public $baseUrl;
    public $username;
    public $apiKey;

    public function __construct($options = [])
    {
        $requiredAttributes = ['baseUrl', 'username', 'apiKey'];
        if ($missingAttributes = array_diff($requiredAttributes, array_keys($options))) {
            throw new \Exception("Some attributes are missing: " . implode(", ", $missingAttributes));
        }
        foreach ($options as $attribute => $value) {
            $this->$attribute = $value;
        }
    }

    /**
     * @param $method
     * @param $params
     * @throws \Exception
     * @return mixed
     */
    public function request($method, $params)
    {
        $client = new \GuzzleHttp\Client([
            'defaults' => ['exceptions' => false]
        ]);
        $client->setDefaultOption('headers',  ["Accept: application/json"]);
        $attributes = http_build_query(array_merge([
            'username' => $this->username,
            'api_key' => $this->apiKey,
        ], $params));
        /**
         * @var \GuzzleHttp\Message\ResponseInterface $response
         */
        $response = $client->get($this->baseUrl . "/api/" . $method . "?" . $attributes);
        $result = $response->json();
        if ($response->getStatusCode() != 200) {
            throw new \Exception($response->getStatusCode() . ": " . $result["message"]);
        }

        return $result;
    }
} 