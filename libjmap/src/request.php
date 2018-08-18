<?php
namespace Wikisuite\JMAPCore;

require 'response.php';
use Zend\Http\Request;
use Wikisuite\JMAPCore\JMAPResponse;

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
          'name' => $this->backReferencedMethodCall->name,
          'path' => $this->path,
          'resultOf'=>$this->backReferencedMethodCall->client_id];
    }
}

class MethodCall
{
    public $name;
    public $client_id;
    private $arguments;

    public function __construct($name, $arguments)
    {
        $this->name = $name;
        $this->arguments = $arguments;
        $this->client_id = uniqid();
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
    public function toRequest()
    {
        return array($this->name,
     array_map(array($this, 'methodCallArgumentToRequest'), $this->arguments),
     $this->client_id);
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
        $methodCall = new MethodCall($name, $arguments);

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

    public function toJson()
    {
        $rawRequest =   array(
        //'using' => array('urn:ietf:params:jmap:core', 'urn:ietf:params:jmap:mail'),
        'using' => array('ietf:jmap', 'ietf:jmapmail'),
      'methodCalls' => array_map(function ($methodCall) {
          return $methodCall->toRequest();
      }, $this->methodCalls)

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
        if ($this->connection->DEBUG) {
            echo("DEBUG: Sending request: \n{$this->toJson()}\n");
        }
        $request->setcontent($this->toJson());
        $response = $this->connection->client->send();
        if ($this->connection->DEBUG) {
            echo("DEBUG: Received response: \n".json_encode(json_decode($response->getBody(), true), JSON_PRETTY_PRINT)."\n");
        }
        return new JMAPResponse($response->getBody());
    }
}
