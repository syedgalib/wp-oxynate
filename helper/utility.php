<?php


/**
 * Oxynate is Truthy
 * 
 * @param mixed $value
 * @return bool
 */
function oxynate_is_truthy( $value = '' ) {

    if ( true === $value ) {
        return true;
    }

    if ( 'true' === $value ) {
        return true;
    }

    if ( 1 === $value ) {
        return true;
    }

    if ( '1' === $value ) {
        return true;
    }

    if ( 'yes' === $value ) {
        return true;
    }

    return false;
}