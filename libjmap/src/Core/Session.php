<?php
namespace Wikisuite\Jmap\Core;

use QL\UriTemplate\UriTemplate;
use League\Uri;

class Session
{
    private $sessionJson;
    private $baseUrl;
    public function __construct($initialUrl, $sessionString)
    {
        $urlParts = parse_url($initialUrl);
        $this->baseUrl = $urlParts['scheme']."://".$urlParts['host'];
        $this->sessionJson = json_decode($sessionString, true);
        //print_r($this->sessionJson);
    }

    private function _isPrimaryAccount($account)
    {
        if ($account['isPrimary']) {
            return true;
        }
    }


    public function getPrimaryAccount()
    {
        $primaryArray = array_filter($this->sessionJson['accounts'], array($this, '_isPrimaryAccount'));
        if (count($primaryArray) != 1) {
            throw new \Exception("Unable to find primary account");
        }
        //var_dump($primaryArray[array_keys($primaryArray)[0]]['name']);
        return $primaryArray[array_keys($primaryArray)[0]]['name'];
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    public function getApiUrl()
    {
        return $this->baseUrl.$this->sessionJson['apiUrl'];
    }
    /**
    * The following parameters need to be filled in the UriTemplate:
    * accountId
    * blobId
    * type
    * name
    * @return UriTemplate
    */
    public function getDownloadUrlTemplate()
    {
        return new UriTemplate($this->sessionJson['downloadUrl']);
    }
    /**
    * The following parameters need to be filled in the UriTemplate:
    * accountId
    * @return UriTemplate
    */
    public function getUploadUrlTemplate()
    {
        return new UriTemplate($this->sessionJson['downloadUrl']);
    }
}
