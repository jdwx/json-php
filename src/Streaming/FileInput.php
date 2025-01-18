<?php


declare( strict_types = 1 );


namespace JDWX\Json\Streaming;


use JsonException;


class FileInput extends StreamInput {


    public function __construct( string      $i_stFilePath,
                                 bool        $i_bSkipOuterArray = false,
                                 int         $i_uBufferSize = self::DEFAULT_BUFFER_SIZE,
                                 int         $i_uMaxReadSize = self::DEFAULT_MAX_READ_SIZE,
                                 string|null $i_elementDelimiters = null ) {
        $fp = fopen( $i_stFilePath, 'r' );
        if ( ! is_resource( $fp ) ) {
            throw new JsonException( 'Error opening file: ' . $i_stFilePath );
        }
        parent::__construct( $fp, $i_bSkipOuterArray, $i_uBufferSize, $i_uMaxReadSize, $i_elementDelimiters );
    }


}
