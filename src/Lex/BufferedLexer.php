<?php


declare( strict_types = 1 );


namespace JDWX\Json\Lex;


class BufferedLexer {


    protected Lexer $lexer;

    protected bool $bFirst = true;


    public function __construct( protected string $stBuffer = '', Lexer|string $stElementDelimiters = "\r\n",
                                 protected bool   $bEndOfInput = false ) {
        if ( ! $stElementDelimiters instanceof Lexer ) {
            $stElementDelimiters = new Lexer( $stElementDelimiters );
        }
        $this->lexer = $stElementDelimiters;
    }


    /**
     * @return \Generator<string|Result>
     */
    public function __invoke() : \Generator {
        foreach ( $this->lexer->lex( $this->stBuffer, $this->bFirst, $this->bEndOfInput ) as $x ) {
            if ( Result::INCOMPLETE !== $x ) {
                $this->bFirst = false;
            }
            if ( ! is_string( $x ) ) {
                yield $x;
                break;
            }
            $this->stBuffer = substr( $this->stBuffer, strlen( $x ) );
            yield $x;
        }
    }


    public function add( string $i_st ) : void {
        $this->stBuffer .= $i_st;
    }


    public function endOfInput() : void {
        $this->bEndOfInput = true;
    }


}
