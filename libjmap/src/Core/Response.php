<?php
namespace Wikisuite\Jmap\Core;

class Response
{
    protected $rawResponse;
    protected $connection;
    public function __construct($connection, $rawJson)
    {
        $this->connection = $connection;
        $this->rawResponse = json_decode($rawJson, true);
        $this->processResponse();
    }
    private function getObjectFromFullMethodName($fullMethodName)
    {
        return explode('/', $fullMethodName)[0];
    }
    private function processResponse()
    {
        foreach ($this->rawResponse['methodResponses'] as $key => $response) {
            $methodNameOrError = $response[0];
            $clientId = $response[2];
            $methodResponse = $response[1];

            if ($methodNameOrError !== 'error') {
                $state = null;
                if (!empty($methodResponse['newState'])) {
                    $state = $methodResponse['newState'];
                }
                if (!empty($methodResponse['state'])) {
                    $state = $methodResponse['state'];
                }
                if ($state) {
                    $object = $this->getObjectFromFullMethodName($methodNameOrError);
                    $this->connection->updateLatestState($state);
                    $this->connection->cache->garbageCollectCache($object, $state);
                }
            }
        }
    }
    public function getResponsesForMethodCall($methodCall)
    {
        $retVal = [];
        //var_dump($this->rawResponse);
        foreach ($this->rawResponse['methodResponses'] as $key => $response) {
            if ($response[2] === $methodCall->client_id) {
                if ($response[0] === $methodCall->getFullMethodName()) {
                    array_push($retVal, $response[1]);
                } elseif ($response[0] === 'error') {
                    switch ($response[1]['type']) {
                    case 'unsupportedFilter':
                    throw new Exception\ResponseUnsupportedFilterException($response[1]['filters']);
                    break;
                    default:
                    throw new Exception\ResponseErrorException($response[1]['type']);
                  }
                } else {
                    throw new \Exception("Unable to parse response: ".$response[1]);
                }
            }
        }
        return $retVal;
    }
}
