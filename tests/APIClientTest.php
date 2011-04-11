<?php
namespace PoundPay;
require_once __DIR__ . '/TestCase.php';
require_once 'Zend/Http/Client/Adapter/Interface.php';

class APIClientTest extends TestCase {

    protected function createClient() {
        $client = new APIClient('DVtest', 'testAuth', 'https://test.com', 'testVer');
        $adapter = $this->getMock('Zend_Http_Client_Adapter_Interface');
        $client->getHttpClient()->setAdapter($adapter);
        return $client;
    }

    protected function setupClient($response = array(), $statusCode = 200) {
        $client = $this->createClient();
        $adapter = $client->getHttpClient()->getAdapter();
        $adapter->expects($this->once())
                ->method('read')
                ->will($this->returnValue($this->makeHttpResponseText($response, $statusCode)));
        return $client;
    }

    public function methodProvider() {
        $methodData = $this->makeTestData();
        return array(array('get', null),
                     array('delete', null),
                     array('post', $methodData),
                     array('put', $methodData));
    }

    protected function callMethod($client, $method, $methodData) {
        if ($methodData === null) {
            return $client->$method('testEndpoint');
        } else {
            return $client->$method('testEndpoint', $methodData);
        }
    }

    protected function makeHttpResponseText($data, $status_code = 200) {
        return (string) new \Zend_Http_Response($status_code, array(), json_encode($data));
    }

    /** @dataProvider methodProvider */
    public function testMethodFailure($method, $methodData) {
        $statusCode = 500;
        $client = $this->setupClient(array(), $statusCode);

        try {
            $this->callMethod($client, $method, $methodData);
        } catch (APIException $e) {
            $this->assertEquals($statusCode, $e->getCode());
            $this->assertSame($e->api_response, $client->getLastResponse());
            return;
        }

        $this->fail('APIException was not thrown');
    }

    /** @dataProvider methodProvider */
    public function testMethodRequest($method, $methodData) {
        $client = $this->setupClient();

        $adapter = $client->getHttpClient()->getAdapter();
        $self = $this;
        $adapter->expects($this->once())
                ->method('write')
                ->will($this->returnCallback(function ($httpMethod, $url, $http_ver, $headers, $body)
                                             use ($self, $method, $methodData) {
                    $self->assertEquals(strtoupper($method), $httpMethod);
                    $self->assertRegExp('|https://test.com(:443)?/testVer/testEndpoint|', (string)$url);
                    $self->assertContains('Authorization: Basic ' . base64_encode('DVtest:testAuth'), $headers);
                    if ($methodData !== null) {
                        $self->assertAny($self->stringStartsWith('Content-Type: application/x-www-form-urlencoded'),
                                         $headers);
                        $self->assertEquals($body, http_build_query($methodData));
                    }
                }));

        $this->callMethod($client, $method, $methodData);
    }

    /** @dataProvider methodProvider */
    public function testMethodResult($method, $methodData) {
        $responseData = $this->makeTestData();
        $client = $this->setupClient($responseData);

        $response = $this->callMethod($client, $method, $methodData);

        $this->assertEquals($responseData, $response->json);
        $this->assertSame($response, $client->getLastResponse());
    }

    public function testDeveloperSidStartsWithDv() {
        $this->setExpectedException('Exception');
        new APIClient('xxx', 'xxx');
    }

}
