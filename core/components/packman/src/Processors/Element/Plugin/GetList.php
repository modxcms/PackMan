<?php
namespace PackMan\Processors\Element\Plugin;

use MODX\Revolution\Processors\Element\Plugin\GetList as PluginGetList;
use xPDO\Om\xPDOQuery;

class GetList extends PluginGetList
{
    public function prepareQueryBeforeCount(xPDOQuery $c)
    {
        parent::prepareQueryBeforeCount($c);

        $query = $this->getProperty('query');
        if (!empty($query)){
            $c->where(
                [
                    'name:LIKE' => '%' . $query . '%'
                ]
            );
        }
        return $c;
    }
}