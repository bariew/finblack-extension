<?php
/**
 * Client class file.
 * @copyright (c) 2014, Galament
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

namespace bariew\finblack;

/**
 * Class for API requests. See README.
 *
 *
 * @author Pavel Bariev <bariew@yandex.ru>
 */

class Client
{
    /**
     * @var string base url like https://finblack.com
     */
    public $baseUrl;
    /**
     * @var string finblack user login
     */
    public $username;
    /**
     * @var string finblack user api key.
     */
    public $apiKey;

    /**
     * Well this is how we create Client.
     * @param array $options this class attributes.
     * @throws \Exception
     */
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
     * Sends request to some API method.
     * @param string $method API method name.
     * @param array $params search and pagination/sort params.
     * @throws \Exception
     * @return mixed request json decoded response.
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
        //echo $this->baseUrl . "/api/" . $method . "?" . $attributes;exit;
        $result = $response->json();
        if ($response->getStatusCode() != 200) {
            throw new \Exception($response->getStatusCode() . ": " . $result["message"]);
        }

        return $result;
    }

    /**
     * Gets data for all pages.
     * @param $method
     * @param $params
     * @throws \Exception
     * @return array
     */
    public function getAll($method, $params)
    {
        $lastResponse = [];
        $result = [];
        $params['page'] = 1;
        // when last response is the same as previous - it means that there are
        // no more items in database - it's Yii2 rest pagination feature.
        while (($response = $this->request($method, $params)) != $lastResponse) {
            $lastResponse = $response;
            $params['page']++;
            $result = array_merge($result, $lastResponse);
        }
        return $result;
    }
} 