<?php
namespace Zend\Jmap;

use Zend\Mail\Storage;
use Zend\Mail\Storage\Message;

function arrayCopy(array $array)
{
    $result = array();
    foreach ($array as $key => $val) {
        if (is_array($val)) {
            $result[$key] = arrayCopy($val);
        } elseif (is_object($val)) {
            $result[$key] = clone $val;
        } else {
            $result[$key] = $val;
        }
    }
    return $result;
}
class JmapMessage extends \Zend\Mail\Storage\Message implements Message\MessageInterface
{
    /**
     * @var string
     */
    protected $jmapMessage;
    protected $messageId = '';
    protected $threadId = '';
    protected $mailboxIds = [];
    protected $jmapFrom = [];
    public static $jmapFlagsLookup = [
        '$draft'=>['imap'=>'\Draft', 'flag'=>Storage::FLAG_DRAFT],
        '$seen'=>['imap'=>'\Seen', 'flag'=>Storage::FLAG_SEEN],
        '$flagged'=>['imap'=>'\Flagged', 'flag'=>Storage::FLAG_FLAGGED],
        '$answered'=>['imap'=>'\Answered', 'flag'=>Storage::FLAG_ANSWERED],
    ];
    public function getUniqueId()
    {
        return $this->messageId;
    }

    public function __construct(array $params)
    {
        if (isset($params['jmap'])) {
            $this->jmapMessage = $params['jmap'];
            $this->messageId = $this->jmapMessage['id'];
            // messageNum is a string right now
            // blobId: string
            //$this->messageNum = $this->jmapMessage['id'];
            $this->threadId = $this->jmapMessage['threadId'];
            // array of string => bool
            $this->mailboxIds = $this->jmapMessage['mailboxIds'];
            // ["name"=>"", "email"=>""]
            $this->jmapFrom = $this->jmapMessage['from'];

            $headers = array_map('Zend\Mail\Header\HeaderValue::filter', $this->jmapMessage['headers']) ?? [];

            // others properties: keywords, size, messageId, inReplyTo, sender, to, cc, bcc, replyTo, sentAt, hasAttachment, preview
            // cyrus:
            // annotations: []
            // sender: NULL
            // from: [["name"=>"", "email"=>""]]
            // to: [["name"=>"", "email"=>""]]
            // cc: NULL,
            // bcc: NULL,
            // replyTo: NULL

            // which one do we take?
            /*$appendHeaders = array('subject', 'receivedAt', 'sentAt');
            foreach ($appendHeaders as $n) {
                if (isset($this->jmapMessage[$n]) && !isset($headers[$n])) {
                    $headers[$n] = $this->jmapMessage[$n];
                }
            }*/

            // textBody is a string?
            // htmlBody is a string?
            // attachments NULL
            // attachmentsEmail NULL
            // keywords: []
            if (isset($this->jmapMessage['keywords']) && $this->jmapMessage['keywords']) {
                $flags = [];
                foreach ($this->jmapMessage['keywords'] as $keyword) {
                    if (isset(self::$jmapFlagsLookup[strtolower($keyword)])) {
                        $flags[] = self::$jmapFlagsLookup[strtolower($keyword)]['flag'];
                    }
                }
                // same as Storage/Message
                $this->flags = array_combine($flags, $flags);
            }
            // size: int
            // preview: string

            // ???
            if (array_key_exists('header:List-POST:asURLs', $this->jmapMessage)) {
                $headers['List-POST'] = $this->jmapMessage['header:List-POST:asURLs'];
            }

            if (is_array($this->jmapMessage['htmlBody'])) {
                $nbParts = count($this->jmapMessage['htmlBody']);
                if ($nbParts > 0) {
                    $this->countParts = $nbParts;
                    $this->parts = []; // TODO
                    $headers['content-type'] = 'multipart/mixed';
                    $counter = 1;
                    foreach ($this->jmapMessage['htmlBody'] as $i => $body) {
                        $partHeaders = [
                            'partid' => $body['partId'],
                            'blobid' => $body['blogId'],
                            'size' => $body['size'],
                            'content-type' => $body['type'],
                        ];
                        $partContent = '';
                        if (isset($this->jmapMessage['bodyValues'][$body['partId']])) {
                            $bodyProps = $this->jmapMessage['bodyValues'][$body['partId']];
                            $partHeaders['isEncodingProblem'] = $bodyProps['isEncodingProblem'];
                            $partHeaders['isTruncated'] = $bodyProps['isTruncated'];
                            $partContent = $bodyProps['value'];
                        }
                        $this->parts[$counter++] = new Part(['headers' => $partHeaders, 'content' => $partContent]);
                    }
                } else {
                }
            } elseif (is_string($this->jmapMessage['htmlBody'])) {
                $params['content'] = $this->jmapMessage['htmlBody'];
            } elseif (is_string($this->jmapMessage['textBody'])) {
                $params['content'] = $this->jmapMessage['textBody'];
            }
            $params['headers'] = $headers;
        }
        //var_dump($params);
        parent::__construct($params);
    }
    public function getRawJmap()
    {
        return arrayCopy($this->jmapMessage);
    }

    /**
 * return toplines as found after headers
 *
 * @return string toplines
 */
    public function getTopLines()
    {
        return $this->jmapMessage['preview'];
    }

    private static function AddressInterfacetoJmapEmailAddress($address)
    {
        $retVal = array();
        if ($address->getName()) {
            $retVal['name'] = $address->getName();
        } else {
            $retVal['name'] = '';
        }

        $retVal['email'] = $address->getEmail();

        return $retVal;
    }
    /**
     * Builds an array of parameters representing a JMAP message from a Zend\Message instance
     *
     * @param object \Zend\Mail\Message
     * @return object The resulting associative array is compatible with libjmap
     */
    public static function toJmapRawMessage($message)
    {
        $rawJmapMessage = array();
        if (!($message instanceof \Zend\Mail\Message)) {
            throw new Storage\Exception\InvalidArgumentException("message provided is of class ".get_class($message).", in should be a Zend\Mail\Message");
        }

        foreach ($message->getFrom() as $address) {
            $rawJmapMessage['from'][] = self::AddressInterfacetoJmapEmailAddress($address);
        }
        foreach ($message->getTo() as $address) {
            $rawJmapMessage['to'][] = self::AddressInterfacetoJmapEmailAddress($address);
        }
        foreach ($message->getCc() as $address) {
            $rawJmapMessage['cc'][] = self::AddressInterfacetoJmapEmailAddress($address);
        }
        foreach ($message->getBcc() as $address) {
            $rawJmapMessage['bcc'][] = self::AddressInterfacetoJmapEmailAddress($address);
        }
        foreach ($message->getReplyTo() as $address) {
            $rawJmapMessage['replyTo'][] = self::AddressInterfacetoJmapEmailAddress($address);
        }
        if ($message->getSender()!== null) {
            foreach ($message->getSender() as $address) {
                $rawJmapMessage['sender'][] = self::AddressInterfacetoJmapEmailAddress($address);
            }
        }
        $rawJmapMessage['subject'] = $message->getSubject();
        $zendBody = $message->getBody();
        if ($zendBody instanceof Mime\Message) {
            $rawJmapMessage['bodyStructure'] = array('type'=>"multipart/alternative");
            $rawJmapMessage['bodyStructure']['subParts'] = array();
            $rawJmapMessage['bodyStructure']['bodyValues'] = array();
            foreach ($zendBody->getParts() as $part) {
                $partId = $part->getId();
                $rawJmapMessage['bodyStructure']['subParts'][] = array(
                  'partId'=>$partId,
                  'type'=>$part->getType()
                );
                $rawJmapMessage['bodyStructure']['bodyValues'][] = array(
                  'partId'=>$partId,
                  'value'=>$part->getRawContent(),
                  'isTruncated'=>false
                );
            }
        } else {
            $rawJmapMessage['textBody'] = (string) $zendBody;
        }
        $rawJmapMessage['keywords'] = array();
        return $rawJmapMessage;
    }
    /**
     * Builds an array of parameters representing a JMAP message from a Zend\Message instance
     *
     * @param object \Zend\Mail\Message
     * @return object The resulting associative array is compatible with libjmap
     */
    public static function stripImmutableProperties($rawJmapMessage)
    {
        unset($rawJmapMessage['id']);
        unset($rawJmapMessage['blobId']);
        unset($rawJmapMessage['threadId']);
        unset($rawJmapMessage['size']);
        unset($rawJmapMessage['hasAttachment']);
        unset($rawJmapMessage['preview']);
        return $rawJmapMessage;
    }
}
