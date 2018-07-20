<?php
namespace Wikisuite\JMAPCore;

class JMAPResponse
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
            if ($response[0] === $methodCall->name && $response[2] === $methodCall->client_id) {
              array_push($retVal, $response[1]);
            }
        }
        return $retVal;
    }
}
