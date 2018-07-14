<?php

namespace Wikisuite\JMAPMail;

require 'request.php';
use Wikisuite\JMAPCore;
use Wikisuite\JMAPCore\JMAPRequest;
use Wikisuite\JMAPCore\ResultReference;

class Mailbox
{
    private $connection;

    public function __construct($connection)
    {
        $this->connection = $connection;
    }
    public function getInbox()
    {
        $filter =  array('filter'=>array('hasRole' => true));
        $request = new JMAPRequest($this->connection);
        $mailboxeWithroles = $request->addQuery('Mailbox', $filter);
        $previousIds = new ResultReference("/ids", $mailboxeWithroles);
        $request->addMethodCall('Mailbox/get', array('#ids'=>$previousIds));
        $request->send();
    }
}
