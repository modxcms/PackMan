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
 * @package packman
 * @subpackage controllers
 */
use MODX\Revolution\modManagerController;

class PackManHomeManagerController extends modManagerController
{
    /** @var \PackMan\PackMan $packman */
    public $packman;

    public function initialize()
    {
        $this->packman = $this->modx->services->get('packman');
        parent::initialize();
    }

    public function checkPermissions()
    {
        return true;
    }

    public function getLanguageTopics()
    {
        return ['packman:default'];
    }

    public function process(array $scriptProperties = [])
    {
        return '<div id="tp-panel-home-div"></div>';
    }

    public function getPageTitle()
    {
        return $this->modx->lexicon('packman');
    }

    public function loadCustomCssJs()
    {
        $this->addCss($this->packman->config['cssUrl'] . 'mgr.css');
        $this->addJavascript($this->packman->config['jsUrl'] . 'packman.js');

        $this->addHtml('
            <script type="text/javascript">
                Ext.onReady(function() {
                    TP.config = ' . $this->modx->toJSON($this->packman->config) . ';
                    TP.request = ' . $this->modx->toJSON($_GET) . ';
                });
            </script>
        ');

        $this->addLastJavascript($this->packman->config['jsUrl'] . 'templates.grid.js');
        $this->addLastJavascript($this->packman->config['jsUrl'] . 'chunks.grid.js');
        $this->addLastJavascript($this->packman->config['jsUrl'] . 'snippets.grid.js');
        $this->addLastJavascript($this->packman->config['jsUrl'] . 'plugins.grid.js');
        $this->addLastJavascript($this->packman->config['jsUrl'] . 'packages.grid.js');
        $this->addLastJavascript($this->packman->config['jsUrl'] . 'directories.grid.js');
        $this->addLastJavascript($this->packman->config['jsUrl'] . 'home.panel.js');
        $this->addLastJavascript($this->packman->config['jsUrl'] . 'home.js');
    }

    public function getTemplateFile()
    {
        return '';
    }
}