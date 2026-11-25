<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_pmdownloads
 *
 * @copyright   (C) 2026 Ponto Mega
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace PontoMega\Component\Pmdownloads\Site\View\Items;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\AbstractView;
use Joomla\CMS\Response\JsonResponse;

class JsonView extends AbstractView
{
    public array $items = [];
    public $pagination;
    public $state;
    public $params;

    public function display($tpl = null): void
    {
        /** @var \PontoMega\Component\Pmdownloads\Site\Model\ItemsModel $model */
        $model            = $this->getModel();
        $this->items      = $model->getItems();
        $this->pagination = $model->getPagination();
        $this->state      = $model->getState();
        $this->params     = $this->state->get('params');

        ob_start();
        include JPATH_COMPONENT . '/tmpl/items/default_items.php';
        $html = ob_get_clean();

        $paginationHtml = $this->pagination->pagesTotal > 1 ? $this->pagination->getPagesLinks() : '';

        $payload = [
            'html'       => $html,
            'pagination' => $paginationHtml,
            'total'      => (int) $this->pagination->total,
            'shown'      => \count($this->items),
            'summary'    => Text::sprintf('COM_PMDOWNLOADS_RESULTS_SUMMARY', \count($this->items), (int) $this->pagination->total),
        ];

        echo new JsonResponse($payload);
    }
}
