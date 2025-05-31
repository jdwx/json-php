<?php


declare( strict_types = 1 );


namespace JDWX\Json\Tests;


use JDWX\Json\Lex\BufferedLexer;
use JDWX\Json\Lex\Result;
use PHPUnit\Framework\TestCase;


final class BufferedLexerTest extends TestCase {


    public function testBufferedLexerForGood() : void {
        $lex = new BufferedLexer( "true\nfalse\n123\n456", bEndOfInput: true );
        $r = iterator_to_array( $lex() );
        self::assertSame( [ 'true', ' false', ' 123', ' 456' ], $r );
    }


    public function testBufferedLexerForIncomplete() : void {
        $lex = new BufferedLexer( "true\nfalse\nnul", bEndOfInput: false );
        $r = iterator_to_array( $lex() );
        self::assertSame( [ 'true', ' false', Result::INCOMPLETE ], $r );
    }


    public function testBufferedLexerForInvalid() : void {
        $lex = new BufferedLexer( "true\nfalse\nnul", bEndOfInput: true );
        $r = iterator_to_array( $lex() );
        self::assertSame( [ 'true', ' false', Result::INVALID ], $r );

        $lex = new BufferedLexer( "true\nfal\nnull", bEndOfInput: false );
        $r = iterator_to_array( $lex() );
        self::assertSame( [ 'true', Result::INVALID ], $r );
    }


    public function testBufferedLexerForMultipleSteps() : void {
        $lex = new BufferedLexer( "true\nfalse\n123\n456" );
        $r = iterator_to_array( $lex() );
        self::assertSame( [ 'true', ' false', ' 123', Result::INCOMPLETE ], $r );
        $lex->add( '789' );
        $lex->endOfInput();
        $r = iterator_to_array( $lex() );
        self::assertSame( [ ' 456789' ], $r );
    }


}
