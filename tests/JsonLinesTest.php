<?php


declare( strict_types = 1 );


namespace JDWX\Json\Tests;


use JDWX\Json\JsonLines;
use PHPUnit\Framework\TestCase;
use RuntimeException;


final class JsonLinesTest extends TestCase {


    public const string JSONL_EXAMPLE   = "{\"foo\": \"bar\"}\n{\"baz\": \"qux\"}\n";

    public const string JSONL_EXAMPLE_2 = "{\"foo\": \"bar\"}\n{\"baz\": \"qux\"}";


    public function testDecode() : void {
        $gen = JsonLines::decode( self::JSONL_EXAMPLE );
        self::assertEquals( [ 'foo' => 'bar' ], $gen->current() );
        $gen->next();
        self::assertEquals( [ 'baz' => 'qux' ], $gen->current() );

        $gen = JsonLines::decode( self::JSONL_EXAMPLE_2 );
        self::assertEquals( [ 'foo' => 'bar' ], $gen->current() );
        $gen->next();
        self::assertEquals( [ 'baz' => 'qux' ], $gen->current() );
    }


    public function testDecodeAll() : void {
        $r = JsonLines::decodeAll( self::JSONL_EXAMPLE );
        self::assertEquals( [ [ 'foo' => 'bar' ], [ 'baz' => 'qux' ] ], $r );
    }


    public function testEncode() : void {
        $r = JsonLines::encode( [ [ 'foo' => 'bar' ], [ 'baz' => 'qux' ] ] );
        self::assertEquals( "{\"foo\":\"bar\"}\n{\"baz\":\"qux\"}\n", $r );

        $r = JsonLines::encode( [ [ 'foo' => "bar\n baz" ] ] );
        self::assertEquals( '{"foo":"bar\n baz"}' . "\n", $r );
    }


    /*
    public function testFromFile() : void {
        $stFileName = __DIR__ . '/data/test.jsonl';
        $gen = JsonLines::fromFile( $stFileName );

        $r = $gen->current();
        self::assertSame( 'bar', $r[ 'foo' ] );
        self::assertSame( 5, $r[ 'baz' ] );
        $gen->next();

        $r = $gen->current();
        self::assertEquals( [ 1, 2, 3 ], $r[ 'qux' ] );
        self::assertNull( $r[ 'quux' ] );
        $gen->next();

        $r = $gen->current();
        self::assertIsArray( $r[ 'corge' ] );
        $rCorge = $r[ 'corge' ];
        self::assertSame( 'garply', $rCorge[ 'grault' ] );
        self::assertSame( "fred\nplugh", $rCorge[ 'waldo' ] );

        $gen->next();
        self::assertNull( $gen->current() );

    }


    public function testFromFileForNoSuchFile() : void {
        $gen = JsonLines::fromFile( '/no/such/file' );
        self::expectException( RuntimeException::class );
        $gen->current();
    }
    */


    public function testFromFile() : void {
        $gen = JsonLines::fromFile( __DIR__ . '/data/test2.jsonl' );

        self::assertSame( 'foo', $gen->current() );
        $gen->next();
        self::assertSame( 'bar', $gen->current() );
        $gen->next();
        self::assertSame( 'baz', $gen->current() );
        $gen->next();
        self::assertSame( 'qux', $gen->current() );

    }


    public function testFromFileForNoSuchFile() : void {
        $gen = JsonLines::fromFile( '/no/such/file' );
        $this->expectException( RuntimeException::class );
        $gen->current();
    }


    public function testFromFileForOffsetAndLimit() : void {
        $gen = JsonLines::fromFile( __DIR__ . '/data/test2.jsonl', 3, 2 );

        self::assertSame( 'qux', $gen->current() );
        $gen->next();
        self::assertSame( 'quux', $gen->current() );
        $gen->next();
        self::assertFalse( $gen->valid() );
    }


    public function testFromStream() : void {
        # Add an extra newline to test blank skipping.
        $st = "{\"foo\": \"bar\"}\n\n{\"baz\": \"qux\"}\n";
        $stream = $this->createStream( $st );

        $gen = JsonLines::fromStream( $stream );

        $r = $gen->current();
        self::assertSame( 'bar', $r[ 'foo' ] );
        $gen->next();

        $r = $gen->current();
        self::assertSame( 'qux', $r[ 'baz' ] );
        $gen->next();

        self::assertNull( $gen->current() );
    }


    public function testFromStreamForOffsetAndLimit() : void {
        $st = "{\"foo\": \"bar\"}\n\n{\"baz\": \"qux\"}\n{\"quux\":\"corge\"}\n";
        $stream = $this->createStream( $st );

        $gen = JsonLines::fromStream( $stream, 1, 2 );

        # Test offset 1-2.
        $r = $gen->current();
        self::assertSame( 'qux', $r[ 'baz' ] );
        $gen->next();

        $r = $gen->current();
        self::assertSame( 'corge', $r[ 'quux' ] );
        $gen->next();

        self::assertNull( $gen->current() );

        # Test offset 0-1.
        $stream = $this->createStream( $st );
        $gen = JsonLines::fromStream( $stream, 0, 2 );

        $r = $gen->current();
        self::assertSame( 'bar', $r[ 'foo' ] );
        $gen->next();

        $r = $gen->current();
        self::assertSame( 'qux', $r[ 'baz' ] );
        $gen->next();

        self::assertNull( $gen->current() );

        # Test offset 1-1.
        $stream = $this->createStream( $st );

        $gen = JsonLines::fromStream( $stream, 1, 1 );
        $r = $gen->current();
        self::assertSame( 'qux', $r[ 'baz' ] );
        $gen->next();
        self::assertNull( $gen->current() );

    }


    private function createStream( string $i_st ) {
        $stream = fopen( 'php://memory', 'rb+' );
        assert( is_resource( $stream ) );
        fwrite( $stream, $i_st );
        rewind( $stream );
        return $stream;
    }


}
