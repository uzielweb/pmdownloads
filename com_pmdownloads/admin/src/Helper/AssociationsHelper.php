<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_pmdownloads
 *
 * @copyright   (C) 2026 Ponto Mega
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace PontoMega\Component\Pmdownloads\Administrator\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Association\AssociationExtensionHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use PontoMega\Component\Pmdownloads\Administrator\Table\ItemTable;
use PontoMega\Component\Pmdownloads\Site\Helper\RouteHelper;

class AssociationsHelper extends AssociationExtensionHelper
{
    protected $extension = 'com_pmdownloads';

    protected $itemTypes = ['item', 'category'];

    protected $associationsSupport = true;

    public function getAssociationsForItem($id = 0, $view = null): array
    {
        $app   = Factory::getApplication();
        $input = $app->getInput();

        $view = $view ?? $input->getCmd('view');
        $id   = empty($id) ? $input->getInt('id') : (int) $id;

        if ($view === 'item' && $id > 0) {
            $associations = Associations::getAssociations(
                $this->extension,
                '#__pmdownloads_items',
                'com_pmdownloads.item',
                $id,
                'id',
                'alias',
                'catid'
            );

            $return = [];
            foreach ($associations as $tag => $item) {
                $return[$tag] = RouteHelper::getItemRoute($item->id, (int) ($item->catid ?? 0), $tag);
            }

            return $return;
        }

        return [];
    }

    public function getAssociations($typeName = '', $id = 0): array
    {
        $typeName = (string) ($typeName ?? '');
        $id       = (int) $id;

        if ($typeName === '' || $id <= 0) {
            return [];
        }

        $type = $this->getType($typeName);

        if ($typeName === 'category') {
            $context    = 'com_categories.item';
            $catidField = '';
        } else {
            $context    = 'com_pmdownloads.item';
            $catidField = 'catid';
        }

        return Associations::getAssociations(
            $this->extension,
            $type['tables']['a'],
            $context,
            $id,
            'id',
            'alias',
            $catidField
        );
    }

    public function getItem($typeName, $id)
    {
        if (empty($id)) {
            return null;
        }

        if ($typeName === 'category') {
            $table = Table::getInstance('Category');
        } else {
            $table = new ItemTable(Factory::getContainer()->get(DatabaseInterface::class));
        }

        $table->load($id);

        return $table;
    }

    public function getType($typeName = ''): array
    {
        if ($typeName === 'category') {
            return [
                'fields'  => $this->getFieldsTemplate(),
                'support' => $this->getSupportTemplate(),
                'tables'  => ['a' => '#__categories'],
                'joins'   => [],
                'title'   => 'category',
            ];
        }

        return [
            'fields'  => $this->getFieldsTemplate(),
            'support' => $this->getSupportTemplate(),
            'tables'  => ['a' => '#__pmdownloads_items'],
            'joins'   => [],
            'title'   => 'item',
        ];
    }
}
