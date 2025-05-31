<?php


declare( strict_types = 1 );


namespace JDWX\Json\Tests;


use JDWX\Json\Lex\Result;
use JDWX\Json\Streaming\FileInput;
use PHPUnit\Framework\TestCase;


class FileInputTest extends TestCase {


    public function testNext() : void {
        $input = new FileInput( __DIR__ . '/data/test.jsonl' );

        $rResult = [ 'foo' => 'bar', 'baz' => 5 ];
        self::assertSame( $rResult, $input->next() );

        $rResult = [ 'qux' => [ 1, 2, 3 ], 'quux' => null ];
        self::assertSame( $rResult, $input->next() );

        $rResult = [ 'corge' => [ 'grault' => 'garply', 'waldo' => "fred\nplugh" ] ];
        self::assertSame( $rResult, $input->next() );
    }


    public function testNextForSkipOuter() : void {
        $input = new FileInput( __DIR__ . '/data/test.json', true );

        self::assertSame( 1, $input->next() );

        self::assertSame( 2, $input->next() );

        self::assertSame( null, $input->next() );

        self::assertSame( 'foo', $input->next() );

        self::assertSame( [ 'bar', 'baz' ], $input->next() );

        self::assertSame( [ 'qux' => 'quux' ], $input->next() );

        self::assertSame( true, $input->next() );

        self::assertSame( Result::END_OF_INPUT, $input->next() );

    }


}
