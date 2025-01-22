<?php
namespace PackMan\Processors\Element\Snippet;

use MODX\Revolution\Processors\Element\Snippet\GetList as SnippetGetList;
use xPDO\Om\xPDOQuery;

class GetList extends SnippetGetList
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