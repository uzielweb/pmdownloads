<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_pmdownloads
 *
 * @copyright   (C) 2026 Ponto Mega
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace PontoMega\Module\Pmdownloads\Site\Dispatcher;

\defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;

class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    protected function getLayoutData(): array
    {
        $data   = parent::getLayoutData();
        $helper = $this->getHelperFactory()->getHelper('DownloadsHelper');

        $data['list'] = $helper->getItems($data['params'], $this->getApplication());

        return $data;
    }
}
