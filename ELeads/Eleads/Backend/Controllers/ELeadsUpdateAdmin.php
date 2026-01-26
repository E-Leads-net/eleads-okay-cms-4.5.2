<?php


namespace Okay\Modules\ELeads\Eleads\Backend\Controllers;


use Okay\Admin\Controllers\IndexAdmin;
use Okay\Core\Request;
use Okay\Modules\ELeads\Eleads\Helpers\ELeadsUpdateHelper;

class ELeadsUpdateAdmin extends IndexAdmin
{
    public function fetch()
    {
        if (!$this->request->method('POST')) {
            $this->response->redirectTo(Request::getRootUrl() . '/backend/index.php?controller=ELeads.Eleads.ELeadsAdmin&active_tab=tab_update');
            return;
        }

        $result = ELeadsUpdateHelper::updateToLatest();
        $status = $result['ok'] ? 'success' : 'error';
        $message = $result['message'] ?? '';

        $redirectUrl = Request::getRootUrl() . '/backend/index.php?controller=ELeads.Eleads.ELeadsAdmin&active_tab=tab_update&update_result=' . $status;
        if ($message !== '') {
            $redirectUrl .= '&update_message=' . urlencode($message);
        }

        $this->response->redirectTo($redirectUrl);
    }
}
