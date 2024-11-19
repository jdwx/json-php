<?php


declare( strict_types = 1 );


use JDWX\Json\JsonLines;
use PHPUnit\Framework\TestCase;


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


}
