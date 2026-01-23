<?php


namespace Okay\Modules\ELeads\YmlFeed\Init;


use Okay\Core\Modules\AbstractInit;

class Init extends AbstractInit
{
    const PERMISSION = 'eleads__yml_feed';

    public function install()
    {
        $this->setModuleType(MODULE_TYPE_XML);
        $this->setBackendMainController('ELeadsYmlFeedAdmin');
    }

    public function init()
    {
        $this->addPermission(self::PERMISSION);
        $this->registerBackendController('ELeadsYmlFeedAdmin');
        $this->addBackendControllerPermission('ELeadsYmlFeedAdmin', self::PERMISSION);
    }
}
