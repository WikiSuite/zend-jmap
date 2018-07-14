<?php
namespace Wikisuite\JMAPCore;

use Zend\Http\Request;

class JMAPRequest
{
    protected $methodCalls = [];
    private $connection;
    public function __construct($connection)
    {
        $this->connection = $connection;
    }
    private function addMethodCall($name, $arguments)
    {
        $methodCall = [];
        $methodCall['name'] = $name;
        $methodCall['arguments'] = $arguments;
        $methodCall['client_id'] = uniqid();
        array_push($this->methodCalls, $methodCall);
    }

    public function addQuery($object, $filter)
    {
        /*if (!in_array($filterOperator, ['AND','OR','NOT'])) {
            throw new \Exception("Invalid filter operator");
        }*/
        $this->addMethodCall($object.'/query', $filter);
    }

    private function methodCallToRequest($methodCall)
    {
        return array($methodCall['name'],
       $methodCall['arguments'],
       $methodCall['client_id']);
    }
    public function toJson()
    {
        $rawRequest =   array(
        //'using' => array('urn:ietf:params:jmap:core', 'urn:ietf:params:jmap:mail'),
        'using' => array('ietf:jmap', 'ietf:jmapmail'),
      'methodCalls' => array_map(array($this, 'methodCallToRequest'), $this->methodCalls)

    );

        $json = json_encode($rawRequest, JSON_PRETTY_PRINT);
        return $json;
    }

    public function send()
    {
        $request = $this->connection->client->getRequest();
        $request->setMethod(Request::METHOD_POST);
        $request->getHeaders()->addHeaders([
    'Content-Type' => 'application/json'
]);
        var_dump($this->toJson());
        $request->setcontent($this->toJson());
        $response = $this->connection->client->send();
        var_dump($response->getBody());
        return json_decode($response->getBody());
    }
}
