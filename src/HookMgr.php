<?php
/**
 * HookMgr manages PHP hooks and associated callables
 *
 * Copyright 2020 Kjell-Inge Gustafsson, kigkonsult, All rights reserved
 * Link <https://kigkonsult.se>
 * Support <https://github.com/iCalcreator/HookMgr>
 *
 * This file is part of HookMgr.
 *
 * HookMgr is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License,
 * or (at your option) any later version.
 *
 * HookMgr is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with HookMgr. If not, see <https://www.gnu.org/licenses/>.
 */
namespace Kigkonsult\HookMgr;

use InvalidArgumentException;
use RuntimeException;

use function array_key_exists;
use function array_keys;
use function call_user_func_array;
use function count;
use function get_class;
use function gettype;
use function is_array;
use function is_callable;
use function is_string;
use function ksort;
use function sprintf;
use function str_replace;
use function trim;
use function var_export;

/**
 * Class HookMgr manages PHP hooks
 *
 * A hook is a (string) key for invoking callable(s)
 *
 * A callable can be
 *   1. simple function
 *   2. anonymous function
 *   3. instantiated object+method                              : [ object, methodName ]
 *   4. class name and static method                            : [ namespaceClassName, methodName ]
 *   5. instantiated object, class with (magic) __call method   : [ object, 'someMethod' ]
 *   6. class name, class with (magic) __callStatic method      : [ namespaceClassName, 'someMethod' ]
 *   7. instantiated object, class with (magic) __invoke method : object
 *
 * Define a hook with callable
 * <code>
 * HookMgr::addHook( $hook, $callable );
 * </code>
 *
 * Invoke a hook with callable
 * <code>
 * $result = HookMgr::apply( $hook );
 * </code>
 *
 * @package Kigkonsult\HookMgr
 */
class HookMgr
{

    /**
     * @var array   [ hook => [ callable ]]
     */
    private static $actions = [];

    /**
     * Add single hook with single callable, syntax_only callable check
     *
     * @param string   $hook
     * @param callable $callable
     * @throws InvalidArgumentException
     */
    public static function addAction( $hook, $callable ) {
        switch( true ) {
            case ( ! is_string( $hook ) || empty( trim( $hook ))) :
                throw new InvalidArgumentException( self::getMsg( $hook ));
                break;
            case ( is_string( $callable ) && empty( trim( $callable ))) :
                throw new InvalidArgumentException( self::getMsg( $hook, $callable ));
                break;
            case ( ! is_array( $callable ) && ! is_callable( $callable, true )) :
                throw new InvalidArgumentException( self::getMsg( $hook, $callable ));
                break;
            case ( ! is_array( $callable )) :
                break;
            case ( ! is_callable( $callable, true )) :
                throw new InvalidArgumentException( self::getMsg( $hook, $callable ));
                break;
            default :
                break;
        }
        if( ! isset( self::$actions[$hook] )) {
            self::$actions[$hook] = [];
            ksort( self::$actions );
        }
        self::$actions[$hook][] = $callable;
    }

    /**
     * Add single hook invoking an array of callable(s)
     *
     * Note, if invoked with arguments, same arguments are used for all callables
     *
     * @param string     $hook
     * @param callable[] $callables
     * @throws InvalidArgumentException
     */
    public static function addActions( $hook, array $callables ) {
        foreach( array_keys( $callables ) as $cIx ) {
            self::addAction( $hook, $callables[$cIx] );
        }
    }

    /**
     * Set array hooks with action(s), each hook (key) with array of callable(s)
     *
     * @param array $actions
     * @throws InvalidArgumentException
     */
    public static function setActions( array $actions ) {
        self::init();
        foreach( $actions as $hook => $callable ) {
            self::addActions( $hook, $callable );
        }
    }

    /**
     * Invoke 'hook' action(s), return (last) result
     *
     * Opt arguments are used in all invoke(s)
     * To use an argument by-reference, use HookMgr::apply( 'hook', [ & $arg ] );
     *
     * @param string $hook
     * @param array  $args
     * @return mixed
     * @throws RuntimeException
     */
    public static function apply( $hook, $args = [] ) {
        if( ! self::exists( $hook )) {
            throw new RuntimeException( self::getMsg( $hook ));
        }
        foreach( array_keys( self::$actions[$hook] ) as $hIx ) {
            if( ! is_callable( self::$actions[$hook][$hIx], false )) {
                throw new RuntimeException( self::getMsg( $hook, self::$actions[$hook][$hIx] ));
            }
            $return = call_user_func_array( self::$actions[$hook][$hIx], $args );
        } // end foreach
        return $return;
    }

    /**
     * Return count of hooks or callables for hook, not found return 0
     *
     * @param string $hook
     * @return bool
     */
    public static function count( $hook = null ) {
        if( null === $hook ) {
            return count( self::$actions );
        }
        return self::exists( $hook ) ? count( self::$actions[$hook] ) : 0;
    }

    /**
     * Return bool true if hook is set
     *
     * @param string $hook
     * @return bool
     */
    public static function exists( $hook ) {
        return array_key_exists( $hook, self::$actions );
    }

    /**
     * Return array callables for hook, not found return []
     *
     * @param string $hook
     * @return array callables[]
     */
    public static function getCallables( $hook ) {
        return self::exists( $hook ) ? self::$actions[$hook] : [];
    }

    /**
     * Return array hooks
     *
     * @return array
     */
    public static function getHooks() {
        return array_keys( self::$actions );
    }

    /**
     * Clear (remove) all hooks with callables
     */
    public static function init() {
        self::$actions = [];
    }

    /**
     * Remove single hook (with callable)
     *
     * @param string   $hook
     */
    public static function remove( $hook ) {
        if( self::exists( $hook )) {
            unset( self::$actions[$hook] );
        }
    }

    /**
     * Return (string) nice rendered hooks with callable(s)
     *
     * @return string
     */
    public static function toString() {
        $output = null;
        $hooks  = array_keys( self::$actions );
        $len    = 0;
        foreach( $hooks as $hook ) {
            if( $len < strlen( $hook )) {
                $len = strlen( $hook );
            }
        }
        foreach( $hooks as $hook ) {
            $hookp = str_pad( $hook, $len );
            foreach( array_keys( self::$actions[$hook] ) as $hIx ) {
                $output .= self::getMsg( $hookp, self::$actions[$hook][$hIx], false ) . PHP_EOL;
            }
        }
        return $output;
    }

    /**
     * Return (Exception) message, opt with nice rendered callable
     *
     * @param string   $hook
     * @param callable $callable
     * @param bool     $exceptionMsg
     * @return string
     */
    private static function getMsg( $hook, $callable = null, $exceptionMsg = true ) {
        static $ERR1    = 'Invalid/unFound hook (string) : %s';
        static $ERR2    = '%s : %s';
        static $OBJECT  = 'object';
        static $OBJECT2 = '(obj)  ';
        static $FCN     = '(fcn)  ';
        static $FQCN    = '(fqcn) ';
        static $Q       = '(?)    ';
        static $SEARCH  = [ PHP_EOL, ' ' ];
        static $EMPTY   = '';
        static $DC      = '::';
        static $DA      = '->';
        static $ERRPRFX = 'Invalid ';
        if( empty( $callable )) {
            return sprintf( $ERR1, var_export( $hook, true ));
        }
        switch( true ) {
            case ( $OBJECT == gettype( $callable )) :
                $type = $OBJECT2 . get_class( $callable );
                break;
            case ( is_string( $callable ) && function_exists(  $callable  )) :
                $type = $FCN . $callable;
                break;
            case is_string( $callable ) :
                $type = $FQCN . $callable;
                break;
            case ! is_array( $callable ) :
                $type = $Q . str_replace( $SEARCH, $EMPTY, var_export( $callable, true ));
                break;
            default :
                $pd = $DC;
                switch( true ) {
                    case ( $OBJECT == gettype( $callable[0] )) :
                        $type = $OBJECT2 . get_class( $callable[0] );
                        $pd = $DA;
                        break;
                    case is_string( $callable[0] ) :
                        $type = $FQCN . $callable[0];
                        break;
                    default :
                        $type = $Q . str_replace( $SEARCH, $EMPTY, var_export( $callable[0], true ));
                        break;
                }
                if( isset( $callable[1] )) {
                    $type .= $pd . $callable[1];
                }
                break;
        }
        return ( $exceptionMsg ? $ERRPRFX : $EMPTY ) . sprintf( $ERR2, $hook, $type );
    }

}
