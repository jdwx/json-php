<?php


declare( strict_types = 1 );


use JDWX\Json\Streaming\StringInput;
use PHPUnit\Framework\TestCase;


final class StringInputTest extends TestCase {


    public function testBasic() : void {
        $input = new StringInput( '"foo"' );
        self::assertSame( 'foo', $input->next() );
    }


    public function testNextFromFile() : void {
        $st = file_get_contents( __DIR__ . '/data/test.jsonl' );
        $input = new StringInput( $st );

        $rResult = [ 'foo' => 'bar', 'baz' => 5 ];
        self::assertSame( $rResult, $input->next() );

        $rResult = [ 'qux' => [ 1, 2, 3 ], 'quux' => null ];
        self::assertSame( $rResult, $input->next() );

        $rResult = [ 'corge' => [ 'grault' => 'garply', 'waldo' => "fred\nplugh" ] ];
        self::assertSame( $rResult, $input->next() );
    }


    public function testStreamFromFile() : void {
        $r = [
            [ 'foo' => 'bar', 'baz' => 5 ],
            [ 'qux' => [ 1, 2, 3 ], 'quux' => null ],
            [ 'corge' => [ 'grault' => 'garply', 'waldo' => "fred\nplugh" ] ],
        ];
        $st = file_get_contents( __DIR__ . '/data/test.jsonl' );
        $input = new StringInput( $st );

        foreach ( $input->stream() as $result ) {
            self::assertSame( array_shift( $r ), $result );
        }

    }


}
