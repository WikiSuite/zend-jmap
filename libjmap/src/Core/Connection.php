<?php

namespace Wikisuite\Jmap\Core;

use Zend\Http\Client;

class Connection
{
    public $session;

    protected $account;

    private $client;

    public $DEBUG = false;

    private $latestState = null;

    private $user;
    private $password;
    public function prepareNextRequest()
    {
        $this->client->resetParameters();
        $this->client->setAuth($this->user, $this->password, Client::AUTH_BASIC);
        return $this->client;
    }
    public function __construct($url = '', $user, $password)
    {
        $this->user = $user;
        $this->password = $password;
        $this->client = new Client($url, array(
                  'maxredirects' => 0,
                  'timeout'      => 30,
                  'keepalive' => true
              ));
        $client = $this->prepareNextRequest();
        $response = $client->send();
        //print_r($response);
        $body = $response->getBody();
        $this->session = new Session($url, $body);
        $this->account = $this->session->getPrimaryAccount();
    }

    public function getLatestState()
    {
        return $this->latestState;
    }

    /**
    * @param string $newState
    */
    public function updateLatestState($newState)
    {
        $this->latestState = $newState;
    }
}
