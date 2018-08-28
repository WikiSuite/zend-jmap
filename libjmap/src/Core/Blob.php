<?php

namespace Wikisuite\Jmap\Core;

class Blob
{
    private $connection;

    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    public function get($blobId, $type='application/octet-stream', $name='downloadBlobFile', $accountId=null)
    {
        $urlTemplate = $this->connection->session->getDownloadUrlTemplate();
        if (!$accountId) {
            $accountId = $this->connection->session->getPrimaryAccount();
        }
        $url = $urlTemplate->expand([
          'accountId' => $accountId,
          'blobId' => $blobId,
          'type' => $type,
          'name' => $blobId
        ]);

        $uri = $this->connection->session->getBaseUrl().$url;
        $client = $this->connection->prepareNextRequest();
        $client->setUri($uri);
        $request = $client->getRequest();

        $request->setMethod(\Zend\Http\Request::METHOD_GET);
        $request->getHeaders()->clearHeaders();
        $request->getHeaders()->addHeaders([
            'Content-Type' => $type
          ]);
        if ($this->connection->DEBUG) {
            echo("DEBUG: Blob:get() sending request: \n{$uri}\n");
        }
        $response = $client->send();
        if ($this->connection->DEBUG) {
            //var_dump($response->getBody());
            echo("DEBUG: Blob:get() received response: \n".$response->getBody()."\n");
        }
        return $response->getBody();
    }
}
