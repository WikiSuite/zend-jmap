<?php
namespace Zend\Mail\Storage;

use Zend\Mail;
use Zend\Mail\Storage\Folder;

class JmapFolder extends Folder
{
    /**
    * JMAP id of the mailbox represented by this folder
    * @var string
    * */
    private $id;

    /**
    * Public constructor
    *
    * In addition to the parameters of Zend\Mail\Storage\Folder::__construct() this constructor supports:
    * - flags array with flags for message, keys are ignored, use constants defined in Zend\Mail\Storage
    *
    * @param  array $params
    */
    public function __construct($localName, $globalName = '', $selectable = true, array $folders = [], $id)
    {
        $this->id = $id;
        parent::__construct($localName, $globalName, $selectable, $folders);
    }

    /**
     * JMAP id of the mailbox represented by this folder
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
}
