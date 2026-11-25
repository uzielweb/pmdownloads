<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_pmdownloads
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

$typeOpts = PmdownloadsHelper::getItemTypeOptions();
$statOpts = PmdownloadsHelper::getStatusOptions();

if (empty($this->items)) : ?>
    <div class="pmd-empty">
        <i class="fa-solid fa-box-open pmd-empty__icon" aria-hidden="true"></i>
        <h2 class="pmd-empty__title"><?php echo Text::_('COM_PMDOWNLOADS_NO_RESULTS_TITLE'); ?></h2>
        <p class="pmd-empty__desc"><?php echo Text::_('COM_PMDOWNLOADS_NO_RESULTS_DESC'); ?></p>
    </div>
<?php else : ?>
    <div class="pmd-grid">
        <?php foreach ($this->items as $item) :
            $itemUrl   = Route::_(RouteHelper::getItemRoute($item->slug, (int) $item->catid, (string) $item->language), false);
            $rel       = $item->current_release;
            $dlUrl     = $rel ? Route::_(RouteHelper::getDownloadRoute((int) $rel->id), false) : $itemUrl;
            $isActive  = ($item->maintenance_status === 'active');
            $rawCover  = !empty($item->cover_image) ? HTMLHelper::cleanImageURL((string) $item->cover_image)->url : '';
            $coverUrl  = $rawCover !== '' ? (preg_match('#^https?://#i', $rawCover) ? $rawCover : Uri::root(true) . '/' . ltrim($rawCover, '/')) : '';
            $typeLabel = $typeOpts[$item->item_type] ?? strtoupper((string) $item->item_type);
            $statLabel = $statOpts[$item->maintenance_status] ?? ucfirst((string) $item->maintenance_status);
            ?>
            <article class="pmd-card<?php echo $isActive ? ' pmd-card--active' : ' pmd-card--legacy'; ?>">
                <?php if ($coverUrl !== '') : ?>
                    <a href="<?php echo htmlspecialchars($itemUrl, ENT_QUOTES, 'UTF-8'); ?>" class="pmd-card__media">
                        <img src="<?php echo htmlspecialchars($coverUrl, ENT_QUOTES, 'UTF-8'); ?>"
                             alt="<?php echo htmlspecialchars((string) ($item->cover_alt ?: $item->title), ENT_QUOTES, 'UTF-8'); ?>"
                             loading="lazy">
                    </a>
                <?php endif; ?>

                <div class="pmd-card__head">
                    <div class="pmd-card__badges">
                        <span class="pmd-badge <?php echo $isActive ? 'pmd-badge--brand' : 'pmd-badge--muted'; ?>">
                            <?php echo htmlspecialchars($statLabel, ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                        <span class="pmd-badge pmd-badge--muted">
                            <?php echo htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                        <?php foreach ($item->versions as $ver) : ?>
                            <span class="pmd-badge pmd-badge--dark">
                                <?php echo htmlspecialchars(PmdownloadsHelper::getVersionShortLabel($ver), ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($item->category_title)) : ?>
                        <span class="pmd-card__cat"><?php echo htmlspecialchars((string) $item->category_title, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                </div>

                <div class="pmd-card__body">
                    <h2 class="pmd-card__title">
                        <a href="<?php echo htmlspecialchars($itemUrl, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars((string) $item->title, ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </h2>
                    <p class="pmd-card__summary"><?php echo htmlspecialchars((string) $item->summary, ENT_QUOTES, 'UTF-8'); ?></p>

                    <div class="pmd-card__specs">
                        <?php if ($rel && !empty($rel->version)) : ?>
                            <span class="pmd-spec">
                                <i class="fa-solid fa-tag" aria-hidden="true"></i>
                                v<?php echo htmlspecialchars((string) $rel->version, ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($rel && (int) $rel->file_size > 0) : ?>
                            <span class="pmd-spec">
                                <i class="fa-regular fa-file-zipper" aria-hidden="true"></i>
                                <?php echo htmlspecialchars(PmdownloadsHelper::formatFileSize((int) $rel->file_size), ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ((int) $item->releases_count > 1) : ?>
                            <span class="pmd-spec pmd-spec--brand">
                                <?php echo Text::sprintf('COM_PMDOWNLOADS_FILES_COUNT', (int) $item->releases_count); ?>
                            </span>
                        <?php endif; ?>
                        <span class="pmd-spec">
                            <i class="fa-regular fa-eye" aria-hidden="true"></i>
                            <?php echo number_format((int) $item->hits, 0, ',', '.'); ?>
                        </span>
                    </div>
                </div>

                <div class="pmd-card__foot">
                    <?php if ($rel) : ?>
                        <a href="<?php echo htmlspecialchars($dlUrl, ENT_QUOTES, 'UTF-8'); ?>"
                           class="pmd-btn <?php echo $isActive ? 'pmd-btn--primary' : 'pmd-btn--outline'; ?> pmd-btn--sm">
                            <i class="fa-solid fa-download" aria-hidden="true"></i>
                            <?php echo Text::_('COM_PMDOWNLOADS_BTN_DOWNLOAD'); ?>
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo htmlspecialchars($itemUrl, ENT_QUOTES, 'UTF-8'); ?>" class="pmd-card__link">
                        <?php echo Text::_('COM_PMDOWNLOADS_BTN_DETAILS'); ?>
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
