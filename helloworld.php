<?php
require __DIR__ . '/vendor/autoload.php';
use Zend\Mail\Storage\Imap;
require  __DIR__ . '/src/Jmap.php';
use Zend\Mail\Storage\Jmap;
// Connecting with Imap:
/*echo "IMAP\n";
$mail = new Imap([
    'host'     => 'cyrus.suite.wiki',
    'user'     => 'test',
    'password' => 'wikisuite',
]);
echo $mail->countMessages() . " messages found\n";
foreach ($mail as $message) {
    printf("Mail from '%s': %s\n", $message->from, $message->subject);
}
*/
echo "JMAP\n";
$mail = new Jmap([
    'url'     => 'http://cyrus.suite.wiki/jmap/',
    'user'     => 'test',
    'password' => 'wikisuite',
]);
echo $mail->countMessages() . " messages found\n";
foreach ($mail as $message) {
    printf("Mail from '%s': %s\n", $message->from, $message->subject);
}
//echo $mail->countMessages([Mail\Storage::FLAG_UNSEEN]) . " unread messages found\n";
