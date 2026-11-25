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

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

class ReleaseTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__pmdownloads_releases', 'id', $db);
    }

    public function check(): bool
    {
        if (empty($this->release_date)) {
            $this->release_date = Factory::getDate()->toSql();
        }

        return parent::check();
    }
}
