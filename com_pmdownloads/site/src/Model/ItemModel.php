<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_pmdownloads
 *
 * @copyright   (C) 2026 Ponto Mega
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace PontoMega\Component\Pmdownloads\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ItemModel as BaseItemModel;
use Joomla\Database\ParameterType;

class ItemModel extends BaseItemModel
{
    protected $_context = 'com_pmdownloads.item';

    protected function populateState(): void
    {
        $app    = Factory::getApplication();
        $id     = $app->getInput()->getInt('id', 0);
        $params = $app->getParams('com_pmdownloads');

        $this->setState('item.id', $id);
        $this->setState('params', $params);
    }

    public function getItem($pk = null)
    {
        $pk = (int) ($pk ?: $this->getState('item.id'));

        if ($pk <= 0) {
            throw new \RuntimeException(Text::_('COM_PMDOWNLOADS_ERROR_ITEM_NOT_FOUND'), 404);
        }

        if (!isset($this->_item[$pk])) {
            $db     = $this->getDatabase();
            $user   = Factory::getApplication()->getIdentity();
            $groups = $user ? $user->getAuthorisedViewLevels() : [1];

            $query = $db->getQuery(true)
                ->select([
                    'a.*',
                    $db->quoteName('c.title', 'category_title'),
                    $db->quoteName('c.alias', 'category_alias'),
                    '(SELECT GROUP_CONCAT(v.version_code ORDER BY FIELD(v.version_code,\'j6\',\'j5\',\'j4\',\'j3\',\'j25\',\'other\') SEPARATOR \',\') FROM ' . $db->quoteName('#__pmdownloads_item_versions', 'v') . ' WHERE v.item_id = a.id) AS versions_csv',
                ])
                ->from($db->quoteName('#__pmdownloads_items', 'a'))
                ->join('LEFT', $db->quoteName('#__categories', 'c'), $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
                ->where($db->quoteName('a.id') . ' = :id')
                ->where($db->quoteName('a.state') . ' = 1')
                ->whereIn($db->quoteName('a.access'), $groups)
                ->bind(':id', $pk, ParameterType::INTEGER);

            $data = $db->setQuery($query)->loadObject();

            if (!$data) {
                throw new \RuntimeException(Text::_('COM_PMDOWNLOADS_ERROR_ITEM_NOT_FOUND'), 404);
            }

            $qRel = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__pmdownloads_releases'))
                ->where($db->quoteName('item_id') . ' = :iid')
                ->where($db->quoteName('state') . ' = 1')
                ->order($db->quoteName('is_current') . ' DESC, ' . $db->quoteName('ordering') . ' ASC, ' . $db->quoteName('release_date') . ' DESC')
                ->bind(':iid', $pk, ParameterType::INTEGER);

            $releases              = $db->setQuery($qRel)->loadObjectList() ?: [];
            $data->releases        = $releases;
            $data->current_release = $releases[0] ?? null;
            $data->versions        = array_values(array_filter(explode(',', (string) ($data->versions_csv ?? ''))));
            $data->slug            = $data->id . ':' . $data->alias;

            $tagsHelper = new TagsHelper();
            $data->tags = $tagsHelper->getItemTags('com_pmdownloads.item', $pk);

            $this->_item[$pk] = $data;
        }

        return $this->_item[$pk];
    }

    public function hit(int $pk = 0): void
    {
        $pk = $pk ?: (int) $this->getState('item.id');
        if ($pk <= 0) {
            return;
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__pmdownloads_items'))
            ->set($db->quoteName('hits') . ' = ' . $db->quoteName('hits') . ' + 1')
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $pk, ParameterType::INTEGER);
        $db->setQuery($query)->execute();
    }

    public function getRelated(object $item, int $limit = 3): array
    {
        $db     = $this->getDatabase();
        $user   = Factory::getApplication()->getIdentity();
        $groups = $user ? $user->getAuthorisedViewLevels() : [1];
        $itemId = (int) $item->id;
        $catId  = (int) $item->catid;

        $q = $db->getQuery(true)
            ->select([
                'a.id, a.catid, a.title, a.alias, a.summary, a.item_type, a.maintenance_status, a.language, a.downloads_count',
                '(SELECT GROUP_CONCAT(v.version_code ORDER BY FIELD(v.version_code,\'j6\',\'j5\',\'j4\',\'j3\',\'j25\',\'other\') SEPARATOR \',\') FROM ' . $db->quoteName('#__pmdownloads_item_versions', 'v') . ' WHERE v.item_id = a.id) AS versions_csv',
                '(SELECT r.version FROM ' . $db->quoteName('#__pmdownloads_releases', 'r') . ' WHERE r.item_id = a.id AND r.state = 1 ORDER BY r.is_current DESC, r.ordering ASC LIMIT 1) AS current_version',
            ])
            ->from($db->quoteName('#__pmdownloads_items', 'a'))
            ->where($db->quoteName('a.state') . ' = 1')
            ->where($db->quoteName('a.id') . ' != :id')
            ->whereIn($db->quoteName('a.access'), $groups)
            ->order('CASE WHEN ' . $db->quoteName('a.catid') . ' = :catid THEN 0 ELSE 1 END ASC')
            ->order('FIELD(' . $db->quoteName('a.maintenance_status') . ', \'active\', \'maintenance\', \'legacy\') ASC')
            ->order($db->quoteName('a.downloads_count') . ' DESC')
            ->bind(':id', $itemId, ParameterType::INTEGER)
            ->bind(':catid', $catId, ParameterType::INTEGER);

        if (Multilanguage::isEnabled()) {
            $langTag = Factory::getApplication()->getLanguage()->getTag();
            $q->whereIn($db->quoteName('a.language'), [$langTag, '*'], ParameterType::STRING);
        }

        $rows = $db->setQuery($q, 0, $limit)->loadObjectList() ?: [];
        foreach ($rows as $row) {
            $row->versions = array_values(array_filter(explode(',', (string) ($row->versions_csv ?? ''))));
            $row->slug     = $row->id . ':' . $row->alias;
        }

        return $rows;
    }
}
