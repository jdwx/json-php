<?php


declare( strict_types = 1 );


namespace JDWX\Json\Tests;


use JDWX\Json\Json;
use JsonException;
use PHPUnit\Framework\TestCase;


require_once __DIR__ . '/../vendor/autoload.php';


final class JsonTest extends TestCase {


    public function testDecode() : void {
        $stJson = '{"a":1,"b":2}';
        $decode = Json::decode( $stJson );
        self::assertSame( [ 'a' => 1, 'b' => 2 ], $decode );

        $stJson = 'null';
        self::assertNull( Json::decode( $stJson ) );

        $stJson = '5';
        self::assertSame( 5, Json::decode( $stJson ) );

        $stJson = '////nope///';
        $this->expectException( JsonException::class );
        Json::decode( $stJson );
    }


    public function testDecodeArray() : void {
        $stJson = '[1,2]';
        $rDecode = Json::decodeArray( $stJson );
        self::assertSame( [ 1, 2 ], $rDecode );

        $stJson = '{"a":1,"b":2}';
        $rDecode = Json::decodeArray( $stJson );
        self::assertSame( [ 'a' => 1, 'b' => 2 ], $rDecode );

        $stJson = 'null';
        $this->expectException( JsonException::class );
        Json::decodeArray( $stJson );
    }


    public function testDecodeDict() : void {
        $stJson = '{"a":1,"b":2}';
        $rDecode = Json::decodeDict( $stJson );
        self::assertSame( [ 'a' => 1, 'b' => 2 ], $rDecode );

        $stJson = '[1,2]';
        $this->expectException( JsonException::class );
        Json::decodeDict( $stJson );
    }


    public function testDecodeDictEmpty() : void {
        $stJson = '{}';
        $rDecode = Json::decodeDict( $stJson );
        self::assertSame( [], $rDecode );
    }


    public function testDecodeDictWithInt() : void {
        $stJson = '5';
        $this->expectException( JsonException::class );
        Json::decodeDict( $stJson );
    }


    public function testDecodeList() : void {
        $stJson = '[1,2]';
        $rDecode = Json::decodeList( $stJson );
        self::assertSame( [ 1, 2 ], $rDecode );

        $stJson = '{"a":1,"b":2}';
        $this->expectException( JsonException::class );
        Json::decodeList( $stJson );
    }


    public function testDecodeListEmpty() : void {
        $stJson = '[]';
        $rDecode = Json::decodeList( $stJson );
        self::assertSame( [], $rDecode );
    }


    public function testDecodeListWithInt() : void {
        $stJson = '5';
        $this->expectException( JsonException::class );
        Json::decodeList( $stJson );
    }


    public function testDecodeScalar() : void {
        $stJson = '5';
        $rDecode = Json::decodeScalar( $stJson );
        self::assertSame( 5, $rDecode );

        $stJson = 'true';
        $rDecode = Json::decodeScalar( $stJson );
        self::assertTrue( $rDecode );

        $stJson = '{"a":1,"b":2}';
        $this->expectException( JsonException::class );
        Json::decodeScalar( $stJson );
    }


    public function testDecodeStringMap() : void {
        $stJson = '{"a":"1","b":"2"}';
        $rDecode = Json::decodeStringMap( $stJson );
        self::assertSame( [ 'a' => '1', 'b' => '2' ], $rDecode );

        $stJson = '{"a":1,"b":2}';
        $this->expectException( JsonException::class );
        $r = Json::decodeStringMap( $stJson );
        /** @noinspection ForgottenDebugOutputInspection */
        var_dump( $r );
    }


    public function testDecodeStringMapEmpty() : void {
        $stJson = '{}';
        $rDecode = Json::decodeStringMap( $stJson );
        self::assertSame( [], $rDecode );
    }


    public function testDecodeStringMapWithInt() : void {
        $stJson = '5';
        $this->expectException( JsonException::class );
        Json::decodeStringMap( $stJson );
    }


    public function testDecodeStringable() : void {
        $stJson = '5';
        $rDecode = Json::decodeStringable( $stJson );
        self::assertSame( 5, $rDecode );

        $stJson = 'true';
        $rDecode = Json::decodeStringable( $stJson );
        self::assertTrue( $rDecode );

        $stJson = '{"a":1,"b":2}';
        $this->expectException( JsonException::class );
        Json::decodeStringable( $stJson );
    }


    public function testEncode() : void {
        self::assertSame( '{"a":1,"b":2}', Json::encode( [ 'a' => 1, 'b' => 2 ] ) );
        self::assertSame( 'null', Json::encode( null ) );
        $x = fopen( 'php://memory', 'wb' );
        $this->expectException( JsonException::class );
        Json::encode( $x );
    }


    public function testEncodeForFlags() : void {
        $st = '😀';
        self::assertSame( '"\ud83d\ude00"', Json::encode( $st ) );
        self::assertSame( '"😀"', Json::encode( $st, JSON_UNESCAPED_UNICODE ) );
    }


    public function testEncodePretty() : void {
        $r = [
            'a' => 1,
            'b' => 2,
            'c' => [
                'd' => 3,
                'e' => 4,
            ],
        ];
        $stJson = Json::encodePretty( $r );
        $r2 = Json::decode( $stJson );
        self::assertSame( $r, $r2 );
    }


    public function testExpectArray() : void {
        self::assertSame( [ 1, 2 ], Json::expectArray( [ 1, 2 ] ) );
        $this->expectException( JsonException::class );
        Json::expectArray( 5 );
    }


    public function testExpectBoolean() : void {
        self::assertTrue( Json::expectBoolean( true ) );
        self::assertFalse( Json::expectBoolean( false ) );
        $this->expectException( JsonException::class );
        Json::expectBoolean( 5 );
    }


    public function testExpectDict() : void {
        self::assertSame( [ 'a' => 1, 'b' => 2 ], Json::expectDict( [ 'a' => 1, 'b' => 2 ] ) );
        $this->expectException( JsonException::class );
        Json::expectDict( 5 );
    }


    public function testExpectScalar() : void {
        self::assertSame( 5, Json::expectScalar( 5 ) );
        self::assertSame( 5.5, Json::expectScalar( 5.5 ) );
        self::assertTrue( Json::expectScalar( true ) );
        self::assertFalse( Json::expectScalar( false ) );
        self::assertSame( 'foo', Json::expectScalar( 'foo' ) );
        $this->expectException( JsonException::class );
        Json::expectScalar( null );
    }


    public function testExpectScalarWithArray() : void {
        $this->expectException( JsonException::class );
        Json::expectScalar( [ 'foo' => 'bar' ] );
    }


    public function testExpectStringable() : void {
        self::assertSame( 5, Json::expectStringable( 5 ) );
        self::assertSame( 5.5, Json::expectStringable( 5.5 ) );
        self::assertTrue( Json::expectStringable( true ) );
        self::assertFalse( Json::expectStringable( false ) );
        self::assertSame( 'foo', Json::expectStringable( 'foo' ) );
        self::assertNull( Json::expectStringable( null ) );
        $this->expectException( JsonException::class );
        Json::expectStringable( [ 'foo' => 'bar' ] );
    }


    public function testFromFile() : void {
        $stJson = '{"a":1,"b":2}';
        $stFilename = tempnam( sys_get_temp_dir(), 'jdwx_json-api-client' );
        file_put_contents( $stFilename, $stJson );
        $rDecode = Json::fromFile( $stFilename );
        self::assertSame( [ 'a' => 1, 'b' => 2 ], $rDecode );
        unlink( $stFilename );

        $this->expectException( JsonException::class );
        Json::fromFile( $stFilename );

    }


    public function testGet() : void {
        $stJson = '{"a":1,"b":"foo"}';
        $r = Json::decode( $stJson );
        self::assertSame( 1, Json::get( $r, 'a' ) );
        self::assertSame( 'foo', Json::get( $r, 'b' ) );
        self::assertNull( Json::get( $r, 'c', i_bNullOK: true ) );
        self::assertSame( 5, Json::get( $r, 'c', 5 ) );
        $this->expectException( JsonException::class );
        Json::get( $r, 'c' );
    }


    public function testGetArray() : void {
        $stJson = '{"a":[1,2], "b":"foo"}';
        $r = Json::decode( $stJson );
        self::assertSame( [ 1, 2 ], Json::getArray( $r, 'a' ) );
        self::assertSame( [], Json::getArray( $r, 'c', [] ) );
        $this->expectException( JsonException::class );
        Json::getArray( $r, 'b' );
    }


    public function testGetArrayWithMissingKey() : void {
        $stJson = '{"a":[1,2]}';
        $r = Json::decode( $stJson );
        $this->expectException( JsonException::class );
        Json::getArray( $r, 'b' );
    }


    public function testGetBoolean() : void {
        $stJson = '{"a":true,"b":false,"c":"foo"}';
        $r = Json::decode( $stJson );
        self::assertTrue( Json::getBoolean( $r, 'a' ) );
        self::assertFalse( Json::getBoolean( $r, 'b' ) );
        self::assertTrue( Json::getBoolean( $r, 'd', true ) );
        self::assertFalse( Json::getBoolean( $r, 'd', false ) );
        $this->expectException( JsonException::class );
        Json::getBoolean( $r, 'c' );
    }


    public function testGetBooleanWithMissingKey() : void {
        $stJson = '{"a":true}';
        $r = Json::decode( $stJson );
        $this->expectException( JsonException::class );
        Json::getBoolean( $r, 'b' );
    }


    public function testGetNull() : void {
        $stJson = '{"a":null, "b":5}';
        $r = Json::decode( $stJson );
        /** @phpstan-ignore-next-line */
        self::assertNull( Json::getNull( $r, 'a' ) );
        $this->expectException( JsonException::class );
        Json::getNull( $r, 'b' );
    }


    public function testGetNullWithMissingKey() : void {
        $stJson = '{"a":null}';
        $r = Json::decode( $stJson );
        $this->expectException( JsonException::class );
        Json::getNull( $r, 'b' );
    }


    public function testGetNumber() : void {
        $stJson = '{"a":1,"b":"foo"}';
        $r = Json::decode( $stJson );
        self::assertSame( 1, Json::getNumber( $r, 'a' ) );
        self::assertSame( 5, Json::getNumber( $r, 'c', 5 ) );
        $this->expectException( JsonException::class );
        Json::getNumber( $r, 'b' );
    }


    public function testGetString() : void {
        $stJson = '{"a":"foo","b":5}';
        $r = Json::decode( $stJson );
        self::assertSame( 'foo', Json::getString( $r, 'a' ) );
        self::assertSame( 'bar', Json::getString( $r, 'c', 'bar' ) );
        $this->expectException( JsonException::class );
        Json::getString( $r, 'b' );
    }


    public function testGetWithMissingKey() : void {
        $stJson = '{"a":1}';
        $r = Json::decode( $stJson );
        $this->expectException( JsonException::class );
        Json::get( $r, 'b' );
    }


    public function testSafeString() : void {
        self::assertSame( 'true', Json::safeString( true ) );
        self::assertSame( 'false', Json::safeString( false ) );
        self::assertSame( 'null', Json::safeString( null ) );
        self::assertSame( '5', Json::safeString( 5 ) );
        self::assertSame( '5.5', Json::safeString( 5.5 ) );
        self::assertSame( 'foo', Json::safeString( 'foo' ) );
        self::assertSame( '{"a":1,"b":2}', Json::safeString( [ 'a' => 1, 'b' => 2 ] ) );
    }


    public function testToFile() : void {
        $r = [ 'a' => 1, 'b' => 2 ];
        $stFilename = tempnam( sys_get_temp_dir(), 'jdwx_json-api-client' );
        Json::toFile( $stFilename, $r );

        $r2 = Json::fromFile( $stFilename );
        self::assertSame( $r, $r2 );
        unlink( $stFilename );

        $this->expectException( JsonException::class );
        Json::toFile( $stFilename, fopen( 'php://memory', 'wb' ) );
    }


    public function testToFileForDirectory() : void {
        $stFilename = sys_get_temp_dir();
        $this->expectException( JsonException::class );
        Json::toFile( $stFilename, [ 'a' => 1, 'b' => 2 ] );
    }


}
