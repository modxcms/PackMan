<?php
namespace PackMan\Processors\Profile;

use MODX\Revolution\Processors\Processor;
use PackMan\Model\pacProfile;

/**
 * Create a profile
 *
 * @package packman
 * @subpackage processors
 */
class Create extends Processor
{
    public function process()
    {
        if (empty($this->properties['name'])) {
            return $this->failure($this->modx->lexicon('packman.profile_err_ns_name'));
        }
        $profile = $this->modx->getObject(pacProfile::class, ['name' => $this->properties['name']]);
        if ($profile) return $this->failure($this->modx->lexicon('packman.profile_err_ae'));

        $profile = $this->modx->newObject(pacProfile::class);
        $profile->fromArray($this->properties);

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
