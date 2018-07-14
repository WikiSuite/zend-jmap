<?php
namespace Zend\Mail\Storage;

use Zend\Mail;
use Zend\Mail\Storage\Jmap;

class JmapMessage extends Message implements Message\MessageInterface
{
    /**
     * @var string
     */
    protected $messageId = '';
    protected $threadId = '';
    protected $mailboxIds = [];
    protected $jmapFrom = [];

    public function __construct(array $params)
    {
        if (isset($params['jmap'])) {
            $message = $params['jmap'];
            $this->messageId = $message['id'];
            // messageNum is a string right now
            // blobId: string
            //$this->messageNum = $message['id'];
            $this->threadId = $message['threadId'];
            // array of string => bool
            $this->mailboxIds = $message['mailboxIds'];
            // ["name"=>"", "email"=>""]
            $this->jmapFrom = $message['from'];

            $headers = $messages['headers'] ?? [];

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
            $appendHeaders = array('subject', 'receivedAt', 'sentAt');
            foreach ($appendHeaders as $n) {
                if (isset($message[$n]) && !isset($headers[$n])) {
                    $headers[$n] = $message[$n];
                }
            }

            // textBody is a string?
            // htmlBody is a string?
            // attachments NULL
            // attachmentsEmail NULL
            // keywords: []
            if (isset($message['keywords']) && $message['keywords']) {
                $jmapFlags = [
                    '$draft'=>['imap'=>'\Draft', 'flag'=>Mail\Storage::Flag_DRAFT],
                    '$seen'=>['imap'=>'\Seen', 'flag'=>Mail\Storage::Flag_SEEN],
                    '$flagged'=>['imap'=>'\Flagged', 'flag'=>Mail\Storage::Flag_FLAGGED],
                    '$answered'=>['imap'=>'\Answered', 'flag'=>Mail\Storage::Flag_ANSWERED],
                ];
                $flags = [];
                foreach ($message['keywords'] as $keyword) {
                    if (isset($jmapFlags[$keywords])) {
                        $flags[] = $jmapFlags[$keywords]['flag'];
                    }
                }
                // same as Storage/Message
                $this->flags = array_combine($flags, $flags);
            }
            // size: int
            // preview: string

            // ???
            if (array_key_exists('header:List-POST:asURLs', $message)) {
                $headers['List-POST'] = $message['header:List-POST:asURLs'];
            }

            if (is_array($message['htmlBody'])) {
                $nbParts = count($message['htmlBody']);
                if ($nbParts > 0) {
                    $this->countParts = $nbParts;
                    $this->parts = []; // TODO
                    $headers['content-type'] = 'multipart/mixed';
                    $counter = 1;
                    foreach ($message['htmlBody'] as $i => $body) {
                        $partHeaders = [
                            'partid' => $body['partId'],
                            'blobid' => $body['blogId'],
                            'size' => $body['size'],
                            'content-type' => $body['type'],
                        ];
                        $partContent = '';
                        if (isset($message['bodyValues'][$body['partId']])) {
                            $bodyProps = $message['bodyValues'][$body['partId']];
                            $partHeaders['isEncodingProblem'] = $bodyProps['isEncodingProblem'];
                            $partHeaders['isTruncated'] = $bodyProps['isTruncated'];
                            $partContent = $bodyProps['value'];
                        }
                        $this->parts[$counter++] = new Part(['headers' => $partHeaders, 'content' => $partContent]);
                    }
                } else {
                }
            } elseif (is_string($message['htmlBody'])) {
                // ...
            }
            $params['headers'] = $headers;
        }
        parent::__construct($params);
    }

    public function getTopLines()
    {
    }

    public function hasFlag($flag)
    {
    }

    public function getFlags()
    {
    }
}
