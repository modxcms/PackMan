<?php
/**
 * PackMan
 *
 * Copyright 2010 by Shaun McCormick <shaun@modxcms.com>
 *
 * This file is part of PackMan.
 *
 * PackMan is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option) any
 * later version.
 *
 * PackMan is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * PackMan; if not, write to the Free Software Foundation, Inc., 59
 * Temple Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @package packman
 */
/**
 * PackMan main class file.
 *
 * @package packman
 */
namespace PackMan;

use MODX\Revolution\modX;

class PackMan {
    public $modx = null;
    public $config = [];

    /**
     * Default constructor for PackMan
     *
     * @constructor
     * @param modX &$modx A reference to a modX instance.
     * @param array $config (optional) Configuration properties.
     * @return packman
     */
    function __construct(modX &$modx, array $config = []) {
        $this->modx =& $modx;

        $corePath = $modx->getOption('packman.core_path', null, $modx->getOption('core_path') . 'components/packman/');
        $assetsUrl = $modx->getOption('packman.assets_url', null, $modx->getOption('assets_url') . 'components/packman/');

        $this->config = array_merge(
            [
                'corePath' => $corePath,
                'srcPath'   => $corePath . 'src/',
                'modelPath' => $corePath . 'src/Model/',
                'processorsPath' => $corePath . 'src/Processors/',
                'controllersPath' => $corePath . 'controllers/',
                'includesPath' => $corePath . 'includes/',

                'baseUrl' => $assetsUrl,
                'cssUrl' => $assetsUrl . 'css/',
                'jsUrl' => $assetsUrl . 'js/'
            ],
            $config
        );

        $this->modx->lexicon->load('packman:default');
    }
}