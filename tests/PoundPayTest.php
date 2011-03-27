<?php

require_once dirname(__FILE__) . '/../PoundPay.php';
require_once 'PHPUnit/Autoload.php';

class PoundPayTest extends PHPUnit_Framework_TestCase {

    protected function getConfig() {
        return array(
            'developer_sid' => 'DV0383d447360511e0bbac00264a09ff3c',
            'auth_token' => 'c31155b9f944d7aed204bdb2a253fef13b4fdcc6ae15402004',
            'api_url' => 'https://api.zzpoundpay.com',
            'api_version' => 'silver'
        );
    }
}

class ResourceTest extends PoundPayTest {

    protected $config;
    protected $client;
    
    public function resourceProvider() {
        return array(array('developers', 'PoundPay\\Developer'),
                     array('payments', 'PoundPay\\Payment'));
    }

    protected function setUp() {
        $this->config = $this->getConfig();
        $this->client = $this->getMockBuilder('PoundPay\APIClient')
                       ->disableOriginalConstructor()
                       ->getMock();
        PoundPay\Resource::setClient($this->client);
    }

    protected function makeApiResponse($data, $status_code = 200) {
        return new PoundPay\APIResponse($this->config['api_url'], json_encode($data), $status_code);
    }

    /** @dataProvider resourceProvider */
    public function testFind($resource, $class) {
        $findSid = 'foo';
        $findAttrs = array('foo' => 'bar');
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
        $allAttrs = array(array('foo' => 'bar'), array('foo2' => 'bar2'));
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
        $initVars = array('sid' => $sid, 'k1' => 'v1', 'k2' => 'v2');
        $responseVars = array('k3' => 'v3', 'k4' => 'v4');
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
        $initVars = array('k1' => 'v1', 'k2' => 'v2');
        $responseVars = array('k3' => 'v3', 'k4' => 'v4');
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

}