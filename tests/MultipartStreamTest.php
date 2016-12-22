<?php
namespace GuzzleHttp\Tests;

use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\MultipartStream;

class MultipartStreamTest extends \PHPUnit_Framework_TestCase
{
    public function testCreatesDefaultBoundary()
    {
        $b = new MultipartStream();
        $this->assertNotEmpty($b->getBoundary());
    }

    public function testCanProvideBoundary()
    {
        $b = new MultipartStream(array(), 'foo');
        $this->assertEquals('foo', $b->getBoundary());
    }

    public function testIsNotWritable()
    {
        $b = new MultipartStream();
        $this->assertFalse($b->isWritable());
    }

    public function testCanCreateEmptyStream()
    {
        $b = new MultipartStream();
        $boundary = $b->getBoundary();
        $this->assertSame("--{$boundary}--\r\n", $b->getContents());
        $this->assertSame(strlen($boundary) + 6, $b->getSize());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testValidatesFilesArrayElement()
    {
        new MultipartStream(array(array('foo' => 'bar')));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testEnsuresFileHasName()
    {
        new MultipartStream(array(array('contents' => 'bar')));
    }

    public function testSerializesFields()
    {
        $b = new MultipartStream(array(
            array(
                'name'     => 'foo',
                'contents' => 'bar'
            ),
            array(
                'name' => 'baz',
                'contents' => 'bam'
            )
        ), 'boundary');
        $this->assertEquals(
            "--boundary\r\nContent-Disposition: form-data; name=\"foo\"\r\nContent-Length: 3\r\n\r\n"
            . "bar\r\n--boundary\r\nContent-Disposition: form-data; name=\"baz\"\r\nContent-Length: 3"
            . "\r\n\r\nbam\r\n--boundary--\r\n", (string) $b);
    }

    public function testSerializesNonStringFields()
    {
        $b = new MultipartStream(array(
            array(
                'name'     => 'int',
                'contents' => (int) 1
            ),
            array(
                'name' => 'bool',
                'contents' => (boolean) false
            ),
            array(
                'name' => 'bool2',
                'contents' => (boolean) true
            ),
            array(
                'name' => 'float',
                'contents' => (float) 1.1
            )
        ), 'boundary');
        $this->assertEquals(
            "--boundary\r\nContent-Disposition: form-data; name=\"int\"\r\nContent-Length: 1\r\n\r\n"
            . "1\r\n--boundary\r\nContent-Disposition: form-data; name=\"bool\"\r\n\r\n\r\n--boundary"
            . "\r\nContent-Disposition: form-data; name=\"bool2\"\r\nContent-Length: 1\r\n\r\n"
            . "1\r\n--boundary\r\nContent-Disposition: form-data; name=\"float\"\r\nContent-Length: 3"
            . "\r\n\r\n1.1\r\n--boundary--\r\n", (string) $b);
    }

    public function testSerializesFiles()
    {
        $f1 = Psr7\FnStream::decorate(Psr7\stream_for('foo'), array(
            'getMetadata' => function () {
                return '/foo/bar.txt';
            }
        ));

        $f2 = Psr7\FnStream::decorate(Psr7\stream_for('baz'), array(
            'getMetadata' => function () {
                return '/foo/baz.jpg';
            }
        ));

        $f3 = Psr7\FnStream::decorate(Psr7\stream_for('bar'), array(
            'getMetadata' => function () {
                return '/foo/bar.gif';
            }
        ));

        $b = new MultipartStream(array(
            array(
                'name'     => 'foo',
                'contents' => $f1
            ),
            array(
                'name' => 'qux',
                'contents' => $f2
            ),
            array(
                'name'     => 'qux',
                'contents' => $f3
            ),
        ), 'boundary');

        $expected = <<<EOT
--boundary
Content-Disposition: form-data; name="foo"; filename="bar.txt"
Content-Length: 3
Content-Type: text/plain

foo
--boundary
Content-Disposition: form-data; name="qux"; filename="baz.jpg"
Content-Length: 3
Content-Type: image/jpeg

baz
--boundary
Content-Disposition: form-data; name="qux"; filename="bar.gif"
Content-Length: 3
Content-Type: image/gif

bar
--boundary--

EOT;

        $this->assertEquals($expected, str_replace("\r", '', $b));
    }

    public function testSerializesFilesWithCustomHeaders()
    {
        $f1 = Psr7\FnStream::decorate(Psr7\stream_for('foo'), array(
            'getMetadata' => function () {
                return '/foo/bar.txt';
            }
        ));

        $b = new MultipartStream(array(
            array(
                'name' => 'foo',
                'contents' => $f1,
                'headers'  => array(
                    'x-foo' => 'bar',
                    'content-disposition' => 'custom'
                )
            )
        ), 'boundary');

        $expected = <<<EOT
--boundary
x-foo: bar
content-disposition: custom
Content-Length: 3
Content-Type: text/plain

foo
--boundary--

EOT;

        $this->assertEquals($expected, str_replace("\r", '', $b));
    }

    public function testSerializesFilesWithCustomHeadersAndMultipleValues()
    {
        $f1 = Psr7\FnStream::decorate(Psr7\stream_for('foo'), array(
            'getMetadata' => function () {
                return '/foo/bar.txt';
            }
        ));

        $f2 = Psr7\FnStream::decorate(Psr7\stream_for('baz'), array(
            'getMetadata' => function () {
                return '/foo/baz.jpg';
            }
        ));

        $b = new MultipartStream(array(
            array(
                'name'     => 'foo',
                'contents' => $f1,
                'headers'  => array(
                    'x-foo' => 'bar',
                    'content-disposition' => 'custom'
                )
            ),
            array(
                'name'     => 'foo',
                'contents' => $f2,
                'headers'  => array('cOntenT-Type' => 'custom'),
            )
        ), 'boundary');

        $expected = <<<EOT
--boundary
x-foo: bar
content-disposition: custom
Content-Length: 3
Content-Type: text/plain

foo
--boundary
cOntenT-Type: custom
Content-Disposition: form-data; name="foo"; filename="baz.jpg"
Content-Length: 3

baz
--boundary--

EOT;

        $this->assertEquals($expected, str_replace("\r", '', $b));
    }
}
