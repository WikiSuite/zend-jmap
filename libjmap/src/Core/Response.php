<?php
namespace Wikisuite\Jmap\Core;

class Response
{
    protected $rawResponse;
    public function __construct($rawJson)
    {
        $this->rawResponse = json_decode($rawJson, true);
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
