<?php


declare( strict_types = 1 );


namespace JDWX\Json\Streaming;


use JDWX\Json\Json;
use JDWX\Json\Lex\Lexer;
use JDWX\Json\Lex\Result;
use JsonException;


abstract class AbstractInput {


    public const int DEFAULT_BUFFER_SIZE   = 1_048_576;

    public const int DEFAULT_MAX_READ_SIZE = 65_536;

    private string $stBuffer = '';

    private bool $bOuterArraySkipped = false;

    private Lexer $lexer;

    private bool $bFirst = true;


    public function __construct( private readonly bool $bSkipOuterArray = false,
                                 private readonly int  $uBufferSize = self::DEFAULT_BUFFER_SIZE,
                                 private readonly int  $uMaxReadSize = self::DEFAULT_MAX_READ_SIZE,
                                 string|null           $i_elementDelimiters = null ) {
        if ( $bSkipOuterArray && ! is_null( $i_elementDelimiters ) ) {
            throw new \InvalidArgumentException( 'Cannot skip outer array and specify element delimiters' );
        }
        if ( $bSkipOuterArray ) {
            $i_elementDelimiters = ',';
        } elseif ( is_null( $i_elementDelimiters ) ) {
            $i_elementDelimiters = "\r\n";
        }
        $this->lexer = new Lexer( $i_elementDelimiters );
    }


    /** @return int|array|bool|float|null|string|Result */
    public function next() : mixed {

        # Skip the outer array if requested
        if ( $this->bFirst && $this->bSkipOuterArray && ! $this->bOuterArraySkipped ) {
            while ( true ) {
                $x = $this->lexer->marker( $this->stBuffer, '[', false );
                if ( is_string( $x ) ) {
                    break;
                }
                if ( $this->eof() ) {
                    throw new JsonException( 'No outer JSON array found' );
                }
                $this->fill();
            }
            $uPos = strpos( $this->stBuffer, '[' );
            assert( is_int( $uPos ) );
            $this->stBuffer = substr( $this->stBuffer, $uPos + 1 );
            $this->bOuterArraySkipped = true;
        }

        # If we have skipped the outer array and the next marker is
        # a close bracket, we've reached the end of the outer array.
        # Skip the bracket and reset the flag. We are probably done,
        # but we'll let the lexer decide.
        if ( $this->bSkipOuterArray && $this->bOuterArraySkipped ) {
            $x = $this->lexer->marker( $this->stBuffer, ']', true );
            if ( is_string( $x ) ) {
                $this->stBuffer = substr( $this->stBuffer, strlen( $x ) );
                $this->bOuterArraySkipped = false;
            }
        }

        # Read JSON elements one at a time until something unusual happens
        while ( Result::INCOMPLETE === ( $x = $this->nextItem() ) ) {
            if ( $this->eof() ) {
                return Result::INCOMPLETE;
            }
            $this->fill();
        }

        if ( ! is_string( $x ) ) {
            return $x;
        }
        $this->bFirst = false;
        $this->stBuffer = substr( $this->stBuffer, strlen( $x ) );
        return Json::decode( $x );
    }


    public function stream() : \Generator {
        while ( true ) {
            $x = $this->next();
            if ( $x === Result::END_OF_INPUT ) {
                break;
            }
            if ( $x instanceof Result ) {
                throw new JsonException( 'Incomplete decode at end of input: ' . substr( $this->stBuffer, 0, 80 ) );
            }
            yield $x;
        }
    }


    abstract protected function eof() : bool;


    protected function fill() : void {
        if ( strlen( $this->stBuffer ) >= $this->uBufferSize ) {
            throw new JsonException(
                'JSON object size exceeds buffer size of ' . number_format( $this->uBufferSize )
            );
        }
        $uLength = min( $this->uMaxReadSize, $this->uBufferSize - strlen( $this->stBuffer ) );
        $this->stBuffer .= $this->read( $uLength );
    }


    protected function nextItem() : string|Result {
        return $this->lexer->element( $this->stBuffer, $this->bFirst, $this->eof() );
    }


    abstract protected function read( int $i_uLength ) : string;


}
