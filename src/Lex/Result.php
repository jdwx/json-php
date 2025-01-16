<?php


declare( strict_types = 1 );


namespace JDWX\Json\Lex;


enum Result {


    case INCOMPLETE;

    case INVALID;

    case END_OF_INPUT;

    case EXPECTED_DELIMITER;


}
