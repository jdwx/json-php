<?php


declare( strict_types = 1 );


namespace JDWX\Json;


use JDWX\Json\Streaming\StreamInput;
use JDWX\Json\Streaming\StringInput;


/**
 * Class JsonLines
 *
 * JSON Lines file utilities. JSON Lines is a format where each line is a JSON object.
 * We provide both full-file encoders and decoders as well as generators for reading
 * and writing single objects.
 */
final class JsonLines {


    public static function decode( string $i_stJsonLines ) : \Generator {
        $input = new StringInput( $i_stJsonLines );
        yield from $input->stream();
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


    public static function fromFile( string $i_stFileName, int $i_uOffset = 0,
                                     int    $i_uLimit = PHP_INT_MAX ) : \Generator {
        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        $stream = @fopen( $i_stFileName, 'r' );
        if ( ! is_resource( $stream ) ) {
            throw new \RuntimeException( "Failed to open file: {$i_stFileName}" );
        }
        yield from self::fromStream( $stream, $i_uOffset, $i_uLimit );
    }


    public static function fromStream( $i_stream, int $i_uOffset = 0,
                                       int $i_uLimit = PHP_INT_MAX ) : \Generator {
        $input = new StreamInput( $i_stream );
        for ( $ii = 0 ; $ii < $i_uOffset ; $ii++ ) {
            $input->next();
        }
        $uCount = 0;
        foreach ( $input->stream() as $x ) {
            if ( $uCount >= $i_uLimit ) {
                break;
            }
            yield $x;
            $uCount++;
        }
        fclose( $i_stream );
    }


}
