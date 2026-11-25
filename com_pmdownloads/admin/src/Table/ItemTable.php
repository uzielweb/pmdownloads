<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_pmdownloads
 *
 * @copyright   (C) 2026 Ponto Mega
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace PontoMega\Component\Pmdownloads\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Tag\TaggableTableInterface;
use Joomla\CMS\Tag\TaggableTableTrait;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;

class ItemTable extends Table implements TaggableTableInterface
{
    use TaggableTableTrait;

    public function __construct(DatabaseDriver $db)
    {
        $this->typeAlias = 'com_pmdownloads.item';

        parent::__construct('#__pmdownloads_items', 'id', $db);

        $this->setColumnAlias('published', 'state');
    }

    public function check(): bool
    {
        $this->title = trim((string) $this->title);

        if ($this->title === '') {
            $this->setError(Text::_('COM_PMDOWNLOADS_ERROR_TITLE_REQUIRED'));

            return false;
        }

        if (trim((string) $this->alias) === '') {
            $this->alias = $this->title;
        }

        $this->alias = ApplicationHelper::stringURLSafe($this->alias, $this->language ?: 'pt-BR');

        if (trim(str_replace('-', '', $this->alias)) === '') {
            $this->alias = Factory::getDate()->format('Y-m-d-H-i-s');
        }

        // Ensure alias uniqueness per language
        $db    = $this->getDatabase();
        $id    = (int) $this->id;
        $alias = (string) $this->alias;
        $lang  = (string) ($this->language ?: '*');

        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__pmdownloads_items'))
            ->where($db->quoteName('alias') . ' = :alias')
            ->where($db->quoteName('language') . ' = :lang')
            ->where($db->quoteName('id') . ' != :id')
            ->bind(':alias', $alias, ParameterType::STRING)
            ->bind(':lang', $lang, ParameterType::STRING)
            ->bind(':id', $id, ParameterType::INTEGER);

        if ((int) $db->setQuery($query)->loadResult() > 0) {
            $this->alias .= '-' . Factory::getDate()->format('His');
        }

        $now  = Factory::getDate()->toSql();
        $user = Factory::getApplication()->getIdentity();

        if (empty($this->created)) {
            $this->created = $now;
        }

        if (empty($this->created_by) && $user) {
            $this->created_by = (int) $user->id;
        }

        $this->modified = $now;
        if ($user) {
            $this->modified_by = (int) $user->id;
        }

        if (empty($this->publish_up)) {
            $this->publish_up = $this->created;
        }

        if ($this->publish_down === '') {
            $this->publish_down = null;
        }

        return parent::check();
    }

    public function getTypeAlias(): string
    {
        return $this->typeAlias;
    }
}
