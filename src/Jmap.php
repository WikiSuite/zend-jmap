<?php

namespace Zend\Jmap;

use Zend\Mail;
use Zend\Mail\Protocol;
use Zend\Mail\Storage;
use Zend\Mail\Storage\Folder;

require  __DIR__ . '/../libjmap/src/jmap-core.php';
require  __DIR__ . '/../libjmap/src/jmap-mail.php';
require 'JmapMessage.php';
require 'JmapFolder.php';

use Wikisuite\JMAPCore;
use Wikisuite\JMAPMail;

class Jmap extends Storage\AbstractStorage implements Storage\Folder\FolderInterface, Storage\Writable\WritableInterface
{
    protected $connection;

    /**
     * name of current folder
     * @var string
     */
    protected $currentFolder;
    private $mailboxes;
    private $mailBoxCache;

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
    /*protected static $constantsToJmapKeywordsSearch = [
        Mail\Storage::FLAG_ANSWERED => array('$answered'=> true),
        Mail\Storage::FLAG_SEEN => array('$seen' => true),
        Mail\Storage::FLAG_UNSEEN => array('$seen' => false),
        Mail\Storage::FLAG_DRAFT => array('$draft'=> true),
        Mail\Storage::FLAG_FLAGGED => array('$flagged'=> true),
    ];*/

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
        print_r($params);
        if (! isset($params->user)) {
            throw new Exception\InvalidArgumentException('need a user in params');
        }
        if (! isset($params->password)) {
            throw new Exception\InvalidArgumentException('need a password in params');
        }
        if (! isset($params->url)) {
            throw new Exception\InvalidArgumentException('need a url in params');
        }
        $user = $params->user;
        $url     = $params->url;
        $password = $params->password;
        $ssl      = isset($params->ssl) ? $params->ssl : false;
        $this->connection = new JMAPCore\Connection($url, $user, $password);
        $this->mailboxes = new JMAPMail\Mailbox($this->connection);

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
            throw new Exception\RuntimeException('No selected folder to count');
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
        if (isset($this->mailBoxCache->messages[$id])) {
            //echo "getMessage($id): CACHE HIT\n";
        } else {
            //echo "getMessage($id): CACHE MISS\n";
            $jmapMessages = $this->mailboxes->getMessages($this->currentFolder->getId(), null, $id -1);
            $cacheId = $id;
            foreach ($jmapMessages as $jmapMessage) {
                $this->mailBoxCache->messages[$cacheId] = new JmapMessage(array('jmap' => $jmapMessage));
                $cacheId++;
            }
        }
        return $this->mailBoxCache->messages[$id];
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
        throw new Exception\RuntimeException('not implemented');
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
        throw new Exception\RuntimeException('not implemented');
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
        echo "WRITEME: ".__METHOD__."\n";
        die;
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

        $root = new Folder('/', '/', false);
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
                if (! $parent) {
                    throw new Exception\RuntimeException('error while constructing folder tree, parent ' . $data['parentId']. ' not found');
                }
                $globalName = $parent->getGlobalName() . $this->delimiter . $localName;
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
            throw new Exception\RuntimeException('empty JMAP id provided');
        }
        ['mailboxesById'=>$mailboxesById] = $this->getFolderInfo();
        $foundFolder = $mailboxesById[$jmapId];
        if (! $foundFolder) {
            throw new Exception\RuntimeException('folder with jmap id ' . $jmapId . ' not found');
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
            $foundFolder = $mailboxesByGlobalName[$rootFolder];
            if (! $foundFolder) {
                throw new Exception\RuntimeException('folder with globalName ' . $rootFolder . ' not found');
            }
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
     */
    public function createFolder($name, $parentFolder = null)
    {
        echo "WRITEME: ".__METHOD__."\n";
        die;
    }
    /**
     * remove a folder
     *
     * @param  string|Folder $name name or instance of folder
     * @throws Exception\RuntimeException
     */
    public function removeFolder($name)
    {
        echo "WRITEME: ".__METHOD__."\n";
        die;
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
        echo "WRITEME: ".__METHOD__."\n";
        die;
    }


    /**
     * append a new message to mail storage
     *
     * @param string $message message as string or instance of message class
     * @param null|string|Folder $folder  folder for new message, else current
     *     folder is taken
     * @param null|array $flags set flags for new message, else a default set
     *     is used
     * @throws Exception\RuntimeException
     */
    public function appendMessage($message, $folder = null, $flags = null)
    {
        echo "WRITEME: ".__METHOD__."\n";
        die;
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
        echo "WRITEME: ".__METHOD__."\n";
        die;
    }
    /**
     * move an existing message
     *
     * NOTE: IMAP has no native move command, thus it's emulated with copy and delete
     *
     * @param int $id number of message
     * @param string|Folder $folder name or instance of target folder
     * @throws Exception\RuntimeException
     */
    public function moveMessage($id, $folder)
    {
        echo "WRITEME: ".__METHOD__."\n";
        die;
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
        echo "WRITEME: ".__METHOD__."\n";
        die;
    }
}
