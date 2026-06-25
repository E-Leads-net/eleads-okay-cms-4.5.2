<?php


namespace Okay\Modules\ELeads\Eleads\Init;


use Okay\Core\Modules\AbstractInit;
use Okay\Core\Request;
use Okay\Core\Response;
use Okay\Core\ServiceLocator;
use Okay\Core\Settings;
use Okay\Core\Languages;
use Okay\Core\Router;
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

        $this->handleFilterRedirect($serviceLocator);
    }

    private function handleFilterRedirect(ServiceLocator $serviceLocator): void
    {
        if (PHP_SAPI === 'cli' || empty($_SERVER['REQUEST_URI']) || strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            return;
        }

        /** @var Languages $languages */
        $languages = $serviceLocator->getService(Languages::class);

        $requestPath = ltrim((string) parse_url('/' . Request::getRequestUri(), PHP_URL_PATH), '/');
        $langPrefix = trim((string) $languages->getLangLink(), '/');

        if ($langPrefix !== '' && strpos($requestPath, $langPrefix . '/') === 0) {
            $requestPath = substr($requestPath, strlen($langPrefix) + 1);
        }

        if (rtrim($requestPath, '/') !== 'e-filter') {
            return;
        }

        $query = trim((string) ($_GET['query'] ?? ''));
        $searchUrl = Router::generateUrl('products', [], true, $languages->getLangId());

        if ($query !== '') {
            $separator = strpos($searchUrl, '?') === false ? '?' : '&';
            $searchUrl .= $separator . 'keyword=' . urlencode($query);
        }

        Response::redirectTo($searchUrl, 301);
    }
}
