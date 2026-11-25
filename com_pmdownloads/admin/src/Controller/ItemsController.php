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
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;

class ItemsController extends AdminController
{
    public function __construct($config = [], $factory = null, $app = null, $input = null)
    {
        parent::__construct($config, $factory, $app, $input);

        $this->registerTask('unfeatured', 'featured');
    }

    public function getModel($name = 'Item', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    public function featured(): void
    {
        $this->checkToken();

        $ids    = (array) $this->input->get('cid', [], 'array');
        $values = ['featured' => 1, 'unfeatured' => 0];
        $task   = $this->getTask();
        $value  = ArrayHelper::getValue($values, $task, 0, 'int');

        if (empty($ids)) {
            $this->app->enqueueMessage(Text::_('JERROR_NO_ITEMS_SELECTED'), 'warning');
        } else {
            /** @var \PontoMega\Component\Pmdownloads\Administrator\Model\ItemModel $model */
            $model = $this->getModel();
            if ($model->featured($ids, $value)) {
                $this->app->enqueueMessage(Text::_('COM_PMDOWNLOADS_FEATURED_UPDATED'), 'success');
            }
        }

        $this->setRedirect(Route::_('index.php?option=com_pmdownloads&view=items', false));
    }
}
