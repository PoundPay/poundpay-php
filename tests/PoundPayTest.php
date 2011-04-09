<?php

require_once dirname(__FILE__) . '/../PoundPay.php';
require_once 'PHPUnit/Autoload.php';

class PoundPayTest extends PHPUnit_Framework_TestCase {

    public function assertAny($constraint, $other) {
        foreach ($other as $element) {
            if ($constraint->evaluate($element)) {
                return;
            }
        }   
        $this->fail(sprintf('Failed asserting any in %s %s', print_r($other, true), $constraint->toString()));
    }

    protected function makeTestData($id = 'test', $count = 3) {
        $data = array();
        for ($i = 1; $i <= $count; ++$i) {
            $data["{$id}_key$i"] = "{$id}_val$i";
        }
        return $data;
    }
}

class ResourceTest extends PoundPayTest {

    protected $client;
    
    public function resourceProvider() {
        return array(array('developers', 'PoundPay\Developer'),
                     array('payments', 'PoundPay\Payment'));
    }

    protected function setUp() {
        $this->client = $this->getMockBuilder('PoundPay\APIClient')
                       ->disableOriginalConstructor()
                       ->getMock();
        PoundPay\Resource::setClient($this->client);
    }

    protected function makeApiResponse($data, $status_code = 200) {
        $http_response = new Zend_Http_Response($status_code, array(), json_encode($data));
        return new PoundPay\APIResponse($http_response);
    }

    /** @dataProvider resourceProvider */
    public function testFind($resource, $class) {
        $findSid = 'foo';
        $findAttrs = $this->makeTestData();
        $apiResponse = $this->makeApiResponse($findAttrs);

        $this->client->expects($this->once())
                     ->method('get')
                     ->with($this->equalTo("$resource/$findSid"))
                     ->will($this->returnValue($apiResponse));

        $foundObj = $class::find($findSid);
        $createdObj = new $class($findAttrs);
        $this->assertEquals($foundObj, $createdObj);
    }

    /** @dataProvider resourceProvider */
    public function testAll($resource, $class) {
        $allAttrs = array($this->makeTestData('foo'), $this->makeTestData('bar'));
        $apiResponse = $this->makeApiResponse(array($resource => $allAttrs));

        $this->client->expects($this->once())
                     ->method('get')
                     ->with($this->equalTo($resource))
                     ->will($this->returnValue($apiResponse));

        $all = $class::all();
        $expectedAll = array();
        foreach ($allAttrs as $attrs) {
            $expectedAll[] = new $class($attrs);
        }
        $this->assertEquals($expectedAll, $all);
    }

    /** @dataProvider resourceProvider */
    public function testSaveUpdate($resource, $class) {
        $sid = 'DVxxx';
        $initVars = array('sid' => $sid) + $this->makeTestData('init');
        $responseVars = $this->makeTestData('response');
        $resourceObj = new $class($initVars);
        $apiResponse = $this->makeApiResponse($responseVars);

        $this->client->expects($this->once())
                     ->method('put')
                     ->with($this->equalTo("$resource/$sid"), $this->equalTo($initVars))
                     ->will($this->returnValue($apiResponse));

        $resourceObj->save();
        $expectedObj = new $class($initVars + $responseVars);
        $this->assertEquals($resourceObj, $expectedObj);
    }

    /** @dataProvider resourceProvider */
    public function testSaveCreate($resource, $class) {
        $initVars = $this->makeTestData('init');
        $responseVars = $this->makeTestData('response');
        $resourceObj = new $class($initVars);
        $apiResponse = $this->makeApiResponse($responseVars);

        $this->client->expects($this->once())
                     ->method('post')
                     ->with($this->equalTo($resource), $this->equalTo($initVars))
                     ->will($this->returnValue($apiResponse));

        $resourceObj->save();
        $expectedObj = new $class($initVars + $responseVars);
        $this->assertEquals($resourceObj, $expectedObj);
    }

    /** @dataProvider resourceProvider */
    public function testDelete($resource, $class) {
        $resourceObj = new $class();
        $sid = 'DVxxx';

        $this->client->expects($this->once())
                     ->method('delete')
                     ->with($this->equalTo("$resource/$sid"));

        $resourceObj->delete($sid);
    }
}

require_once 'Zend/Http/Client/Adapter/Interface.php';

class APIClientTest extends PoundPayTest {

    protected function createClient() {
        $client = new PoundPay\APIClient('DVtest', 'testAuth', 'https://test.com', 'testVer');
        $adapter = $this->getMock('Zend_Http_Client_Adapter_Interface');
        $client->get_http_client()->setAdapter($adapter);
        return $client;
    }

    protected function setupClient($response = array(), $statusCode = 200) {
        $client = $this->createClient();
        $adapter = $client->get_http_client()->getAdapter();
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
        return (string) new Zend_Http_Response($status_code, array(), json_encode($data));
    }

    /** @dataProvider methodProvider */
    public function testMethodFailure($method, $methodData) {
        $statusCode = 500;
        $client = $this->setupClient(array(), $statusCode);

        try {
            $this->callMethod($client, $method, $methodData);
        } catch (PoundPay\APIException $e) {
            $this->assertEquals($statusCode, $e->getCode());
            $this->assertSame($e->api_response, $client->get_last_response());
            return;
        }

        $this->fail('APIException was not thrown');
    }

    /** @dataProvider methodProvider */
    public function testMethodRequest($method, $methodData) {
        $client = $this->setupClient();

        $adapter = $client->get_http_client()->getAdapter();
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
        $this->assertSame($response, $client->get_last_response());
    }

    public function testDeveloperSidStartsWithDv() {
        $this->setExpectedException('Exception');
        new PoundPay\APIClient('xxx', 'xxx');
    }

}