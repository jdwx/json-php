<?php


declare( strict_types = 1 );


namespace JDWX\Json\Lex;


/**
 * The callback lexer is a buffered lexer that calls a callback when it needs more data.
 * This allows you to have one foreach loop that reads all the data from a stream without
 * any need to handle loading additional data on incomplete results.
 */
class CallbackLexer extends BufferedLexer {


    /** @param callable $callback */
    public function __construct( private      $callback, string $i_stBuffer = '',
                                 Lexer|string $i_xElementDelimiters = "\r\n",
                                 bool         $i_bEndOfInput = false ) {
        parent::__construct( $i_stBuffer, $i_xElementDelimiters, $i_bEndOfInput );
    }


    public function __invoke() : \Generator {
        while ( true ) {
            $x = $this->lexer->element( $this->stBuffer, $this->bFirst, $this->bEndOfInput );
            if ( Result::END_OF_INPUT === $x ) {
                break;
            }
            if ( Result::INCOMPLETE === $x ) {
                if ( $this->bEndOfInput ) {
                    yield Result::INVALID;
                    break;
                }
                $nst = ( $this->callback )();
                if ( null === $nst ) {
                    $this->bEndOfInput = true;
                } else {
                    $this->add( $nst );
                }
                continue;
            }
            if ( ! is_string( $x ) ) {
                yield $x;
                break;
            }
            $this->stBuffer = substr( $this->stBuffer, strlen( $x ) );
            $this->bFirst = false;
            yield $x;
        }
    }


}
