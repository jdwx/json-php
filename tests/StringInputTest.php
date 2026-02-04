<?php


declare( strict_types = 1 );


namespace JDWX\Json\Tests;


use InvalidArgumentException;
use JDWX\Json\Lex\Result;
use JDWX\Json\Streaming\StringInput;
use JsonException;
use PHPUnit\Framework\TestCase;


final class StringInputTest extends TestCase {


    public function testBasic() : void {
        $input = new StringInput( '"foo"' );
        self::assertSame( 'foo', $input->next() );
    }


    /** @suppress PhanNoopNew */
    public function testConstructorForInvalidParameters() : void {
        $this->expectException( InvalidArgumentException::class );
        new StringInput( 'foo', true, i_elementDelimiters: 'bar' );
    }


    public function testNext() : void {
        $st = file_get_contents( __DIR__ . '/data/test.jsonl' );
        assert( is_string( $st ) );
        $input = new StringInput( $st );

        $rResult = [ 'foo' => 'bar', 'baz' => 5 ];
        self::assertSame( $rResult, $input->next() );

        $rResult = [ 'qux' => [ 1, 2, 3 ], 'quux' => null ];
        self::assertSame( $rResult, $input->next() );

        $rResult = [ 'corge' => [ 'grault' => 'garply', 'waldo' => "fred\nplugh" ] ];
        self::assertSame( $rResult, $input->next() );
    }


    public function testNextForIncomplete() : void {
        $input = new StringInput( "\"foo\", 1, \"bar", i_elementDelimiters: ',' );
        self::assertSame( 'foo', $input->next() );
        self::assertSame( 1, $input->next() );
        self::assertSame( Result::INCOMPLETE, $input->next() );
    }


    public function testNextForInvalidSkipOuter() : void {
        $st = file_get_contents( __DIR__ . '/data/test.jsonl' );
        assert( is_string( $st ) );
        $input = new StringInput( $st, true );

        $this->expectException( JsonException::class );
        $input->next();
    }


    public function testNextForTooLargeObject() : void {
        $input = new StringInput( str_repeat( '[', 4096 ), i_uBufferSize: 128 );
        $this->expectException( JsonException::class );
        var_dump( $input->next() );
    }


    public function testStream() : void {
        $r = [
            [ 'foo' => 'bar', 'baz' => 5 ],
            [ 'qux' => [ 1, 2, 3 ], 'quux' => null ],
            [ 'corge' => [ 'grault' => 'garply', 'waldo' => "fred\nplugh" ] ],
        ];
        $st = file_get_contents( __DIR__ . '/data/test.jsonl' );
        assert( is_string( $st ) );
        $input = new StringInput( $st );

        foreach ( $input->stream() as $result ) {
            self::assertSame( array_shift( $r ), $result );
        }
    }


    public function testStreamForIncomplete() : void {
        $input = new StringInput( "\"foo\", 1, \"bar", i_elementDelimiters: ',' );
        $gen = $input->stream();
        $gen->next();
        self::assertSame( 1, $gen->current() );
        $this->expectException( JsonException::class );
        $gen->next();
    }


}
