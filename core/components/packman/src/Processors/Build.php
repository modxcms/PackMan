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
namespace PackMan\Processors;

use MODX\Revolution\Processors\Processor;
use MODX\Revolution\Transport\modPackageBuilder;
use MODX\Revolution\Transport\modTransportPackage;
use MODX\Revolution\modX;
use MODX\Revolution\modTemplate;
use MODX\Revolution\modTemplateVar;
use MODX\Revolution\modTemplateVarTemplate;
use MODX\Revolution\modChunk;
use MODX\Revolution\modSnippet;
use MODX\Revolution\modPlugin;
use MODX\Revolution\modCategory;
use xPDO\Transport\xPDOTransport;
use xPDO\Transport\xPDOTransportVehicle;
use PackMan\PackMan;
use Psr\Container\ContainerExceptionInterface;

/**
 * Builds the package and exports it.
 *
 * @package packman
 * @subpackage processors
 */
class Build extends Processor
{
    public function process()
    {
        /* if downloading the file last exported */
        if (!empty($_REQUEST['download'])) {
            $file = $_REQUEST['download'];
            sleep(.5); /* to make sure not to go too fast */
            $d = $this->modx->getOption('core_path') . 'packages/' . $_REQUEST['download'];
            $f = $d . '.transport.zip';

            if (!is_file($f)) return '';

            $o = file_get_contents($f);
            $bn = basename($file);

            header("Content-Type: application/force-download");
            header("Content-Disposition: attachment; filename=\"{$bn}.transport.zip\"");

            /* remove package files now that we are through */
            @unlink($f);
            $this->modx->cacheManager->deleteTree($d . '/', true, false, []);

            return $o;
        }

        // Get service
        $packman = null;
        try {
            if ($this->modx->services->has('packman')) {
                $packman = $this->modx->services->get('packman');
            }
        } catch (ContainerExceptionInterface $e) {
        }
        if (!($packman instanceof PackMan)){
            return $this->failure('Service class couldn\'t be loaded!');
        }

        /* verify form */
        if (empty($_POST['category'])) $this->addFieldError('category', $this->modx->lexicon('packman.category_err_ns'));
        if (empty($_POST['version'])) $this->addFieldError('version', $this->modx->lexicon('packman.version_err_nf'));
        if (empty($_POST['release'])) $this->addFieldError('release', $this->modx->lexicon('packman.release_err_nf'));

        /* if any errors, return and dont proceed */
        if ($this->hasErrors()) {
            return $this->failure();
        }

        /* get version, release, files */
        $version = $_POST['version'];
        $release = $_POST['release'];

        /* format package name */
        $name_lower = strtolower($_POST['category']);
        $name_lower = str_replace([' ','-','.','*','!','@','#','$','%','^','&','_'], '', $name_lower);

        /* define file paths and string replacements */
        $directories = [];
        $cachePath = $this->modx->getOption('core_path') . 'cache/';
        $pathLookups = [
            'sources' => [
                '{base_path}',
                '{core_path}',
                '{assets_path}'
            ],
            'targets' => [
                $this->modx->getOption('base_path', null, MODX_BASE_PATH),
                $this->modx->getOption('core_path', null, MODX_CORE_PATH),
                $this->modx->getOption('assets_path', null, MODX_ASSETS_PATH)
            ]
        ];

        $builder = new modPackageBuilder($this->modx);
        $builder->createPackage($name_lower, $version, $release);
        $builder->registerNamespace($name_lower, false, true, '{core_path}components/' . $name_lower . '/');

        /* create category */
        $category= $this->modx->newObject(modCategory::class);
        $category->set('id', 1);
        $category->set('category', $_POST['category']);

        /* add Chunks */
        $chunkList = $this->modx->fromJSON($_POST['chunks']);
        if (!empty($chunkList)) {
            $chunks = [];
            foreach ($chunkList as $chunkData) {
                if (empty($chunkData['id'])) continue;
                $chunk = $this->modx->getObject(modChunk::class, $chunkData['id']);
                if (empty($chunk)) continue;

                $chunks[] = $chunk;
            }
            if (empty($chunks)) {
                return $this->failure('Error packaging chunks!');
            }
            $category->addMany($chunks, 'Chunks');
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Packaged in ' . count($chunks) . ' chunks...');
        }

        /* add snippets */
        $snippetList = $this->modx->fromJSON($_POST['snippets']);
        if (!empty($snippetList)) {
            $snippets = [];
            foreach ($snippetList as $snippetData) {
                if (empty($snippetData['id'])) continue;
                $snippet = $this->modx->getObject(modSnippet::class, $snippetData['id']);
                if (empty($snippet)) continue;

                $snippets[] = $snippet;

                /* package in assets_path if it exists */
                if (!empty($snippetData['assets_path'])) {
                    $files = str_replace($pathLookups['sources'], $pathLookups['targets'], $snippetData['assets_path']);
                    $l = strlen($files);
                    if (substr($files, $l-1, $l) != '/') $files .= '/';
                    /* verify files exist */
                    if (file_exists($files) && is_dir($files)) {
                        $directories[] = [
                            'source' => $files,
                            'target' => "return MODX_ASSETS_PATH . 'components/';"
                        ];
                    }
                }
                /* package in core_path if it exists */
                if (!empty($snippetData['core_path'])) {
                    $files = str_replace($pathLookups['sources'], $pathLookups['targets'], $snippetData['core_path']);
                    $l = strlen($files);
                    if (substr($files, $l-1, $l) != '/') $files .= '/';
                    /* verify files exist */
                    if (file_exists($files) && is_dir($files)) {
                        $directories[] = [
                            'source' => $files,
                            'target' => "return MODX_CORE_PATH . 'components/';"
                        ];
                    }
                }
            }
            if (empty($snippets)) {
                return $this->failure('Error packaging Snippets!');
            }
            $category->addMany($snippets, 'Snippets');
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Packaged in ' . count($snippets) . ' Snippets...');
        }

        /* add Plugins */
        $pluginList = $this->modx->fromJSON($_POST['plugins']);
        if (!empty($pluginList)) {
            $plugins = [];
            foreach ($pluginList as $pluginData) {
                if (empty($pluginData['id'])) continue;
                $plugin = $this->modx->getObject(modPlugin::class, $pluginData['id']);
                if (empty($plugin)) continue;

                $pluginEvents = $plugin->getMany('PluginEvents');
                $plugin->addMany($pluginEvents);

                $attr = [
                    xPDOTransport::PRESERVE_KEYS => false,
                    xPDOTransport::UPDATE_OBJECT => true,
                    xPDOTransport::UNIQUE_KEY => 'name',
                    xPDOTransport::RELATED_OBJECTS => true,
                    xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
                        'PluginEvents' => [
                            xPDOTransport::PRESERVE_KEYS => true,
                            xPDOTransport::UPDATE_OBJECT => false,
                            xPDOTransport::UNIQUE_KEY => ['pluginid', 'event']
                        ]
                    ]
                ];
                $vehicle = $builder->createVehicle($plugin, $attr);
                $builder->putVehicle($vehicle);

                $plugins[] = $plugin;
            }
            if (empty($plugins)) {
                return $this->failure('Error packaging plugins!');
            }
            //$category->addMany($plugins, 'Plugins');
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Packaged in ' . count($plugins) . ' plugins...');
        }

        /* add Templates */
        $tvs = [];
        $tvMap = [];
        $templateList = $this->modx->fromJSON($_POST['templates']);
        if (!empty($templateList)) {
            $templates = [];
            foreach ($templateList as $templateData) {
                if (empty($templateData['id'])) continue;
                $template = $this->modx->getObject(modTemplate::class, $templateData['id']);
                if (empty($template)) continue;

                $templates[] = $template;
                /* add in directory for Template */
                if (!empty($templateData['directory'])) {
                    $files = str_replace($pathLookups['sources'], $pathLookups['targets'], $templateData['directory']);
                    $l = strlen($files);
                    if (substr($files, $l-1, $l) != '/') $files .= '/';
                    /* verify files exist */
                    if (file_exists($files) && is_dir($files)) {
                        $directories[] = [
                            'source' => $files,
                            'target' => "return MODX_ASSETS_PATH . 'templates/';"
                        ];
                    }
                }

                /* collect TVs assigned to Template */
                $c = $this->modx->newQuery(modTemplateVar::class);
                $c->innerJoin(modTemplateVarTemplate::class, 'TemplateVarTemplates');
                $c->where([
                    'TemplateVarTemplates.templateid' => $template->get('id')
                ]);
                $tvList = $this->modx->getCollection(modTemplateVar::class, $c);
                foreach ($tvList as $tv) {
                    if (!isset($tvMap[$tv->get('name')])) {
                        $tvs[] = $tv; /* only add TV once */
                        $tvMap[$tv->get('name')] = [];
                    }
                    array_push($tvMap[$tv->get('name')], $template->get('templatename'));
                    $tvMap[$tv->get('name')] = array_unique($tvMap[$tv->get('name')]);
                }
            }
            if (empty($templates)) {
                return $this->failure('Error packaging Templates!');
            }
            $category->addMany($templates, 'Templates');
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Packaged in ' . count($templates) . ' Templates...');
        }

        /* add in TVs */
        $category->addMany($tvs);

        /* package in category vehicle */
        $attr = [
            xPDOTransport::UNIQUE_KEY => 'category',
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
                'Chunks' => [
                    xPDOTransport::PRESERVE_KEYS => false,
                    xPDOTransport::UPDATE_OBJECT => true,
                    xPDOTransport::UNIQUE_KEY => 'name'
                ],
                'TemplateVars' => [
                    xPDOTransport::PRESERVE_KEYS => false,
                    xPDOTransport::UPDATE_OBJECT => true,
                    xPDOTransport::UNIQUE_KEY => 'name'
                ],
                'Templates' => [
                    xPDOTransport::PRESERVE_KEYS => false,
                    xPDOTransport::UPDATE_OBJECT => true,
                    xPDOTransport::UNIQUE_KEY => 'templatename'
                ],
                'Snippets' => [
                    xPDOTransport::PRESERVE_KEYS => false,
                    xPDOTransport::UPDATE_OBJECT => true,
                    xPDOTransport::UNIQUE_KEY => 'name'
                ]
            ]
        ];
        $vehicle = $builder->createVehicle($category, $attr);

        /* add user-specified directories */
        $directoryList = $this->modx->fromJSON($_POST['directories']);
        if (!empty($directoryList)) {
            foreach ($directoryList as $directoryData) {
                if (empty($directoryData['source']) || empty($directoryData['target'])) continue;

                $source = str_replace($pathLookups['sources'], $pathLookups['targets'], $directoryData['source']);
                if (empty($source)) continue;
                $l = strlen($source);
                if (substr($source, $l-1, $l) != '/') $source .= '/';
                if (!file_exists($source) || !is_dir($source)) continue;

                $target = str_replace($pathLookups['sources'], [
                    '".MODX_BASE_PATH."',
                    '".MODX_CORE_PATH."',
                    '".MODX_ASSETS_PATH."'
                ], $directoryData['target']);
                if (empty($target)) continue;
                $l = strlen($target);
                if (substr($target, $l-1, $l) != '/') $target .= '/';

                $target = 'return "' . $target . '";';

                $directories[] = [
                    'source' => $source,
                    'target' => $target
                ];
            }
        }

        /* add directories to category vehicle */
        if (!empty($directories)) {
            foreach ($directories as $directory) {
                $vehicle->resolve('file', $directory);
            }
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Added ' . count($directories) . ' directories to category...');
        }

        /* create dynamic TemplateVarTemplate resolver */
        if (!empty($tvMap)) {
            $tvp = var_export($tvMap, true);
            $resolverCachePath = $cachePath . 'packman/resolve.tvt.php';
            $resolver = file_get_contents($packman->config['includesPath'] . 'resolve.tvt.php');
            $resolver = str_replace(['{tvs}'], [$tvp], $resolver);

            $this->modx->cacheManager->writeFile($resolverCachePath, $resolver);
            $vehicle->resolve('php', [
                'source' => $resolverCachePath
            ]);
        }

        /* add category vehicle to build */
        $builder->putVehicle($vehicle);

        /* add in packages */
        $packageList = $this->modx->fromJSON($_POST['packages']);
        if (!empty($packageList)) {
            $packageDir = $this->modx->getOption('core_path', null, MODX_CORE_PATH) . 'packages/';
            $spAttr = ['vehicle_class' => xPDOTransportVehicle::class];
            $spReplaces = [
                '{version}',
                '{version_major}',
                '{name}'
            ];
            $resolverReplaces = [
                '{signature}',
                '{provider}',
                '{attributes}',
                '{metadata}'
            ];

            foreach ($packageList as $packageData) {
                $file = $packageDir . $packageData['signature'] . '.transport.zip';
                if (!file_exists($file)) continue;

                $package = $this->modx->getObject(modTransportPackage::class, ['signature' => $packageData['signature']]);
                if (!$package) {
                    $this->modx->log(modX::LOG_LEVEL_ERROR, '[PackMan] Package could not be found with signature: ' . $packageData['signature']);
                    continue;
                }

                /* create package as subpackage */
                $vehicle = $builder->createVehicle([
                    'source' => $file,
                    'target' => "return MODX_CORE_PATH . 'packages/';"
                ], $spAttr);

                /* get signature values */
                $sig = explode('-', $packageData['signature']);
                $vsig = explode('.', $sig[1]);

                /* create custom package validator to resolve if the package on the client server is newer than this version */
                $cacheKey = 'packman/validators/' . $packageData['signature'] . '.php';
                $validator = file_get_contents($packman->config['includesPath'] . 'validate.subpackage.php');
                $validator = str_replace($spReplaces, [
                    $sig[1] . (!empty($sig[2]) ? '-' . $sig[2] : ''),
                    $vsig[0],
                    $sig[0]
                ], $validator);
                $this->modx->cacheManager->writeFile($cachePath . $cacheKey, $validator);

                /* add validator to vehicle */
                $vehicle->validate('php', [
                    'source' => $cachePath . $cacheKey
                ]);

                /* add resolver to subpackage to add to packages grid */
                $cacheKey = 'packman/resolvers/' . $packageData['signature'] . '.php';
                $resolver = file_get_contents($packman->config['includesPath'] . 'resolve.subpackage.php');
                $resolver = str_replace($resolverReplaces, [
                    $packageData['signature'],
                    $package->get('provider'),
                    str_replace("'", "\'", $this->modx->toJSON($package->get('attributes'))),
                    str_replace("'", "\'", $this->modx->toJSON($package->get('metadata'))),
                ], $resolver);
                $this->modx->cacheManager->writeFile($cachePath . $cacheKey, $resolver);

                /* add resolver to vehicle */
                $vehicle->resolve('php', [
                    'source' => $cachePath . $cacheKey
                ]);

                /* add subpackage to build */
                $builder->putVehicle($vehicle);
            }
        }

        /* now pack in the license file, readme and setup options */
        $packageAttributes = [];
        if (isset($_FILES['license']) && !empty($_FILES['license']) && $_FILES['license']['error'] == UPLOAD_ERR_OK) {
            $packageAttributes['license'] = file_get_contents($_FILES['license']['tmp_name']);
        }
        if (isset($_FILES['readme']) && !empty($_FILES['readme']) && $_FILES['readme']['error'] == UPLOAD_ERR_OK) {
            $packageAttributes['readme'] = file_get_contents($_FILES['readme']['tmp_name']);
        }
        if (isset($_FILES['changelog']) && !empty($_FILES['changelog']) && $_FILES['changelog']['error'] == UPLOAD_ERR_OK) {
            $packageAttributes['changelog'] = file_get_contents($_FILES['changelog']['tmp_name']);
        }
        if (!empty($packageAttributes)) $builder->setPackageAttributes($packageAttributes);

        /* zip up the package */
        $builder->pack();

        /* remove any cached files */
        $this->modx->cacheManager->deleteTree($cachePath . 'packman/', [
            'deleteTop' => true,
            'skipDirs' => false,
            'extensions' => ['.php']
        ]);

        /* output name to browser */
        $signature = $name_lower . '-' . $version . '-' . $release;
        return $this->success($signature);
    }

    public function getLanguageTopics()
    {
        return ['packman:default'];
    }
}