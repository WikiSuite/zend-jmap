<?php

namespace Zend\Mail\Storage;

use Zend\Mail;
use Zend\Mail\Protocol;
require  __DIR__ . '/../libjmap/src/jmap-core.php';
require  __DIR__ . '/../libjmap/src/jmap-mail.php';

use Wikisuite\JMAPCore;
use Wikisuite\JMAPMail;

class Jmap extends AbstractStorage implements Folder\FolderInterface, Writable\WritableInterface
{
    protected $connection;

    /**
     * name of current folder
     * @var string
     */
    protected $currentFolder = '';

    /**
     * Create instance with parameters
     *
     * @param  array $params mail reader specific parameters
     * @throws Exception\ExceptionInterface
     */
    public function __construct($params)
    {
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
        $mailbox = new JMAPMail\Mailbox($this->connection);
        $inbox = $mailbox->getInbox();
        /*$this->selectFolder(isset($params->folder) ? $params->folder : 'INBOX');
            $this->connection = new Connection();
        }*/
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
        if ($flags === null) {
            return count($this->protocol->search(['ALL']));
        }
        $params = [];
        foreach ((array) $flags as $flag) {
            if (isset(static::$searchFlags[$flag])) {
                $params[] = static::$searchFlags[$flag];
            } else {
                $params[] = 'KEYWORD';
                $params[] = $this->protocol->escapeString($flag);
            }
        }
        return count($this->protocol->search($params));
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
     * @param  $id int number of message
     * @return Message
     */
    public function getMessage($id)
    {
        echo "WRITEME: ".__METHOD__."\n";
        die;
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
        echo "WRITEME: ".__METHOD__."\n";
        die;
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
        echo "WRITEME: ".__METHOD__."\n";
        die;
    }


    /**
     * Close resource for mail lib. If you need to control, when the resource
     * is closed. Otherwise the destructor would call this.
     */
    public function close()
    {
        echo "WRITEME: ".__METHOD__."\n";
        die;
    }
    /**
     * Keep the resource alive.
     */
    public function noop()
    {
        echo "WRITEME: ".__METHOD__."\n";
        die;
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
        echo "WRITEME: ".__METHOD__."\n";
        die;
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
        echo "WRITEME: ".__METHOD__."\n";
        die;
    }


    /**
     * get root folder or given folder
     *
     * @param  string $rootFolder get folder structure for given folder, else root
     * @throws Exception\RuntimeException
     * @throws Exception\InvalidArgumentException
     * @throws Protocol\Exception\RuntimeException
     * @return Folder root or wanted folder
     */
    public function getFolders($rootFolder = null)
    {
        echo "WRITEME: ".__METHOD__."\n";
        die;
    }
    /**
     * select given folder
     *
     * folder must be selectable!
     *
     * @param  Folder|string $globalName global name of folder or instance for subfolder
     * @throws Exception\RuntimeException
     * @throws Protocol\Exception\RuntimeException
     */
    public function selectFolder($globalName)
    {
        echo "WRITEME: ".__METHOD__."\n";
        die;
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
