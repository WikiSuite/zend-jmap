<?php

namespace Wikisuite\Jmap\Mail;

use Wikisuite\Jmap\Core;
use Wikisuite\Jmap\Core\Request;
use Wikisuite\Jmap\Core\ResultReference;

const DEFAULT_NUM_MESSAGES_RETRIEVED = 10;
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
        $request = new Request($this->connection);
        $mailboxeWithroles = $request->addQuery('Mailbox', $filter);
        $previousIds = new ResultReference("/ids", $mailboxeWithroles);
        $inboxCall = $request->addMethodCall('Mailbox/get', array('#ids'=>$previousIds));
        $response = $request->send();
        $inbox = ($response->getResponsesForMethodCall($inboxCall))[0]['list'][0];
        //var_dump($inbox);
        return $inbox;
    }

    public function getMailboxes()
    {
        $filter =  array('filter'=>null);
        //array('filter'=>array('hasRole' => true));
        $request = new Request($this->connection);
        $mailboxeWithroles = $request->addQuery('Mailbox', $filter);
        $previousIds = new ResultReference("/ids", $mailboxeWithroles);
        $inboxCall = $request->addMethodCall('Mailbox/get', array('#ids'=>$previousIds));
        $response = $request->send();
        $mailboxes = ($response->getResponsesForMethodCall($inboxCall))[0]['list'];
        //var_dump($mailboxes);
        return $mailboxes;
    }

    public function getInboxId()
    {
        return $this->getInbox()['id'];
    }

    public function getMessageCount($mailboxId)
    {
      $request = new Request($this->connection);
      $getArguments =  array('ids'=>array($mailboxId));
      $mailboxCall = $request->addMethodCall('Mailbox/get', $getArguments);
              $response = $request->send();
              $count = ($response->getResponsesForMethodCall($mailboxCall))[0]['list'][0]['totalEmails'];
              return $count;
    }
    public function getMessages($mailboxId, $propertiesToRetrieve=null, $position=null)
    {
        $filterArguments =  array('filter'=>array('inMailbox' => $mailboxId));
        if (is_int($position)) {
            $filterArguments['position'] = $position;
            $filterArguments['limit'] = DEFAULT_NUM_MESSAGES_RETRIEVED;
        }
        $request = new Request($this->connection);
        $emailsInMailbox = $request->addQuery('Email', $filterArguments);
        $previousIds = new ResultReference("/ids", $emailsInMailbox);
        $getArguments = array('#ids'=>$previousIds);
        if ($propertiesToRetrieve) {
            $getArguments['properties'] = $propertiesToRetrieve;
        }
        $emailCall = $request->addMethodCall('Email/get', $getArguments);
        $response = $request->send();
        $mails = ($response->getResponsesForMethodCall($emailCall))[0]['list'];
        //var_dump("\n\nDEBUG\n\n");
        //var_dump($mails);
        return $mails;
    }
}
