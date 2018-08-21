<?php
require __DIR__ . '/vendor/autoload.php';
use Zend\Mail\Storage\Imap;
require  __DIR__ . '/src/Jmap.php';
use Zend\Jmap\Jmap;
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
$uniqueIdOfFirstMessage = $mail->getUniqueId(1);
echo "Unique id of first message is $uniqueIdOfFirstMessage .\n";
$numberOfFirstMessage = $mail->getNumberByUniqueId($uniqueIdOfFirstMessage);
echo "Index of first message is $numberOfFirstMessage (it should be 1...)\n";

$folders = $mail->getFolders();
echo "Folders in account\n";
var_dump($folders);
//echo $mail->countMessages([Mail\Storage::FLAG_UNSEEN]) . " unread messages found\n";
