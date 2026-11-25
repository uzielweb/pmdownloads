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
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    public array $items = [];
    public $pagination;
    public $state;
    public $params;
    public array $facets = [];

    public function display($tpl = null): void
    {
        /** @var \PontoMega\Component\Pmdownloads\Site\Model\ItemsModel $model */
        $model            = $this->getModel();
        $this->items      = $model->getItems();
        $this->pagination = $model->getPagination();
        $this->state      = $model->getState();
        $this->params     = $this->state->get('params');
        $this->facets     = $model->getFacets();

        $this->prepareDocument();

        parent::display($tpl);
    }

    protected function prepareDocument(): void
    {
        $doc = $this->getDocument();
        $wa  = $doc->getWebAssetManager();
        $wa->getRegistry()->addExtensionRegistryFile('com_pmdownloads');

        $wa->useStyle('com_pmdownloads.site')
            ->useScript('com_pmdownloads.app');

        Text::script('COM_PMDOWNLOADS_COPIED');
        Text::script('COM_PMDOWNLOADS_LOADING');
        Text::script('COM_PMDOWNLOADS_RESULTS_SUMMARY');

        $title = trim((string) $this->params->get('page_title', ''));
        if ($title === '') {
            $title = Text::_('COM_PMDOWNLOADS_CATALOG_TITLE');
        }
        $this->setDocumentTitle($title);

        if ($this->params->get('menu-meta_description')) {
            $doc->setDescription((string) $this->params->get('menu-meta_description'));
        }
    }
}
