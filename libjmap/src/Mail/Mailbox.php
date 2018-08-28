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
        $filter = array('filter'=>array('hasRole' => true));
        $request = new Request($this->connection);
        $mailboxeWithroles = $request->addQuery('Mailbox', $filter);
        $previousIds = new ResultReference("/ids", $mailboxeWithroles);
        $inboxCall = $request->addMethodCall('Mailbox', 'get', array('#ids'=>$previousIds));
        $response = $request->send();
        $inbox = ($response->getResponsesForMethodCall($inboxCall))[0]['list'][0];
        //var_dump($inbox);
        return $inbox;
    }

    /**
     * @return array full mailbox json
     */
    public function getMailboxByName($name, $parentMailboxId=null)
    {
        $filter = array(
          'filter'=>null/*array(
            //'name' => $name, //Cyrus-impad does not yet support the name filter
            //'parentId' => $parentMailboxId  //Cyrus-impad currently crashes if parentId is null
          )*/
        );
        $request = new Request($this->connection);
        $mailboxesByNameCall = $request->addQuery('Mailbox', $filter);
        $previousIds = new ResultReference("/ids", $mailboxesByNameCall);
        $mailboxesByIdCall = $request->addMethodCall('Mailbox', 'get', array('#ids'=>$previousIds));
        $response = $request->send();
        $response->getResponsesForMethodCall($mailboxesByIdCall);
        $mailboxes = ($response->getResponsesForMethodCall($mailboxesByIdCall))[0]['list'];
        $mailboxes = array_filter($mailboxes, function ($mailbox) use ($name, $parentMailboxId) {
            //var_dump($mailbox['parentId'],$parentMailboxId,$mailbox['name'],$name->__toString());
            return $mailbox['parentId']===$parentMailboxId && $mailbox['name']===(string)$name;
        });
        return $mailboxes;
    }

    public function getMailboxes()
    {
        $filter =  array('filter'=>null);
        //array('filter'=>array('hasRole' => true));
        $request = new Request($this->connection);
        $mailboxeWithroles = $request->addQuery('Mailbox', $filter);
        $previousIds = new ResultReference("/ids", $mailboxeWithroles);
        $inboxCall = $request->addMethodCall('Mailbox', 'get', array('#ids'=>$previousIds));
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
        $mailboxCall = $request->addMethodCall('Mailbox', 'get', $getArguments);
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
        $emailCall = $request->addMethodCall('Email', 'get', $getArguments);
        $response = $request->send();
        $mails = ($response->getResponsesForMethodCall($emailCall))[0]['list'];
        //var_dump("\n\nDEBUG\n\n");
        //var_dump($mails);
        return $mails;
    }
    /**
     * @param string $mailboxName name of the new mailBox
     * @param string $parentMailboxId id new mailBox's parent, else root is parent
     * @return string id of the created mailbox
     */
    public function create($mailboxName, $parentMailboxId=null)
    {
        $request = new \Wikisuite\Jmap\Core\Request($this->connection);
        $createId = $mailboxName;
        $arguments =  array(
        'create'=>array(
          $createId => array(
            'name' => $mailboxName,
            'parentId' => $parentMailboxId
          )
        )
      );
        $mailboxCall = $request->addMethodCall('Mailbox', 'set', $arguments);
        $response = $request->send();
        $rawResponse = $response->getResponsesForMethodCall($mailboxCall)[0];

        if (!empty($rawResponse["notCreated"])) {
            throw new \Zend\Mail\Exception\RuntimeException($rawResponse['notCreated'][$createId]['type']);
        }
        $id = $rawResponse['created'][$createId]['id'];
        return $id;
    }
    public function destroy($mailboxId)
    {
        $request = new \Wikisuite\Jmap\Core\Request($this->connection);
        $arguments =  array(
        'destroy'=>array(
          $mailboxId
        )
      );
        $mailboxCall = $request->addMethodCall('Mailbox', 'set', $arguments);
        $response = $request->send();
        $rawResponse = $response->getResponsesForMethodCall($mailboxCall)[0];

        if (!empty($rawResponse["notDestroyed"])) {
            throw new \Zend\Mail\Exception\RuntimeException($rawResponse['notDestroyed'][$mailboxId]['type']);
        }
    }
    /**
     * enable raw request output
     */
    public function enableDebug()
    {
        return $this->connection->enableDebug();
    }
    /**
     * disable raw request output
     */
    public function disableDebug()
    {
        return $this->connection->disableDebug();
    }
}
