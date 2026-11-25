<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_pmdownloads
 *
 * @copyright   (C) 2026 Ponto Mega
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace PontoMega\Component\Pmdownloads\Administrator\View\Item;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    public $form;
    public $item;
    public $state;

    public function display($tpl = null): void
    {
        $this->form  = $this->get('Form');
        $this->item  = $this->get('Item');
        $this->state = $this->get('State');

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        Factory::getApplication()->getInput()->set('hidemainmenu', true);

        $isNew   = ((int) ($this->item->id ?? 0) === 0);
        $toolbar = Toolbar::getInstance('toolbar');

        ToolbarHelper::title(
            $isNew ? Text::_('COM_PMDOWNLOADS_MANAGER_ITEM_NEW') : Text::_('COM_PMDOWNLOADS_MANAGER_ITEM_EDIT'),
            'download'
        );

        $toolbar->apply('item.apply');
        $toolbar->save('item.save');
        $toolbar->save2new('item.save2new');

        if (!$isNew && !empty($this->item->repo_url)) {
            $toolbar->standardButton('syncgithub', 'COM_PMDOWNLOADS_BTN_SYNC_GITHUB', 'item.syncgithub')
                ->icon('icon-refresh')
                ->buttonClass('btn btn-info');
        }

        $toolbar->cancel('item.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
    }
}
