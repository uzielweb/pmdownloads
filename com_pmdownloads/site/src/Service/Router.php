<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_pmdownloads
 *
 * @copyright   (C) 2026 Ponto Mega
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace PontoMega\Component\Pmdownloads\Site\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

class Router extends RouterView
{
    private DatabaseInterface $db;

    public function __construct(
        SiteApplication $app,
        AbstractMenu $menu,
        CategoryFactoryInterface $categoryFactory,
        DatabaseInterface $db
    ) {
        $this->db = $db;

        $items = new RouterViewConfiguration('items');
        $this->registerView($items);

        $item = new RouterViewConfiguration('item');
        $item->setKey('id')->setParent($items);
        $this->registerView($item);

        parent::__construct($app, $menu);

        $this->attachRule(new MenuRules($this));
        $this->attachRule(new StandardRules($this));
        $this->attachRule(new NomenuRules($this));
    }

    public function getItemSegment($id, $query): array
    {
        if (!str_contains((string) $id, ':')) {
            $intId = (int) $id;
            $q     = $this->db->getQuery(true)
                ->select($this->db->quoteName('alias'))
                ->from($this->db->quoteName('#__pmdownloads_items'))
                ->where($this->db->quoteName('id') . ' = :id')
                ->bind(':id', $intId, ParameterType::INTEGER);
            $alias = (string) $this->db->setQuery($q)->loadResult();

            if ($alias !== '') {
                return [$intId => $alias];
            }

            return [$intId => (string) $intId];
        }

        [$intId, $alias] = explode(':', (string) $id, 2);

        return [(int) $intId => $alias];
    }

    public function getItemId($segment, $query): int
    {
        $segment = trim((string) $segment);

        if ($segment === '') {
            return 0;
        }

        if (ctype_digit($segment)) {
            return (int) $segment;
        }

        $langTag = $this->app->getLanguage()->getTag();

        $q = $this->db->getQuery(true)
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__pmdownloads_items'))
            ->where($this->db->quoteName('alias') . ' = :alias')
            ->whereIn($this->db->quoteName('language'), [$langTag, '*'], ParameterType::STRING)
            ->order('CASE WHEN ' . $this->db->quoteName('language') . ' = ' . $this->db->quote($langTag) . ' THEN 0 ELSE 1 END ASC')
            ->bind(':alias', $segment, ParameterType::STRING);

        $id = (int) $this->db->setQuery($q, 0, 1)->loadResult();

        if ($id === 0) {
            $qAny = $this->db->getQuery(true)
                ->select($this->db->quoteName('id'))
                ->from($this->db->quoteName('#__pmdownloads_items'))
                ->where($this->db->quoteName('alias') . ' = :alias')
                ->bind(':alias', $segment, ParameterType::STRING);
            $id = (int) $this->db->setQuery($qAny, 0, 1)->loadResult();
        }

        return $id;
    }
}
