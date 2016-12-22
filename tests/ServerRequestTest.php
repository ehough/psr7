<?php
namespace GuzzleHttp\Tests\Psr7;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\UploadedFile;
use GuzzleHttp\Psr7\Uri;

/**
 * @covers GuzzleHttp\Psr7\ServerRequest
 */
class ServerRequestTest extends \PHPUnit_Framework_TestCase
{
    public function dataNormalizeFiles()
    {
        return array(
            'Single file' => array(
                array(
                    'file' => array(
                        'name' => 'MyFile.txt',
                        'type' => 'text/plain',
                        'tmp_name' => '/tmp/php/php1h4j1o',
                        'error' => '0',
                        'size' => '123'
                    )
                ),
                array(
                    'file' => new UploadedFile(
                        '/tmp/php/php1h4j1o',
                        123,
                        UPLOAD_ERR_OK,
                        'MyFile.txt',
                        'text/plain'
                    )
                )
            ),
            'Empty file' => array(
                array(
                    'image_file' => array(
                        'name' => '',
                        'type' => '',
                        'tmp_name' => '',
                        'error' => '4',
                        'size' => '0'
                    )
                ),
                array(
                    'image_file' => new UploadedFile(
                        '',
                        0,
                        UPLOAD_ERR_NO_FILE,
                        '',
                        ''
                    )
                )
            ),
            'Already Converted' => array(
                array(
                    'file' => new UploadedFile(
                        '/tmp/php/php1h4j1o',
                        123,
                        UPLOAD_ERR_OK,
                        'MyFile.txt',
                        'text/plain'
                    )
                ),
                array(
                    'file' => new UploadedFile(
                        '/tmp/php/php1h4j1o',
                        123,
                        UPLOAD_ERR_OK,
                        'MyFile.txt',
                        'text/plain'
                    )
                )
            ),
            'Already Converted array' => array(
                array(
                    'file' => array(
                        new UploadedFile(
                            '/tmp/php/php1h4j1o',
                            123,
                            UPLOAD_ERR_OK,
                            'MyFile.txt',
                            'text/plain'
                        ),
                        new UploadedFile(
                            '',
                            0,
                            UPLOAD_ERR_NO_FILE,
                            '',
                            ''
                        )
                    ),
                ),
                array(
                    'file' => array(
                        new UploadedFile(
                            '/tmp/php/php1h4j1o',
                            123,
                            UPLOAD_ERR_OK,
                            'MyFile.txt',
                            'text/plain'
                        ),
                        new UploadedFile(
                            '',
                            0,
                            UPLOAD_ERR_NO_FILE,
                            '',
                            ''
                        )
                    ),
                )
            ),
            'Multiple files' => array(
                array(
                    'text_file' => array(
                        'name' => 'MyFile.txt',
                        'type' => 'text/plain',
                        'tmp_name' => '/tmp/php/php1h4j1o',
                        'error' => '0',
                        'size' => '123'
                    ),
                    'image_file' => array(
                        'name' => '',
                        'type' => '',
                        'tmp_name' => '',
                        'error' => '4',
                        'size' => '0'
                    )
                ),
                array(
                    'text_file' => new UploadedFile(
                        '/tmp/php/php1h4j1o',
                        123,
                        UPLOAD_ERR_OK,
                        'MyFile.txt',
                        'text/plain'
                    ),
                    'image_file' => new UploadedFile(
                        '',
                        0,
                        UPLOAD_ERR_NO_FILE,
                        '',
                        ''
                    )
                )
            ),
            'Nested files' => array(
                array(
                    'file' => array(
                        'name' => array(
                            0 => 'MyFile.txt',
                            1 => 'Image.png',
                        ),
                        'type' => array(
                            0 => 'text/plain',
                            1 => 'image/png',
                        ),
                        'tmp_name' => array(
                            0 => '/tmp/php/hp9hskjhf',
                            1 => '/tmp/php/php1h4j1o',
                        ),
                        'error' => array(
                            0 => '0',
                            1 => '0',
                        ),
                        'size' => array(
                            0 => '123',
                            1 => '7349',
                        ),
                    ),
                    'nested' => array(
                        'name' => array(
                            'other' => 'Flag.txt',
                            'test' => array(
                                0 => 'Stuff.txt',
                                1 => '',
                            ),
                        ),
                        'type' => array(
                            'other' => 'text/plain',
                            'test' => array(
                                0 => 'text/plain',
                                1 => '',
                            ),
                        ),
                        'tmp_name' => array(
                            'other' => '/tmp/php/hp9hskjhf',
                            'test' => array(
                                0 => '/tmp/php/asifu2gp3',
                                1 => '',
                            ),
                        ),
                        'error' => array(
                            'other' => '0',
                            'test' => array(
                                0 => '0',
                                1 => '4',
                            ),
                        ),
                        'size' => array(
                            'other' => '421',
                            'test' => array(
                                0 => '32',
                                1 => '0',
                            )
                        )
                    ),
                ),
                array(
                    'file' => array(
                        0 => new UploadedFile(
                            '/tmp/php/hp9hskjhf',
                            123,
                            UPLOAD_ERR_OK,
                            'MyFile.txt',
                            'text/plain'
                        ),
                        1 => new UploadedFile(
                            '/tmp/php/php1h4j1o',
                            7349,
                            UPLOAD_ERR_OK,
                            'Image.png',
                            'image/png'
                        ),
                    ),
                    'nested' => array(
                        'other' => new UploadedFile(
                            '/tmp/php/hp9hskjhf',
                            421,
                            UPLOAD_ERR_OK,
                            'Flag.txt',
                            'text/plain'
                        ),
                        'test' => array(
                            0 => new UploadedFile(
                                '/tmp/php/asifu2gp3',
                                32,
                                UPLOAD_ERR_OK,
                                'Stuff.txt',
                                'text/plain'
                            ),
                            1 => new UploadedFile(
                                '',
                                0,
                                UPLOAD_ERR_NO_FILE,
                                '',
                                ''
                            ),
                        )
                    )
                )
            )
        );
    }

    /**
     * @dataProvider dataNormalizeFiles
     */
    public function testNormalizeFiles($files, $expected)
    {
        $result = ServerRequest::normalizeFiles($files);

        $this->assertEquals($expected, $result);
    }

    public function testNormalizeFilesRaisesException()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid value in files specification');

        ServerRequest::normalizeFiles(array('test' => 'something'));
    }

    public function dataGetUriFromGlobals()
    {
        $server = array(
            'PHP_SELF' => '/blog/article.php',
            'GATEWAY_INTERFACE' => 'CGI/1.1',
            'SERVER_ADDR' => 'Server IP: 217.112.82.20',
            'SERVER_NAME' => 'www.blakesimpson.co.uk',
            'SERVER_SOFTWARE' => 'Apache/2.2.15 (Win32) JRun/4.0 PHP/5.2.13',
            'SERVER_PROTOCOL' => 'HTTP/1.0',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_TIME' => 'Request start time: 1280149029',
            'QUERY_STRING' => 'id=10&user=foo',
            'DOCUMENT_ROOT' => '/path/to/your/server/root/',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'HTTP_ACCEPT_ENCODING' => 'gzip,deflate',
            'HTTP_ACCEPT_LANGUAGE' => 'en-gb,en;q=0.5',
            'HTTP_CONNECTION' => 'keep-alive',
            'HTTP_HOST' => 'www.blakesimpson.co.uk',
            'HTTP_REFERER' => 'http://previous.url.com',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-GB; rv:1.9.2.6) Gecko/20100625 Firefox/3.6.6 ( .NET CLR 3.5.30729)',
            'HTTPS' => '1',
            'REMOTE_ADDR' => '193.60.168.69',
            'REMOTE_HOST' => 'Client server\'s host name',
            'REMOTE_PORT' => '5390',
            'SCRIPT_FILENAME' => '/path/to/this/script.php',
            'SERVER_ADMIN' => 'webmaster@blakesimpson.co.uk',
            'SERVER_PORT' => '80',
            'SERVER_SIGNATURE' => 'Version signature: 5.123',
            'SCRIPT_NAME' => '/blog/article.php',
            'REQUEST_URI' => '/blog/article.php?id=10&user=foo',
        );

        return array(
            'Normal request' => array(
                'http://www.blakesimpson.co.uk/blog/article.php?id=10&user=foo',
                $server,
            ),
            'Secure request' => array(
                'https://www.blakesimpson.co.uk/blog/article.php?id=10&user=foo',
                array_merge($server, array('HTTPS' => 'on', 'SERVER_PORT' => '443')),
            ),
            'HTTP_HOST missing' => array(
                'http://www.blakesimpson.co.uk/blog/article.php?id=10&user=foo',
                array_merge($server, array('HTTP_HOST' => null)),
            ),
            'No query String' => array(
                'http://www.blakesimpson.co.uk/blog/article.php',
                array_merge($server, array('REQUEST_URI' => '/blog/article.php', 'QUERY_STRING' => '')),
            ),
            'Different port' => array(
                'http://www.blakesimpson.co.uk:8324/blog/article.php?id=10&user=foo',
                array_merge($server, array('SERVER_PORT' => '8324')),
            ),
            'Empty server variable' => array(
                '',
                array(),
            ),
        );
    }

    /**
     * @dataProvider dataGetUriFromGlobals
     */
    public function testGetUriFromGlobals($expected, $serverParams)
    {
        $_SERVER = $serverParams;

        $this->assertEquals(new Uri($expected), ServerRequest::getUriFromGlobals());
    }

    public function testFromGlobals()
    {
        $_SERVER = array(
            'PHP_SELF' => '/blog/article.php',
            'GATEWAY_INTERFACE' => 'CGI/1.1',
            'SERVER_ADDR' => 'Server IP: 217.112.82.20',
            'SERVER_NAME' => 'www.blakesimpson.co.uk',
            'SERVER_SOFTWARE' => 'Apache/2.2.15 (Win32) JRun/4.0 PHP/5.2.13',
            'SERVER_PROTOCOL' => 'HTTP/1.0',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_TIME' => 'Request start time: 1280149029',
            'QUERY_STRING' => 'id=10&user=foo',
            'DOCUMENT_ROOT' => '/path/to/your/server/root/',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'HTTP_ACCEPT_ENCODING' => 'gzip,deflate',
            'HTTP_ACCEPT_LANGUAGE' => 'en-gb,en;q=0.5',
            'HTTP_CONNECTION' => 'keep-alive',
            'HTTP_HOST' => 'www.blakesimpson.co.uk',
            'HTTP_REFERER' => 'http://previous.url.com',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-GB; rv:1.9.2.6) Gecko/20100625 Firefox/3.6.6 ( .NET CLR 3.5.30729)',
            'HTTPS' => '1',
            'REMOTE_ADDR' => '193.60.168.69',
            'REMOTE_HOST' => 'Client server\'s host name',
            'REMOTE_PORT' => '5390',
            'SCRIPT_FILENAME' => '/path/to/this/script.php',
            'SERVER_ADMIN' => 'webmaster@blakesimpson.co.uk',
            'SERVER_PORT' => '80',
            'SERVER_SIGNATURE' => 'Version signature: 5.123',
            'SCRIPT_NAME' => '/blog/article.php',
            'REQUEST_URI' => '/blog/article.php?id=10&user=foo',
        );

        $_COOKIE = array(
            'logged-in' => 'yes!'
        );

        $_POST = array(
            'name' => 'Pesho',
            'email' => 'pesho@example.com',
        );

        $_GET = array(
            'id' => 10,
            'user' => 'foo',
        );

        $_FILES = array(
            'file' => array(
                'name' => 'MyFile.txt',
                'type' => 'text/plain',
                'tmp_name' => '/tmp/php/php1h4j1o',
                'error' => UPLOAD_ERR_OK,
                'size' => 123,
            )
        );

        $server = ServerRequest::fromGlobals();

        $this->assertEquals('POST', $server->getMethod());
        $this->assertEquals(array('Host' => array('www.blakesimpson.co.uk')), $server->getHeaders());
        $this->assertEquals('', (string) $server->getBody());
        $this->assertEquals('1.0', $server->getProtocolVersion());
        $this->assertEquals($_COOKIE, $server->getCookieParams());
        $this->assertEquals($_POST, $server->getParsedBody());
        $this->assertEquals($_GET, $server->getQueryParams());

        $this->assertEquals(
            new Uri('http://www.blakesimpson.co.uk/blog/article.php?id=10&user=foo'),
            $server->getUri()
        );

        $expectedFiles = array(
            'file' => new UploadedFile(
                '/tmp/php/php1h4j1o',
                123,
                UPLOAD_ERR_OK,
                'MyFile.txt',
                'text/plain'
            ),
        );

        $this->assertEquals($expectedFiles, $server->getUploadedFiles());
    }

    public function testUploadedFiles()
    {
        $request1 = new ServerRequest('GET', '/');

        $files = array(
            'file' => new UploadedFile('test', 123, UPLOAD_ERR_OK)
        );

        $request2 = $request1->withUploadedFiles($files);

        $this->assertNotSame($request2, $request1);
        $this->assertSame(array(), $request1->getUploadedFiles());
        $this->assertSame($files, $request2->getUploadedFiles());
    }

    public function testServerParams()
    {
        $params = array('name' => 'value');

        $request = new ServerRequest('GET', '/', array(), null, '1.1', $params);
        $this->assertSame($params, $request->getServerParams());
    }

    public function testCookieParams()
    {
        $request1 = new ServerRequest('GET', '/');

        $params = array('name' => 'value');

        $request2 = $request1->withCookieParams($params);

        $this->assertNotSame($request2, $request1);
        $this->assertEmpty($request1->getCookieParams());
        $this->assertSame($params, $request2->getCookieParams());
    }

    public function testQueryParams()
    {
        $request1 = new ServerRequest('GET', '/');

        $params = array('name' => 'value');

        $request2 = $request1->withQueryParams($params);

        $this->assertNotSame($request2, $request1);
        $this->assertEmpty($request1->getQueryParams());
        $this->assertSame($params, $request2->getQueryParams());
    }

    public function testParsedBody()
    {
        $request1 = new ServerRequest('GET', '/');

        $params = array('name' => 'value');

        $request2 = $request1->withParsedBody($params);

        $this->assertNotSame($request2, $request1);
        $this->assertEmpty($request1->getParsedBody());
        $this->assertSame($params, $request2->getParsedBody());
    }

    public function testAttributes()
    {
        $request1 = new ServerRequest('GET', '/');

        $request2 = $request1->withAttribute('name', 'value');
        $request3 = $request2->withAttribute('other', 'otherValue');
        $request4 = $request3->withoutAttribute('other');
        $request5 = $request3->withoutAttribute('unknown');

        $this->assertNotSame($request2, $request1);
        $this->assertNotSame($request3, $request2);
        $this->assertNotSame($request4, $request3);
        $this->assertNotSame($request5, $request4);

        $this->assertEmpty($request1->getAttributes());
        $this->assertEmpty($request1->getAttribute('name'));
        $this->assertEquals(
            'something',
            $request1->getAttribute('name', 'something'),
            'Should return the default value'
        );

        $this->assertEquals('value', $request2->getAttribute('name'));
        $this->assertEquals(array('name' => 'value'), $request2->getAttributes());
        $this->assertEquals(array('name' => 'value', 'other' => 'otherValue'), $request3->getAttributes());
        $this->assertEquals(array('name' => 'value'), $request4->getAttributes());
    }

    public function testNullAttribute()
    {
        $request = (new ServerRequest('GET', '/'))->withAttribute('name', null);

        $this->assertSame(array('name' => null), $request->getAttributes());
        $this->assertNull($request->getAttribute('name', 'different-default'));

        $requestWithoutAttribute = $request->withoutAttribute('name');

        $this->assertSame(array(), $requestWithoutAttribute->getAttributes());
        $this->assertSame('different-default', $requestWithoutAttribute->getAttribute('name', 'different-default'));
    }
}
