<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_pmdownloads
 *
 * @copyright   (C) 2026 Ponto Mega
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace PontoMega\Module\Pmdownloads\Site\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Language\Multilanguage;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

class DownloadsHelper implements DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    public function getItems(Registry $params, SiteApplication $app): array
    {
        $db      = $this->getDatabase();
        $user    = $app->getIdentity();
        $groups  = $user ? $user->getAuthorisedViewLevels() : [1];
        $langTag = Multilanguage::isEnabled() ? $app->getLanguage()->getTag() : '';
        $count   = max(1, min(12, (int) $params->get('count', 4)));
        $catid   = (int) $params->get('catid', 0);
        $status  = trim((string) $params->get('status_filter', 'active'));
        $mode    = (string) $params->get('mode', 'featured');

        $q = $db->getQuery(true)
            ->select([
                'a.id, a.catid, a.title, a.alias, a.summary, a.item_type, a.maintenance_status',
                'a.cover_image, a.cover_alt, a.downloads_count, a.hits, a.language',
                $db->quoteName('c.title', 'category_title'),
                '(SELECT GROUP_CONCAT(v.version_code ORDER BY FIELD(v.version_code,\'j6\',\'j5\',\'j4\',\'j3\',\'j25\',\'other\') SEPARATOR \',\') FROM ' . $db->quoteName('#__pmdownloads_item_versions', 'v') . ' WHERE v.item_id = a.id) AS versions_csv',
                '(SELECT r.id FROM ' . $db->quoteName('#__pmdownloads_releases', 'r') . ' WHERE r.item_id = a.id AND r.state = 1 ORDER BY r.is_current DESC, r.ordering ASC LIMIT 1) AS current_release_id',
                '(SELECT r.version FROM ' . $db->quoteName('#__pmdownloads_releases', 'r') . ' WHERE r.item_id = a.id AND r.state = 1 ORDER BY r.is_current DESC, r.ordering ASC LIMIT 1) AS current_version',
            ])
            ->from($db->quoteName('#__pmdownloads_items', 'a'))
            ->join('LEFT', $db->quoteName('#__categories', 'c'), $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
            ->where($db->quoteName('a.state') . ' = 1')
            ->whereIn($db->quoteName('a.access'), $groups);

        if ($langTag !== '') {
            $q->whereIn($db->quoteName('a.language'), [$langTag, '*'], ParameterType::STRING);
        }

        if ($catid > 0) {
            $q->where($db->quoteName('a.catid') . ' = :catid')
                ->bind(':catid', $catid, ParameterType::INTEGER);
        }

        if ($status !== '') {
            $q->where($db->quoteName('a.maintenance_status') . ' = :mstatus')
                ->bind(':mstatus', $status, ParameterType::STRING);
        }

        switch ($mode) {
            case 'downloads':
                $q->order($db->quoteName('a.downloads_count') . ' DESC, ' . $db->quoteName('a.hits') . ' DESC');
                break;
            case 'recent':
                $q->order($db->quoteName('a.modified') . ' DESC, ' . $db->quoteName('a.id') . ' DESC');
                break;
            case 'hits':
                $q->order($db->quoteName('a.hits') . ' DESC');
                break;
            default:
                $q->order($db->quoteName('a.featured') . ' DESC, ' . $db->quoteName('a.ordering') . ' ASC, ' . $db->quoteName('a.id') . ' DESC');
                break;
        }

        $rows = $db->setQuery($q, 0, $count)->loadObjectList() ?: [];
        foreach ($rows as $row) {
            $row->versions = array_values(array_filter(explode(',', (string) ($row->versions_csv ?? ''))));
            $row->slug     = $row->id . ':' . $row->alias;
        }

        return $rows;
    }
}
