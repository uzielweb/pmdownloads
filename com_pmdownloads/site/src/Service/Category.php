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

use Joomla\CMS\Categories\Categories;

class Category extends Categories
{
    public function __construct($options = [])
    {
        $options['table']     = '#__pmdownloads_items';
        $options['extension'] = 'com_pmdownloads';

        parent::__construct($options);
    }
}
