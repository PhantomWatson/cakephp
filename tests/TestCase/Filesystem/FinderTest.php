<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Filesystem;

use Cake\Filesystem\Finder;
use Cake\TestSuite\TestCase;
use Iterator;
use org\bovigo\vfs\vfsStream;

/**
 * FinderTest class
 */
class FinderTest extends TestCase
{
    /**
     * Test virtual filesystem
     *
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    protected $root;

    /**
     * Setup test
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->root = vfsStream::setup('root', null, [
            'src' => [
                'Controller' => [
                    'AppController.php' => '<?php',
                    'UsersController.php' => '<?php',
                ],
                'Model' => [
                    'Entity' => [
                        'User.php' => '<?php',
                    ],
                    'Table' => [
                        'UsersTable.php' => '<?php',
                    ],
                ],
                'View' => [
                    'layout.php' => '<?php',
                ],
            ],
            'tests' => [
                'TestCase' => [
                    'Controller' => [
                        'UsersControllerTest.php' => '<?php',
                    ],
                ],
            ],
            'webroot' => [
                '.htaccess' => 'rules',
                'index.php' => '<?php',
                'css' => [
                    'style.css' => 'body {}',
                ],
                'js' => [
                    'app.js' => 'console.log();',
                ],
            ],
        ]);
    }

    public function testBasicFind(): void
    {
        $finder = new Finder();
        $files = $finder->in(vfsStream::url('root/src'))->files();

        $this->assertInstanceOf(Iterator::class, $files);

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getPathname();
        }

        $this->assertCount(5, $paths);
        $this->assertStringContainsString('AppController.php', implode(',', $paths));
        $this->assertStringContainsString('UsersController.php', implode(',', $paths));
        $this->assertStringContainsString('User.php', implode(',', $paths));
    }

    public function testMultiplePaths(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->in(vfsStream::url('root/tests'))
            ->files();

        $count = 0;
        foreach ($files as $file) {
            $count++;
        }

        $this->assertEquals(6, $count);
    }

    public function testNamePattern(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->name('*Controller.php')
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getFilename();
        }

        $this->assertCount(2, $paths);
        $this->assertContains('AppController.php', $paths);
        $this->assertContains('UsersController.php', $paths);
    }

    public function testExclude(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root'))
            ->exclude('tests')
            ->exclude('webroot')
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getPathname();
        }

        $this->assertStringNotContainsString('TestCase', implode(',', $paths));
        $this->assertStringNotContainsString('webroot', implode(',', $paths));
        $this->assertStringContainsString('src', implode(',', $paths));
    }

    public function testDepth(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->depth('== 0')
            ->files();

        $count = 0;
        foreach ($files as $file) {
            $count++;
        }

        // No files at depth 0 in src directory
        $this->assertEquals(0, $count);

        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->depth('== 1')
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getFilename();
        }

        // Should find files in Controller/ and View/
        $this->assertGreaterThan(0, count($paths));
    }

    public function testPath(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root'))
            ->path('Controller')
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getPathname();
        }

        $this->assertStringContainsString('Controller', implode(',', $paths));
        $this->assertStringNotContainsString('Model', implode(',', $paths));
    }

    public function testNotPath(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->notPath('Controller')
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getPathname();
        }

        $this->assertStringNotContainsString('Controller', implode(',', $paths));
        $this->assertStringContainsString('Model', implode(',', $paths));
    }

    public function testIgnoreHiddenFiles(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/webroot'))
            ->ignoreHiddenFiles(false)
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getFilename();
        }

        $this->assertContains('.htaccess', $paths);
    }

    public function testChaining(): void
    {
        $finder = new Finder();
        $result = $finder
            ->in(vfsStream::url('root/src'))
            ->name('*.php')
            ->exclude('View')
            ->depth('< 3');

        $this->assertInstanceOf(Finder::class, $result);
    }

    public function testGlobPatternSimpleWildcard(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root'))
            ->name('*.css')
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getFilename();
        }

        $this->assertContains('style.css', $paths);
        $this->assertCount(1, $paths);
    }

    public function testGlobPatternRecursiveWildcard(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->name('*.php')
            ->files();

        // Should find all PHP files recursively (name filters filename only)
        $count = 0;
        foreach ($files as $file) {
            $count++;
        }

        $this->assertEquals(5, $count);
    }

    public function testGlobPatternWithPath(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->name('User*.php')
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        $this->assertContains('User.php', $filenames);
        $this->assertContains('UsersTable.php', $filenames);
        $this->assertContains('UsersController.php', $filenames);
        $this->assertCount(3, $filenames);
    }

    public function testGlobPatternMultipleExtensions(): void
    {
        $finder = new Finder();

        // Test .js files
        $jsFiles = $finder
            ->in(vfsStream::url('root/webroot'))
            ->name('*.js')
            ->files();

        $count = 0;
        foreach ($jsFiles as $file) {
            $this->assertEquals('js', $file->getExtension());
            $count++;
        }
        $this->assertEquals(1, $count);

        // Test .css files
        $finder2 = new Finder();
        $cssFiles = $finder2
            ->in(vfsStream::url('root/webroot'))
            ->name('*.css')
            ->files();

        $count = 0;
        foreach ($cssFiles as $file) {
            $this->assertEquals('css', $file->getExtension());
            $count++;
        }
        $this->assertEquals(1, $count);
    }

    public function testGlobPatternWithExclude(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->name('*.php')
            ->exclude('Controller')
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getPathname();
        }

        $pathString = implode(',', $paths);
        $this->assertStringNotContainsString('Controller', $pathString);
        $this->assertStringContainsString('Model', $pathString);
    }

    public function testGlobPatternSpecificDirectory(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->path('Controller')
            ->name('*Controller.php')
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        $this->assertContains('AppController.php', $filenames);
        $this->assertContains('UsersController.php', $filenames);
        $this->assertCount(2, $filenames);
    }

    public function testPatternMethod(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root'))
            ->pattern('src/**/*.php')
            ->files();

        $count = 0;
        foreach ($files as $file) {
            $count++;
        }

        // Should find all PHP files under src/
        $this->assertEquals(5, $count);
    }

    public function testPatternMethodControllers(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root'))
            ->pattern('src/Controller/*.php')
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        $this->assertContains('AppController.php', $filenames);
        $this->assertContains('UsersController.php', $filenames);
        $this->assertCount(2, $filenames);
    }

    public function testPatternMethodMultiple(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root'))
            ->pattern('src/**/*Controller.php')
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        $this->assertContains('AppController.php', $filenames);
        $this->assertContains('UsersController.php', $filenames);
        $this->assertCount(2, $filenames);
    }

    public function testPatternMethodCssFiles(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root'))
            ->pattern('webroot/**/*.css')
            ->files();

        $count = 0;
        foreach ($files as $file) {
            $this->assertEquals('css', $file->getExtension());
            $count++;
        }

        $this->assertEquals(1, $count);
    }

    public function testPatternWithExclude(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root'))
            ->pattern('src/**/*.php')
            ->exclude('Controller')
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getPathname();
        }

        $pathString = implode(',', $paths);
        $this->assertStringNotContainsString('Controller', $pathString);
        $this->assertStringContainsString('Model', $pathString);
    }

    public function testPatternWithDepth(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->pattern('**/*.php')
            ->depth('== 1')
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        // Should find files at depth 1 (src/Controller/, src/View/)
        $this->assertContains('AppController.php', $filenames);
        $this->assertContains('UsersController.php', $filenames);
        $this->assertContains('layout.php', $filenames);
    }

    public function testMultiplePatterns(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root'))
            ->pattern('**/*.css')
            ->pattern('**/*.js')
            ->files();

        $extensions = [];
        foreach ($files as $file) {
            $extensions[] = $file->getExtension();
        }

        $this->assertContains('css', $extensions);
        $this->assertContains('js', $extensions);
        $this->assertCount(2, $extensions);
    }

    public function testComplexFiltering(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->name('*.php')
            ->path('Model')
            ->notPath('Entity')
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getPathname();
        }

        $pathString = implode(',', $paths);
        $this->assertStringContainsString('Model', $pathString);
        $this->assertStringContainsString('Table', $pathString);
        $this->assertStringNotContainsString('Entity', $pathString);
    }

    public function testMultipleInPaths(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src/Controller'))
            ->in(vfsStream::url('root/src/View'))
            ->name('*.php')
            ->files();

        $count = 0;
        foreach ($files as $file) {
            $count++;
        }

        // 2 controllers + 1 view file
        $this->assertEquals(3, $count);
    }

    public function testPatternWithMultipleInPaths(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->in(vfsStream::url('root/tests'))
            ->pattern('**/*Controller*.php')
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        $this->assertContains('AppController.php', $filenames);
        $this->assertContains('UsersController.php', $filenames);
        $this->assertContains('UsersControllerTest.php', $filenames);
    }

    public function testNoMatches(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root'))
            ->pattern('**/*.nonexistent')
            ->files();

        $count = 0;
        foreach ($files as $file) {
            $count++;
        }

        $this->assertEquals(0, $count);
    }

    public function testNameAndPathCombination(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root'))
            ->name('User*.php')
            ->path('Table')
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        $this->assertContains('UsersTable.php', $filenames);
        $this->assertNotContains('UsersController.php', $filenames);
        $this->assertCount(1, $filenames);
    }

    public function testDepthZero(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/webroot'))
            ->depth('== 0')
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        $this->assertContains('index.php', $filenames);
        $this->assertNotContains('style.css', $filenames); // In subdirectory
    }

    public function testExcludeMultiple(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root'))
            ->name('*.php')
            ->exclude('tests')
            ->exclude('webroot')
            ->exclude('View')
            ->files();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->getPathname();
        }

        $pathString = implode(',', $paths);
        $this->assertStringNotContainsString('tests', $pathString);
        $this->assertStringNotContainsString('webroot', $pathString);
        $this->assertStringNotContainsString('View', $pathString);
        $this->assertStringContainsString('Controller', $pathString);
    }

    public function testNotName(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->name('*.php')
            ->notName('*Controller.php')
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        $this->assertContains('User.php', $filenames);
        $this->assertContains('UsersTable.php', $filenames);
        $this->assertContains('layout.php', $filenames);
        $this->assertNotContains('AppController.php', $filenames);
        $this->assertNotContains('UsersController.php', $filenames);
    }

    public function testNotNameMultiple(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/webroot'))
            ->name('*.*')
            ->notName('*.php')
            ->notName('.htaccess')
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        $this->assertContains('style.css', $filenames);
        $this->assertContains('app.js', $filenames);
        $this->assertNotContains('index.php', $filenames);
        $this->assertNotContains('.htaccess', $filenames);
    }

    public function testNameWithNotName(): void
    {
        $finder = new Finder();
        $files = $finder
            ->in(vfsStream::url('root/src'))
            ->name('User*.php')
            ->notName('*Table.php')
            ->files();

        $filenames = [];
        foreach ($files as $file) {
            $filenames[] = $file->getFilename();
        }

        // Should match User*.php (User.php, UsersController.php) but exclude *Table.php (UsersTable.php)
        $this->assertContains('User.php', $filenames);
        $this->assertContains('UsersController.php', $filenames);
        $this->assertNotContains('UsersTable.php', $filenames);
        $this->assertCount(2, $filenames);
    }
}
