<?php
use xPDO\xPDO;
use xPDO\Transport\xPDOTransport;
use MODX\Revolution\modTemplate;
use MODX\Revolution\modTemplateVar;
use MODX\Revolution\modTemplateVarTemplate;

if ($transport && $transport->xpdo) {
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            $modx =& $transport->xpdo;

            /* list of tvs and templates for each */
            $tvs = {tvs};

            foreach ($tvs as $tvName => $templateNames) {
                if (!is_array($templateNames) || empty($templateNames)) continue;

                $tv = $modx->getObject(modTemplateVar::class, ['name' => $tvName]);
                if (empty($tv)) {
                    $modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not find TV: ' . $tvName . ' to associate to Templates.');
                    continue;
                }

                $rank = 0;
                foreach ($templateNames as $idx => $templateName) {
                    $template = $modx->getObject(modTemplate::class, ['templatename' => $templateName]);

                    if (!empty($template)) {
                        $templateVarTemplate = $modx->getObject(modTemplateVarTemplate::class, [
                            'tmplvarid' => $tv->get('id'),
                            'templateid' => $template->get('id'),
                        ]);
                        if (!$templateVarTemplate) {

                            $templateVarTemplate = $modx->newObject(modTemplateVarTemplate::class);
                            $templateVarTemplate->fromArray([
                                'tmplvarid' => $tv->get('id'),
                                'templateid' => $template->get('id'),
                                'rank' => $rank,
                            ], '', true, true);

                            if ($templateVarTemplate->save() == false) {
                                $modx->log(xPDO::LOG_LEVEL_ERROR, 'An unknown error occurred while trying to associate the TV ' . $tvName . ' to the Template ' . $templateName);
                            }
                        }
                    } else {
                        $modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not find Template ' . $templateName);
                    }
                    $rank++;
                }
            }
            break;
    }
}
return true;