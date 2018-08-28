<?php
namespace Wikisuite\Jmap\Core;

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
          'name' => $this->backReferencedMethodCall->getFullMethodName(),
          'path' => $this->path,
          'resultOf'=>$this->backReferencedMethodCall->client_id];
    }
}

class MethodCall
{
    private $methodName;
    public $client_id;
    private $arguments;

    public function __construct($object, $methodName, $arguments)
    {
        $this->object = $object;
        $this->methodName = $methodName;
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
    public function getFullMethodName()
    {
        return $this->object.'/'.$this->methodName;
    }
    public function toRequest()
    {
        return array(
          $this->getFullMethodName(),
          array_map(
            array(
              $this, 'methodCallArgumentToRequest'),
              $this->arguments
            ),
          $this->client_id
      );
    }
}
class Request
{
    protected $methodCalls = [];
    private $connection;
    public function __construct($connection)
    {
        $this->connection = $connection;
    }
    public function addMethodCall($object, $methodName, $arguments)
    {
        $methodCall = new MethodCall($object, $methodName, $arguments);

        array_push($this->methodCalls, $methodCall);
        return $methodCall;
    }


    public function addQuery($object, $filter)
    {
        /*if (!in_array($filterOperator, ['AND','OR','NOT'])) {
            throw new \Exception("Invalid filter operator");
        }*/
        return $this->addMethodCall($object, 'query', $filter);
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
        $request->setMethod(\Zend\Http\Request::METHOD_POST);
        $request->getHeaders()->addHeaders([
    'Content-Type' => 'application/json'
]);
        if ($this->connection->DEBUG) {
            echo("DEBUG: Sending request: \n{$this->toJson()}\n");
        }
        $request->setcontent($this->toJson());
        $response = $this->connection->client->send();
        if (!$response->getHeaders()->get('Content-Type')->match('application/json')) {
            throw new Exception\ResponseErrorException("The response had ".$response->getHeaders()->get('Content-Type')->toString()." instead of application/json.  Body is:\n ".$response->getBody());
        }
        if ($this->connection->DEBUG) {
            //var_dump($response->getBody());
            echo("DEBUG: Received response: \n".json_encode(json_decode($response->getBody(), true), JSON_PRETTY_PRINT)."\n");
        }
        return new Response($response->getBody());
    }
}
