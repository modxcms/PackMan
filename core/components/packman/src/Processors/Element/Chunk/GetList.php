<?php
namespace PackMan\Processors\Element\Chunk;

use MODX\Revolution\Processors\Element\Chunk\GetList as ChunkGetList;
use xPDO\Om\xPDOQuery;

class GetList extends ChunkGetList
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