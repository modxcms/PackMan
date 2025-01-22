<?php
namespace PackMan\Processors\Profile;

use MODX\Revolution\Processors\Processor;
use PackMan\Model\pacProfile;

/**
 * Remove a profile
 *
 * @package packman
 * @subpackage processors
 */
class Remove extends Processor
{
    public function process()
    {
        if (empty($this->properties['id'])) return $this->failure($this->modx->lexicon('packman.profile_err_ns'));
        $profile = $this->modx->getObject(pacProfile::class, $this->properties['id']);
        if (empty($profile)) return $this->failure($this->modx->lexicon('packman.profile_err_nf'));

        if ($profile->remove() === false) {
            return $this->failure($this->modx->lexicon('packman.profile_err_remove'));
        }

        return $this->success('', $profile);
    }

    public function getLanguageTopics()
    {
        return ['packman:default'];
    }
}
