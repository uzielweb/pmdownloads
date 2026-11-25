<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_pmdownloads
 *
 * @copyright   (C) 2026 Ponto Mega
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace PontoMega\Component\Pmdownloads\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

class DisplayController extends BaseController
{
    protected $default_view = 'items';

    public function display($cachable = false, $urlparams = [])
    {
        $safeUrlParams = [
            'id'         => 'INT',
            'catid'      => 'INT',
            'q'          => 'STRING',
            'status'     => 'CMD',
            'type'       => 'CMD',
            'version'    => 'CMD',
            'sort'       => 'CMD',
            'limit'      => 'UINT',
            'limitstart' => 'UINT',
            'lang'       => 'CMD',
            'layout'     => 'CMD',
        ];

        $format   = $this->input->getCmd('format', 'html');
        $viewName = $this->input->getCmd('view', $this->default_view);

        return parent::display($format === 'html' && $viewName === 'item', $safeUrlParams);
    }
}
