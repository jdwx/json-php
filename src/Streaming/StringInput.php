<?php


declare( strict_types = 1 );


namespace JDWX\Json\Streaming;


class StringInput extends AbstractInput {


    private int $uOffset = 0;


    public function __construct( private readonly string $stData, bool $i_bSkipOuterArray = false,
                                 int                     $i_uBufferSize = self::DEFAULT_BUFFER_SIZE,
                                 int                     $i_uMaxObjectSize = self::DEFAULT_MAX_OBJECT_SIZE ) {
        parent::__construct( $i_bSkipOuterArray, $i_uBufferSize, $i_uMaxObjectSize );
    }


    protected function eof() : bool {
        return $this->uOffset >= strlen( $this->stData );
    }


    protected function read( int $i_uLength ) : string {
        $stData = substr( $this->stData, $this->uOffset, $i_uLength );
        $this->uOffset += strlen( $stData );
        return $stData;
    }


}
