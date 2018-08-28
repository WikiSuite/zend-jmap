<?php

namespace Wikisuite\Jmap\Mail;

use Wikisuite\Jmap\Core;

class Email
{
    private $connection;

    public function __construct($connection)
    {
        $this->connection = $connection;
    }
    /**
     * @param string $mailboxName name of the new mailBox
     * @param string $parentMailboxId id new mailBox's parent, else root is parent
     * @return string id of the created mailbox
     */
    public function create($rawJmapMessage)
    {
        $request = new Core\Request($this->connection);
        $createId = md5(json_encode($rawJmapMessage));
        $arguments =  array(
        'create'=>array(
          $createId => $rawJmapMessage
        )
      );
        $call = $request->addMethodCall('Email', 'set', $arguments);
        $response = $request->send();
        $rawResponse = $response->getResponsesForMethodCall($call)[0];

        if (!empty($rawResponse["notCreated"])) {
            throw new \Zend\Mail\Exception\RuntimeException($rawResponse['notCreated'][$createId]['type']);
        }
        $id = $rawResponse['created'][$createId]['id'];
        return $id;
    }
    /**
     * @param string $messageId message id of the message to copy
     * @param string $mailboxId destination mailbox id
     * @return string id of the copied message
     */
    public function copy($messageId, $mailboxId)
    {
        $request = new Core\Request($this->connection);
        $createId = md5($messageId.$mailboxId);
        $arguments =  array(
        'create'=>array(
          $createId => array(
            'id'=>$messageId,
            'mailboxIds'=>array($mailboxId=>true)
            )
          )
        );
        $call = $request->addMethodCall('Email', 'copy', $arguments);
        $response = $request->send();
        $rawResponse = $response->getResponsesForMethodCall($call)[0];

        if (!empty($rawResponse["notCreated"])) {
            throw new \Zend\Mail\Exception\RuntimeException($rawResponse['notCreated'][$createId]['type']);
        }
        $id = $rawResponse['created'][$createId]['id'];
        return $id;
    }
    /**
     * @param string $messageId The id of the message to act on
     * @param array $properties Associative array of JMAP $properties to modify
     */
    public function update($messageId, $properties)
    {
        $request = new \Wikisuite\Jmap\Core\Request($this->connection);
        $arguments =  array(
        'update'=>array(
          $messageId=>$properties
        )
      );
        $call = $request->addMethodCall('Email', 'set', $arguments);
        $response = $request->send();
        $rawResponse = $response->getResponsesForMethodCall($call)[0];

        if (!empty($rawResponse["notUpdated"])) {
            throw new \Zend\Mail\Exception\RuntimeException($rawResponse['notUpdated'][$mailboxId]['type']);
        }
    }
    /**
     * @param string $messageId message id of the message to delete
     */
    public function destroy($messageId)
    {
        $request = new \Wikisuite\Jmap\Core\Request($this->connection);
        $arguments =  array(
        'destroy'=>array(
          $messageId
        )
      );

        $call = $request->addMethodCall('Email', 'set', $arguments);
        $response = $request->send();
        $rawResponse = $response->getResponsesForMethodCall($call)[0];

        if (!empty($rawResponse["notDestroyed"])) {
            throw new \Zend\Mail\Exception\RuntimeException($rawResponse['notDestroyed'][$messageId]['type']);
        }
    }
}
