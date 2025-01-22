<?php
namespace PackMan\Processors\Profile;

use MODX\Revolution\Processors\Processor;
use PackMan\Model\pacProfile;

/**
 * Grabs a list of profiles
 *
 * @package packman
 */
class GetList extends Processor
{
    public function process()
    {
        $this->modx->lexicon->load('chunk');

        /* setup default properties */
        $isLimit = !empty($this->properties['limit']);
        $start = $this->modx->getOption('start', $this->properties, 0);
        $limit = $this->modx->getOption('limit', $this->properties, 20);
        $sort = $this->modx->getOption('sort', $this->properties, 'name');
        $dir = $this->modx->getOption('dir', $this->properties, 'ASC');

        /* query for chunks */
        $c = $this->modx->newQuery(pacProfile::class);
        $count = $this->modx->getCount(pacProfile::class);
        $c->sortby($sort, $dir);
        if ($isLimit) $c->limit($limit, $start);
        $profiles = $this->modx->getCollection(pacProfile::class, $c);

        /* iterate through profiles */
        $list = [];
        foreach ($profiles as $profile) {
            $list[] = $profile->toArray();
        }

        $list[] = ['id' => '-', 'name' => '<hr class="combo-hr" />'];
        $list[] = ['id' => 'CNEW', 'name' => $this->modx->lexicon('packman.create_new...')];

        return $this->outputArray($list, $count);
    }

    public function getLanguageTopics()
    {
        return ['packman:default'];
    }
}