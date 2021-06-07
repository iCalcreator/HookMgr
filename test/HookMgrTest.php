<?php
/**
 * HookMgr manages PHP hooks and associated callables
 *
 * This file is part of HookMgr.
 *
 * @author    Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @copyright 2020-21 Kjell-Inge Gustafsson, kigkonsult, All rights reserved
 * @link      https://kigkonsult.se
 * @license   Subject matter of licence is the software HookMgr.
 *            The above copyright, link, package and version notices,
 *            this licence notice shall be included in all copies or substantial
 *            portions of the HookMgr.
 *
 *            HookMgr is free software: you can redistribute it and/or modify
 *            it under the terms of the GNU Lesser General Public License as
 *            published by the Free Software Foundation, either version 3 of
 *            the License, or (at your option) any later version.
 *
 *            HookMgr is distributed in the hope that it will be useful,
 *            but WITHOUT ANY WARRANTY; without even the implied warranty of
 *            MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *            GNU Lesser General Public License for more details.
 *
 *            You should have received a copy of the GNU Lesser General Public License
 *            along with HookMgr. If not, see <https://www.gnu.org/licenses/>.
 */
namespace Kigkonsult\HookMgr;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;
use TypeError;

/*
 * A callable can be
 *   1. simple function
 *   2. anonymous function
 *   3. instantiated object+method                              : [ object, methodName ]
 *   4. class name and static method                            : [ namespaceClassName, methodName ]
 *   5. instantiated object, class with (magic) __call method   : [ object, 'someMethod' ]
 *   6. class name, class has an (magic) __callStatic method    : [ namespaceClassName, 'someMethod' ]
 *   7. instantiated object, class eith (magic) __invoke method : object
 */

define ('HALLOWORLD', 'Hallo world' );

function callable1( $arg1 = null, & $arg2 = [] ) : string
{
    $arg2[] = 1;
    return HALLOWORLD . ' 1 : ' . $arg1;
}

class Callable3
{
    public function callable3Method( $arg1 = null, & $arg2 = [] ) : string
    {
        $arg2[] = 3;
        return HALLOWORLD . ' 3 : ' . $arg1;
    }
}

class Callable4
{
    public static function callable4( $arg1 = null, & $arg2 = [] ) : string
    {
        $arg2[] = 4;
        return HALLOWORLD . ' 4 : ' . $arg1;
    }
}

class Callable5
{
    public function __call( $arg1, $argArr2 ) : string
    {
        // arg1 : 'method'
        // arg2 : array arguments
        if( isset( $argArr2[1] )) {
            $argArr2[1][] = 5;
        }
        return HALLOWORLD . ' 5 : ' . $argArr2[0];
    }
}

class Callable6
{
    public static function __callStatic( $arg1, $argArr2 ) : string
    {
        // arg1 : static 'method'
        // arg2 : array arguments
        if( isset( $argArr2[1] )) {
            $argArr2[1][] = 6;
        }
        return HALLOWORLD . ' 6 : ' . $argArr2[0];
    }
}
class Callable7
{
    public function __invoke( $arg1 = null, & $arg2 = [] ) : string
    {
        $arg2[] = 7;
        return HALLOWORLD . ' 7 : ' . $arg1;
    }
}

class HookMgrTest extends TestCase
{

    /**
     * Testing 'hooks'
     *
     * @test
     */
    public function HookMgrTest1()
    {
        HookMgr::init();

        $CALLABLE = 'callable';
        $EXPECTED = HALLOWORLD . ' %1$d : callable%1$d';

        // 1. simple function
        $hook = $arg = $CALLABLE . 1;
        HookMgr::addAction( $hook, 'Kigkonsult\HookMgr\callable1' );
        $this->assertTrue( HookMgr::exists( $hook ));
        $this->assertEquals( 1, HookMgr::count( $hook ));
        $this->assertEquals( 'Kigkonsult\HookMgr\callable1', HookMgr::getCallables( $hook )[0] );

        $this->assertEquals(
            sprintf( $EXPECTED, 1 ) ,
            HookMgr::apply( $hook, [ $arg ] )
        );

        // 2. anonymous function
        $hook = $arg = $CALLABLE . 2;
        HookMgr::addAction( $hook, function( $arg ) { return HALLOWORLD . ' 2 : ' . $arg; } );
        $this->assertTrue( HookMgr::exists( $hook ));
        $this->assertEquals( 1, HookMgr::count( $hook ));
        $this->assertEquals(
            sprintf( $EXPECTED, 2 ) ,
            HookMgr::apply( $hook, [ $arg ] )
        );

        // 3. instantiated object+method : [object, methodName]
        $hook = $arg = $CALLABLE . 3;
        $callable3 = new Callable3();
        $method    = 'callable3Method';
        HookMgr::addAction( $hook, [ $callable3, $method ] );
        $this->assertTrue( HookMgr::exists( $hook ));
        $this->assertEquals( 1, HookMgr::count( $hook ));
        $this->assertEquals(
            sprintf( $EXPECTED, 3 ) ,
            HookMgr::apply( $hook, [ $arg ] )
        );

        // 4. class name and static method, passed as an array: [namespaceClassName, methodName]
        $hook = $arg = $CALLABLE . 4;
        HookMgr::addAction( $hook, [ Callable4::class, 'callable4' ] );
        $this->assertTrue( HookMgr::exists( $hook ));
        $this->assertEquals( 1, HookMgr::count( $hook ));
        $this->assertEquals(
            sprintf( $EXPECTED, 4 ) ,
            HookMgr::apply( $hook, [ $arg ] )
        );

        // 5. instantiated object, class has an (magic) __call method : object
        $hook = $arg = $CALLABLE . 5;
        $callable5 = new Callable5();
//        HookMgr::addAction( $CALLABLE . 5, [ $callable5, '__call' ] ); // do not work
        HookMgr::addAction( $hook, [ $callable5, 'fakeMethod' ] );       // array + fake function name works
        $this->assertTrue( HookMgr::exists( $hook ));
        $this->assertEquals( 1, HookMgr::count( $hook ));
        $this->assertEquals(
            sprintf( $EXPECTED, 5 ) ,
            HookMgr::apply( $hook, [ $arg ] )
        );

        // 6. class name, class has an (magic) __callStatic method : namespaceClassName
        $hook = $arg = $CALLABLE . 6;
//        HookMgr::addAction( $CALLABLE . 6, Callable6::class ); // do not work
        HookMgr::addAction( $hook, [ Callable6::class, 'fakeMethod' ] ); // array + fake function name works
        $this->assertTrue( HookMgr::exists( $hook ));
        $this->assertEquals( 1, HookMgr::count( $hook ));
        $this->assertEquals(
            sprintf( $EXPECTED, 6 ) ,
            HookMgr::apply( $hook, [ $arg ] )
        );

        // 7. instantiated object, class has an (magic) __invoke method : object
        $hook = $arg = $CALLABLE . 7;
        $callable7 = new Callable7();
        HookMgr::addAction( $hook, $callable7 );
        $this->assertTrue( HookMgr::exists( $hook ));
        $this->assertEquals( 1, HookMgr::count( $hook ));
        $this->assertEquals(
            sprintf( $EXPECTED, 7 ) ,
            HookMgr::apply( $hook, [ $arg ] )
        );

        $hooks = HookMgr::getHooks();
        $this->assertTrue(
            ( 7 == count( $hooks ))
            , 'case 1-23, got ' . implode( ',', $hooks )
        );
        for( $tIx = 1; $tIx <= 7; $tIx++ ) {
            $hook = $CALLABLE . $tIx;
            $this->assertTrue(
                in_array( $hook, $hooks ),
                'case 1-24' . $tIx. ' , expect ' . $hook . ' in ' . implode( ',', $hooks )
            );
        }
        for( $tIx = 1; $tIx <= 7; $tIx++ ) {
            $hook  = $CALLABLE . $tIx;
            HookMgr::remove( $hook );
            $hooks = HookMgr::getHooks();
            $this->assertFalse(
                HookMgr::exists( $hook ),
                'case 1-25' . $tIx. ' , expect ' . $hook . ' NOT in ' . implode( ',', $hooks )
            );
            $this->assertFalse(
                in_array( $hook, $hooks ),
                'case 1-26' . $tIx. ' , expect ' . $hook . ' NOT in ' . implode( ',', $hooks )
            );
        }

    }

    /**
     * Testing addActions, single hook with many actions
     *
     * @test
     */
    public function HookMgrTest2()
    {
        HookMgr::init();
        $hook           = 'hook';
        $arg1           = 'test';
        $arg2           = [];
        $callable3      = new Callable3();
        $callable4class = Callable4::class;
        $callable5      = new Callable5();
        $actions        = [
            'Kigkonsult\HookMgr\callable1',
            [ $callable3, 'callable3Method' ],
            [ Callable4::class, 'callable4' ],
            $callable4class . '::callable4',
            [ $callable5, 'someMethod' ],
            [ Callable6::class, 'someMethod' ],
            new Callable7(),
            function( $arg1, & $arg2 ) { $arg2[] = 'last'; return HALLOWORLD . ' last result : ' . $arg1; },
        ];
        HookMgr::addActions( $hook, $actions );
        $this->assertTrue( HookMgr::exists( $hook ));
        $this->assertEquals( 8, HookMgr::count( $hook ));

        foreach( HookMgr::getCallables( $hook ) as $cIx => $action ) {
            $this->assertEquals(
                $actions[$cIx],
                $action,
                'case 2-3-' . $cIx
            );
        }

        $result = HookMgr::apply( $hook, [ $arg1, & $arg2 ] );

        $this->assertEquals(
            HALLOWORLD . ' last result : ' . $arg1,
            $result
        );
        $this->assertEquals(
            '1-3-4-4-5-6-7-last', // i.e. arg2 is updated from each callable
            implode( '-', $arg2 )
        );

        echo __FUNCTION__ . ' result : ' . $result . PHP_EOL; // test ###

        HookMgr::addAction( 'veryLong', 'Kigkonsult\HookMgr\callable1' );
        $string = HookMgr::toString();
        $this->assertEquals( 8, substr_count( $string, $hook ));
    }

    /**
     * Testing setActions
     *
     * @test
     */
    public function HookMgrTest3()
    {
        HookMgr::init();
        $hook           = 'test';
        $arg1           = 'test';
        $arg2           = [];
        $callable3      = new Callable3();
        $callable4class = Callable4::class;
        $callable5      = new Callable5();
        HookMgr::setActions(
            [
                $hook . 1 => [ 'Kigkonsult\HookMgr\callable1' ],
                $hook . 2 => [ function( $arg1, & $arg2 ) { $arg2[] = 2; return HALLOWORLD . ' 2 : ' . $arg1; } ],
                $hook . 3 => [ [ $callable3, 'callable3Method' ] ],
                $hook . 4 => [ [ $callable4class, 'callable4' ] ],
                $hook . 5 => [ [ $callable5, 'someMethod' ] ],
                $hook . 6 => [ [ Callable6::class, 'someMethod' ] ],
                $hook . 7 => [ new Callable7() ]
            ]
        );
        for( $x = 1; $x <= 7; $x++ ) {
            $this->assertEquals(
                HALLOWORLD . ' ' . $x . ' : ' . $arg1,
                HookMgr::apply( $hook . $x, [ $arg1, & $arg2 ] )
            );
        }
        $this->assertEquals(
            '1-2-3-4-5-6-7',
            implode( '-', $arg2 )
        );

        $string = HookMgr::toString();
        $this->assertEquals( 7, substr_count( $string, $hook ));
        echo __FUNCTION__ . PHP_EOL . $string; // test ###

    }

    /**
     * Testing 'hook' exceptions
     *
     * @test
     */
    public function HookMgrTest7()
    {
        // 'empty' hook, InvalidArgumentException expected
        HookMgr::init();
        $hook = ' ';
        $ok   = 0;
        try {
            HookMgr::addAction( $hook, function( $arg = null ) { return HALLOWORLD . ' ' . $arg; } );
            $ok = 1;
        }
        catch( TypeError $e ) {
            $ok = 2;
        }
        catch( InvalidArgumentException $e ) {
            $ok = 3;
        }
        catch( Throwable $e ) {
            $ok = 4;
        }
        $this->assertEquals( 3, $ok, 'test7-1 exp : 3, got : ' . $ok );

        // empty hook action, TypeError expected
        HookMgr::init();
        $hook = 'hook72';
        $ok   = 0;
        try {
            HookMgr::addAction( $hook, ' ' );
            $ok = 1;
        }
        catch( TypeError $e ) {
            $ok = 2;
        }
        catch( Throwable $e ) {
            $ok = 3;
        }
        $this->assertEquals( 2, $ok, 'test7-2 exp : 2, got : ' . $ok );

        // invalid hook action, TypeError expected
        HookMgr::init();
        $hook = 'hook73';
        $ok   = 0;
        try {
            HookMgr::addAction( $hook, '123' );
            $ok = 1;
        }
        catch( TypeError $e ) {
            $ok = 2;
        }
        catch( Throwable $e ) {
            $ok = 3;
        }
        $this->assertEquals( 2, $ok, 'test7-3 exp : 2, got : ' . $ok );

        // invalid hook action, TypeError expected
        HookMgr::init();
        $hook = 'hook74';
        $ok   = 0;
        try {
            HookMgr::addAction( $hook, [ 74, 74 ] );
            $ok = 1;
        }
        catch( TypeError $e ) {
            $ok = 2;
        }
        catch( Throwable $e ) {
            $ok = 3;
        }
        $this->assertEquals( 2, $ok, 'test7-4 exp : 2, got : ' . $ok );

        // invalid hook action, TypeError expected
        HookMgr::init();
        $callable3 = new Callable3();
        $hook      = 'hook75';
        $ok        = 0;
        try {
            HookMgr::addAction( $hook, [ $callable3, 75 ] );
            $ok = 1;
        }
        catch( TypeError $e ) {
            $ok = 2;
        }
        catch( Throwable $e ) {
            $ok = 3;
        }
        $this->assertEquals( 2, $ok, 'test7-5 exp : 2, got : ' . $ok );

        // invalid hook action, TypeError expected
        HookMgr::init();
        $method = 'fakeMethod';
        $hook   = 'hook76';
        $ok     = 0;
        try {
            HookMgr::addAction( $hook, [ 76, $method ] );
            $ok = 1;
        }
        catch( TypeError $e ) {
            $ok = 2;
        }
        catch( Throwable $e ) {
            $ok = 3;
        }
        $this->assertEquals( 2, $ok, 'test7-6 exp : 2, got : ' . $ok );

        // apply unset hook, RuntimeException expected
        HookMgr::init();
        $hook = 'hook77';
        $ok   = 0;
        try {
            HookMgr::apply( $hook );
            $ok = 1;
        }
        catch( RuntimeException $e ) {
            $ok = 2;
        }
        catch( Throwable $e ) {
            $ok = 3;
        }
        $this->assertEquals( 2, $ok, 'test7-7 exp : 2, got : ' . $ok );

        // 3. instantiated object+method : [object, fakeMethodName], TypeError expected
        HookMgr::init();
        $hook = 'hook78';
        $ok   = 0;
        $this->assertFalse( method_exists( $callable3, $method ));
        try {
            HookMgr::addAction( $hook, [ $callable3, $method ] );
            $ok = 1;
            HookMgr::apply( $hook );
            $ok = 2;
        }
        catch( TypeError $e ) {
            $ok = 3;
        }
        catch( RuntimeException $e ) {
            $ok = 4;
        }
        catch( Throwable $e ) {
            $ok = 5;
        }
        $this->assertEquals( 3, $ok, 'test7-8 exp : 2, got : ' . $ok );
    }
}
