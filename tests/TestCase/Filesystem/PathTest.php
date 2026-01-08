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

use Cake\Filesystem\Path;
use Cake\TestSuite\TestCase;

/**
 * Path test case
 */
class PathTest extends TestCase
{
    public function testNormalize(): void
    {
        $this->assertSame('path/to/file', Path::normalize('path\to\file'));
        $this->assertSame('path/to/file', Path::normalize('path/to/file'));
        $this->assertSame('C:/Windows/System', Path::normalize('C:\Windows\System'));
    }

    public function testNormalizeWithTrailing(): void
    {
        $this->assertSame('path/to/dir', Path::normalize('path/to/dir/'));
        $this->assertSame('path/to/dir/', Path::normalize('path/to/dir/', trailing: true));
        $this->assertSame('path/to/dir/', Path::normalize('path/to/dir', trailing: true));
        $this->assertSame('/', Path::normalize('/', trailing: true));
        $this->assertSame('', Path::normalize('/'));
    }

    public function testMakeRelative(): void
    {
        $this->assertSame('src/Model/Table.php', Path::makeRelative('/var/www/src/Model/Table.php', '/var/www'));
        $this->assertSame('src/file.php', Path::makeRelative('/var/www/src/file.php', '/var/www/'));
        $this->assertSame('', Path::makeRelative('/var/www', '/var/www'));

        // Cross-platform
        $this->assertSame('src/file.php', Path::makeRelative('C:\project\src\file.php', 'C:\project'));

        // Going up directories
        $this->assertSame('../other/path', Path::makeRelative('/var/other/path', '/var/www'));
    }

    public function testJoin(): void
    {
        // Basic cases
        $this->assertSame('path/to/file', Path::join('path', 'to', 'file'));
        $this->assertSame('/absolute/path/file', Path::join('/absolute', 'path', 'file'));
        $this->assertSame('', Path::join());
        $this->assertSame('path', Path::join('path'));
        $this->assertSame('path/file', Path::join('path', '', 'file'));

        // Slash combinations
        $this->assertSame('path/to/file', Path::join('path/', 'to', 'file'));
        $this->assertSame('path/to/file', Path::join('path', 'to/', 'file'));
        $this->assertSame('path/to/file', Path::join('path/', 'to/', 'file'));
        $this->assertSame('path/to/file', Path::join('path/', '/to/', '/file'));

        // Absolute paths
        $this->assertSame('/path/to/file', Path::join('/', 'path', 'to', 'file'));
        $this->assertSame('/path/to/file', Path::join('/', '/path', 'to', 'file'));

        // Natural trailing slashes
        $this->assertSame('/', Path::join('/', '/'));
        $this->assertSame('/path/to/file/', Path::join('/path', 'to', 'file/'));
        $this->assertSame('/path/to/file/', Path::join('/path', 'to', 'file', '/'));

        // Windows-style backslashes
        $this->assertSame('path\\to\\file', Path::join('path\\', '\\to\\', '\\file'));
        $this->assertSame('/path/to\\file', Path::join('/', '\\path', 'to', '\\file'));
    }

    public function testJoinWithTrailing(): void
    {
        // Test empty cases with trailing
        $this->assertSame('', Path::join(false));
        $this->assertSame('/', Path::join(true));
        $this->assertSame('/', Path::join('/', true));

        // Test adding trailing slash (true)
        $this->assertSame('/path/to/file/', Path::join('/path', 'to', 'file', true));
        $this->assertSame('/path/to/file/', Path::join('/path', 'to', 'file/', '/', true));

        // Test removing trailing slash (false)
        $this->assertSame('', Path::join('/', false));
        $this->assertSame('/path/to/file', Path::join('/path', 'to', 'file/', false));
        $this->assertSame('/path/to/file', Path::join('/path', 'to', 'file/', '/', false));

        // Test null (ignore trailing slashes)
        $this->assertSame('', Path::join(null));
        $this->assertSame('/path/to/file/', Path::join('/path', 'to', 'file/', null));
        $this->assertSame('/path/to/file', Path::join('/path', 'to', 'file', null));

        // Test Windows backslashes with trailing control
        $this->assertSame('/path\\to\\file/', Path::join('/', 'path', '\\to\\', 'file', true));
        $this->assertSame('/path\\to\\file\\', Path::join('/', 'path', '\\to\\', 'file', '\\', true));
        $this->assertSame('/path\\to\\file', Path::join('/', 'path', '\\to\\', 'file', false));
        $this->assertSame('/path\\to\\file', Path::join('/', 'path', '\\to\\', 'file', '\\', false));
    }

    public function testMatches(): void
    {
        $this->assertTrue(Path::matches('*.php', 'file.php'));
        $this->assertFalse(Path::matches('*.php', 'file.txt'));
        $this->assertTrue(Path::matches('src/**/*.php', 'src/Model/Table.php'));
        $this->assertTrue(Path::matches('vendor/**', 'vendor/lib/file.php'));
        $this->assertFalse(Path::matches('tests/**', 'src/file.php'));
        $this->assertTrue(Path::matches('src/*/Table.php', 'src/Model/Table.php'));
        $this->assertTrue(Path::matches('**/file.php', 'deep/nested/path/file.php'));
    }
}
