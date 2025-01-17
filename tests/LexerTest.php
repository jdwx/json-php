<?php


declare( strict_types = 1 );


use JDWX\Json\Lex\Lexer;
use JDWX\Json\Lex\Result;
use PHPUnit\Framework\TestCase;


final class LexerTest extends TestCase {


    public function testArrayForGood() : void {
        $lex = new Lexer();
        self::assertSame( '[ ]', $lex->array( '[ ]', true ) );
        self::assertSame( '[ ]', $lex->array( '[ ] foo', false ) );
        self::assertSame( '[ 1 ]', $lex->array( '[ 1 ]', true ) );
        self::assertSame( '[ 1 ]', $lex->array( '[ 1 ]', true ) );
        self::assertSame( '[1,2]', $lex->array( '[1,2]', true ) );
        self::assertSame( '[1, 2]', $lex->array( '[1, 2]', true ) );
        self::assertSame( '[ 1, 2]', $lex->array( '[ 1, 2]', true ) );
        self::assertSame( '[1, 2 ]', $lex->array( '[1, 2 ]', true ) );
        self::assertSame( '[ 1, 2 ]', $lex->array( '[ 1, 2 ]', true ) );
        self::assertSame(
            '[ 1, true, false, null, "foo" ]',
            $lex->array( '[ 1, true, false, null, "foo" ]', true )
        );
        self::assertSame(
            '[ 1, [ 2, [ 3 ] ] ]',
            $lex->array( '[ 1, [ 2, [ 3 ] ] ]', true )
        );
    }


    public function testArrayForIncomplete() : void {
        $lex = new Lexer();
        self::assertSame( Result::INCOMPLETE, $lex->array( '', false ) );
        self::assertSame( Result::INCOMPLETE, $lex->array( '', true ) );
        self::assertSame( Result::INCOMPLETE, $lex->array( '[', false ) );
        self::assertSame( Result::INCOMPLETE, $lex->array( '[', true ) );
        self::assertSame( Result::INCOMPLETE, $lex->array( '[ ', false ) );
        self::assertSame( Result::INCOMPLETE, $lex->array( '[ ', true ) );
        self::assertSame( Result::INCOMPLETE, $lex->array( '[1,2,3', false ) );
        self::assertSame( Result::INCOMPLETE, $lex->array( '[1,2,3', true ) );
    }


    public function testArrayForInvalid() : void {

        $lex = new Lexer();
        self::assertSame( Result::INVALID, $lex->array( 'foo', true ) );
        self::assertSame( Result::INVALID, $lex->array( '123', true ) );
        self::assertSame( Result::INVALID, $lex->array( '[]foo', true ) );
        self::assertSame( Result::INVALID, $lex->array( '[1,2foo', true ) );
        self::assertSame( Result::INVALID, $lex->array( '[1,2,]', true ) );
        self::assertSame( Result::INVALID, $lex->array( '[1,2 !, 3', true ) );
        self::assertSame( Result::INVALID, $lex->array( '[1,2]foo', true ) );
    }


    public function testElementForAlternateDelimiter() : void {
        $lex = new Lexer( '|' );
        self::assertSame(
            Result::INCOMPLETE,
            $lex->element( '', true, false )
        );
        self::assertSame(
            Result::INCOMPLETE,
            $lex->element( '   ', true, false )
        );
        self::assertSame(
            Result::INCOMPLETE,
            $lex->element( '   |', false, false )
        );
        self::assertSame(
            Result::INVALID,
            $lex->element( '   |', true, false )
        );
        self::assertSame(
            Result::INCOMPLETE,
            $lex->element( '   |   ', false, false )
        );
        self::assertSame(
            Result::INVALID,
            $lex->element( '   |   ', true, false )
        );
        self::assertSame(
            'true',
            $lex->element( 'true', true, false )
        );
        self::assertSame(
            Result::EXPECTED_DELIMITER,
            $lex->element( 'true', false, false )
        );
        self::assertSame(
            Result::END_OF_INPUT,
            $lex->element( '|', false, true )
        );
        self::assertSame(
            Result::END_OF_INPUT,
            $lex->element( '   |', false, true )
        );
        self::assertSame(
            Result::END_OF_INPUT,
            $lex->element( '|   ', false, true )
        );
        self::assertSame(
            Result::END_OF_INPUT,
            $lex->element( '   |   ', false, true )
        );
    }


    public function testElementForEndOfInput() : void {
        $lex = new Lexer();
        self::assertSame(
            Result::END_OF_INPUT,
            $lex->element( '', true, true )
        );
        self::assertSame(
            Result::END_OF_INPUT,
            $lex->element( '  ', true, true )
        );
        self::assertSame(
            Result::END_OF_INPUT,
            $lex->element( "  \r\n", true, true )
        );
        self::assertSame(
            Result::END_OF_INPUT,
            $lex->element( "  \n  ", true, true )
        );
    }


    public function testElementForExpectedDelimiter() : void {
        $lex = new Lexer();
        self::assertSame( Result::EXPECTED_DELIMITER, $lex->element( 'true', false, false ) );
        self::assertSame( Result::EXPECTED_DELIMITER, $lex->element( ' true', false, false ) );
        self::assertSame( Result::EXPECTED_DELIMITER, $lex->element( '\"foo\"', false, true ) );
    }


    public function testElementForGoodFirst() : void {
        $lex = new Lexer();
        self::assertSame( 'true', $lex->element( 'true', true, true ) );
        self::assertSame( 'false', $lex->element( 'false }', true, true ) );
        self::assertSame( '   "foo"', $lex->element( '   "foo"', true, true ) );
        self::assertSame( '123.456E+789', $lex->element( '123.456E+789,', true, true ) );
        self::assertSame( '   null', $lex->element( '   null, ', true, true ) );
    }


    public function testElementForGoodNotFirst() : void {
        $lex = new Lexer();
        self::assertSame( ' true', $lex->element( "\ntrue ]", false, true ) );
        self::assertSame( ' false', $lex->element( "\nfalse }", false, true ) );
        self::assertSame( '    "foo"', $lex->element( " \r\n \"foo\"", false, true ) );
        self::assertSame( '  123.456E+789', $lex->element( "\r\n123.456E+789,", false, true ) );
        self::assertSame( '     null', $lex->element( " \r \n null, ", false, true ) );
    }


    public function testElementForIncomplete() : void {
        $lex = new Lexer();
        self::assertSame( Result::INCOMPLETE, $lex->element( '', true, false ) );
        self::assertSame( Result::INCOMPLETE, $lex->element( "  \r\n  ", true, false ) );
        self::assertSame( Result::INCOMPLETE, $lex->element( '  tru', true, false ) );
    }


    public function testElementForInvalid() : void {
        $lex = new Lexer();
        self::assertSame( Result::INVALID, $lex->element( 'foo', true, false ) );
        self::assertSame( Result::INVALID, $lex->element( 'foo', true, true ) );
    }


    public function testFalseForGood() : void {
        $lex = new Lexer();
        self::assertSame( 'false', $lex->false( 'false' ) );
        self::assertSame( 'false', $lex->false( 'false,' ) );
        self::assertSame( 'false', $lex->false( 'false foo' ) );
        self::assertSame( 'false', $lex->false( 'false, foo' ) );
    }


    public function testFalseForIncomplete() : void {
        $lex = new Lexer();
        self::assertSame( Result::INCOMPLETE, $lex->false( '' ) );
        self::assertSame( Result::INCOMPLETE, $lex->false( 'f' ) );
        self::assertSame( Result::INCOMPLETE, $lex->false( 'fa' ) );
        self::assertSame( Result::INCOMPLETE, $lex->false( 'fal' ) );
        /** @noinspection SpellCheckingInspection */
        self::assertSame( Result::INCOMPLETE, $lex->false( 'fals' ) );
    }


    public function testFalseForInvalid() : void {
        $lex = new Lexer();
        /** @noinspection SpellCheckingInspection */
        self::assertSame( Result::INVALID, $lex->false( 'falsex' ) );
        self::assertSame( Result::INVALID, $lex->false( 'false!' ) );
        self::assertSame( Result::INVALID, $lex->false( 'true' ) );
    }


    public function testInvokeForGood() : void {
        $lex = new Lexer();
        $r = iterator_to_array( $lex( '', true, true ) );
        self::assertSame( [], $r );
        $r = iterator_to_array( $lex( 'true', true, true ) );
        self::assertSame( [ 'true' ], $r );
        $r = iterator_to_array( $lex( "false\n1", true, true ) );
        self::assertSame( [ 'false', ' 1' ], $r );
        $r = iterator_to_array( $lex( "null\n\"foo\"\n[0,1,2,true]", true, true ) );
        self::assertSame( [ 'null', ' "foo"', ' [0,1,2,true]' ], $r );
        $r = iterator_to_array( $lex( "true\nfalse\n123\n456", true, true ) );
        self::assertSame( [ 'true', ' false', ' 123', ' 456' ], $r );
    }


    public function testInvokeForIncomplete() : void {
        $lex = new Lexer();
        $r = iterator_to_array( $lex( 'tru', true, false ) );
        self::assertSame( [ Result::INCOMPLETE ], $r );
        $r = iterator_to_array( $lex( 'true', true, false ) );
        self::assertSame( [ 'true', Result::INCOMPLETE ], $r );
    }


    public function testInvokeForInvalid() : void {
        $lex = new Lexer();
        $r = iterator_to_array( $lex( 'tru', true, true ) );
        self::assertSame( [ Result::INVALID ], $r );
        $r = iterator_to_array( $lex( "true\r\n123\n\"foo", true, true ) );
        self::assertSame( [ 'true', '  123', Result::INVALID ], $r );
    }


    /**
     * null() and false() are both covers for the same underlying function.
     * We do the thorough tests in false() and just a quick check here.
     */
    public function testNull() : void {
        $lex = new Lexer();
        self::assertSame( 'null', $lex->null( 'null' ) );
        self::assertSame( Result::INCOMPLETE, $lex->null( 'nu' ) );
        self::assertSame( Result::INVALID, $lex->null( 'null!' ) );
        self::assertSame( Result::INVALID, $lex->null( 'false' ) );
    }


    public function testNumberForExponentsGood() : void {
        $lex = new Lexer();
        self::assertSame( '1.1e1', $lex->number( '1.1e1', true ) );
        self::assertSame( '1.1e+1', $lex->number( '1.1e+1', true ) );
        self::assertSame( '1.1e-1', $lex->number( '1.1e-1', true ) );
        self::assertSame( '1E1', $lex->number( '1E1', true ) );
        self::assertSame( '1e1', $lex->number( '1e1 foo', false ) );
        self::assertSame( '1e1', $lex->number( '1e1, foo', false ) );
        self::assertSame( '123.456E+789', $lex->number( '123.456E+789,', true ) );
    }


    public function testNumberForExponentsIncomplete() : void {
        $lex = new Lexer();
        self::assertSame( Result::INCOMPLETE, $lex->number( '1e', false ) );
        self::assertSame( Result::INCOMPLETE, $lex->number( '1e1', false ) );
        self::assertSame( Result::INCOMPLETE, $lex->number( '1e+', false ) );
        self::assertSame( Result::INCOMPLETE, $lex->number( '1e-', false ) );
        self::assertSame( Result::INCOMPLETE, $lex->number( '1e+1', false ) );
        self::assertSame( Result::INCOMPLETE, $lex->number( '1e-1', false ) );
    }


    public function testNumberForExponentsInvalid() : void {
        $lex = new Lexer();
        self::assertSame( Result::INVALID, $lex->number( '1e', true ) );
        self::assertSame( Result::INVALID, $lex->number( '1e1.1', true ) );
        self::assertSame( Result::INVALID, $lex->number( '1e+', true ) );
        self::assertSame( Result::INVALID, $lex->number( '1e-', true ) );
        self::assertSame( Result::INVALID, $lex->number( '1ex', false ) );
        self::assertSame( Result::INVALID, $lex->number( '1e!x', false ) );
        self::assertSame( Result::INVALID, $lex->number( '1e1foo', true ) );
    }


    public function testNumberForFractionsGood() : void {
        $lex = new Lexer();
        self::assertSame( '0.0', $lex->number( '0.0', true ) );
        self::assertSame( '1.1', $lex->number( '1.1', true ) );
        self::assertSame( '123.123', $lex->number( '123.123', true ) );
        self::assertSame( '-123.123', $lex->number( '-123.123', true ) );
        self::assertSame( '123.123', $lex->number( '123.123 foo', true ) );
        self::assertSame( '123.123', $lex->number( '123.123, foo', true ) );
    }


    public function testNumberForFractionsIncomplete() : void {
        $lex = new Lexer();
        self::assertSame( Result::INCOMPLETE, $lex->number( '0.', false ) );
        self::assertSame( Result::INCOMPLETE, $lex->number( '1.', false ) );
        self::assertSame( Result::INCOMPLETE, $lex->number( '123.123', false ) );
    }


    public function testNumberForFractionsInvalid() : void {
        $lex = new Lexer();
        self::assertSame( Result::INVALID, $lex->number( '0.', true ) );
        self::assertSame( Result::INVALID, $lex->number( '1.x', false ) );
        self::assertSame( Result::INVALID, $lex->number( '123.123foo', true ) );
    }


    public function testNumberForIntegersGood() : void {
        $lex = new Lexer();
        self::assertSame( '0', $lex->number( '0', true ) );
        self::assertSame( '-0', $lex->number( '-0', true ) );
        self::assertSame( '1', $lex->number( '1', true ) );
        self::assertSame( '123', $lex->number( '123', true ) );
        self::assertSame( '-123', $lex->number( '-123', true ) );
        self::assertSame( '123', $lex->number( '123 foo', false ) );
        self::assertSame( '123', $lex->number( '123, foo', false ) );
    }


    public function testNumberForIntegersIncomplete() : void {
        $lex = new Lexer();
        self::assertSame( Result::INCOMPLETE, $lex->number( '', false ) );
        self::assertSame( Result::INCOMPLETE, $lex->number( '0', false ) );
        self::assertSame( Result::INCOMPLETE, $lex->number( '1', false ) );
        self::assertSame( Result::INCOMPLETE, $lex->number( '123', false ) );
    }


    public function testNumberForIntegersInvalid() : void {
        $lex = new Lexer();
        self::assertSame( Result::INVALID, $lex->number( '', true ) );
        self::assertSame( Result::INVALID, $lex->number( '01', true ) );
        self::assertSame( Result::INVALID, $lex->number( '-01', true ) );
        self::assertSame( Result::INVALID, $lex->number( 'foo', true ) );
        self::assertSame( Result::INVALID, $lex->number( '123foo', true ) );
    }


    public function testObjectForGood() : void {
        $lex = new Lexer();
        self::assertSame( '{}', $lex->object( '{}', true ) );
        self::assertSame( '{ }', $lex->object( '{ }', true ) );
        self::assertSame( '{ }', $lex->object( '{ }   ', true ) );
        self::assertSame( '{ }', $lex->object( '{ },', true ) );
        self::assertSame( '{ }', $lex->object( '{ }]', true ) );
        self::assertSame( '{ }', $lex->object( '{ }}', true ) );
        self::assertSame( '{ "foo": "bar" }', $lex->object( '{ "foo": "bar" }', true ) );
        self::assertSame( '{ "foo": "bar", "baz": 0 }', $lex->object( '{ "foo": "bar", "baz": 0 }', true ) );
        self::assertSame(
            '{ "foo": { "bar": 0 }, "baz": [ 0, 1 ], "qux": null , "quux": {} }',
            $lex->object( '{ "foo": { "bar": 0 }, "baz": [ 0, 1 ], "qux": null , "quux": {} }', true )
        );
    }


    public function testObjectForIncomplete() : void {
        $lex = new Lexer();
        self::assertSame( Result::INCOMPLETE, $lex->object( '', false ) );
        self::assertSame( Result::INCOMPLETE, $lex->object( '', true ) );
        self::assertSame( Result::INCOMPLETE, $lex->object( '{', false ) );
        self::assertSame( Result::INCOMPLETE, $lex->object( '{', true ) );
        self::assertSame( Result::INCOMPLETE, $lex->object( '{ ', false ) );
        self::assertSame( Result::INCOMPLETE, $lex->object( '{ "foo', false ) );
        self::assertSame( Result::INCOMPLETE, $lex->object( '{ "foo":', false ) );
        self::assertSame( Result::INCOMPLETE, $lex->object( '{ "foo": "bar" ', false ) );
        self::assertSame( Result::INCOMPLETE, $lex->object( '{ "foo": "bar",', false ) );
        self::assertSame( Result::INCOMPLETE, $lex->object( '{ "foo": "bar", ', false ) );
    }


    public function testObjectForInvalid() : void {
        $lex = new Lexer();
        self::assertSame( Result::INVALID, $lex->object( 'foo', true ) );
        self::assertSame( Result::INVALID, $lex->object( '123', true ) );
        self::assertSame( Result::INVALID, $lex->object( '{}foo', true ) );
        self::assertSame( Result::INVALID, $lex->object( '{ 123: "bar" }', true ) );
        self::assertSame( Result::INVALID, $lex->object( '{ "foo", "bar" }', true ) );
        self::assertSame( Result::INVALID, $lex->object( '{ "foo": "bar" ]', true ) );
        self::assertSame( Result::INVALID, $lex->object( '{ "foo": "bar"foo', true ) );
        self::assertSame( Result::INVALID, $lex->object( '{ "foo": "bar" }baz', true ) );
        self::assertSame( Result::INVALID, $lex->object( '{ "foo": [ 0, 1 }', true ) );
    }


    public function testStringForGood() : void {
        $lex = new Lexer();
        self::assertSame( '"foo"', $lex->string( '"foo"' ) );
        self::assertSame( '"foo"', $lex->string( '"foo" bar' ) );
        self::assertSame( '"foo"', $lex->string( '"foo", bar' ) );
    }


    public function testStringForIncomplete() : void {
        $lex = new Lexer();
        self::assertSame( Result::INCOMPLETE, $lex->string( '' ) );
        self::assertSame( Result::INCOMPLETE, $lex->string( '"' ) );
        self::assertSame( Result::INCOMPLETE, $lex->string( '"f' ) );
        self::assertSame( Result::INCOMPLETE, $lex->string( '"fo' ) );
        self::assertSame( Result::INCOMPLETE, $lex->string( '"foo' ) );
        self::assertSame( Result::INCOMPLETE, $lex->string( '"foo\"' ) );
    }


    public function testStringForInvalid() : void {
        $lex = new Lexer();
        self::assertSame( Result::INVALID, $lex->string( 'foo' ) );
        self::assertSame( Result::INVALID, $lex->string( "foo'" ) );
        self::assertSame( Result::INVALID, $lex->string( '123' ) );
        self::assertSame( Result::INVALID, $lex->string( 'false' ) );
        self::assertSame( Result::INVALID, $lex->string( '[ "foo" ]' ) );
        self::assertSame( Result::INVALID, $lex->string( '"foo"bar' ) );
        self::assertSame( Result::INVALID, $lex->string( '"foo""bar"' ) );
        self::assertSame( Result::INVALID, $lex->string( ' "foo"' ) );
    }


    /**
     * true() and false() are both covers for the same underlying function.
     * We do the thorough tests in false() and just a quick check here.
     */
    public function testTrue() : void {
        $lex = new Lexer();
        self::assertSame( 'true', $lex->true( 'true' ) );
        self::assertSame( Result::INCOMPLETE, $lex->true( 'tr' ) );
        self::assertSame( Result::INVALID, $lex->true( 'true!' ) );
        self::assertSame( Result::INVALID, $lex->true( 'false' ) );
    }


    public function testValueForGood() : void {
        $lex = new Lexer();
        self::assertSame( 'null', $lex->value( 'null', true ) );
        self::assertSame( 'null', $lex->value( 'null', false ) );
        self::assertSame( 'true', $lex->value( 'true', true ) );
        self::assertSame( 'false', $lex->value( 'false', true ) );
        self::assertSame( '"foo"', $lex->value( '"foo"', true ) );
        self::assertSame( '123.456E+789', $lex->value( '123.456E+789', true ) );
        self::assertSame( '[ 0, 1 ]', $lex->value( '[ 0, 1 ]', true ) );
        self::assertSame( '{ "foo": "bar" }', $lex->value( '{ "foo": "bar" }', true ) );
    }


    public function testValueForIncomplete() : void {
        $lex = new Lexer();
        self::assertSame( Result::INCOMPLETE, $lex->value( 'nul', false ) );
        self::assertSame( Result::INCOMPLETE, $lex->value( 'tru', false ) );
        self::assertSame( Result::INCOMPLETE, $lex->value( 'fal', false ) );
        self::assertSame( Result::INCOMPLETE, $lex->value( '"foo', false ) );
        self::assertSame( Result::INCOMPLETE, $lex->value( '123.456E+789', false ) );
        self::assertSame( Result::INCOMPLETE, $lex->value( '[ 0, 1', false ) );
        self::assertSame( Result::INCOMPLETE, $lex->value( '{ "foo": "bar"', false ) );
    }


    public function testValueForInvalid() : void {
        $lex = new Lexer();
        self::assertSame( Result::INVALID, $lex->value( 'null!', false ) );
        self::assertSame( Result::INVALID, $lex->value( 'true!', false ) );
        self::assertSame( Result::INVALID, $lex->value( 'false!', false ) );
        self::assertSame( Result::INVALID, $lex->value( '"foo"bar', false ) );
        self::assertSame( Result::INVALID, $lex->value( '123_456E+789!', false ) );
        self::assertSame( Result::INVALID, $lex->value( '[ 0, 1 ]!', false ) );
        self::assertSame( Result::INVALID, $lex->value( '{ "foo": "bar" !', false ) );
    }


}
