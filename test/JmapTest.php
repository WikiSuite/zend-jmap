<?php
/**
 * @see       https://github.com/zendframework/zend-jmap for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-jmap/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Jmap;

use PHPUnit\Framework\TestCase;
use Zend\Jmap\ConfigProvider;
use Zend\Jmap\Jmap;
const TESTS_ZEND_JMAP_TESTMAILBOX = "zend_jmap_mailbox";
class JmapTest extends TestCase
{
    public function setUp()
    {
      if (! getenv('TESTS_ZEND_JMAP_HOST')) {
                  $this->markTestSkipped('Zend_Mail IMAP tests are not enabled');
              }
              $this->params = ['url'     => getenv('TESTS_ZEND_JMAP_HOST'),
                                     'user'     => getenv('TESTS_ZEND_JMAP_USER'),
                                     'password' => getenv('TESTS_ZEND_JMAP_PASSWORD')];
    }
    public function testCountMessages()
    {
      $mail = new Jmap($this->params);
      echo $mail->countMessages() . " messages found\n";
      $this->assertEquals(3, $mail->countMessages(), "TEMPORARY:  Mail count should be 3");

    }

}
