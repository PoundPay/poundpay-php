<?php
namespace PoundPay;
require_once __DIR__ . '/TestCase.php';
require_once 'HTTP/Request2/Adapter/Curl.php';
require_once 'PHPUnit/Autoload.php';

class APIClientTest extends TestCase {
    protected $adapter = null;

    protected function createClient() {
        $client = new APIClient('DVtest', 'testAuth', 'https://test.com', 'testVer');
        $this->adapter = $this->getMock('HTTP_Request2_Adapter_Curl');
        $client->getHttpClient()->setAdapter($this->adapter);
        return $client;
    }

    protected function setupClient($response = array(), $statusCode = 200) {
        $client = $this->createClient();
        $this->adapter->expects($this->once())
                      ->method('sendRequest')
                      ->will($this->returnValue($this->makeHttpResponse($response, $statusCode)));
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

    protected function makeHttpResponse($data, $statusCode = 200) {
        $reasonPhrase = \HTTP_Request2_Response::getDefaultReasonPhrase($statusCode);
        $statusLine = sprintf('HTTP/1.1 %d %s \r\n\r\n', $statusCode, $reasonPhrase);
        $response = new \HTTP_Request2_Response($statusLine);
        $response->appendBody(json_encode($data));
        return $response; 
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

        $adapter = $this->adapter;
        $self = $this;
        $adapter->expects($this->once())
                ->method('sendRequest')
                ->will($this->returnCallback(function (\HTTP_Request2 $request)
                                             use ($self, $method, $methodData) {    
                    $self->assertEquals(strtoupper($method), $request->getMethod());
                    $self->assertRegExp('|https://test.com(:443)?/testVer/testEndpoint|', (string)$request->getUrl());
                    if ($methodData !== null) {
                        $headers = $request->getHeaders();
                        $self->assertTrue(array_key_exists('content-type', $headers));
                        $self->assertEquals($headers['content-type'], 'application/x-www-form-urlencoded');
                        $self->assertEquals($request->getBody(), http_build_query($methodData));
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
        $this->setExpectedException('InvalidArgumentException', 'Invalid developer_sid (must start with DV)');
        new APIClient('xxx', 'xxx');
    }

}
