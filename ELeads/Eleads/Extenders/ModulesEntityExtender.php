<?php


namespace Okay\Modules\ELeads\Eleads\Extenders;


use Okay\Core\EntityFactory;
use Okay\Core\ServiceLocator;
use Okay\Core\Settings;
use Okay\Entities\ModulesEntity;
use Okay\Core\Modules\Extender\ExtensionInterface;
use Okay\Modules\ELeads\Eleads\Helpers\SyncWidgetsTagHelper;

class ModulesEntityExtender implements ExtensionInterface
{
    public function afterUpdate($result, $ids, $object): void
    {
        if (!$result) {
            return;
        }

        if (!is_array($object) && !is_object($object)) {
            return;
        }

        $enabledValue = null;
        if (is_array($object) && array_key_exists('enabled', $object)) {
            $enabledValue = $object['enabled'];
        } elseif (is_object($object) && isset($object->enabled)) {
            $enabledValue = $object->enabled;
        }

        if ($enabledValue === null) {
            return;
        }

        $this->handleToggle((int) $enabledValue === 1, $ids);
    }

    public function afterEnable($result, $ids): void
    {
        if (!$result) {
            return;
        }

        $this->handleToggle(true, $ids);
    }

    public function afterDisable($result, $ids): void
    {
        if (!$result) {
            return;
        }

        $this->handleToggle(false, $ids);
    }

    private function handleToggle(bool $enabled, $ids): void
    {
        $ids = (array) $ids;
        if (empty($ids)) {
            return;
        }

        $serviceLocator = ServiceLocator::getInstance();
        /** @var EntityFactory $entityFactory */
        $entityFactory = $serviceLocator->getService(EntityFactory::class);
        /** @var Settings $settings */
        $settings = $serviceLocator->getService(Settings::class);
        /** @var ModulesEntity $modulesEntity */
        $modulesEntity = $entityFactory->get(ModulesEntity::class);

        $widgetHelper = new SyncWidgetsTagHelper($settings);

        foreach ($ids as $id) {
            $module = $modulesEntity->get((int) $id);
            if (empty($module) || $module->vendor !== 'ELeads' || $module->module_name !== 'Eleads') {
                continue;
            }

            if ($enabled) {
                $widgetHelper->activate();
            } else {
                $widgetHelper->deactivate();
            }
            break;
        }
    }
}
