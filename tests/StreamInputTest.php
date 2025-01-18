<?php


declare( strict_types = 1 );


use PHPUnit\Framework\TestCase;


final class StreamInputTest extends TestCase {


    public function testNextForReadError() : void {

        $fp = fopen( __DIR__ . '/data/test.json', 'r' );
        $input = new JDWX\Json\Streaming\StreamInput( $fp, true );
        self::assertSame( 1, $input->next() );

        fclose( $fp );
        self::expectException( JsonException::class );
        $input->next();

    }


}
