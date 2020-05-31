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
/**
 * Kigkonsult\HookMgr autoloader
 */
spl_autoload_register(
    function( $class ) {
        static $PREFIX   = 'Kigkonsult\\HookMgr\\';
        static $BS       = '\\';
        static $SRC      = 'src';
        static $PHP      = '.php';
        if ( 0 != strncmp( $PREFIX, $class, 19 )) {
            return;
        }
        $class = substr( $class, 19 );
        if ( false !== strpos( $class, $BS )) {
            $class = str_replace( $BS, DIRECTORY_SEPARATOR, $class );
        }
        $file = __DIR__ . DIRECTORY_SEPARATOR . $SRC . DIRECTORY_SEPARATOR . $class . $PHP;
        if ( is_file( $file )) {
            include $file;
        }
    }
);
