<?php
/**
 * @var \MODX\Revolution\modX $modx
 * @var array $namespace
 */

try {
    $modx->addPackage('PackMan\\Model\\', $namespace['path'] . 'src/', null, 'PackMan\\');

    if (!$modx->services->has('packman')) {
        $modx->services->add('packman', function($c) use ($modx) {
            return new \PackMan\PackMan($modx);
        });
    }
}
catch (\Throwable $t) {
    $modx->log(\xPDO\xPDO::LOG_LEVEL_ERROR, $t->getMessage());
}
