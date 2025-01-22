<?php
namespace PackMan\Processors\Profile;

use MODX\Revolution\Processors\Processor;
use PackMan\Model\pacProfile;

/**
 * Update a profile
 *
 * @package packman
 * @subpackage processors
 */
class Update extends Processor
{
    public function process()
    {
        if (empty($this->properties['id'])) return $this->failure($this->modx->lexicon('packman.profile_err_ns'));
        $profile = $this->modx->getObject(pacProfile::class, $this->properties['id']);
        if (empty($profile)) return $this->failure($this->modx->lexicon('packman.profile_err_nf'));

        $data = $this->modx->fromJSON($_POST['data']);
        $profile->set('data', $data);

        if ($profile->save() === false) {
            return $this->failure($this->modx->lexicon('packman.profile_err_save'));
        }

        return $this->success('', $profile);
    }

    public function getLanguageTopics()
    {
        return ['packman:default'];
    }
}
