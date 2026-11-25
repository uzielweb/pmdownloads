<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_pmdownloads
 *
 * @copyright   (C) 2026 Ponto Mega
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use PontoMega\Component\Pmdownloads\Site\Helper\RouteHelper;

if (empty($list)) {
    return;
}

$wa = $app->getDocument()->getWebAssetManager();
$wa->getRegistry()->addExtensionRegistryFile('com_pmdownloads');
$wa->useStyle('com_pmdownloads.site');
?>
<div class="mod-pmdownloads">
    <ul class="pmd-mod-list">
        <?php foreach ($list as $item) :
            $itemUrl = Route::_(RouteHelper::getItemRoute($item->slug, (int) $item->catid, (string) $item->language));
            ?>
            <li>
                <a href="<?php echo htmlspecialchars($itemUrl, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars((string) $item->title, ENT_QUOTES, 'UTF-8'); ?>
                </a>
                <?php if (!empty($item->current_version)) : ?>
                    <span class="pmd-badge pmd-badge--brand">v<?php echo htmlspecialchars((string) $item->current_version, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
