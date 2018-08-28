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

const TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL = "ZEND_JMAP_ROOTTESTMAILBOX";
const TESTS_ZEND_JMAP_TESTMAILBOX_LOCAL = "ZEND_JMAP_LOCALTESTMAILBOX";
class JmapTest extends TestCase
{
    public static $jmap;
    public static function setUpBeforeClass()
    {
        if (! getenv('TESTS_ZEND_JMAP_HOST')) {
            $this->markTestSkipped('Zend_Mail IMAP tests are not enabled');
        }
        $params = ['url'     => getenv('TESTS_ZEND_JMAP_HOST'),
                       'user'     => getenv('TESTS_ZEND_JMAP_USER'),
                       'password' => getenv('TESTS_ZEND_JMAP_PASSWORD')];
        self::$jmap = new Jmap($params);
    }

    public function setUp()
    {
        try {
            self::deleteFoldersRecursive(TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL);
        } catch (\Zend\Mail\Storage\Exception\InvalidArgumentException $e) {
        }
        self::$jmap->createFolder(TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL, null);
    }
    public function tearDown()
    {
        try {
            self::deleteFoldersRecursive(TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL);
        } catch (Exception $e) {
            echo 'Exception in tearDown: ',  $e->getMessage(), "\n";
        }
    }
    private static function deleteFoldersRecursive($folder)
    {
        //echo "deleteFoldersRecursive called on  $folder\n";
        if (empty($folder)) {
            throw new \Exception('Refusing to delete root folder');
        }
        self::$jmap->disableDebug();
        $folder = self::$jmap->getFolders($folder);

        $iterator = new \RecursiveIteratorIterator($folder, \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($iterator as $key => $value) {
            self::$jmap->removeFolder($value);
        }
        self::$jmap->removeFolder($folder); //Remove parent, as it's not traversed.
    }
    private function countFolders()
    {
        $iterator = new \RecursiveIteratorIterator(self::$jmap->getFolders(), \RecursiveIteratorIterator::SELF_FIRST);
        $count = 0;
        foreach ($iterator as $key => $value) {
            $count++;
        }
        return $count;
    }

    public function testCreateExistingFolder()
    {
        self::$jmap->createFolder(TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL.'/subfolder');
        $folderCountBefore = $this->countFolders();
        try {
            $this->expectException('Zend\Mail\Storage\Exception\InvalidArgumentException');
            self::$jmap->createFolder(TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL.'/subfolder');
        } finally {
            $folderCountAfter = $this->countFolders();
            $this->assertEquals($folderCountBefore, $folderCountAfter);
        }
    }

    public function testCreateFolder()
    {
        //Setup parent folder
        //self::$jmap->enableDebug();
        self::$jmap->createFolder(TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL.'/subfolder');
        $folderCountBefore = $this->countFolders();
        self::$jmap->createFolder(TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL.'/subfolder/test1');
        self::$jmap->createFolder('test2', TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL.'/subfolder');
        self::$jmap->createFolder('test3', self::$jmap->getFolders(TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL)->subfolder);
        self::$jmap->getFolders()->{TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL}->subfolder->test1;
        self::$jmap->getFolders()->{TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL}->subfolder->test2;
        self::$jmap->getFolders()->{TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL}->subfolder->test3;
        $folderCountAfter = $this->countFolders();
        $this->assertEquals($folderCountBefore+3, $folderCountAfter);
    }
    public function testCreateFolderNoName()
    {
        $this->expectException('Zend\Mail\Storage\Exception\InvalidArgumentException');
        self::$jmap->createFolder("", null);
    }
    public function testCreateFolderNonexistentParent()
    {
        $this->expectException('Zend\Mail\Storage\Exception\InvalidArgumentException');
        self::$jmap->createFolder("subfolder", "SomeRandomString");
    }
    public function testCreateFolderOnTheFlyParentCreation()
    {
        $folderCountBefore = $this->countFolders();
        self::$jmap->createFolder(TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL.'/subfolder/test1');
        $createdFolder = self::$jmap->getFolders(TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL.'/subfolder/test1');
        $this->assertEquals($createdFolder->getGlobalName(), TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL.'/subfolder/test1');
        $folderCountAfter = $this->countFolders();
        $this->assertEquals($folderCountBefore+2, $folderCountAfter);
    }
    public function testRemoveFolder()
    {
        self::$jmap->createFolder(TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL.'/subfolder');
        $folderCountBefore = $this->countFolders();
        self::$jmap->removeFolder(TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL.'/subfolder');
        $folderCountAfter = $this->countFolders();
        $this->assertEquals($folderCountBefore-1, $folderCountAfter);
    }
    public function testRemoveNonexistentFolder()
    {
        $this->expectException('\Zend\Mail\Storage\Exception\InvalidArgumentException');
        self::$jmap->removeFolder(TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL.'/SomeRandomString');
    }
    public function testRenameFolderSameParent()
    {
        self::$jmap->createFolder(TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL.'/test1');
        $folderCountBefore = $this->countFolders();
        self::$jmap->renameFolder(TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL.'/test1', TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL.'/test2');
        $movedFolder = self::$jmap->getFolders(TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL.'/test2');
        $folderCountAfter = $this->countFolders();
        $this->assertEquals($folderCountBefore, $folderCountAfter);
    }
    public function testRenameFolderDifferentParent()
    {
        self::$jmap->createFolder(TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL.'/test1');
        self::$jmap->createFolder(TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL.'/subfolder');
        $folderCountBefore = $this->countFolders();
        self::$jmap->renameFolder(TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL.'/test1', TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL.'/subfolder/test1');
        $movedFolder = self::$jmap->getFolders(TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL.'/subfolder/test1');
        $folderCountAfter = $this->countFolders();
        $this->assertEquals($folderCountBefore, $folderCountAfter);
    }
    public function testRenameFolderNonexistentParent()
    {
        self::$jmap->createFolder(TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL.'/test1');
        $folderCountBefore = $this->countFolders();
        self::$jmap->renameFolder(TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL.'/test1', TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL.'/subfolder/test1');
        $movedFolder = self::$jmap->getFolders(TESTS_ZEND_JMAP_TESTMAILBOX_GLOBAL.'/subfolder/test1');
        $folderCountAfter = $this->countFolders();
        $this->assertEquals($folderCountBefore+1, $folderCountAfter);
    }
    public function testCountMessages()
    {
        $this->assertEquals(3, self::$jmap->countMessages(), "TEMPORARY:  Mail count should be 3");
    }
}
