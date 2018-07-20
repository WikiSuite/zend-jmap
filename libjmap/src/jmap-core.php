<?php


namespace Wikisuite\JMAPCore;

use Zend\Http\Client;

require 'session.php';

class Connection
{
    private $session;

    protected $account;

    public $client;

    public $DEBUG = false;

    public function __construct($url = '', $user, $password)
    {
        $this->client = new Client($url, array(
                  'maxredirects' => 0,
                  'timeout'      => 30
              ));
        $this->client->setAuth( $user, $password, Client::AUTH_BASIC);
        $response = $this->client->send();
        //print_r($response);
        $body= $response->getBody();
        $this->session = new Session($body);
        $this->account = $this->session->getPrimaryAccount();

    }

}
