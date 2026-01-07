<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use Cake\Utility\Filesystem;
use org\bovigo\vfs\vfsStream;
use RecursiveIteratorIterator;

/**
 * Filesystem class
 */
class FilesystemTest extends TestCase
{
    protected $vfs;

    protected $fs;

    protected $vfsPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vfs = vfsStream::setup('root');
        $this->vfsPath = vfsStream::url('root');

        $this->fs = new Filesystem();

        clearstatcache();
    }

    public function testMkdir(): void
    {
        $path = $this->vfsPath . DS . 'tests' . DS . 'first' . DS . 'second' . DS . 'third';
        $this->fs->mkdir($path);
        $this->assertTrue(is_dir($path));
    }

    public function testDumpFile(): void
    {
        $path = $this->vfsPath . DS . 'foo.txt';

        $this->fs->dumpFile($path, 'bar');
        $this->assertEquals(file_get_contents($path), 'bar');

        $path = $this->vfsPath . DS . 'empty.txt';
        $this->fs->dumpFile($path, '');
        $this->assertSame(file_get_contents($path), '');
    }

    public function testCopyDir(): void
    {
        $return = $this->fs->copyDir(WWW_ROOT, $this->vfsPath . DS . 'dest');

        $this->assertTrue($return);
    }

    public function testDeleteDir(): void
    {
        $structure = [
            'Core' => [
                'AbstractFactory' => [
                    'test.php' => 'some text content',
                    'other.php' => 'Some more text content',
                    'Invalid.csv' => 'Something else',
                ],
                'AnEmptyFolder' => [],
                'badlocation.php' => 'some bad content',
            ],
        ];
        vfsStream::create($structure);

        $return = $this->fs->deleteDir($this->vfsPath . DS . 'Core');

        $this->assertTrue($return);
    }

    /**
     * Tests deleteDir() on directory that contains symlinks
     */
    public function testDeleteDirWithLinks(): void
    {
        $path = TMP . 'fs_links_test';
        // phpcs:ignore
        @mkdir($path);
        $target = $path . DS . 'target';
        // phpcs:ignore
        @mkdir($target);

        $link = $path . DS . 'link';
        // phpcs:ignore
        @symlink($target, $link);

        $this->assertTrue($this->fs->deleteDir($path));
        $this->assertFalse(file_exists($link));
    }

    public function testCreateRecursiveIteratorBasic(): void
    {
        $structure = [
            'file1.php' => 'content',
            'file2.txt' => 'content',
            'subdir' => [
                'file3.php' => 'content',
                'file4.txt' => 'content',
            ],
        ];
        vfsStream::create($structure);

        $iterator = $this->fs->createRecursiveIterator($this->vfsPath);

        $this->assertInstanceOf(RecursiveIteratorIterator::class, $iterator);

        $files = [];
        foreach ($iterator as $file) {
            $files[] = $file->getFilename();
        }

        $this->assertContains('file1.php', $files);
        $this->assertContains('file2.txt', $files);
        $this->assertContains('file3.php', $files);
        $this->assertContains('file4.txt', $files);
    }

    public function testCreateRecursiveIteratorSkipsHiddenDirs(): void
    {
        $structure = [
            'visible.php' => 'content',
            '.hidden' => [
                'secret.php' => 'should not see this',
            ],
            'subdir' => [
                'visible2.php' => 'content',
            ],
        ];
        vfsStream::create($structure);

        $iterator = $this->fs->createRecursiveIterator($this->vfsPath, includeHiddenDirs: false);

        $files = [];
        foreach ($iterator as $file) {
            $files[] = $file->getFilename();
        }

        $this->assertContains('visible.php', $files);
        $this->assertContains('visible2.php', $files);
        $this->assertNotContains('secret.php', $files);
        $this->assertNotContains('.hidden', $files);
    }

    public function testCreateRecursiveIteratorWithCustomFilter(): void
    {
        $structure = [
            'file1.php' => 'content',
            'file2.txt' => 'content',
            'subdir' => [
                'file3.php' => 'content',
                'file4.txt' => 'content',
            ],
        ];
        vfsStream::create($structure);

        // Filter to only include .php files
        // Note: Must allow directories to pass for recursion to work
        $filter = fn($file) => $file->isDir() || $file->getExtension() === 'php';
        $iterator = $this->fs->createRecursiveIterator(
            $this->vfsPath,
            customFilter: $filter,
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getFilename();
            }
        }

        $this->assertContains('file1.php', $files);
        $this->assertContains('file3.php', $files);
        $this->assertNotContains('file2.txt', $files);
        $this->assertNotContains('file4.txt', $files);
    }

    public function testCreateRecursiveIteratorWithDifferentModes(): void
    {
        $structure = [
            'file1.php' => 'content',
            'subdir' => [
                'file2.php' => 'content',
            ],
        ];
        vfsStream::create($structure);

        // Test LEAVES_ONLY mode
        $iterator = $this->fs->createRecursiveIterator(
            $this->vfsPath,
            mode: RecursiveIteratorIterator::LEAVES_ONLY,
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getFilename();
            }
        }

        $this->assertContains('file1.php', $files);
        $this->assertContains('file2.php', $files);
    }

    public function testFindRecursiveStillWorks(): void
    {
        $structure = [
            'file1.php' => 'content',
            'file2.txt' => 'content',
            '.hidden' => [
                'secret.php' => 'should not see this',
            ],
            'subdir' => [
                'file3.php' => 'content',
            ],
        ];
        vfsStream::create($structure);

        $iterator = $this->fs->findRecursive($this->vfsPath);

        $files = [];
        foreach ($iterator as $file) {
            $files[] = $file->getFilename();
        }

        $this->assertContains('file1.php', $files);
        $this->assertContains('file2.txt', $files);
        $this->assertContains('file3.php', $files);
        // Hidden directories should be skipped
        $this->assertNotContains('.hidden', $files);
        $this->assertNotContains('secret.php', $files);
    }

    public function testCreateRecursiveIteratorAllowsHiddenDirs(): void
    {
        $structure = [
            'visible.php' => 'content',
            '.hidden' => [
                'secret.php' => 'content in hidden dir',
            ],
        ];
        vfsStream::create($structure);

        $iterator = $this->fs->createRecursiveIterator($this->vfsPath, includeHiddenDirs: true);

        $files = [];
        foreach ($iterator as $file) {
            $files[] = $file->getFilename();
        }

        $this->assertContains('visible.php', $files);
        $this->assertContains('.hidden', $files);
        $this->assertContains('secret.php', $files);
    }
}
