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
        $inboxCall = $request->addMethodCall('Mailbox/get', array('#ids'=>$previousIds));
        $response = $request->send();
        $inbox = ($response->getResponsesForMethodCall($inboxCall))[0]->list[0];
        //var_dump($inbox);
        return $inbox;
    }

    public function getInboxId()
    {
        return $this->getInbox()->id;
    }

    public function getMessages($mailboxId)
    {
        $filter =  array('filter'=>array('inMailbox' => $mailboxId));
        $request = new JMAPRequest($this->connection);
        $emailsInMailbox = $request->addQuery('Email', $filter);
        $previousIds = new ResultReference("/ids", $emailsInMailbox);
        $emailCall = $request->addMethodCall('Email/get', array('#ids'=>$previousIds));
        $response = $request->send();
        $mails = ($response->getResponsesForMethodCall($emailCall))[0]->list;
        var_dump("\n\nDEBUG\n\n");
        var_dump($mails);
        //return $inbox;
    }
}
