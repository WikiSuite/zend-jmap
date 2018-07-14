<?php

namespace Wikisuite\JMAPMail;

require 'request.php';
use Wikisuite\JMAPCore;
use Wikisuite\JMAPCore\JMAPRequest;

class Mailbox {
  private $connection;

  public function __construct($connection){
    $this->connection = $connection;
  }
  public function getInbox() {
    $filter =  array('hasRole' => 'inbox');
    $request = new JMAPRequest($this->connection);
    $request->addQuery("Mailbox", $filter);
    $request->send();
    

  }
}
