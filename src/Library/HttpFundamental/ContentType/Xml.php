<?php
/**
 * PHP Library package of Les Ateliers Pierrot
 * Copyleft (ↄ) 2013-2015 Pierre Cassat and contributors
 * <www.ateliers-pierrot.fr> - <contact@ateliers-pierrot.fr>
 * License GPL-3.0 <http://www.opensource.org/licenses/gpl-3.0.html>
 * Sources <http://github.com/atelierspierrot/library>
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
 */
namespace Library\HttpFundamental\ContentType;

use \Library\HttpFundamental\ContentTypeInterface;
use \Library\HttpFundamental\Response;

/**
 * @author      Piero Wbmstr <me@e-piwi.fr>
 */
class Xml
    implements ContentTypeInterface
{

    /**
     * Prepare the content of the response before to send it to client
     *
     * @param \Library\HttpFundamental\Response $response
     * @return void
     */
    public function prepareResponse(Response $response)
    {
    }

    /**
     * Parse an input content
     *
     * @param string $content
     * @return mixed
     */
    public function parseContent($content)
    {
        return (string) $content;
    }

    /**
     * Prepare a content for output
     *
     * @param mixed $content
     * @return string
     */
    public function prepareContent($content)
    {
        if (is_array($content)) {
            $ctt = '';
            foreach ($content as $key=>$ctt_entry) {
                $ctt .= $ctt_entry;
            }
            $content = $ctt;
        }
        return (string) $content;
    }

    /**
     * Get the "content-Type" header value
     *
     * @return string
     */
    public static function getContentType()
    {
        return 'application/xml';
    }

}

// Endfile
