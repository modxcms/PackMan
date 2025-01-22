<?php
namespace PackMan\Processors\Element\Template;

use MODX\Revolution\Processors\Element\Template\GetList as TemplateGetList;
use xPDO\Om\xPDOQuery;

class GetList extends TemplateGetList
{
    public function prepareQueryBeforeCount(xPDOQuery $c)
    {
        parent::prepareQueryBeforeCount($c);

        $query = $this->getProperty('query');
        if (!empty($query)){
            $c->where(
                [
                    'templatename:LIKE' => '%' . $query . '%'
                ]
            );
        }
        return $c;
    }
}