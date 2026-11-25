<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_pmdownloads
 *
 * @copyright   (C) 2026 Ponto Mega
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use PontoMega\Component\Pmdownloads\Administrator\Helper\PmdownloadsHelper;
use PontoMega\Component\Pmdownloads\Site\Helper\RouteHelper;

if (empty($list)) {
    return;
}

$app->getLanguage()->load('com_pmdownloads', JPATH_SITE);
$wa = $app->getDocument()->getWebAssetManager();
$wa->getRegistry()->addExtensionRegistryFile('com_pmdownloads');
$wa->useStyle('com_pmdownloads.site');

$langTag    = $app->getLanguage()->getTag();
$catalogUrl = Route::_(RouteHelper::getItemsRoute(0, $langTag));
?>
<div class="mod-pmdownloads">
    <div class="pmd-mod-grid">
        <?php foreach ($list as $item) :
            $itemUrl  = Route::_(RouteHelper::getItemRoute($item->slug, (int) $item->catid, (string) $item->language));
            $dlUrl    = !empty($item->current_release_id) ? Route::_(RouteHelper::getDownloadRoute((int) $item->current_release_id)) : $itemUrl;
            $rawCover = !empty($item->cover_image) ? HTMLHelper::cleanImageURL((string) $item->cover_image)->url : '';
            $coverUrl = $rawCover !== '' ? (preg_match('#^https?://#i', $rawCover) ? $rawCover : Uri::root(true) . '/' . ltrim($rawCover, '/')) : '';
            ?>
            <article class="pmd-card">
                <?php if ($coverUrl !== '') : ?>
                    <a href="<?php echo htmlspecialchars($itemUrl, ENT_QUOTES, 'UTF-8'); ?>" class="pmd-card__media">
                        <img src="<?php echo htmlspecialchars($coverUrl, ENT_QUOTES, 'UTF-8'); ?>"
                             alt="<?php echo htmlspecialchars((string) ($item->cover_alt ?: $item->title), ENT_QUOTES, 'UTF-8'); ?>"
                             loading="lazy">
                    </a>
                <?php endif; ?>
                <div class="pmd-card__head">
                    <div class="pmd-card__badges">
                        <?php if (!empty($item->current_version)) : ?>
                            <span class="pmd-badge pmd-badge--brand">v<?php echo htmlspecialchars((string) $item->current_version, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                        <?php foreach ($item->versions as $ver) : ?>
                            <span class="pmd-badge pmd-badge--dark"><?php echo htmlspecialchars(PmdownloadsHelper::getVersionShortLabel($ver), ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="pmd-card__body">
                    <h3 class="pmd-card__title">
                        <a href="<?php echo htmlspecialchars($itemUrl, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars((string) $item->title, ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </h3>
                    <p class="pmd-card__summary"><?php echo htmlspecialchars((string) $item->summary, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div class="pmd-card__foot">
                    <a href="<?php echo htmlspecialchars($dlUrl, ENT_QUOTES, 'UTF-8'); ?>" class="pmd-btn pmd-btn--primary pmd-btn--sm">
                        <i class="fa-solid fa-download" aria-hidden="true"></i>
                        <?php echo Text::_('COM_PMDOWNLOADS_BTN_DOWNLOAD'); ?>
                    </a>
                    <a href="<?php echo htmlspecialchars($itemUrl, ENT_QUOTES, 'UTF-8'); ?>" class="pmd-card__link">
                        <?php echo Text::_('COM_PMDOWNLOADS_BTN_DETAILS'); ?> &rarr;
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <div class="mt-4">
        <a href="<?php echo htmlspecialchars($catalogUrl, ENT_QUOTES, 'UTF-8'); ?>" class="pmd-btn pmd-btn--outline">
            <?php echo Text::_('MOD_PMDOWNLOADS_VIEW_ALL'); ?> &rarr;
        </a>
    </div>
</div>
