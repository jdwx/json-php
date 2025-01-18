<?php


declare( strict_types = 1 );


namespace JDWX\Json\Streaming;


use JsonException;


class StreamInput extends AbstractInput {


    /** @var resource */
    private $stream;


    public function __construct( $i_stream, bool $i_bSkipOuterArray = false,
                                 int $i_uBufferSize = self::DEFAULT_BUFFER_SIZE,
                                 int $i_uMaxReadSize = self::DEFAULT_MAX_READ_SIZE,
                                 string|null $i_elementDelimiters = null ) {
        parent::__construct( $i_bSkipOuterArray, $i_uBufferSize, $i_uMaxReadSize, $i_elementDelimiters );
        $this->stream = $i_stream;
    }


    protected function eof() : bool {
        try {
            /** @noinspection PhpUsageOfSilenceOperatorInspection */
            return @feof( $this->stream );
        } catch ( \TypeError $e ) {
            throw new JsonException( 'Error checking for end of stream', previous: $e );
        }
    }


    protected function read( int $i_uLength ) : string {
        $x = fread( $this->stream, $i_uLength );
        if ( ! is_string( $x ) ) {
            # Because fread() always comes right after feof(), there's no good
            # way to test this.
            throw new JsonException( 'Error reading from stream' );
        }
        return $x;
    }


}
