<?php

namespace Okay\Modules\ELeads\Eleads\Controllers;

use Okay\Controllers\AbstractController;
use Okay\Core\Request;
use Okay\Core\Router;

class FilterRedirectController extends AbstractController
{
    public function render(Request $request)
    {
        $query = trim((string) $request->get('query', 'string'));
        $searchUrl = Router::generateUrl('search', [], true, (int) ($this->language->id ?? 0));

        if ($query !== '') {
            $separator = strpos($searchUrl, '?') === false ? '?' : '&';
            $searchUrl .= $separator . 'keyword=' . urlencode($query);
        }

        $this->response->redirectTo($searchUrl, 301);
    }
}
