<?php


declare( strict_types = 1 );


namespace JDWX\Json;


enum Result {


    case EOF;

    case NOTHING;


    public static function check( mixed $i_x ) : bool {
        return $i_x instanceof self;
    }


}
