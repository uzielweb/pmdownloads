<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_pmdownloads
 *
 * @copyright   (C) 2026 Ponto Mega
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace PontoMega\Component\Pmdownloads\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;

class ItemController extends FormController
{
    protected $view_list = 'items';

    public function syncgithub(): void
    {
        $this->checkToken();

        $id = $this->input->getInt('id', 0);
        if ($id <= 0) {
            $data = (array) $this->input->post->get('jform', [], 'array');
            $id   = (int) ($data['id'] ?? 0);
        }

        if ($id <= 0) {
            $this->app->enqueueMessage(Text::_('COM_PMDOWNLOADS_SYNC_SAVE_FIRST'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_pmdownloads&view=items', false));

            return;
        }

        /** @var \PontoMega\Component\Pmdownloads\Administrator\Model\ItemModel $model */
        $model = $this->getModel();
        try {
            $version = $model->syncLatestGithubRelease($id);
            $this->app->enqueueMessage(Text::sprintf('COM_PMDOWNLOADS_SYNC_GITHUB_OK', $version), 'success');
        } catch (\Throwable $e) {
            $this->app->enqueueMessage($e->getMessage(), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_pmdownloads&view=item&layout=edit&id=' . $id, false));
    }
}
