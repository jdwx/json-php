<?php


declare( strict_types = 1 );


namespace JDWX\Json;


/**
 * Class JsonLines
 *
 * JSON Lines file utilities. JSON Lines is a format where each line is a JSON object.
 * We provide both full-file encoders and decoders as well as generators for reading
 * and writing single objects.
 */
final class JsonLines {


    public static function decode( string $i_stJsonLines ) : \Generator {
        # To avoid copies, we'll walk through the string by offsets looking
        # for newlines. This is a bit more complex than just exploding on
        # newlines, but it's much more memory-efficient.
        $uOffset = 0;
        $uNewOffset = strpos( $i_stJsonLines, "\n", $uOffset );
        while ( $uNewOffset !== false ) {
            $stLine = substr( $i_stJsonLines, $uOffset, $uNewOffset - $uOffset );
            $uOffset = $uNewOffset + 1;
            $uNewOffset = strpos( $i_stJsonLines, "\n", $uOffset );
            yield Json::decode( $stLine );
        }
        $stLine = substr( $i_stJsonLines, $uOffset );
        if ( $stLine !== '' ) {
            yield Json::decode( $stLine );
        }
    }


    public static function decodeAll( string $i_stJsonLines ) : array {
        return iterator_to_array( self::decode( $i_stJsonLines ) );
    }


    /**
     * @param iterable<int, mixed> $i_itObjects
     */
    public static function encode( iterable $i_itObjects ) : string {
        $st = '';
        foreach ( $i_itObjects as $rObject ) {
            $st .= Json::encode( $rObject ) . "\n";
        }
        return $st;
    }


    public static function fromFile( string $i_stFileName ) : \Generator {
        $stream = @fopen( $i_stFileName, 'r' );
        if ( $stream === false ) {
            throw new \RuntimeException( "Failed to open file: {$i_stFileName}" );
        }
        yield from self::fromStream( $stream );
    }


    public static function fromStream( $i_stream ) : \Generator {
        while ( true ) {
            $x = self::fromStreamOnce( $i_stream );
            if ( Result::EOF === $x ) {
                break;
            }
            if ( Result::NOTHING === $x ) {
                continue;
            }
            yield $x;
        }
        fclose( $i_stream );
    }


    public static function fromStreamOnce( $i_stream ) : int|float|bool|string|array|null|Result {
        $stLine = fgets( $i_stream );
        if ( $stLine === false ) {
            return Result::EOF;
        }
        $stLine = trim( $stLine );
        if ( $stLine === '' ) {
            return Result::NOTHING;
        }
        return Json::decode( $stLine );
    }


}
