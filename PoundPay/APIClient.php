<?php
/**
 * PoundPay Client Library
 *
 * @category   APIClients
 * @package    PoundPay
 * @author     PoundPay Inc.
 * @version    v2.1.0
 * @link       http://dev.poundpay.com/
 */

namespace PoundPay;
require_once __DIR__ . '/Autoload.php';
require_once 'HTTP/Request2.php';
require_once 'HTTP/Request2/Adapter/Curl.php';

/*
 * APIClient: the core API client, talks to the PoundPay REST API.
 *
 * @return APIResponse for all responses if PoundPay's API was reachable.
 * @throws APIException for error responses from the API
 * @throws Exception for all other errors
 */
class APIClient {

    protected $base_uri;

    /** @var APIResponse the response from the last api call */
    protected $last_response;
    /** @var \HTTP_Request2 */
    protected $http_client;

    /**
     * @param string $developer_sid Your Developer SID
     * @param string $auth_token Your account's auth_token
     * @param string $api_uri The PoundPay REST Service URI, defaults to https://api.poundpay.com
     * @param string $version The PoundPay API version
     * @param \HTTP_Request2|null $http_client If null, a default client will be used
     */
    public function __construct($developer_sid,
                                $auth_token,
                                $api_uri = "https://api.poundpay.com",
                                $version = 'silver',
                                $http_client = null) {

        if (strpos($developer_sid, 'DV') !== 0) {
            throw new \InvalidArgumentException('Invalid developer_sid (must start with DV)');
        }

        $this->http_client = $http_client ? $http_client : new \HTTP_Request2();
        $this->http_client->setAuth($developer_sid, $auth_token);
        $this->http_client->setConfig('use_brackets', false);
        $adapter = new \HTTP_Request2_Adapter_Curl();
        $this->http_client->setAdapter($adapter);
        $this->base_uri = "$api_uri/$version/";
    }

    public function get($endpoint, $params=null) {
        if ($params != null) {
            $endpoint = $endpoint . '?' . http_build_query($params);
        }
        return $this->request($endpoint, 'GET');
    }

    public function post($endpoint, $vars) {
        $this->http_client->addPostParameter($vars);
        return $this->request($endpoint, 'POST');
    }

    public function put($endpoint, $vars) {
        # XXX: nginx doesn't accept chunked requests
        $this->http_client->setHeader('Transfer-Encoding', '');

        # XXX: HTTP_Request.getBody() does not use addPostParameter for PUTs 
        $body = http_build_query($vars, '', '&');
        if (!$this->http_client->getConfig('use_brackets')) {
            $body = preg_replace('/%5B\d+%5D=/', '=', $body);
        }
        $body = str_replace('%7E', '~', $body);
        $this->http_client->setBody($body);

        return $this->request($endpoint, 'PUT');
    }

    public function delete($endpoint) {
        return $this->request($endpoint, 'DELETE');
    }

    public function getLastResponse() {
        return $this->last_response;
    }

    public function getHttpClient() {
        return $this->http_client;
    }

    /**
     * Sends an HTTP Request to the PoundPay API. GET/POST/PUT parameters must already
     * be set.
     *
     * @param string $endpoint The URL (relative to the base URL, after the /{version})
     * @param string $method The HTTP method to use, defaults to GET
     */
    protected function request($endpoint, $method) {
        $this->http_client->setUrl($this->base_uri . $endpoint);
        $this->http_client->setMethod($method);

        $httpResponse = $this->http_client->send();

        $response = $this->last_response = new APIResponse($httpResponse);
        if ($response->is_error) {
            throw new APIException($response, $this->http_client->getUrl(true));
        }

        return $response;
    }
}
