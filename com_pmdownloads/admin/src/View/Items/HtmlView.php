<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_pmdownloads
 *
 * @copyright   (C) 2026 Ponto Mega
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace PontoMega\Component\Pmdownloads\Administrator\View\Items;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    public $items;
    public $pagination;
    public $state;
    public $filterForm;
    public $activeFilters;

    public function display($tpl = null): void
    {
        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state         = $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        $user    = Factory::getApplication()->getIdentity();
        $toolbar = Toolbar::getInstance('toolbar');

        ToolbarHelper::title(Text::_('COM_PMDOWNLOADS_MANAGER_ITEMS'), 'download');

        if ($user->authorise('core.create', 'com_pmdownloads')) {
            $toolbar->addNew('item.add');
        }

        if ($user->authorise('core.edit.state', 'com_pmdownloads')) {
            $dropdown = $toolbar->dropdownButton('status-group')
                ->text('JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();
            $childBar->publish('items.publish')->listCheck(true);
            $childBar->unpublish('items.unpublish')->listCheck(true);
            $childBar->standardButton('featured', 'JFEATURE', 'items.featured')->listCheck(true);
            $childBar->standardButton('unfeatured', 'JUNFEATURE', 'items.unfeatured')->listCheck(true);
            $childBar->trash('items.trash')->listCheck(true);
        }

        if ((int) $this->state->get('filter.state') === -2 && $user->authorise('core.delete', 'com_pmdownloads')) {
            $toolbar->delete('items.delete', 'JTOOLBAR_EMPTY_TRASH')->message('JGLOBAL_CONFIRM_DELETE')->listCheck(true);
        }

        if ($user->authorise('core.admin', 'com_pmdownloads')) {
            $toolbar->preferences('com_pmdownloads');
        }
    }
}
