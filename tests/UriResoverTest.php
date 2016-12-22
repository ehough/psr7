<?php
namespace Hough\Tests\Psr7;

use Hough\Psr7\Uri;
use Hough\Psr7\UriResolver;

/**
 * @covers Hough\Psr7\UriResolver
 */
class UriResolverTest extends \PHPUnit_Framework_TestCase
{
    const RFC3986_BASE = 'http://a/b/c/d;p?q';

    /**
     * @dataProvider getResolveTestCases
     */
    public function testResolveUri($base, $rel, $expectedTarget)
    {
        $baseUri = new Uri($base);
        $targetUri = UriResolver::resolve($baseUri, new Uri($rel));

        $this->assertInstanceOf('Psr\Http\Message\UriInterface', $targetUri);
        $this->assertSame($expectedTarget, (string) $targetUri);
        // This ensures there are no test cases that only work in the resolve() direction but not the
        // opposite via relativize(). This can happen when both base and rel URI are relative-path
        // references resulting in another relative-path URI.
        $this->assertSame($expectedTarget, (string) UriResolver::resolve($baseUri, $targetUri));
    }

    /**
     * @dataProvider getResolveTestCases
     */
    public function testRelativizeUri($base, $expectedRelativeReference, $target)
    {
        $baseUri = new Uri($base);
        $relativeUri = UriResolver::relativize($baseUri, new Uri($target));

        $this->assertInstanceOf('Psr\Http\Message\UriInterface', $relativeUri);
        // There are test-cases with too many dot-segments and relative references that are equal like "." == "./".
        // So apart from the same-as condition, this alternative success condition is necessary.
        $this->assertTrue(
            $expectedRelativeReference === (string) $relativeUri
            || $target === (string) UriResolver::resolve($baseUri, $relativeUri),
            sprintf(
                '"%s" is not the correct relative reference as it does not resolve to the target URI from the base URI',
                (string) $relativeUri
            )
        );
    }

    /**
     * @dataProvider getRelativizeTestCases
     */
    public function testRelativizeUriWithUniqueTests($base, $target, $expectedRelativeReference)
    {
        $baseUri = new Uri($base);
        $targetUri = new Uri($target);
        $relativeUri = UriResolver::relativize($baseUri, $targetUri);

        $this->assertInstanceOf('Psr\Http\Message\UriInterface', $relativeUri);
        $this->assertSame($expectedRelativeReference, (string) $relativeUri);

        $this->assertSame((string) UriResolver::resolve($baseUri, $targetUri), (string) UriResolver::resolve($baseUri, $relativeUri));
    }

    public function getResolveTestCases()
    {
        return array(
            array(self::RFC3986_BASE, 'g:h',           'g:h'),
            array(self::RFC3986_BASE, 'g',             'http://a/b/c/g'),
            array(self::RFC3986_BASE, './g',           'http://a/b/c/g'),
            array(self::RFC3986_BASE, 'g/',            'http://a/b/c/g/'),
            array(self::RFC3986_BASE, '/g',            'http://a/g'),
            array(self::RFC3986_BASE, '//g',           'http://g'),
            array(self::RFC3986_BASE, '?y',            'http://a/b/c/d;p?y'),
            array(self::RFC3986_BASE, 'g?y',           'http://a/b/c/g?y'),
            array(self::RFC3986_BASE, '#s',            'http://a/b/c/d;p?q#s'),
            array(self::RFC3986_BASE, 'g#s',           'http://a/b/c/g#s'),
            array(self::RFC3986_BASE, 'g?y#s',         'http://a/b/c/g?y#s'),
            array(self::RFC3986_BASE, ';x',            'http://a/b/c/;x'),
            array(self::RFC3986_BASE, 'g;x',           'http://a/b/c/g;x'),
            array(self::RFC3986_BASE, 'g;x?y#s',       'http://a/b/c/g;x?y#s'),
            array(self::RFC3986_BASE, '',              self::RFC3986_BASE),
            array(self::RFC3986_BASE, '.',             'http://a/b/c/'),
            array(self::RFC3986_BASE, './',            'http://a/b/c/'),
            array(self::RFC3986_BASE, '..',            'http://a/b/'),
            array(self::RFC3986_BASE, '../',           'http://a/b/'),
            array(self::RFC3986_BASE, '../g',          'http://a/b/g'),
            array(self::RFC3986_BASE, '../..',         'http://a/'),
            array(self::RFC3986_BASE, '../../',        'http://a/'),
            array(self::RFC3986_BASE, '../../g',       'http://a/g'),
            array(self::RFC3986_BASE, '../../../g',    'http://a/g'),
            array(self::RFC3986_BASE, '../../../../g', 'http://a/g'),
            array(self::RFC3986_BASE, '/./g',          'http://a/g'),
            array(self::RFC3986_BASE, '/../g',         'http://a/g'),
            array(self::RFC3986_BASE, 'g.',            'http://a/b/c/g.'),
            array(self::RFC3986_BASE, '.g',            'http://a/b/c/.g'),
            array(self::RFC3986_BASE, 'g..',           'http://a/b/c/g..'),
            array(self::RFC3986_BASE, '..g',           'http://a/b/c/..g'),
            array(self::RFC3986_BASE, './../g',        'http://a/b/g'),
            array(self::RFC3986_BASE, 'foo////g',      'http://a/b/c/foo////g'),
            array(self::RFC3986_BASE, './g/.',         'http://a/b/c/g/'),
            array(self::RFC3986_BASE, 'g/./h',         'http://a/b/c/g/h'),
            array(self::RFC3986_BASE, 'g/../h',        'http://a/b/c/h'),
            array(self::RFC3986_BASE, 'g;x=1/./y',     'http://a/b/c/g;x=1/y'),
            array(self::RFC3986_BASE, 'g;x=1/../y',    'http://a/b/c/y'),
            // dot-segments in the query or fragment
            array(self::RFC3986_BASE, 'g?y/./x',       'http://a/b/c/g?y/./x'),
            array(self::RFC3986_BASE, 'g?y/../x',      'http://a/b/c/g?y/../x'),
            array(self::RFC3986_BASE, 'g#s/./x',       'http://a/b/c/g#s/./x'),
            array(self::RFC3986_BASE, 'g#s/../x',      'http://a/b/c/g#s/../x'),
            array(self::RFC3986_BASE, 'g#s/../x',      'http://a/b/c/g#s/../x'),
            array(self::RFC3986_BASE, '?y#s',          'http://a/b/c/d;p?y#s'),
            // base with fragment
            array('http://a/b/c?q#s', '?y',            'http://a/b/c?y'),
            // base with user info
            array('http://u@a/b/c/d;p?q', '.',         'http://u@a/b/c/'),
            array('http://u:p@a/b/c/d;p?q', '.',       'http://u:p@a/b/c/'),
            // path ending with slash or no slash at all
            array('http://a/b/c/d/',  'e',             'http://a/b/c/d/e'),
            array('urn:no-slash',     'e',             'urn:e'),
            // falsey relative parts
            array(self::RFC3986_BASE, '//0',           'http://0'),
            array(self::RFC3986_BASE, '0',             'http://a/b/c/0'),
            array(self::RFC3986_BASE, '?0',            'http://a/b/c/d;p?0'),
            array(self::RFC3986_BASE, '#0',            'http://a/b/c/d;p?q#0'),
            // absolute path base URI
            array('/a/b/',            '',              '/a/b/'),
            array('/a/b',             '',              '/a/b'),
            array('/',                'a',             '/a'),
            array('/',                'a/b',           '/a/b'),
            array('/a/b',             'g',             '/a/g'),
            array('/a/b/c',           './',            '/a/b/'),
            array('/a/b/',            '../',           '/a/'),
            array('/a/b/c',           '../',           '/a/'),
            array('/a/b/',            '../../x/y/z/',  '/x/y/z/'),
            array('/a/b/c/d/e',       '../../../c/d',  '/a/c/d'),
            array('/a/b/c//',         '../',           '/a/b/c/'),
            array('/a/b/c/',          './/',           '/a/b/c//'),
            array('/a/b/c',           '../../../../a', '/a'),
            array('/a/b/c',           '../../../..',   '/'),
            // not actually a dot-segment
            array('/a/b/c',           '..a/b..',           '/a/b/..a/b..'),
            // '' cannot be used as relative reference as it would inherit the base query component
            array('/a/b?q',           'b',             '/a/b'),
            array('/a/b/?q',          './',            '/a/b/'),
            // path with colon: "with:colon" would be the wrong relative reference
            array('/a/',              './with:colon',  '/a/with:colon'),
            array('/a/',              'b/with:colon',  '/a/b/with:colon'),
            array('/a/',              './:b/',         '/a/:b/'),
            // relative path references
            array('a',               'a/b',            'a/b'),
            array('',                 '',              ''),
            array('',                 '..',            ''),
            array('/',                '..',            '/'),
            array('urn:a/b',          '..//a/b',       'urn:/a/b'),
            // network path references
            // empty base path and relative-path reference
            array('//example.com',    'a',             '//example.com/a'),
            // path starting with two slashes
            array('//example.com//two-slashes', './',  '//example.com//'),
            array('//example.com',    './/',           '//example.com//'),
            array('//example.com/',   './/',           '//example.com//'),
            // base URI has less components than relative URI
            array('/',                '//a/b?q#h',     '//a/b?q#h'),
            array('/',                'urn:/',         'urn:/'),
        );
    }

    /**
     * Some additional tests to getResolveTestCases() that only make sense for relativize.
     */
    public function getRelativizeTestCases()
    {
        return array(
            // targets that are relative-path references are returned as-is
            array('a/b',             'b/c',          'b/c'),
            array('a/b/c',           '../b/c',       '../b/c'),
            array('a',               '',             ''),
            array('a',               './',           './'),
            array('a',               'a/..',         'a/..'),
            array('/a/b/?q',         '?q#h',         '?q#h'),
            array('/a/b/?q',         '#h',           '#h'),
            array('/a/b/?q',         'c#h',          'c#h'),
            // If the base URI has a query but the target has none, we cannot return an empty path reference as it would
            // inherit the base query component when resolving.
            array('/a/b/?q',         '/a/b/#h',      './#h'),
            array('/',               '/#h',          '#h'),
            array('/',               '/',            ''),
            array('http://a',        'http://a/',    './'),
            array('urn:a/b?q',       'urn:x/y?q',    '../x/y?q'),
            array('urn:',            'urn:/',        './/'),
            array('urn:a/b?q',       'urn:',         '../'),
            // target URI has less components than base URI
            array('http://a/b/',     '//a/b/c',      'c'),
            array('http://a/b/',     '/b/c',         'c'),
            array('http://a/b/',     '/x/y',         '../x/y'),
            array('http://a/b/',     '/',            '../'),
            // absolute target URI without authority but base URI has one
            array('urn://a/b/',      'urn:/b/',      'urn:/b/'),
        );
    }
}
