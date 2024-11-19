<?php


declare( strict_types = 1 );


use JDWX\Json\Result;
use PHPUnit\Framework\TestCase;


final class ResultTest extends TestCase {


    public function testCheck() : void {
        self::assertTrue( Result::check( Result::EOF ) );
        self::assertTrue( Result::check( Result::NOTHING ) );
        self::assertFalse( Result::check( 0 ) );
    }


}
