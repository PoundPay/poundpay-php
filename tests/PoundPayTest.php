<?php

require_once dirname(__FILE__) . '/../PoundPay.php';
require_once 'PHPUnit/Autoload.php';

class PoundPayTest extends PHPUnit_Framework_TestCase {

    public function get_production_config() {
        return array(
            'developer_sid' => 'DV0383d447360511e0bbac00264a09ff3c',
            'auth_token' => 'c31155b9f944d7aed204bdb2a253fef13b4fdcc6ae15402004',
            'api_url' => 'https://api.zzpoundpay.com',
            'api_version' => 'silver'
        );
    }

    public function get_sandbox_config() {
        return array(
            'developer_sid' => 'DV0383d447360511e0bbac00264a09ff3c',
            'auth_token' => 'c31155b9f944d7aed204bdb2a253fef13b4fdcc6ae15402004',
            'api_url' => 'https://api-sandbox.zzpoundpay.com',
            'api_version' => 'gold'
        );
    }

    public function resourceProvider() {
        return array(array('developers', 'PoundPay\\Developer'),
                     array('payments', 'PoundPay\\Payment'));
    }

    /** @dataProvider resourceProvider */
    public function testFind($resource, $class) {
        $config = $this->get_production_config();
        $client = $this->getMockBuilder('PoundPay\APIClient')
                       ->disableOriginalConstructor()
                       ->getMock();
        PoundPay\Resource::setClient($client);

        $findSid = 'foo';
        $findAttrs = array('foo' => 'bar');
        $clientRetVal = new PoundPay\APIResponse($config['api_url'], json_encode($findAttrs), 200);

        $client->expects($this->once())
               ->method('get')
               ->with($this->equalTo("$resource/$findSid"))
               ->will($this->returnValue($clientRetVal));

        $foundObj = $class::find($findSid);
        $createdObj = new $class($findAttrs);
        $this->assertEquals($foundObj, $createdObj);
    }

    /** @dataProvider resourceProvider */
    public function testAll($resource, $class) {
        $config = $this->get_production_config();
        $client = $this->getMockBuilder('PoundPay\APIClient')
                       ->disableOriginalConstructor()
                       ->getMock();
        PoundPay\Resource::setClient($client);

        $allAttrs = array(array('foo' => 'bar'), array('foo2' => 'bar2'));
        $jsonResponse = json_encode(array($resource => $allAttrs));
        $clientRetVal = new PoundPay\APIResponse($config['api_url'], $jsonResponse, 200);

        $client->expects($this->once())
               ->method('get')
               ->with($this->equalTo($resource))
               ->will($this->returnValue($clientRetVal));

        $all = $class::all();
        $refAll = array();
        foreach ($allAttrs as $attrs) {
            $refAll[] = new $class($attrs);
        }
        $this->assertEquals($refAll, $all);
    }

}