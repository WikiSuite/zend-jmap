<?php
namespace Wikisuite\JMAPCore;

use Zend\Http\Request;

class ResultReference
{
    private $name;
    private $path;
    private $resultOf;
    public function __construct($path, $backReferencedMethodCall)
    {
        $this->path = $path;
        $this->backReferencedMethodCall = $backReferencedMethodCall;
    }
    public function toRequest()
    {
        return (object) [
          'name' => $this->backReferencedMethodCall['name'],
          'path' => $this->path,
          'resultOf'=>$this->backReferencedMethodCall['client_id']];
    }
}

class JMAPRequest
{
    protected $methodCalls = [];
    private $connection;
    public function __construct($connection)
    {
        $this->connection = $connection;
    }
    public function addMethodCall($name, $arguments)
    {
        $methodCall = [];
        $methodCall['name'] = $name;
        $methodCall['arguments'] = $arguments;
        $methodCall['client_id'] = uniqid();
        array_push($this->methodCalls, $methodCall);
        return $methodCall;
    }


    public function addQuery($object, $filter)
    {
        /*if (!in_array($filterOperator, ['AND','OR','NOT'])) {
            throw new \Exception("Invalid filter operator");
        }*/
        return $this->addMethodCall($object.'/query', $filter);
    }

    private function methodCallArgumentToRequest($argument)
    {
        if ($argument instanceof ResultReference) {
            $retVal = $argument->toRequest();
        } else {
            $retVal = $argument;
        }

        return $retVal;
    }


    private function methodCallToRequest($methodCall)
    {
        return array($methodCall['name'],
       array_map(array($this, 'methodCallArgumentToRequest'), $methodCall['arguments']),
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
