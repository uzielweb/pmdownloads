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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use PontoMega\Component\Pmdownloads\Administrator\Helper\PmdownloadsHelper;

class ItemsModel extends ListModel
{
    protected function populateState($ordering = null, $direction = null): void
    {
        $app    = Factory::getApplication();
        $input  = $app->getInput();
        $params = $app->getParams('com_pmdownloads');

        $this->setState('params', $params);

        $defaultLimit = (int) $params->get('items_per_page', ComponentHelper::getParams('com_pmdownloads')->get('items_per_page', 12));
        $limit        = $input->getUint('limit', $defaultLimit);
        $limitstart   = $input->getUint('limitstart', 0);

        $this->setState('list.limit', max(1, min(60, $limit)));
        $this->setState('list.start', $limitstart);

        $this->setState('filter.q', trim($input->getString('q', '')));
        $this->setState('filter.catid', $input->getInt('catid', (int) $params->get('catid', 0)));
        $this->setState('filter.status', strtolower(trim($input->getCmd('status', (string) $params->get('default_status', '')))));
        $this->setState('filter.type', strtolower(trim($input->getCmd('type', ''))));
        $this->setState('filter.version', strtolower(trim($input->getCmd('version', ''))));

        $defaultSort = (string) $params->get('default_sort', 'featured');
        $sort        = strtolower(trim($input->getCmd('sort', $defaultSort)));
        $allowedSort = ['featured', 'downloads', 'recent', 'hits', 'alpha'];
        if (!\in_array($sort, $allowedSort, true)) {
            $sort = $defaultSort;
        }
        $this->setState('filter.sort', $sort);
        $this->setState('filter.language', Multilanguage::isEnabled() ? $app->getLanguage()->getTag() : '');
    }

    protected function getStoreId($id = ''): string
    {
        $id .= ':' . $this->getState('filter.q');
        $id .= ':' . $this->getState('filter.catid');
        $id .= ':' . $this->getState('filter.status');
        $id .= ':' . $this->getState('filter.type');
        $id .= ':' . $this->getState('filter.version');
        $id .= ':' . $this->getState('filter.sort');
        $id .= ':' . $this->getState('filter.language');

        return parent::getStoreId($id);
    }

    protected function getListQuery(): QueryInterface
    {
        $db     = $this->getDatabase();
        $query  = $db->getQuery(true);
        $user   = Factory::getApplication()->getIdentity();
        $groups = $user ? $user->getAuthorisedViewLevels() : [1];
        $now    = Factory::getDate()->toSql();

        $query->select([
            'a.id, a.catid, a.title, a.alias, a.subtitle, a.summary, a.item_type, a.maintenance_status',
            'a.platform, a.license, a.min_php, a.repo_url, a.demo_url, a.cover_image, a.cover_alt',
            'a.enable_updateserver, a.featured, a.hits, a.downloads_count, a.language, a.created, a.modified',
            $db->quoteName('c.title', 'category_title'),
            $db->quoteName('c.alias', 'category_alias'),
            '(SELECT GROUP_CONCAT(v.version_code ORDER BY FIELD(v.version_code,\'j6\',\'j5\',\'j4\',\'j3\',\'j25\',\'other\') SEPARATOR \',\') FROM ' . $db->quoteName('#__pmdownloads_item_versions', 'v') . ' WHERE v.item_id = a.id) AS versions_csv',
            '(SELECT COUNT(*) FROM ' . $db->quoteName('#__pmdownloads_releases', 'rc') . ' WHERE rc.item_id = a.id AND rc.state = 1) AS releases_count',
        ])
            ->from($db->quoteName('#__pmdownloads_items', 'a'))
            ->join('LEFT', $db->quoteName('#__categories', 'c'), $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
            ->where($db->quoteName('a.state') . ' = 1')
            ->whereIn($db->quoteName('a.access'), $groups)
            ->where('(' . $db->quoteName('a.publish_up') . ' IS NULL OR ' . $db->quoteName('a.publish_up') . ' <= :nowUp)')
            ->where('(' . $db->quoteName('a.publish_down') . ' IS NULL OR ' . $db->quoteName('a.publish_down') . ' >= :nowDown)')
            ->bind(':nowUp', $now, ParameterType::STRING)
            ->bind(':nowDown', $now, ParameterType::STRING);

        $langTag = (string) $this->getState('filter.language');
        if ($langTag !== '') {
            $query->whereIn($db->quoteName('a.language'), [$langTag, '*'], ParameterType::STRING);
        }

        $catid = (int) $this->getState('filter.catid');
        if ($catid > 0) {
            $query->where($db->quoteName('a.catid') . ' = :catid')
                ->bind(':catid', $catid, ParameterType::INTEGER);
        }

        $status = (string) $this->getState('filter.status');
        if ($status !== '') {
            $query->where($db->quoteName('a.maintenance_status') . ' = :mstatus')
                ->bind(':mstatus', $status, ParameterType::STRING);
        }

        $type = (string) $this->getState('filter.type');
        if ($type !== '') {
            $query->where($db->quoteName('a.item_type') . ' = :itype')
                ->bind(':itype', $type, ParameterType::STRING);
        }

        $version = (string) $this->getState('filter.version');
        if ($version !== '') {
            $query->join('INNER', $db->quoteName('#__pmdownloads_item_versions', 'fv'), $db->quoteName('fv.item_id') . ' = ' . $db->quoteName('a.id'))
                ->where($db->quoteName('fv.version_code') . ' = :ver')
                ->bind(':ver', $version, ParameterType::STRING);
        }

        $search    = (string) $this->getState('filter.q');
        $hasSearch = ($search !== '');
        if ($hasSearch) {
            $like      = '%' . $search . '%';
            $likeParts = [
                $db->quoteName('a.title') . ' LIKE :l1',
                $db->quoteName('a.subtitle') . ' LIKE :l2',
                $db->quoteName('a.summary') . ' LIKE :l3',
                $db->quoteName('a.description') . ' LIKE :l4',
                $db->quoteName('a.releases_search_text') . ' LIKE :l5',
            ];
            $query->bind(':l1', $like, ParameterType::STRING)
                ->bind(':l2', $like, ParameterType::STRING)
                ->bind(':l3', $like, ParameterType::STRING)
                ->bind(':l4', $like, ParameterType::STRING)
                ->bind(':l5', $like, ParameterType::STRING);

            if (mb_strlen($search) >= 3) {
                $ftExpr = 'MATCH(' . $db->quoteName('a.title') . ', ' . $db->quoteName('a.subtitle') . ', ' . $db->quoteName('a.summary') . ', ' . $db->quoteName('a.description') . ', ' . $db->quoteName('a.releases_search_text') . ') AGAINST (:ftQ IN NATURAL LANGUAGE MODE)';
                $query->select($ftExpr . ' AS ' . $db->quoteName('relevance_score'))
                    ->bind(':ftQ', $search, ParameterType::STRING);
                $likeParts[] = $ftExpr . ' > 0';
            } else {
                $query->select('0 AS ' . $db->quoteName('relevance_score'));
            }

            $query->where('(' . implode(' OR ', $likeParts) . ')');
        } else {
            $query->select('0 AS ' . $db->quoteName('relevance_score'));
        }

        $sort = (string) $this->getState('filter.sort');
        if ($hasSearch && $sort === 'featured') {
            $query->order($db->quoteName('relevance_score') . ' DESC')
                ->order('FIELD(' . $db->quoteName('a.maintenance_status') . ', \'active\', \'maintenance\', \'legacy\') ASC')
                ->order($db->quoteName('a.featured') . ' DESC');
        } else {
            switch ($sort) {
                case 'downloads':
                    $query->order($db->quoteName('a.downloads_count') . ' DESC')
                        ->order($db->quoteName('a.hits') . ' DESC');
                    break;
                case 'recent':
                    $query->order($db->quoteName('a.modified') . ' DESC')
                        ->order($db->quoteName('a.id') . ' DESC');
                    break;
                case 'hits':
                    $query->order($db->quoteName('a.hits') . ' DESC');
                    break;
                case 'alpha':
                    $query->order($db->quoteName('a.title') . ' ASC');
                    break;
                default:
                    $query->order('FIELD(' . $db->quoteName('a.maintenance_status') . ', \'active\', \'maintenance\', \'legacy\') ASC')
                        ->order($db->quoteName('a.featured') . ' DESC')
                        ->order($db->quoteName('a.ordering') . ' ASC')
                        ->order($db->quoteName('a.hits') . ' DESC');
                    break;
            }
        }

        return $query;
    }

    public function getItems(): array
    {
        $items = parent::getItems() ?: [];
        if (empty($items)) {
            return [];
        }

        $ids = array_map(static fn ($row): int => (int) $row->id, $items);
        $db  = $this->getDatabase();
        $qR  = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__pmdownloads_releases'))
            ->whereIn($db->quoteName('item_id'), $ids)
            ->where($db->quoteName('state') . ' = 1')
            ->order($db->quoteName('is_current') . ' DESC, ' . $db->quoteName('ordering') . ' ASC, ' . $db->quoteName('release_date') . ' DESC');

        $relsByItem = [];
        foreach ($db->setQuery($qR)->loadObjectList() ?: [] as $rel) {
            $iid = (int) $rel->item_id;
            if (!isset($relsByItem[$iid])) {
                $relsByItem[$iid] = $rel;
            }
        }

        foreach ($items as $item) {
            $item->versions        = array_values(array_filter(explode(',', (string) ($item->versions_csv ?? ''))));
            $item->slug            = $item->id . ':' . $item->alias;
            $item->current_release = $relsByItem[(int) $item->id] ?? null;
        }

        return $items;
    }

    public function getFacets(): array
    {
        $db      = $this->getDatabase();
        $user    = Factory::getApplication()->getIdentity();
        $groups  = $user ? $user->getAuthorisedViewLevels() : [1];
        $langTag = (string) $this->getState('filter.language');

        $baseWhere = static function (QueryInterface $q) use ($db, $groups, $langTag): void {
            $q->where($db->quoteName('a.state') . ' = 1')
                ->whereIn($db->quoteName('a.access'), $groups);
            if ($langTag !== '') {
                $q->whereIn($db->quoteName('a.language'), [$langTag, '*'], ParameterType::STRING);
            }
        };

        // Categories
        $qCat = $db->getQuery(true)
            ->select([$db->quoteName('c.id'), $db->quoteName('c.title'), $db->quoteName('c.alias'), 'COUNT(' . $db->quoteName('a.id') . ') AS qty'])
            ->from($db->quoteName('#__categories', 'c'))
            ->join('INNER', $db->quoteName('#__pmdownloads_items', 'a'), $db->quoteName('a.catid') . ' = ' . $db->quoteName('c.id'))
            ->where($db->quoteName('c.extension') . ' = ' . $db->quote('com_pmdownloads'))
            ->where($db->quoteName('c.published') . ' = 1')
            ->group([$db->quoteName('c.id'), $db->quoteName('c.title'), $db->quoteName('c.alias')])
            ->order($db->quoteName('c.lft') . ' ASC');
        $baseWhere($qCat);
        $categories = $db->setQuery($qCat)->loadObjectList() ?: [];

        // Versions
        $qVer = $db->getQuery(true)
            ->select([$db->quoteName('v.version_code'), 'COUNT(DISTINCT ' . $db->quoteName('v.item_id') . ') AS qty'])
            ->from($db->quoteName('#__pmdownloads_item_versions', 'v'))
            ->join('INNER', $db->quoteName('#__pmdownloads_items', 'a'), $db->quoteName('a.id') . ' = ' . $db->quoteName('v.item_id'))
            ->group($db->quoteName('v.version_code'));
        $baseWhere($qVer);
        $verRows  = $db->setQuery($qVer)->loadAssocList('version_code', 'qty') ?: [];
        $versions = [];
        foreach (PmdownloadsHelper::getVersionOptions() as $code => $label) {
            if (!empty($verRows[$code])) {
                $versions[] = (object) ['code' => $code, 'label' => $label, 'qty' => (int) $verRows[$code]];
            }
        }

        // Types
        $qType = $db->getQuery(true)
            ->select([$db->quoteName('a.item_type'), 'COUNT(' . $db->quoteName('a.id') . ') AS qty'])
            ->from($db->quoteName('#__pmdownloads_items', 'a'))
            ->group($db->quoteName('a.item_type'))
            ->order('qty DESC');
        $baseWhere($qType);
        $typeRows = $db->setQuery($qType)->loadAssocList('item_type', 'qty') ?: [];
        $typeOpts = PmdownloadsHelper::getItemTypeOptions();
        $types    = [];
        foreach ($typeRows as $code => $qty) {
            $types[] = (object) ['code' => $code, 'label' => $typeOpts[$code] ?? ucfirst($code), 'qty' => (int) $qty];
        }

        // Statuses
        $qSt = $db->getQuery(true)
            ->select([$db->quoteName('a.maintenance_status'), 'COUNT(' . $db->quoteName('a.id') . ') AS qty'])
            ->from($db->quoteName('#__pmdownloads_items', 'a'))
            ->group($db->quoteName('a.maintenance_status'));
        $baseWhere($qSt);
        $statusRows = $db->setQuery($qSt)->loadAssocList('maintenance_status', 'qty') ?: [];

        // Stats
        $qStats = $db->getQuery(true)
            ->select([
                'COUNT(' . $db->quoteName('a.id') . ') AS total_items',
                'SUM(CASE WHEN ' . $db->quoteName('a.maintenance_status') . ' = \'active\' THEN 1 ELSE 0 END) AS active_items',
                'COALESCE(SUM(' . $db->quoteName('a.downloads_count') . '), 0) AS total_downloads',
                'COALESCE(SUM(' . $db->quoteName('a.hits') . '), 0) AS total_hits',
            ])
            ->from($db->quoteName('#__pmdownloads_items', 'a'));
        $baseWhere($qStats);
        $stats = $db->setQuery($qStats)->loadObject() ?: (object) ['total_items' => 0, 'active_items' => 0, 'total_downloads' => 0, 'total_hits' => 0];

        return [
            'categories' => $categories,
            'versions'   => $versions,
            'types'      => $types,
            'statuses'   => $statusRows,
            'stats'      => $stats,
        ];
    }
}
