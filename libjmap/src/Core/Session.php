<?php
namespace Wikisuite\Jmap\Core;

class Session
{
    private $sessionJson;
    public function __construct($sessionString)
    {
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
}
