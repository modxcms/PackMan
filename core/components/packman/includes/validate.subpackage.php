<?php
use xPDO\Transport\xPDOTransport;
use MODX\Revolution\Transport\modTransportPackage;

$newer = true;
if ($transport && $transport->xpdo) {
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            $modx =& $transport->xpdo;

            /* define version */
            $newVersion = '{version}';
            $newVersionMajor = '{version_major}';
            $name = '{name}';

            /* now loop through packages and check for newer versions
             * Do not install if newer or equal versions are found */
            $newer = true;

            $c = $modx->newQuery(modTransportPackage::class);
            $c->where([
                'package_name' => $name,
                'version_major:>=' => $newVersionMajor
            ]);
            $packages = $modx->getCollection(modTransportPackage::class, $c);

            foreach ($packages as $package) {
                if ($package->compareVersion($newVersion)) {
                    $newer = false;
                    break;
                }
            }
            break;
    }
}

return $newer;