<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_pmdownloads
 *
 * @copyright   (C) 2026 Ponto Mega
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace PontoMega\Component\Pmdownloads\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Associations;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

class ItemsModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'title', 'a.title',
                'catid', 'a.catid', 'category_title',
                'item_type', 'a.item_type',
                'maintenance_status', 'a.maintenance_status',
                'featured', 'a.featured',
                'state', 'a.state',
                'ordering', 'a.ordering',
                'hits', 'a.hits',
                'downloads_count', 'a.downloads_count',
                'language', 'a.language',
                'created', 'a.created',
            ];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.ordering', $direction = 'asc'): void
    {
        $this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'));
        $this->setState('filter.state', $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string'));
        $this->setState('filter.catid', $this->getUserStateFromRequest($this->context . '.filter.catid', 'filter_catid', '', 'cmd'));
        $this->setState('filter.maintenance_status', $this->getUserStateFromRequest($this->context . '.filter.maintenance_status', 'filter_maintenance_status', '', 'cmd'));
        $this->setState('filter.item_type', $this->getUserStateFromRequest($this->context . '.filter.item_type', 'filter_item_type', '', 'cmd'));
        $this->setState('filter.language', $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '', 'string'));

        parent::populateState($ordering, $direction);
    }

    protected function getListQuery(): QueryInterface
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select([
            'a.*',
            $db->quoteName('c.title', 'category_title'),
            $db->quoteName('l.title', 'language_title'),
            '(SELECT r.version FROM ' . $db->quoteName('#__pmdownloads_releases', 'r') . ' WHERE r.item_id = a.id AND r.state = 1 ORDER BY r.is_current DESC, r.ordering ASC, r.release_date DESC LIMIT 1) AS current_version',
            '(SELECT COUNT(*) FROM ' . $db->quoteName('#__pmdownloads_releases', 'rc') . ' WHERE rc.item_id = a.id) AS releases_count',
            '(SELECT GROUP_CONCAT(v.version_code ORDER BY v.version_code DESC SEPARATOR \',\') FROM ' . $db->quoteName('#__pmdownloads_item_versions', 'v') . ' WHERE v.item_id = a.id) AS versions_csv',
        ])
            ->from($db->quoteName('#__pmdownloads_items', 'a'))
            ->join('LEFT', $db->quoteName('#__categories', 'c'), $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
            ->join('LEFT', $db->quoteName('#__languages', 'l'), $db->quoteName('l.lang_code') . ' = ' . $db->quoteName('a.language'));

        if (Associations::isEnabled()) {
            $subQuery = $db->getQuery(true)
                ->select('COUNT(' . $db->quoteName('asso1.id') . ') > 1')
                ->from($db->quoteName('#__associations', 'asso1'))
                ->join('INNER', $db->quoteName('#__associations', 'asso2'), $db->quoteName('asso1.key') . ' = ' . $db->quoteName('asso2.key'))
                ->where($db->quoteName('asso1.id') . ' = ' . $db->quoteName('a.id'))
                ->where($db->quoteName('asso1.context') . ' = ' . $db->quote('com_pmdownloads.item'));
            $query->select('(' . $subQuery . ') AS ' . $db->quoteName('association'));
        }

        $state = $this->getState('filter.state');
        if (is_numeric($state)) {
            $stateInt = (int) $state;
            $query->where($db->quoteName('a.state') . ' = :state')
                ->bind(':state', $stateInt, ParameterType::INTEGER);
        } elseif ($state === '') {
            $query->whereIn($db->quoteName('a.state'), [0, 1]);
        }

        $catid = (int) $this->getState('filter.catid');
        if ($catid > 0) {
            $query->where($db->quoteName('a.catid') . ' = :catid')
                ->bind(':catid', $catid, ParameterType::INTEGER);
        }

        $status = (string) $this->getState('filter.maintenance_status');
        if ($status !== '') {
            $query->where($db->quoteName('a.maintenance_status') . ' = :mstatus')
                ->bind(':mstatus', $status, ParameterType::STRING);
        }

        $type = (string) $this->getState('filter.item_type');
        if ($type !== '') {
            $query->where($db->quoteName('a.item_type') . ' = :itype')
                ->bind(':itype', $type, ParameterType::STRING);
        }

        $lang = (string) $this->getState('filter.language');
        if ($lang !== '') {
            $query->where($db->quoteName('a.language') . ' = :lang')
                ->bind(':lang', $lang, ParameterType::STRING);
        }

        $search = trim((string) $this->getState('filter.search'));
        if ($search !== '') {
            if (stripos($search, 'id:') === 0) {
                $sid = (int) substr($search, 3);
                $query->where($db->quoteName('a.id') . ' = :sid')
                    ->bind(':sid', $sid, ParameterType::INTEGER);
            } else {
                $like = '%' . $search . '%';
                $query->where('(' . $db->quoteName('a.title') . ' LIKE :s1 OR ' . $db->quoteName('a.alias') . ' LIKE :s2 OR ' . $db->quoteName('a.summary') . ' LIKE :s3)')
                    ->bind(':s1', $like, ParameterType::STRING)
                    ->bind(':s2', $like, ParameterType::STRING)
                    ->bind(':s3', $like, ParameterType::STRING);
            }
        }

        $orderCol  = $this->state->get('list.ordering', 'a.ordering');
        $orderDirn = $this->state->get('list.direction', 'asc');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }
}
