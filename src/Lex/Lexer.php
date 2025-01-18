<?php


declare( strict_types = 1 );


namespace JDWX\Json\Lex;


/**
 * We are lexing JSON ourselves because we want to clearly distinguish
 * between incomplete values that might be valid with more date from
 * values that are already provably invalid.
 *
 * We're not actually going to parse it; PHP's already-battle-tested JSON
 * parser does that very well. We're just trying to figure out how
 * much of a given input we should hand to it.
 *
 * Some of these methods are exposed as public in case you want to lex
 * a partial value yourself. But the main entry point is the element()
 * method, which is what you'd call to lex a complete JSON value.
 *
 * For all methods, the return value is either as much of the input string
 * as should be passed to json_decode, or a Result enum indicating that
 * the input is either incomplete or invalid.
 *
 * The usual way to use this is to pass in your input string, get a substring
 * back, and then pass that substring to json_decode. Then chop the
 * substring off the front of the original input string and call the lexer
 * again. Repeat until you run out of input.
 */
class Lexer {


    private const string JSON_MARKERS                    = '{}[],:';

    private const string JSON_WHITESPACE                 = " \t\n\r";

    private const string JSON_POTENTIALLY_VALID_TRAILERS = self::JSON_WHITESPACE . ':,]}';


    /**
     * @param string $stElementDelimiters Any characters in this string will
     *                                    be allowed as delimiters between
     *                                    JSON elements.
     */
    public function __construct( private readonly string $stElementDelimiters = "\r\n" ) {}


    /**
     * @return \Generator<string|Result>
     */
    public function __invoke( string $i_st, bool $i_bFirst, bool $i_bEndOfInput ) : \Generator {
        return $this->lex( $i_st, $i_bFirst, $i_bEndOfInput );
    }


    public function array( string $i_st, bool $i_bEndOfInput ) : string|Result {
        if ( '' === $i_st ) {
            return Result::INCOMPLETE;
        }
        if ( '[' !== $i_st[ 0 ] ) {
            return Result::INVALID;
        }

        $st = substr( $i_st, 1 );
        $stWhitespace = $this->whitespace( $st );
        $st = substr( $st, strlen( $stWhitespace ) );

        if ( '' === $st ) {
            return Result::INCOMPLETE;
        }

        $stOut = '[' . $stWhitespace;

        if ( ']' === $st[ 0 ] ) {
            $stRest = substr( $st, 1 );
            if ( $this->invalidTrailer( $stRest ) ) {
                return Result::INVALID;
            }
            return $stOut . ']';
        }

        $bFirst = true;
        while ( true ) {
            if ( '' === $st ) {
                return Result::INCOMPLETE;
            }
            if ( $bFirst ) {
                $stInnerWhitespace = $this->whitespace( $st );
                $bFirst = false;
            } else {
                $stInnerWhitespace = $this->marker( $st, ',]', false );
                if ( ! is_string( $stInnerWhitespace ) ) {
                    return $stInnerWhitespace;
                }
                if ( str_contains( $stInnerWhitespace, ']' ) ) {
                    $stRest = substr( $st, strlen( $stInnerWhitespace ) );
                    if ( $this->invalidTrailer( $stRest ) ) {
                        return Result::INVALID;
                    }
                    return $stOut . $stInnerWhitespace;
                }
                $stMoreWhitespace = $this->whitespace( substr( $st, strlen( $stInnerWhitespace ) ) );
                $stInnerWhitespace .= $stMoreWhitespace;
            }

            $st = substr( $st, strlen( $stInnerWhitespace ) );
            $stOut .= $stInnerWhitespace;

            $xElement = $this->value( $st, $i_bEndOfInput );
            if ( ! is_string( $xElement ) ) {
                return $xElement;
            }
            $stOut .= $xElement;
            $st = substr( $st, strlen( $xElement ) );
        }
    }


    /**
     * The main differences between an element and a value (for our purposes) are:
     *
     * - An element is allowed to have leading whitespace, a value is not.
     *
     * - An element checks to see if what comes next would screw up an otherwise
     *  valid JSON string. A value does not.
     *
     */
    public function element( string $i_st, bool $i_bBeginningOfInput, bool $i_bEndOfInput ) : string|Result {

        # We might see leading whitespace.
        $stWhitespace = static::whitespace( $i_st );
        $st = substr( $i_st, strlen( $stWhitespace ) );

        # And if there's nothing else, we'll call it early.
        if ( '' === $st ) {
            if ( $i_bEndOfInput ) {
                return Result::END_OF_INPUT;
            }
            return Result::INCOMPLETE;
        }

        # Otherwise, we expect to see a delimiter, and we're going to start
        # over with $i_st so we get any leading whitespace in the delimiter.
        if ( ! $i_bBeginningOfInput ) {
            $xDelim = self::delimiter( $i_st );
            if ( ! is_string( $xDelim ) ) {
                return $xDelim;
            }
            $stWhitespace = $xDelim;
            $st = substr( $i_st, strlen( $xDelim ) );

            # We'll try again to call it early if there's nothing else.
            if ( '' === $st ) {
                if ( $i_bEndOfInput ) {
                    return Result::END_OF_INPUT;
                }
                return Result::INCOMPLETE;
            }
        }

        $x = $this->value( $st, $i_bEndOfInput );
        if ( ! is_string( $x ) ) {
            return $x;
        }

        # We don't actually want to return arbitrary whitespace and the
        # delimiter, because that might break the eventual decode, but
        # we need the string to be the right length so the user will
        # know how much of the input we consumed. So we'll convert all
        # that to leading spaces that they can trim() off or even
        # safely ignore.
        $stWhitespace = str_repeat( ' ', strlen( $stWhitespace ) );
        return $stWhitespace . $x;
    }


    public function false( string $i_st ) : string|Result {
        return $this->word( $i_st, 'false' );
    }


    /**
     * @return \Generator<string|Result>
     */
    public function lex( string $i_st, bool $i_bFirst, bool $i_bEndOfInput ) : \Generator {
        while ( true ) {
            $x = $this->element( $i_st, $i_bFirst, $i_bEndOfInput );
            if ( Result::END_OF_INPUT === $x ) {
                break;
            }
            if ( Result::INCOMPLETE === $x && $i_bEndOfInput ) {
                yield Result::INVALID;
                break;
            }
            if ( ! is_string( $x ) ) {
                yield $x;
                break;
            }
            $i_st = substr( $i_st, strlen( $x ) );
            $i_bFirst = false;
            yield $x;
        }
    }


    /**
     * A marker is a JSON marker character surrounded by any amount
     * (or no amount) of whitespace.  It differs from a delimiter
     * because one marker appears exactly once, whereas multiple
     * delimiters can appear multiple times intermixed with
     * whitespace.
     */
    public function marker( string $i_st, ?string $i_stSpecificMarkers,
                            bool   $i_bIncludeWhitespaceAfter ) : string|Result {
        $stWhitespace = $this->whitespace( $i_st );
        $st = substr( $i_st, strlen( $stWhitespace ) );
        if ( '' === $st ) {
            return Result::INCOMPLETE;
        }
        if ( ! str_contains( $i_stSpecificMarkers ?? self::JSON_MARKERS, $st[ 0 ] ) ) {
            return Result::INVALID;
        }
        $stMarker = $st[ 0 ];
        $stMoreWhitespace = '';
        if ( $i_bIncludeWhitespaceAfter ) {
            $stRest = substr( $st, 1 );
            $stMoreWhitespace = $this->whitespace( $stRest );
        }
        return $stWhitespace . $stMarker . $stMoreWhitespace;
    }


    public function null( string $i_st ) : string|Result {
        return $this->word( $i_st, 'null' );
    }


    public function number( string $st, bool $i_bEndOfInput ) : string|Result {
        if ( $st === '' ) {
            if ( $i_bEndOfInput ) {
                return Result::INVALID;
            }
            return Result::INCOMPLETE;
        }

        # The JSON spec defines a number as: integer fraction exponent. We will
        # (try to) split the string into those three parts.

        $xInt = $this->numberInteger( $st, $i_bEndOfInput );
        if ( ! is_string( $xInt ) ) {
            return $xInt;
        }
        $stRest = substr( $st, strlen( $xInt ) );

        $xFrac = $this->numberFraction( $stRest, $i_bEndOfInput );
        if ( ! is_string( $xFrac ) ) {
            return $xFrac;
        }
        $stRest = substr( $stRest, strlen( $xFrac ) );

        $xExp = $this->numberExponent( $stRest, $i_bEndOfInput );
        if ( ! is_string( $xExp ) ) {
            return $xExp;
        }
        $stRest = substr( $stRest, strlen( $xExp ) );
        if ( $this->invalidTrailer( $stRest ) ) {
            return Result::INVALID;
        }

        return $xInt . $xFrac . $xExp;

    }


    public function object( string $i_st, bool $i_bEndOfInput ) : string|Result {
        if ( '' === $i_st ) {
            return Result::INCOMPLETE;
        }
        if ( '{' !== $i_st[ 0 ] ) {
            return Result::INVALID;
        }

        $st = substr( $i_st, 1 );
        $stWhitespace = $this->whitespace( $st );
        $st = substr( $st, strlen( $stWhitespace ) );

        if ( '' === $st ) {
            return Result::INCOMPLETE;
        }

        $stOut = '{' . $stWhitespace;

        if ( '}' === $st[ 0 ] ) {
            $stRest = substr( $st, 1 );
            if ( $this->invalidTrailer( $stRest ) ) {
                return Result::INVALID;
            }
            return $stOut . '}';
        }

        $bFirst = true;
        while ( true ) {
            if ( $bFirst ) {
                $stInnerWhitespace = $this->whitespace( $st );
                $bFirst = false;
            } else {
                $stInnerWhitespace = $this->marker( $st, ',}', false );
                if ( ! is_string( $stInnerWhitespace ) ) {
                    return $stInnerWhitespace;
                }
                if ( str_contains( $stInnerWhitespace, '}' ) ) {
                    $stRest = substr( $st, strlen( $stInnerWhitespace ) );
                    if ( $this->invalidTrailer( $stRest ) ) {
                        return Result::INVALID;
                    }
                    return $stOut . $stInnerWhitespace;
                }
                $stMoreWhitespace = $this->whitespace( substr( $st, strlen( $stInnerWhitespace ) ) );
                $stInnerWhitespace .= $stMoreWhitespace;
            }

            $st = substr( $st, strlen( $stInnerWhitespace ) );
            $stOut .= $stInnerWhitespace;
            if ( '' === $st ) {
                return Result::INCOMPLETE;
            }

            $xKey = $this->string( $st );
            if ( ! is_string( $xKey ) ) {
                return $xKey;
            }
            $stOut .= $xKey;
            $st = substr( $st, strlen( $xKey ) );

            $xColon = $this->marker( $st, ':', true );
            if ( ! is_string( $xColon ) ) {
                return $xColon;
            }
            $stOut .= $xColon;
            $st = substr( $st, strlen( $xColon ) );
            if ( '' === $st ) {
                return Result::INCOMPLETE;
            }

            $xValue = $this->value( $st, $i_bEndOfInput );
            if ( ! is_string( $xValue ) ) {
                return $xValue;
            }
            $stOut .= $xValue;
            $st = substr( $st, strlen( $xValue ) );

        }

    }


    public function string( string $i_st ) : string|Result {

        # Edge cases.
        if ( '' === $i_st ) {
            return Result::INCOMPLETE;
        }
        if ( '"' !== $i_st[ 0 ] ) {
            return Result::INVALID;
        }
        if ( '"' === $i_st ) {
            return Result::INCOMPLETE;
        }

        # We don't need to parse the string, just find the end of it, accounting for
        # escaped quotes inside it.

        $uOffset = 1;
        while ( $uNewOffset = strpos( $i_st, '"', $uOffset ) ) {
            if ( $i_st[ $uNewOffset - 1 ] !== '\\' ) {
                $stOut = substr( $i_st, 0, $uNewOffset + 1 );
                $stRest = substr( $i_st, strlen( $stOut ) );
                if ( $this->invalidTrailer( $stRest ) ) {
                    return Result::INVALID;
                }
                return $stOut;
            }
            $uOffset = $uNewOffset + 1;
        }
        return Result::INCOMPLETE;
    }


    public function true( string $i_st ) : string|Result {
        return $this->word( $i_st, 'true' );
    }


    /**
     * The main difference between an element and a value (for our purposes) is
     * that an element is allowed to have leading whitespace. So in most cases,
     * it is preferable to call element().
     *
     * @suppress PhanParamSuspiciousOrder
     */
    public function value( string $st, bool $i_bEndOfInput ) : string|Result {

        $stFirst = $st[ 0 ];

        if ( str_contains( '0123456789-', $stFirst ) ) {
            return $this->number( $st, $i_bEndOfInput );
        }

        if ( $stFirst === 't' ) {
            return $this->word( $st, 'true' );
        }

        if ( $stFirst === 'f' ) {
            return $this->word( $st, 'false' );
        }

        if ( $stFirst === 'n' ) {
            return $this->word( $st, 'null' );
        }

        if ( $stFirst === '"' ) {
            return $this->string( $st );
        }

        if ( $stFirst === '[' ) {
            return $this->array( $st, $i_bEndOfInput );
        }

        if ( $stFirst === '{' ) {
            return $this->object( $st, $i_bEndOfInput );
        }

        return Result::INVALID;

    }


    /**
     * @param string $i_st
     * @return string The whitespace at the beginning of the string. (Might be empty.)
     */
    public function whitespace( string $i_st ) : string {
        $uLen = strspn( $i_st, self::JSON_WHITESPACE );
        return substr( $i_st, 0, $uLen );
    }


    /**
     *
     * For our purposes, a delimiter is kind of like special-case whitespace.
     * It can contain any combination of whitespace characters and delimiters,
     * but must contain at least one delimiter.
     *
     * @param string $i_st
     * @return string|Result
     */
    private function delimiter( string $i_st ) : string|Result {
        # If you ever wondered what strspn() is for, this is what it's for.
        $uLen = strspn( $i_st, $this->stElementDelimiters . self::JSON_WHITESPACE );
        if ( $uLen === 0 ) {
            return Result::EXPECTED_DELIMITER;
        }
        $stDelim = substr( $i_st, 0, $uLen );

        # Make sure at least one delimiter exists in what we found.
        # (I.e., it's not all whitespace.)

        if ( false === strpbrk( $stDelim, $this->stElementDelimiters ) ) {
            return Result::EXPECTED_DELIMITER;
        }

        return $stDelim;
    }


    /** There are two types of characters that immediately follow a complete JSON value:
     * 1) Characters that might be invalid.
     * 2) Characters that are definitely invalid.
     *
     * This method checks for the latter.
     *
     * @suppress PhanParamSuspiciousOrder
     */
    private function invalidTrailer( string $i_st ) : bool {
        if ( '' === $i_st ) {
            return false;
        }
        return ! str_contains( self::JSON_POTENTIALLY_VALID_TRAILERS, $i_st[ 0 ] );
    }


    private function numberDigits( string $i_st, bool $i_bEndOfInput ) : string|Result {

        if ( '' === $i_st && ! $i_bEndOfInput ) {
            return Result::INCOMPLETE;
        }

        # Capture as many digits as we can from the front.
        preg_match( '/^([0-9]+)/', $i_st, $rMatch );
        $stDigits = $rMatch[ 1 ] ?? '';

        # We know the string wasn't empty, so if there are no digits here, it's invalid.
        if ( '' === $stDigits ) {
            return Result::INVALID;
        }

        $stRest = substr( $i_st, strlen( $stDigits ) );

        # If the rest is empty and we're at the end of the input, we're done.
        if ( $stRest === '' && $i_bEndOfInput ) {
            return $stDigits;
        }

        # If there's stuff after the digits, we're done.
        if ( $stRest !== '' ) {
            return $stDigits;
        }

        # Otherwise, we're incomplete. Maybe the next character will be a digit too!
        return Result::INCOMPLETE;

    }


    private function numberExponent( string $st, bool $i_bEndOfInput ) : string|Result {
        # The exponent part of a number is optional.
        if ( '' === $st && $i_bEndOfInput ) {
            return '';
        }

        # See numberFraction().
        assert( $st !== '' );

        # If the first character is anything but an 'e' or 'E', this is a complete (but empty)
        # exponent.
        if ( 'e' !== $st[ 0 ] && 'E' !== $st[ 0 ] ) {
            return '';
        }

        $stE = $st[ 0 ];
        $st = substr( $st, 1 );

        # If there's nothing left, it's either incomplete or invalid.
        if ( $st === '' ) {
            if ( $i_bEndOfInput ) {
                return Result::INVALID;
            }
            return Result::INCOMPLETE;
        }

        # Now we might have a sign.
        $stSign = '';
        if ( '-' === $st[ 0 ] || '+' === $st[ 0 ] ) {
            $stSign = $st[ 0 ];
            $st = substr( $st, 1 );
            # If there's nothing left, it's either incomplete or invalid.
            if ( $st === '' ) {
                if ( $i_bEndOfInput ) {
                    return Result::INVALID;
                }
                return Result::INCOMPLETE;
            }
        }

        # What's left had better be digits!
        # So, for us to be done, we need to see one of two things. Either $i_bEndOfInput

        $x = $this->numberDigits( $st, $i_bEndOfInput );
        assert( $x !== '' );
        if ( is_string( $x ) ) {
            return $stE . $stSign . $x;
        }

        return $x;

    }


    private function numberFraction( string $st, bool $i_bEndOfInput ) : string|Result {
        # The fractional part of a number is optional.
        if ( '' === $st && $i_bEndOfInput ) {
            return '';
        }

        # As far as I know, this cannot happen. If you have an empty string
        # but it's not the end of input, the next character might a be a
        # digit that would continue the integer part. So numberInteger() would
        # have returned incomplete already.
        assert( $st !== '' );

        # If the first character is anything but a decimal point, this is a complete (but empty)
        # fraction.
        if ( '.' !== $st[ 0 ] ) {
            return '';
        }
        $st = substr( $st, 1 );

        # What's left had better be digits!
        $x = $this->numberDigits( $st, $i_bEndOfInput );
        if ( is_string( $x ) ) {
            return '.' . $x;
        }
        return $x;
    }


    private function numberInteger( string $st, bool $i_bEndOfInput ) : string|Result {

        # In JSON, there are four valid branches for an integer:
        # 1. A single digit.
        # 2. A 1-9 followed by any number of digits.
        # 3. A negative sign followed by a single digit.
        # 4. A negative sign followed by a 1-9 followed by any number of digits.
        #
        # The only "weird" case this allows is "-0", which is valid JSON but not
        # something we usually think of as normal.

        # We'll capture as many potentially relevant characters as we can.
        preg_match( '/^([-0-9]+)/', $st, $rMatch );
        $stInt = $rMatch[ 1 ] ?? '';

        # If we got nothing, there's no integer here.
        if ( $stInt === '' ) {
            return Result::INVALID;
        }

        # Check all four cases.
        $bCase1 = preg_match( '/^[0-9]$/', $stInt );
        $bCase2 = preg_match( '/^[1-9][0-9]*$/', $stInt );
        $bCase3 = preg_match( '/^-([0-9])$/', $stInt );
        $bCase4 = preg_match( '/^-([1-9][0-9]*)$/', $stInt );

        # If none of the cases match, this isn't a valid integer.
        if ( ! ( $bCase1 || $bCase2 || $bCase3 || $bCase4 ) ) {
            return Result::INVALID;
        }

        # Figure out if we're done.

        $stLeftover = substr( $st, strlen( $stInt ) );
        if ( $i_bEndOfInput && '' === $stLeftover || $stLeftover ) {
            return $stInt;
        }

        return Result::INCOMPLETE;

    }


    private function word( string $st, string $stWord ) : string|Result {
        if ( '' === $st ) {
            return Result::INCOMPLETE;
        }
        if ( $st === $stWord ) {
            return $stWord;
        }
        if ( str_starts_with( $stWord, $st ) ) {
            return Result::INCOMPLETE;
        }
        if ( ! str_starts_with( $st, $stWord ) ) {
            return Result::INVALID;
        }
        $stRest = substr( $st, strlen( $stWord ) );
        if ( $this->invalidTrailer( $stRest ) ) {
            return Result::INVALID;
        }
        return $stWord;
    }


}
