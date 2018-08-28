<?php

namespace Zend\Jmap;

use Zend\Mail\Storage;
use Zend\Mail\Storage\Folder;
use Zend\Mail\Message;

class Jmap extends Storage\AbstractStorage implements Storage\Folder\FolderInterface, Storage\Writable\WritableInterface
{
    protected $connection;

    /**
     * name of current folder
     * @var string
     */
    protected $currentFolder;
    private $mailboxes;
    private $emails;
    private $mailBoxCache = [];

    /**
     * JMAP flags to constants translation
     * Note that the Recent and Deleted IMAP keywords are not exposed in JMAP
     * @var array
     */
    /*protected static $jmapFlagstoConstants = [
        '$answered' => Mail\Storage::FLAG_ANSWERED,
        '$seen'     => Mail\Storage::FLAG_SEEN,
        '$draft'    => Mail\Storage::FLAG_DRAFT,
        '$flagged'  => Mail\Storage::FLAG_FLAGGED,
    ];*/

    /**
     * JMAP flags to constants translation
     * Note that the Recent and Deleted IMAP keywords are not exposed in JMAP
     * @var array
     */
    protected static $storageFlagsToJmapKeyword = [
        Storage::FLAG_ANSWERED => array('$answered'=> true),
        Storage::FLAG_SEEN => array('$seen' => true),
        Storage::FLAG_UNSEEN => array('$seen' => false),
        Storage::FLAG_DRAFT => array('$draft'=> true),
        Storage::FLAG_FLAGGED => array('$flagged'=> true),
    ];

    /**
     * Folder delimiter character
     * @var string
     */
    protected $delimiter = '/';

    /**
     * Create instance with parameters
     *
     * @param  array $params mail reader specific parameters
     * @throws Exception\ExceptionInterface
     */
    public function __construct($params)
    {
        $this->has['flags'] = true;
        if (is_array($params)) {
            $params = (object) $params;
        }
        if (! isset($params->user)) {
            throw new Storage\Exception\InvalidArgumentException('need a user in params');
        }
        if (! isset($params->password)) {
            throw new Storage\Exception\InvalidArgumentException('need a password in params');
        }
        if (! isset($params->url)) {
            throw new Storage\Exception\InvalidArgumentException('need a url in params');
        }
        $user = $params->user;
        $url     = $params->url;
        $password = $params->password;
        $ssl      = isset($params->ssl) ? $params->ssl : false;
        $this->connection = new \Wikisuite\Jmap\Core\Connection($url, $user, $password);
        $this->mailboxes = new \Wikisuite\Jmap\Mail\Mailbox($this->connection);
        $this->emails = new \Wikisuite\Jmap\Mail\Email($this->connection);
        $this->blobs = new \Wikisuite\Jmap\Core\Blob($this->connection);
        $inboxId = $this->mailboxes->getInboxId();
        $this->currentFolder = $this->getFolderByJmapId($inboxId);
    }

    /**
    * Count messages all messages in current box
    *
    * @param null $flags
    * @throws Exception\RuntimeException
    * @throws Protocol\Exception\RuntimeException
    * @return int number of messages
    */
    public function countMessages($flags = null)
    {
        if (! $this->currentFolder) {
            throw new Storage\Exception\RuntimeException('No selected folder to count');
        }
        return $this->mailboxes->getMessageCount($this->currentFolder->getId(), array('ids'));
    }
    /**
     * Get a list of messages with number and size
     *
     * @param  int $id  number of message
     * @return int|array size of given message of list with all messages as array(num => size)
     */
    public function getSize($id = 0)
    {
        echo "WRITEME: ".__METHOD__."\n";
        die;
    }
    /**
     * Get a message with headers and body
     *
     * @param  $id int number of message, 1 based index
     * @return Message
     */
    public function getMessage($id)
    {
        $latestState = $this->connection->getLatestState();
        if (!empty($this->mailBoxCache['state']) && $this->mailBoxCache['state'] !== $latestState) {
            //Cache is obsolete
            $this->mailBoxCache = [];
        }
        if (isset($this->mailBoxCache['messages'][$id])) {
            //echo "getMessage($id): CACHE HIT\n";
        } else {
            //echo "getMessage($id): CACHE MISS\n";
            $jmapMessages = $this->mailboxes->getMessages($this->currentFolder->getId(), null, $id -1);
            $cacheId = 1;
            $this->mailBoxCache['state'] = $latestState;
            foreach ($jmapMessages as $jmapMessage) {
                $this->mailBoxCache['messages'][$cacheId] = new JmapMessage([
                  'jmap' => $jmapMessage,
                  'handler' => $this,
                  'id' => $id
                ]);
                $cacheId++;
            }
        }
        return $this->mailBoxCache['messages'][$id];
    }
    /**
     * Get raw header of message or part
     *
     * @param  int               $id       number of message
     * @param  null|array|string $part     path to part or null for message header
     * @param  int               $topLines include this many lines with header (after an empty line)
     * @return string raw header
     */
    public function getRawHeader($id, $part = null, $topLines = 0)
    {
        throw new Storage\Exception\RuntimeException('not implemented');
    }
    /**
     * Get raw content of message or part
     *
     * @param  int               $id   number of message
     * @param  null|array|string $part path to part or null for message content
     * @return string raw content
     */
    public function getRawContent($id, $part = null)
    {
        if ($part !== null) {
            // TODO: implement
            throw new Storage\Exception\RuntimeException('not implemented');
        }
        return $this->getMessage($id)->getContent();
    }


    /**
     * Close resource for mail lib. If you need to control, when the resource
     * is closed. Otherwise the destructor would call this.
     */
    public function close()
    {
    }
    /**
     * Keep the resource alive.
     */
    public function noop()
    {
    }
    /**
     * delete a message from current box/folder
     *
     * @param $id
     */
    public function removeMessage($id)
    {
        $message = $this->getMessage($id);
        $this->emails->destroy($message->getUniqueId());
    }

    /**
         * get unique id for one or all messages
         *
         * if storage does not support unique ids it's the same as the message number
         *
         * @param int|null $id message number
         * @return array|string message number for given message or all messages as array
         * @throws Exception\ExceptionInterface
         */
    public function getUniqueId($id = null)
    {
        if ($id) {
            return $this->getMessage($id)->getUniqueId();
        } else {
            $retval = [];
            for ($i=1; $i <= $this->count(); $i++) {
                $retval[$i] = $this->getMessage($i)->getUniqueId();
            }
            return $retval;
        }
    }
    /**
     * get a message number from a unique id
     *
     * I.e. if you have a webmailer that supports deleting messages you should use unique ids
     * as parameter and use this method to translate it to message number right before calling removeMessage()
     *
     * @param string $id unique id
     * @return int message number
     * @throws Exception\ExceptionInterface
     */
    public function getNumberByUniqueId($id)
    {
        return array_search($id, $this->getUniqueId(null));
    }


    /**
     * get root folder or given folder
     *
     * @param  string $rootFolder get folder structure for given folder (globalName), else root
     * @throws Exception\RuntimeException
     * @throws Exception\InvalidArgumentException
     * @return Folder root or wanted folder
     */
    private function getFolderInfo()
    {
        $mailboxes = $this->mailboxes->getMailboxes();

        $root = new Folder($this->delimiter, $this->delimiter, false);
        $mailboxesById = [null];
        $mailboxesByGlobalName = [null];
        foreach ($mailboxes as $index => $data) {
            //var_dump($index, $data);
            $localName = $data['name'];
            if ($data['parentId'] === null) {
                $parentFolder = $root;
                $globalName = $localName;
            } else {
                $parentFolder = $mailboxesById[$data['parentId']];
                if (! $parentFolder) {
                    throw new Storage\Exception\RuntimeException('error while constructing folder tree, parent ' . $data['parentId']. ' not found');
                }
                $globalName = $parentFolder->getGlobalName() . $this->delimiter . $localName;
            }
            $folder = new JmapFolder($localName, $globalName, true, [], $data['id']);
            $mailboxesById[$data['id']] = $folder;
            $mailboxesByGlobalName[$globalName] = $folder;
            $parentFolder->$localName = $folder;
        }
        return ['root'=>$root, 'mailboxesById'=>$mailboxesById, 'mailboxesByGlobalName'=>$mailboxesByGlobalName];
    }
    /**
     * get root folder or given folder
     *
     * @param  string $jmapId jmapId
     * @throws Exception\RuntimeException
     * @throws Exception\InvalidArgumentException
     * @return Folder root or wanted folder
     */
    private function getFolderByJmapId($jmapId)
    {
        if (! $jmapId) {
            throw new Storage\Exception\RuntimeException('empty JMAP id provided');
        }
        ['mailboxesById'=>$mailboxesById] = $this->getFolderInfo();
        $foundFolder = $mailboxesById[$jmapId];
        if (! $foundFolder) {
            throw new Storage\Exception\RuntimeException('folder with jmap id ' . $jmapId . ' not found');
        }
        return $foundFolder;
    }
    /**
     * get root folder or given folder
     *
     * @param  string $rootFolder get folder structure for given folder (globalName), else root
     * @throws Exception\RuntimeException
     * @throws Exception\InvalidArgumentException
     * @return Folder root or wanted folder
     */
    public function getFolders($rootFolder = null)
    {
        ['root'=>$root, 'mailboxesByGlobalName'=>$mailboxesByGlobalName] = $this->getFolderInfo();
        $foundFolder = null;
        if ($rootFolder) {
            $rootFolderGlobalName = (string) $rootFolder;
            //echo "getFolders($rootFolderGlobalName) looking into:\n";
            //var_dump(array_keys($mailboxesByGlobalName));
            if (!array_key_exists($rootFolderGlobalName, $mailboxesByGlobalName)) {
                throw new Storage\Exception\InvalidArgumentException('folder with globalName ' . $rootFolder . ' not found');
            }
            $foundFolder = $mailboxesByGlobalName[$rootFolderGlobalName];
        } else {
            $foundFolder = $root;
        }
        return $foundFolder;
    }
    /**
     * select given folder
     *
     * folder must be selectable!
     *
     * @param  Folder|string $globalName global name of folder or instance for subfolder
     * @throws Exception\RuntimeException
     */
    public function selectFolder($globalName)
    {
        $this->currentFolder = $this->getFolders($globalName);
    }
    /**
     * get Folder instance for current folder
     *
     * @return Folder instance of current folder
     */
    public function getCurrentFolder()
    {
        return $this->currentFolder;
    }
    /**
     * create a new folder
     *
     * This method also creates parent folders if necessary. Some mail storages
     * may restrict, which folder may be used as parent or which chars may be
     * used in the folder name
     *
     * @param string $name global name of folder, local name if $parentFolder
     *     is set
     * @param string|Folder $parentFolder parent folder for new folder, else
     *     root folder is parent
     * @throws Exception\RuntimeException
     * @throws Exception\InvalidArgumentException
     */
    public function createFolder($name, $parentFolder = null)
    {
        $parentFolderReal = null;
        $parentMailboxId = null;
        $pathElements = explode($this->delimiter, $name);
        if (empty($name)) {
            throw new Storage\Exception\InvalidArgumentException("name must be provided");
        }

        if ($parentFolder) {
            $pathElementCount = count($pathElements);
            if ($pathElementCount!==1) {
                throw new Storage\Exception\InvalidArgumentException("\$name must be a localName if parentElement is provided.  But \$name has $pathElementCount elements");
            }
            $parentFolderReal = $this->getFolders($parentFolder);
            $parentFolderPathElements = explode($this->delimiter, $parentFolder);
            $pathElements = array_merge($parentFolderPathElements, $pathElements);
        }
        ['root'=>$root, 'mailboxesByGlobalName'=>$mailboxesByGlobalName] = $this->getFolderInfo();
        //Check that each element exists, and create if necessary
        $pathElementCount = count($pathElements);
        //var_dump($pathElementCount, $pathElements);
        for ($i = 0; $i < $pathElementCount; $i++) {
            $currentPath = implode($this->delimiter, array_slice($pathElements, 0, $i + 1));
            $localName = $pathElements[$i];
            //var_dump(array_keys($mailboxesByGlobalName));
            //var_dump($i, $currentPath, $localName, array_key_exists($currentPath, $mailboxesByGlobalName));
            if (array_key_exists($currentPath, $mailboxesByGlobalName)) {
                if ($i + 1 === $pathElementCount) {
                    //The last element in the path already exists
                    throw new Storage\Exception\InvalidArgumentException("Folder $currentPath already exists on the server");
                }
                $parentMailboxId = $mailboxesByGlobalName[$currentPath]->getId();
            } else {
                $parentMailboxId = $this->mailboxes->create($localName, $parentMailboxId);
                if (empty($parentMailboxId)) {
                    throw new Storage\Exception\RuntimeException("mailbox $name wasn't created successfully");
                }
            }
        }
    }
    /**
     * remove a folder
     *
     * @param  string|Folder $name name or instance of folder
     * @param boolean $forceDeleteEvenIfMailPresent will delete mails in the folder before deleting.
     * @throws Exception\RuntimeException
     */
    public function removeFolder($name, $forceDeleteEvenIfMailPresent=false)
    {
        $folder = $this->getFolders($name);
        if (!$folder) {
            throw new Storage\Exception\RuntimeException("mailbox $name not found, cannot delete");
        }
        $this->mailboxes->destroy($folder->getId(), $forceDeleteEvenIfMailPresent);
    }

    private function parseName($name)
    {
        $globalName = $name;
        $pos = strrpos($globalName, $this->delimiter);
        if ($pos === false) {
            $localName = $globalName;
            $parentGlobalName = null;
        } else {
            $localName = substr($globalName, $pos + 1);
            $parentGlobalName = substr($globalName, 0, $pos);
        }
        return array('globalName'=>$globalName,
       'localName'=>$localName,
       'parentGlobalName'=>$parentGlobalName
     );
    }
    /**
     * rename and/or move folder
     *
     * The new name has the same restrictions as in createFolder()
     *
     * @param  string|Folder $oldName name or instance of folder
     * @param  string $newName new global name of folder
     * @throws Exception\RuntimeException
     */
    public function renameFolder($oldName, $newName)
    {
        $folder = $this->getFolders($oldName);
        $propertiesToUpdate = array();
        if (!$folder) {
            throw new Storage\Exception\RuntimeException("mailbox $oldName not found, cannot rename");
        }
        $oldNameInfo = $this->parseName($oldName);
        $newNameInfo =  $this->parseName($newName);
        //var_dump($oldNameInfo, $newNameInfo);
        if ($oldNameInfo['parentGlobalName'] !== $newNameInfo['parentGlobalName']) {
            ['mailboxesByGlobalName'=>$mailboxesByGlobalName] = $this->getFolderInfo();
            if (!array_key_exists($newNameInfo['parentGlobalName'], $mailboxesByGlobalName)) {
                $this->createFolder($newNameInfo['parentGlobalName']);
            }
            $parentFolder = $this->getFolders($newNameInfo['parentGlobalName']);
            $propertiesToUpdate['parentId']=$parentFolder->getId();
        }
        if ($oldNameInfo['localName'] !== $newNameInfo['localName']) {
            $propertiesToUpdate['name']=$newNameInfo['localName'];
        }
        $this->mailboxes->update($folder->getId(), $propertiesToUpdate);
    }


    /**
     * append a new message to mail storage
     *
     * @param string $message message as string or instance of message class
     * @param null|string|Folder $folder  folder for new message, else current
     *     folder is taken
     * @param null|array $flags set flags for new message, else a default set
     *     is used
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    public function appendMessage($message, $folder = null, $flags = null)
    {
        if ($folder) {
            throw new Storage\Exception\InvalidArgumentException("Folder selection not implemented yet");
        }
        if ($flags) {
            throw new Storage\Exception\InvalidArgumentException("Setting flags not implemented yet");
        }
        if (is_string($message)) {
            $message = Message::fromString($message);
        }
        if (!is_a($message, 'Zend\Mail\Message')) {
            throw new Storage\Exception\InvalidArgumentException("message provided is of class ".get_class($message));
        }
        $jmapMessage = JmapMessage::toJmapRawMessage($message);
        $jmapMessage['mailboxIds'] = array($this->currentFolder->getId() => true);
        $this->emails->create($jmapMessage);
    }
    /**
     * copy an existing message
     *
     * @param int $id number of message
     * @param string|Folder $folder name or instance of target folder
     * @throws Exception\RuntimeException
     */
    public function copyMessage($id, $folder)
    {
        $message = $this->getMessage($id);
        $folder = $this->getFolders($folder);
        //This is not yet supported by Cyrus...
        //$this->emails->copy($message->getUniqueId(), $folder->getId());

        //Manual workaround
        $jmapMessage = $message->getRawJmap();

        $blobId = $jmapMessage['blobId'];
        $rawMessage = $this->blobs->get($blobId);
        $zendMailMessage = Message::fromString($rawMessage);
        $newJmapMessage = JmapMessage::toJmapRawMessage($zendMailMessage);
        $newJmapMessage['mailboxIds'] = array($folder->getId() => true);
        $this->emails->create($newJmapMessage);
    }
    /**
     * move an existing message
     *
     * @param int $id number of message
     * @param string|Folder $folder name or instance of target folder
     * @throws Exception\RuntimeException
     */
    public function moveMessage($id, $folder)
    {
        $message = $this->getMessage($id);
        $folder = $this->getFolders($folder);
        $propertiesToUpdate = array();
        $propertiesToUpdate['mailboxIds']=array($folder->getId() => true);
        $this->emails->update($message->getUniqueId(), $propertiesToUpdate);
    }
    /**
     * set flags for message
     *
     * NOTE: this method can't set the recent flag.
     *
     * @param int $id number of message
     * @param array $flags new flags for message
     * @throws Exception\RuntimeException
     */
    public function setFlags($id, $flags)
    {
      $message = $this->getMessage($id);
      $keywords = [];
      forEach($flags as $flag) {
        $keywords[] = self::$storageFlagsToJmapKeyword[$flag];
      }
      $propertiesToUpdate = array('keywords' =>$keywords);
      $this->emails->update($message->getUniqueId(), $propertiesToUpdate);
    }
    /**
     * enable raw request output
     */
    public function enableDebug()
    {
        $this->connection->DEBUG = true;
    }
    /**
     * disable raw request output
     */
    public function disableDebug()
    {
        $this->connection->DEBUG = false;
    }
}
