<?php


declare( strict_types = 1 );


use JDWX\Json\Lex\CallbackLexer;
use JDWX\Json\Lex\Result;
use PHPUnit\Framework\TestCase;


final class CallbackLexerTest extends TestCase {


    private const array CALLBACK_VALUES_GOOD        = [ "true\nfa", "lse\n123", '456', null ];

    private const array CALLBACK_VALUES_INVALID     = [ "true\nfa", "lse!\nnu", 'll', null ];

    private const array CALLBACK_VALUES_INVALID_END = [ "true\nfa", "lse\nnu", 'l', null ];


    public function testInvokeForGood() : void {
        $lex = new CallbackLexer( function () {
            static $uIndex = 0;
            return self::CALLBACK_VALUES_GOOD[ $uIndex++ ];
        } );
        $r = iterator_to_array( $lex() );
        self::assertSame( [ 'true', ' false', ' 123456' ], $r );
    }


    public function testInvokeForInvalid() : void {
        $lex = new CallbackLexer( function () {
            static $uIndex = 0;
            return self::CALLBACK_VALUES_INVALID[ $uIndex++ ];
        } );
        $r = iterator_to_array( $lex() );
        self::assertSame( [ 'true', Result::INVALID ], $r );
    }


    public function testInvokeForInvalidEnd() : void {
        $lex = new CallbackLexer( function () {
            static $uIndex = 0;
            return self::CALLBACK_VALUES_INVALID_END[ $uIndex++ ];
        } );
        $r = iterator_to_array( $lex() );
        self::assertSame( [ 'true', ' false', Result::INVALID ], $r );
    }


}
