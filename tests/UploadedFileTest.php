<?php
namespace Hough\Tests\Psr7;

use ReflectionProperty;
use Hough\Psr7\Stream;
use Hough\Psr7\UploadedFile;

/**
 * @covers Hough\Psr7\UploadedFile
 */
class UploadedFileTest extends \PHPUnit_Framework_TestCase
{
    private $cleanup;

    public function setUp()
    {
        $this->cleanup = array();
    }

    public function tearDown()
    {
        foreach ($this->cleanup as $file) {
            if (is_scalar($file) && file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function invalidStreams()
    {
        return array(
            'null'         => array(null),
            'true'         => array(true),
            'false'        => array(false),
            'int'          => array(1),
            'float'        => array(1.1),
            'array'        => array(array('filename')),
            'object'       => array((object) array('filename')),
        );
    }

    /**
     * @dataProvider invalidStreams
     */
    public function testRaisesExceptionOnInvalidStreamOrFile($streamOrFile)
    {
        $this->setExpectedException('InvalidArgumentException');

        new UploadedFile($streamOrFile, 0, UPLOAD_ERR_OK);
    }

    public function invalidSizes()
    {
        return array(
            'null'   => array(null),
            'float'  => array(1.1),
            'array'  => array(array(1)),
            'object' => array((object) array(1)),
        );
    }

    /**
     * @dataProvider invalidSizes
     */
    public function testRaisesExceptionOnInvalidSize($size)
    {
        $this->setExpectedException('InvalidArgumentException', 'size');

        new UploadedFile(fopen('php://temp', 'wb+'), $size, UPLOAD_ERR_OK);
    }

    public function invalidErrorStatuses()
    {
        return array(
            'null'     => array(null),
            'true'     => array(true),
            'false'    => array(false),
            'float'    => array(1.1),
            'string'   => array('1'),
            'array'    => array(array(1)),
            'object'   => array((object) array(1)),
            'negative' => array(-1),
            'too-big'  => array(9),
        );
    }

    /**
     * @dataProvider invalidErrorStatuses
     */
    public function testRaisesExceptionOnInvalidErrorStatus($status)
    {
        $this->setExpectedException('InvalidArgumentException', 'status');

        new UploadedFile(fopen('php://temp', 'wb+'), 0, $status);
    }

    public function invalidFilenamesAndMediaTypes()
    {
        return array(
            'true'   => array(true),
            'false'  => array(false),
            'int'    => array(1),
            'float'  => array(1.1),
            'array'  => array(array('string')),
            'object' => array((object) array('string')),
        );
    }

    /**
     * @dataProvider invalidFilenamesAndMediaTypes
     */
    public function testRaisesExceptionOnInvalidClientFilename($filename)
    {
        $this->setExpectedException('InvalidArgumentException', 'filename');

        new UploadedFile(fopen('php://temp', 'wb+'), 0, UPLOAD_ERR_OK, $filename);
    }

    /**
     * @dataProvider invalidFilenamesAndMediaTypes
     */
    public function testRaisesExceptionOnInvalidClientMediaType($mediaType)
    {
        $this->setExpectedException('InvalidArgumentException', 'media type');

        new UploadedFile(fopen('php://temp', 'wb+'), 0, UPLOAD_ERR_OK, 'foobar.baz', $mediaType);
    }

    public function testGetStreamReturnsOriginalStreamObject()
    {
        $stream = new Stream(fopen('php://temp', 'r'));
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);

        $this->assertSame($stream, $upload->getStream());
    }

    public function testGetStreamReturnsWrappedPhpStream()
    {
        $stream = fopen('php://temp', 'wb+');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);
        $uploadStream = $upload->getStream()->detach();

        $this->assertSame($stream, $uploadStream);
    }

    public function testGetStreamReturnsStreamForFile()
    {
        $this->cleanup[] = $stream = tempnam(sys_get_temp_dir(), 'stream_file');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);
        $uploadStream = $upload->getStream();
        $r = new ReflectionProperty($uploadStream, 'filename');
        $r->setAccessible(true);

        $this->assertSame($stream, $r->getValue($uploadStream));
    }

    public function testSuccessful()
    {
        $stream = \Hough\Psr7\stream_for('Foo bar!');
        $upload = new UploadedFile($stream, $stream->getSize(), UPLOAD_ERR_OK, 'filename.txt', 'text/plain');

        $this->assertEquals($stream->getSize(), $upload->getSize());
        $this->assertEquals('filename.txt', $upload->getClientFilename());
        $this->assertEquals('text/plain', $upload->getClientMediaType());

        $this->cleanup[] = $to = tempnam(sys_get_temp_dir(), 'successful');
        $upload->moveTo($to);
        $this->assertFileExists($to);
        $this->assertEquals($stream->__toString(), file_get_contents($to));
    }

    public function invalidMovePaths()
    {
        return array(
            'null'   => array(null),
            'true'   => array(true),
            'false'  => array(false),
            'int'    => array(1),
            'float'  => array(1.1),
            'empty'  => array(''),
            'array'  => array(array('filename')),
            'object' => array((object) array('filename')),
        );
    }

    /**
     * @dataProvider invalidMovePaths
     */
    public function testMoveRaisesExceptionForInvalidPath($path)
    {
        $stream = \Hough\Psr7\stream_for('Foo bar!');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);

        $this->cleanup[] = $path;

        $this->setExpectedException('InvalidArgumentException', 'path');
        $upload->moveTo($path);
    }

    public function testMoveCannotBeCalledMoreThanOnce()
    {
        $stream = \Hough\Psr7\stream_for('Foo bar!');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);

        $this->cleanup[] = $to = tempnam(sys_get_temp_dir(), 'diac');
        $upload->moveTo($to);
        $this->assertTrue(file_exists($to));

        $this->setExpectedException('RuntimeException', 'moved');
        $upload->moveTo($to);
    }

    public function testCannotRetrieveStreamAfterMove()
    {
        $stream = \Hough\Psr7\stream_for('Foo bar!');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);

        $this->cleanup[] = $to = tempnam(sys_get_temp_dir(), 'diac');
        $upload->moveTo($to);
        $this->assertFileExists($to);

        $this->setExpectedException('RuntimeException', 'moved');
        $upload->getStream();
    }

    public function nonOkErrorStatus()
    {
        return array(
            'UPLOAD_ERR_INI_SIZE'   => array( UPLOAD_ERR_INI_SIZE ),
            'UPLOAD_ERR_FORM_SIZE'  => array( UPLOAD_ERR_FORM_SIZE ),
            'UPLOAD_ERR_PARTIAL'    => array( UPLOAD_ERR_PARTIAL ),
            'UPLOAD_ERR_NO_FILE'    => array( UPLOAD_ERR_NO_FILE ),
            'UPLOAD_ERR_NO_TMP_DIR' => array( UPLOAD_ERR_NO_TMP_DIR ),
            'UPLOAD_ERR_CANT_WRITE' => array( UPLOAD_ERR_CANT_WRITE ),
            'UPLOAD_ERR_EXTENSION'  => array( UPLOAD_ERR_EXTENSION ),
        );
    }

    /**
     * @dataProvider nonOkErrorStatus
     */
    public function testConstructorDoesNotRaiseExceptionForInvalidStreamWhenErrorStatusPresent($status)
    {
        $uploadedFile = new UploadedFile('not ok', 0, $status);
        $this->assertSame($status, $uploadedFile->getError());
    }

    /**
     * @dataProvider nonOkErrorStatus
     */
    public function testMoveToRaisesExceptionWhenErrorStatusPresent($status)
    {
        $uploadedFile = new UploadedFile('not ok', 0, $status);
        $this->setExpectedException('RuntimeException', 'upload error');
        $uploadedFile->moveTo(__DIR__ . '/' . uniqid());
    }

    /**
     * @dataProvider nonOkErrorStatus
     */
    public function testGetStreamRaisesExceptionWhenErrorStatusPresent($status)
    {
        $uploadedFile = new UploadedFile('not ok', 0, $status);
        $this->setExpectedException('RuntimeException', 'upload error');
        $uploadedFile->getStream();
    }

    public function testMoveToCreatesStreamIfOnlyAFilenameWasProvided()
    {
        $this->cleanup[] = $from = tempnam(sys_get_temp_dir(), 'copy_from');
        $this->cleanup[] = $to = tempnam(sys_get_temp_dir(), 'copy_to');

        copy(__FILE__, $from);

        $uploadedFile = new UploadedFile($from, 100, UPLOAD_ERR_OK, basename($from), 'text/plain');
        $uploadedFile->moveTo($to);

        $this->assertFileEquals(__FILE__, $to);
    }
}
