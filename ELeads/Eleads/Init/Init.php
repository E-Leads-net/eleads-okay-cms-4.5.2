<?php


namespace Okay\Modules\ELeads\Eleads\Init;


use Okay\Core\Modules\AbstractInit;

class Init extends AbstractInit
{
    const PERMISSION = 'eleads__yml_feed';

    public function install()
    {
        $this->setModuleType(MODULE_TYPE_XML);
        $this->setBackendMainController('ELeadsAdmin');
    }

    public function init()
    {
        $this->setBackendMainController('ELeadsAdmin');
        $this->addPermission(self::PERMISSION);
        $this->registerBackendController('ELeadsAdmin');
        $this->addBackendControllerPermission('ELeadsAdmin', self::PERMISSION);
    }
}
