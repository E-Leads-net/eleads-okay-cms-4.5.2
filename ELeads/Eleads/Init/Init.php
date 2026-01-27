<?php


namespace Okay\Modules\ELeads\Eleads\Init;


use Okay\Core\Modules\AbstractInit;
use Okay\Core\ServiceLocator;
use Okay\Core\Settings;
use Okay\Entities\ModulesEntity;
use Okay\Entities\ProductsEntity;
use Okay\Modules\ELeads\Eleads\Extenders\ModulesEntityExtender;
use Okay\Modules\ELeads\Eleads\Extenders\ProductsEntityExtender;
use Okay\Modules\ELeads\Eleads\Helpers\SyncWidgetsTagHelper;

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
        $this->registerBackendController('ELeadsUpdateAdmin');
        $this->addBackendControllerPermission('ELeadsAdmin', self::PERMISSION);
        $this->addBackendControllerPermission('ELeadsUpdateAdmin', self::PERMISSION);

        $this->registerQueueExtension(
            [ProductsEntity::class, 'update'],
            [ProductsEntityExtender::class, 'afterUpdate']
        );
        $this->registerQueueExtension(
            [ProductsEntity::class, 'add'],
            [ProductsEntityExtender::class, 'afterAdd']
        );
        $this->registerQueueExtension(
            [ProductsEntity::class, 'delete'],
            [ProductsEntityExtender::class, 'afterDelete']
        );
        $this->registerQueueExtension(
            [ModulesEntity::class, 'update'],
            [ModulesEntityExtender::class, 'afterUpdate']
        );
        $this->registerQueueExtension(
            [ModulesEntity::class, 'enable'],
            [ModulesEntityExtender::class, 'afterEnable']
        );
        $this->registerQueueExtension(
            [ModulesEntity::class, 'disable'],
            [ModulesEntityExtender::class, 'afterDisable']
        );

        $serviceLocator = ServiceLocator::getInstance();
        /** @var Settings $settings */
        $settings = $serviceLocator->getService(Settings::class);
        $widgetHelper = new SyncWidgetsTagHelper($settings);
        $widgetHelper->ensureInstalled();
    }
}
