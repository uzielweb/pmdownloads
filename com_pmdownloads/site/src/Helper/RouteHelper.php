<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_pmdownloads
 *
 * @copyright   (C) 2026 Ponto Mega
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace PontoMega\Component\Pmdownloads\Site\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Multilanguage;

abstract class RouteHelper
{
    public static function getItemsRoute(int $catid = 0, string $language = ''): string
    {
        $link = 'index.php?option=com_pmdownloads&view=items';

        if ($catid > 0) {
            $link .= '&catid=' . $catid;
        }

        if ($language !== '' && $language !== '*' && Multilanguage::isEnabled()) {
            $link .= '&lang=' . $language;
        }

        return $link;
    }

    public static function getItemRoute($id, int $catid = 0, string $language = ''): string
    {
        $link = 'index.php?option=com_pmdownloads&view=item&id=' . $id;

        if ($language !== '' && $language !== '*' && Multilanguage::isEnabled()) {
            $link .= '&lang=' . $language;
        }

        return $link;
    }

    public static function getDownloadRoute(int $releaseId): string
    {
        return 'index.php?option=com_pmdownloads&task=download.file&id=' . $releaseId;
    }

    public static function getUpdateServerRoute($id, string $language = ''): string
    {
        $link = 'index.php?option=com_pmdownloads&view=item&id=' . $id . '&format=xml&layout=updateserver';

        if ($language !== '' && $language !== '*' && Multilanguage::isEnabled()) {
            $link .= '&lang=' . $language;
        }

        return $link;
    }

    public static function getChangelogRoute($id, string $language = ''): string
    {
        $link = 'index.php?option=com_pmdownloads&view=item&id=' . $id . '&format=xml&layout=changelog';

        if ($language !== '' && $language !== '*' && Multilanguage::isEnabled()) {
            $link .= '&lang=' . $language;
        }

        return $link;
    }
}
