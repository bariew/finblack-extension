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
 * 1. Getting all users by full name search.
   $client = new \bariew\finblack\Client([
        'baseUrl' => 'http://blacklist.dev',
        'username' => 'pt',
        'apiKey' => 123123
    ]);
    print_r($client->request('index', ['full_name' => 'asdf']));
 *
 * 2.
    $client = new \bariew\finblack\Client([
        'baseUrl' => 'http://blacklist.dev',
        'username' => 'pt',
        'apiKey' => 123123
    ]);
    print_r($client->compare(['full_name' => 'tuan', 'list_type' => 1]));
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
        $result = $response->json();
        if ($response->getStatusCode() != 200) {
            throw new \Exception($response->getStatusCode() . ": " . $result["message"]);
        }

        return $result;
    }

    /**
     * Searches for full_name and additionally compares other search params to received data attributes.
     * @param array $params search params.
     * @param string $primary parameter for primary remote search. As others are compared locally.
     * @return array
     */
    public function compare($params, $primary = 'full_name')
    {
        // we get all items by name and compare other fields below.
        if (!$items = $this->getAll([$primary => $params[$primary]])) {
            return [];
        }
        $result = [$primary => true];
        unset($params[$primary]);
        // now we are looking for other matches.
        foreach ($items as $item) {
            $attributes = array_intersect_key($params, $item);
            foreach ($attributes as $attribute => $value) {
                // if once this attribute matches - it is true for result.
                $result[$attribute] = @$result[$attribute] || $this->match($value, $item[$attribute]);
            }
        }
        return $result;
    }

    /**
     * Looks subject string matches for search string.
     * It tries to find shuffled matches when "Moscow, Lenin, 25, 15"
     * matches "Lenin street, 25/15, Moscow".
     * @param string $search
     * @param string $subject
     * @return bool
     */
    private function match($search, $subject)
    {
//        $search = 'Ижевск, Удмуртская, 63, 2, 3'; // try to test it
//        $subject = 'РФ, г.ИЖЕВСК, улица Удмуртская, д.63-2, кв.3';
        $numberIndex = -1; // Pointer for numbers order.
        $searches = preg_split('/\W+/', strtolower($search)); // we compare only letters and numbers.
        $subjects = preg_split('/\W+/', strtolower($subject));
        foreach ($searches as $key => $string) {
            $index = array_search($string, $subjects, true);
            if ($index === false) {
                return false;
            }
            /**
             * We don't want to compare to it again e.g. when there are
             * two same numbers in search "12-12", and only one in subject "12-13" - remove first one!
             */
            $subjects[$index] = false;
            /**
             * Watching for search numbers to be in the same order as in the subject.
             * Like "Moscow, Arbat, 2-12" IS NOT "Moscow, Arbat, 12-2"
             */
            if (is_numeric($string)) {
                if ($numberIndex > $index) { // oops! we had already found previous number in LATER string.
                    return false;
                }
                $numberIndex = $index;
            }
        }
        return true;
    }

    /**
     * Gets index data for all pages.
     * @param $params
     * @return array
     * @throws \Exception
     */
    protected function getAll($params)
    {
        $lastResponse = [];
        $result = [];
        // when last response is the same as previous - it means that there are
        // no more items in database - it's Yii2 rest pagination feature.
        while (($response = $this->request('index', $params)) != $lastResponse) {
            $lastResponse = $response;
            $params['page'] = isset($params['page']) ? $params['page'] + 1 : 1;
            $result = array_merge($result, $lastResponse);
        }
        return $result;
    }
} 