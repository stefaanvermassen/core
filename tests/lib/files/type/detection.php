<?php
/**
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Files\Type;

use \OC\Files\Type\Detection;

class DetectionTest extends \Test\TestCase {

	public function testDetect() {
		$detection = new Detection(\OC::$server->getURLGenerator(), \OC::$SERVERROOT . '/resources/config/');
		$dir = \OC::$SERVERROOT.'/tests/data';

		$result = $detection->detect($dir."/");
		$expected = 'httpd/unix-directory';
		$this->assertEquals($expected, $result);

		$result = $detection->detect($dir."/data.tar.gz");
		$expected = 'application/x-gzip';
		$this->assertEquals($expected, $result);

		$result = $detection->detect($dir."/data.zip");
		$expected = 'application/zip';
		$this->assertEquals($expected, $result);

		$result = $detection->detect($dir."/testimagelarge.svg");
		$expected = 'image/svg+xml';
		$this->assertEquals($expected, $result);

		$result = $detection->detect($dir."/testimage.png");
		$expected = 'image/png';
		$this->assertEquals($expected, $result);
	}

	public function testGetSecureMimeType() {
		$detection = new Detection(\OC::$server->getURLGenerator(), \OC::$SERVERROOT . '/resources/config/');

		$result = $detection->getSecureMimeType('image/svg+xml');
		$expected = 'text/plain';
		$this->assertEquals($expected, $result);

		$result = $detection->getSecureMimeType('image/png');
		$expected = 'image/png';
		$this->assertEquals($expected, $result);
	}

	public function testDetectPath() {
		$detection = new Detection(\OC::$server->getURLGenerator(), \OC::$SERVERROOT . '/resources/config/');

		$this->assertEquals('text/plain', $detection->detectPath('foo.txt'));
		$this->assertEquals('image/png', $detection->detectPath('foo.png'));
		$this->assertEquals('image/png', $detection->detectPath('foo.bar.png'));
		$this->assertEquals('application/octet-stream', $detection->detectPath('.png'));
		$this->assertEquals('application/octet-stream', $detection->detectPath('foo'));
		$this->assertEquals('application/octet-stream', $detection->detectPath(''));
	}

	public function testDetectString() {
		if (\OC_Util::runningOnWindows()) {
			$this->markTestSkipped('[Windows] Strings have mimetype application/octet-stream on Windows');
		}

		$detection = new Detection(\OC::$server->getURLGenerator(), \OC::$SERVERROOT . '/resources/config/');

		$result = $detection->detectString("/data/data.tar.gz");
		$expected = 'text/plain; charset=us-ascii';
		$this->assertEquals($expected, $result);
	}

	public function testMimeTypeIcon() {
		if (!class_exists('org\\bovigo\\vfs\\vfsStream')) {
			$this->markTestSkipped('Package vfsStream not installed');
		}
		$confDir = \org\bovigo\vfs\vfsStream::setup();
		$mimetypealiases_dist = \org\bovigo\vfs\vfsStream::newFile('mimetypealiases.dist.json')->at($confDir);

		//Empty alias file
		$mimetypealiases_dist->setContent(json_encode([], JSON_FORCE_OBJECT));


		/*
		 * Test dir mimetype
		 */

		//Mock UrlGenerator
		$urlGenerator = $this->getMockBuilder('\OCP\IURLGenerator')
			->disableOriginalConstructor()
			->getMock();

		//Only call the url generator once
		$urlGenerator->expects($this->once())
			->method('imagePath')
			->with($this->equalTo('core'), $this->equalTo('filetypes/folder.png'))
			->willReturn('folder.svg');

		$detection = new Detection($urlGenerator, $confDir->url());
		$mimeType = $detection->mimeTypeIcon('dir');
		$this->assertEquals('folder.svg', $mimeType);


		/*
		 * Test dir-shareed mimetype
		 */
		//Mock UrlGenerator
		$urlGenerator = $this->getMockBuilder('\OCP\IURLGenerator')
			->disableOriginalConstructor()
			->getMock();

		//Only call the url generator once
		$urlGenerator->expects($this->once())
			->method('imagePath')
			->with($this->equalTo('core'), $this->equalTo('filetypes/folder-shared.png'))
			->willReturn('folder-shared.svg');

		$detection = new Detection($urlGenerator, $confDir->url());
		$mimeType = $detection->mimeTypeIcon('dir-shared');
		$this->assertEquals('folder-shared.svg', $mimeType);


		/*
		 * Test dir external
		 */

		//Mock UrlGenerator
		$urlGenerator = $this->getMockBuilder('\OCP\IURLGenerator')
			->disableOriginalConstructor()
			->getMock();

		//Only call the url generator once
		$urlGenerator->expects($this->once())
			->method('imagePath')
			->with($this->equalTo('core'), $this->equalTo('filetypes/folder-external.png'))
			->willReturn('folder-external.svg');

		$detection = new Detection($urlGenerator, $confDir->url());
		$mimeType = $detection->mimeTypeIcon('dir-external');
		$this->assertEquals('folder-external.svg', $mimeType);


		/*
		 * Test complete mimetype
		 */

		//Mock UrlGenerator
		$urlGenerator = $this->getMockBuilder('\OCP\IURLGenerator')
			->disableOriginalConstructor()
			->getMock();

		//Only call the url generator once
		$urlGenerator->expects($this->once())
			->method('imagePath')
			->with($this->equalTo('core'), $this->equalTo('filetypes/my-type.png'))
			->willReturn('my-type.svg');

		$detection = new Detection($urlGenerator, $confDir->url());
		$mimeType = $detection->mimeTypeIcon('my-type');
		$this->assertEquals('my-type.svg', $mimeType);


		/*
		 * Test subtype
		 */

		//Mock UrlGenerator
		$urlGenerator = $this->getMockBuilder('\OCP\IURLGenerator')
			->disableOriginalConstructor()
			->getMock();

		//Only call the url generator once
		$urlGenerator->expects($this->exactly(2))
			->method('imagePath')
			->withConsecutive(
				[$this->equalTo('core'), $this->equalTo('filetypes/my-type.png')],
				[$this->equalTo('core'), $this->equalTo('filetypes/my.png')]
			)
			->will($this->returnCallback(
				function($appName, $file) {
					if ($file === 'filetypes/my.png') {
						return 'my.svg';
					}
					throw new \RuntimeException();
				}
			));

		$detection = new Detection($urlGenerator, $confDir->url());
		$mimeType = $detection->mimeTypeIcon('my-type');
		$this->assertEquals('my.svg', $mimeType);


		/*
		 * Test default mimetype
		 */

		//Mock UrlGenerator
		$urlGenerator = $this->getMockBuilder('\OCP\IURLGenerator')
			->disableOriginalConstructor()
			->getMock();

		//Only call the url generator once
		$urlGenerator->expects($this->exactly(3))
			->method('imagePath')
			->withConsecutive(
				[$this->equalTo('core'), $this->equalTo('filetypes/foo-bar.png')],
				[$this->equalTo('core'), $this->equalTo('filetypes/foo.png')],
				[$this->equalTo('core'), $this->equalTo('filetypes/file.png')]
			)
			->will($this->returnCallback(
				function($appName, $file) {
					if ($file === 'filetypes/file.png') {
						return 'file.svg';
					}
					throw new \RuntimeException();
				}
			));

		$detection = new Detection($urlGenerator, $confDir->url());
		$mimeType = $detection->mimeTypeIcon('foo-bar');
		$this->assertEquals('file.svg', $mimeType);

		/*
		 * Test chaching
		 */

		//Mock UrlGenerator
		$urlGenerator = $this->getMockBuilder('\OCP\IURLGenerator')
			->disableOriginalConstructor()
			->getMock();

		//Only call the url generator once
		$urlGenerator->expects($this->once())
			->method('imagePath')
			->with($this->equalTo('core'), $this->equalTo('filetypes/foo-bar.png'))
			->willReturn('foo-bar.svg');

		$detection = new Detection($urlGenerator, $confDir->url());
		$mimeType = $detection->mimeTypeIcon('foo-bar');
		$this->assertEquals('foo-bar.svg', $mimeType);
		$mimeType = $detection->mimeTypeIcon('foo-bar');
		$this->assertEquals('foo-bar.svg', $mimeType);



		/*
		 * Test aliases
		 */

		//Put alias
		$mimetypealiases_dist->setContent(json_encode(['foo' => 'foobar/baz'], JSON_FORCE_OBJECT));

		//Mock UrlGenerator
		$urlGenerator = $this->getMockBuilder('\OCP\IURLGenerator')
			->disableOriginalConstructor()
			->getMock();

		//Only call the url generator once
		$urlGenerator->expects($this->once())
			->method('imagePath')
			->with($this->equalTo('core'), $this->equalTo('filetypes/foobar-baz.png'))
			->willReturn('foobar-baz.svg');

		$detection = new Detection($urlGenerator, $confDir->url());
		$mimeType = $detection->mimeTypeIcon('foo');
		$this->assertEquals('foobar-baz.svg', $mimeType);
	}
}
