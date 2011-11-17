<?php
namespace PoundPay;
require_once __DIR__ . '/TestCase.php';
require_once 'HTTP/Request2/Response.php';

class ResourceTest extends TestCase {

    protected $client;

    public function resourceProvider() {
        return array(array('developers', 'PoundPay\Developer'),
                     array('payments', 'PoundPay\Payment'));
    }

    protected function setUp() {
        $this->client = $this->getMockBuilder('PoundPay\APIClient')
                       ->disableOriginalConstructor()
                       ->getMock();
        Resource::setClient($this->client);
    }

    protected function makeApiResponse($data, $statusCode = 200) {
        $reasonPhrase = \HTTP_Request2_Response::getDefaultReasonPhrase($statusCode);
        $statusLine = sprintf('HTTP/1.1 %d %s \r\n\r\n', $statusCode, $reasonPhrase);
        $response = new \HTTP_Request2_Response($statusLine);
        $response->appendBody(json_encode($data));
        return new APIResponse($response);
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
