<?php
/**
 * This file is part of the Library package.
 *
 * Copyleft (ↄ) 2013-2016 Pierre Cassat <me@e-piwi.fr> and contributors
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * The source code of this package is available online at 
 * <http://github.com/atelierspierrot/library>.
 */

namespace Library\Object;

use \OutOfBoundsException;

/**
 * @author  piwi <me@e-piwi.fr>
 */
class InvokableAccessException
    extends OutOfBoundsException
{

    /**
     * Constructor: creation of the parent instance
     *
     * @param string $property_name The name of the property getted
     * @param string $object_name The name of the invokable class object
     * @param int $code The exception code
     * @param \Exception $previous The previous catched excpetion
     */
    public function __construct($property_name = '', $object_name = '', $code = 0, \Exception $previous = null)
    {
        parent::__construct(
            sprintf('Direct access to property "%s" on object "%s" is not allowed!', $property_name, $object_name),
            $code, $previous
        );
    }

}

