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

/** @var \PontoMega\Component\Pmdownloads\Site\View\Item\HtmlView $this */
$item       = $this->item;
$rel        = $item->current_release;
$typeOpts   = PmdownloadsHelper::getItemTypeOptions();
$statOpts   = PmdownloadsHelper::getStatusOptions();
$matOpts    = PmdownloadsHelper::getMaturityOptions();
$isActive   = ($item->maintenance_status === 'active');
$origin     = Uri::getInstance()->toString(['scheme', 'host', 'port']);
$backUrl    = Route::_(RouteHelper::getItemsRoute(0, (string) $item->language));
$catUrl     = Route::_(RouteHelper::getItemsRoute((int) $item->catid, (string) $item->language));
$rawCover   = !empty($item->cover_image) ? HTMLHelper::cleanImageURL((string) $item->cover_image)->url : '';
$coverUrl   = $rawCover !== '' ? (preg_match('#^https?://#i', $rawCover) ? $rawCover : Uri::root(true) . '/' . ltrim($rawCover, '/')) : '';
$updateUrl  = ((int) $item->enable_updateserver === 1 && !empty($item->element_name))
    ? $origin . Route::_(RouteHelper::getUpdateServerRoute($item->slug, (string) $item->language))
    : '';
$chgUrl     = $updateUrl !== ''
    ? $origin . Route::_(RouteHelper::getChangelogRoute($item->slug, (string) $item->language))
    : '';
?>
<article class="com-pmdownloads pmd-detail" data-pmd-detail>
    <div class="pmd-detail__topbar">
        <a href="<?php echo htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8'); ?>" class="pmd-back-link">
            <span aria-hidden="true">&larr;</span>
            <?php echo Text::_('COM_PMDOWNLOADS_BACK_TO_CATALOG'); ?>
        </a>
        <?php if (!empty($item->category_title)) : ?>
            <a href="<?php echo htmlspecialchars($catUrl, ENT_QUOTES, 'UTF-8'); ?>" class="pmd-badge pmd-badge--brand">
                <?php echo htmlspecialchars((string) $item->category_title, ENT_QUOTES, 'UTF-8'); ?>
            </a>
        <?php endif; ?>
    </div>

    <div class="pmd-detail__hero">
        <div class="pmd-detail__intro">
            <div class="pmd-card__badges">
                <span class="pmd-badge <?php echo $isActive ? 'pmd-badge--brand' : 'pmd-badge--muted'; ?>">
                    <?php echo htmlspecialchars($statOpts[$item->maintenance_status] ?? (string) $item->maintenance_status, ENT_QUOTES, 'UTF-8'); ?>
                </span>
                <span class="pmd-badge pmd-badge--muted">
                    <?php echo htmlspecialchars($typeOpts[$item->item_type] ?? (string) $item->item_type, ENT_QUOTES, 'UTF-8'); ?>
                </span>
                <?php foreach ($item->versions as $ver) : ?>
                    <span class="pmd-badge pmd-badge--dark">
                        <?php echo htmlspecialchars(PmdownloadsHelper::getVersionOptions()[$ver] ?? strtoupper($ver), ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                <?php endforeach; ?>
            </div>

            <h1 class="pmd-detail__title"><?php echo htmlspecialchars((string) $item->title, ENT_QUOTES, 'UTF-8'); ?></h1>

            <?php if (!empty($item->subtitle)) : ?>
                <p class="pmd-detail__subtitle"><?php echo htmlspecialchars((string) $item->subtitle, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <p class="pmd-detail__lead"><?php echo htmlspecialchars((string) $item->summary, ENT_QUOTES, 'UTF-8'); ?></p>

            <?php if (!$isActive) : ?>
                <div class="pmd-legacy-notice" role="note">
                    <i class="fa-solid fa-box-archive" aria-hidden="true"></i>
                    <div>
                        <strong><?php echo Text::_('COM_PMDOWNLOADS_LEGACY_NOTICE_TITLE'); ?></strong>
                        <span><?php echo Text::_('COM_PMDOWNLOADS_LEGACY_NOTICE_DESC'); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($coverUrl !== '') : ?>
                <div class="pmd-detail__cover">
                    <img src="<?php echo htmlspecialchars($coverUrl, ENT_QUOTES, 'UTF-8'); ?>"
                         alt="<?php echo htmlspecialchars((string) ($item->cover_alt ?: $item->title), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            <?php endif; ?>
        </div>

        <aside class="pmd-download-box">
            <div class="pmd-download-box__head">
                <span class="pmd-download-box__eyebrow"><?php echo Text::_('COM_PMDOWNLOADS_BOX_CURRENT_RELEASE'); ?></span>
                <?php if ($rel) : ?>
                    <div class="pmd-download-box__ver">
                        <strong>v<?php echo htmlspecialchars((string) $rel->version, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span class="pmd-badge pmd-badge--brand">
                            <?php echo htmlspecialchars($matOpts[$rel->maturity] ?? ucfirst((string) $rel->maturity), ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($rel) :
                $dlUrl    = Route::_(RouteHelper::getDownloadRoute((int) $rel->id));
                $absDlUrl = $origin . $dlUrl;
                ?>
                <a href="<?php echo htmlspecialchars($dlUrl, ENT_QUOTES, 'UTF-8'); ?>" class="pmd-btn pmd-btn--primary pmd-download-box__cta">
                    <i class="fa-solid fa-download" aria-hidden="true"></i>
                    <?php echo Text::sprintf('COM_PMDOWNLOADS_BTN_DOWNLOAD_VERSION', (string) $rel->version); ?>
                </a>

                <dl class="pmd-meta-list">
                    <?php if (!empty($rel->file_name)) : ?>
                        <div>
                            <dt><?php echo Text::_('COM_PMDOWNLOADS_META_FILE'); ?></dt>
                            <dd><code><?php echo htmlspecialchars((string) $rel->file_name, ENT_QUOTES, 'UTF-8'); ?></code></dd>
                        </div>
                    <?php endif; ?>
                    <?php if ((int) $rel->file_size > 0) : ?>
                        <div>
                            <dt><?php echo Text::_('COM_PMDOWNLOADS_META_SIZE'); ?></dt>
                            <dd><?php echo htmlspecialchars(PmdownloadsHelper::formatFileSize((int) $rel->file_size), ENT_QUOTES, 'UTF-8'); ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($rel->release_date)) : ?>
                        <div>
                            <dt><?php echo Text::_('COM_PMDOWNLOADS_META_RELEASED'); ?></dt>
                            <dd><?php echo HTMLHelper::_('date', $rel->release_date, Text::_('DATE_FORMAT_LC3')); ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($item->min_php)) : ?>
                        <div>
                            <dt><?php echo Text::_('COM_PMDOWNLOADS_META_PHP'); ?></dt>
                            <dd>PHP <?php echo htmlspecialchars((string) $item->min_php, ENT_QUOTES, 'UTF-8'); ?>+</dd>
                        </div>
                    <?php endif; ?>
                    <div>
                        <dt><?php echo Text::_('COM_PMDOWNLOADS_META_LICENSE'); ?></dt>
                        <dd><?php echo htmlspecialchars((string) ($item->license ?: 'GPL-2.0-or-later'), ENT_QUOTES, 'UTF-8'); ?></dd>
                    </div>
                    <div>
                        <dt><?php echo Text::_('COM_PMDOWNLOADS_META_STATS'); ?></dt>
                        <dd>
                            <?php echo Text::sprintf('COM_PMDOWNLOADS_STATS_LINE', number_format((int) $item->downloads_count, 0, ',', '.'), number_format((int) $item->hits, 0, ',', '.')); ?>
                        </dd>
                    </div>
                </dl>

                <?php if (!empty($rel->sha256)) : ?>
                    <div class="pmd-hash-box">
                        <span class="pmd-hash-box__label">SHA-256</span>
                        <code class="pmd-hash-box__value"><?php echo htmlspecialchars((string) $rel->sha256, ENT_QUOTES, 'UTF-8'); ?></code>
                        <button type="button" class="pmd-hash-box__copy" data-pmd-copy="<?php echo htmlspecialchars((string) $rel->sha256, ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="fa-regular fa-copy" aria-hidden="true"></i>
                            <span><?php echo Text::_('COM_PMDOWNLOADS_COPY_HASH'); ?></span>
                        </button>
                    </div>
                <?php endif; ?>

                <button type="button" class="pmd-btn pmd-btn--dark-outline w-100" data-pmd-copy="<?php echo htmlspecialchars($absDlUrl, ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="fa-solid fa-link" aria-hidden="true"></i>
                    <span><?php echo Text::_('COM_PMDOWNLOADS_COPY_INSTALL_URL'); ?></span>
                </button>
            <?php endif; ?>

            <?php if (!empty($item->repo_url) || !empty($item->demo_url) || !empty($item->docs_url)) : ?>
                <div class="pmd-download-box__links">
                    <?php if (!empty($item->repo_url)) : ?>
                        <a href="<?php echo htmlspecialchars((string) $item->repo_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                            <i class="fa-brands fa-github" aria-hidden="true"></i>
                            <?php echo Text::_('COM_PMDOWNLOADS_LINK_GITHUB'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($item->docs_url)) : ?>
                        <a href="<?php echo htmlspecialchars((string) $item->docs_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                            <i class="fa-solid fa-book" aria-hidden="true"></i>
                            <?php echo Text::_('COM_PMDOWNLOADS_LINK_DOCS'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($item->demo_url)) : ?>
                        <a href="<?php echo htmlspecialchars((string) $item->demo_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                            <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                            <?php echo Text::_('COM_PMDOWNLOADS_LINK_DEMO'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </aside>
    </div>

    <div class="pmd-tabs" data-pmd-tabs>
        <div class="pmd-tabs__nav" role="tablist">
            <button type="button" class="pmd-tabs__btn is-active" role="tab" aria-selected="true" data-pmd-tab="overview">
                <?php echo Text::_('COM_PMDOWNLOADS_TAB_OVERVIEW'); ?>
            </button>
            <button type="button" class="pmd-tabs__btn" role="tab" aria-selected="false" data-pmd-tab="releases">
                <?php echo Text::sprintf('COM_PMDOWNLOADS_TAB_RELEASES_COUNT', \count($item->releases)); ?>
            </button>
            <?php if ($updateUrl !== '') : ?>
                <button type="button" class="pmd-tabs__btn" role="tab" aria-selected="false" data-pmd-tab="updateserver">
                    <?php echo Text::_('COM_PMDOWNLOADS_TAB_UPDATESERVER_SITE'); ?>
                </button>
            <?php endif; ?>
        </div>

        <div class="pmd-tabs__panel is-active" role="tabpanel" data-pmd-panel="overview">
            <div class="pmd-detail__prose">
                <?php echo $item->description; ?>
            </div>
        </div>

        <div class="pmd-tabs__panel" role="tabpanel" data-pmd-panel="releases">
            <?php if (!empty($item->releases)) : ?>
                <div class="pmd-releases-list">
                    <?php foreach ($item->releases as $r) :
                        $rDlUrl = Route::_(RouteHelper::getDownloadRoute((int) $r->id));
                        $lines  = PmdownloadsHelper::parseChangelogLines((string) ($r->changelog ?? ''));
                        ?>
                        <div class="pmd-release-row<?php echo (int) $r->is_current === 1 ? ' is-current' : ''; ?>">
                            <div class="pmd-release-row__main">
                                <div class="pmd-release-row__head">
                                    <strong><?php echo htmlspecialchars((string) ($r->title ?: $r->file_name), ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span class="pmd-badge pmd-badge--dark">v<?php echo htmlspecialchars((string) $r->version, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php if ((int) $r->is_current === 1) : ?>
                                        <span class="pmd-badge pmd-badge--brand"><?php echo Text::_('COM_PMDOWNLOADS_BADGE_CURRENT'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="pmd-release-row__meta">
                                    <span><?php echo HTMLHelper::_('date', $r->release_date, Text::_('DATE_FORMAT_LC3')); ?></span>
                                    <?php if ((int) $r->file_size > 0) : ?>
                                        <span><?php echo htmlspecialchars(PmdownloadsHelper::formatFileSize((int) $r->file_size), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                    <span><?php echo Text::sprintf('COM_PMDOWNLOADS_RELEASE_DL_COUNT', (int) $r->downloads_count); ?></span>
                                </div>
                                <?php if (!empty($r->sha256)) : ?>
                                    <div class="pmd-release-row__hash">
                                        <span>SHA-256:</span>
                                        <code><?php echo htmlspecialchars((string) $r->sha256, ENT_QUOTES, 'UTF-8'); ?></code>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($lines)) : ?>
                                    <ul class="pmd-release-row__changelog">
                                        <?php foreach ($lines as $line) : ?>
                                            <li><?php echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8'); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                            <div class="pmd-release-row__action">
                                <a href="<?php echo htmlspecialchars($rDlUrl, ENT_QUOTES, 'UTF-8'); ?>" class="pmd-btn pmd-btn--primary pmd-btn--sm">
                                    <i class="fa-solid fa-download" aria-hidden="true"></i>
                                    <?php echo Text::_('COM_PMDOWNLOADS_BTN_DOWNLOAD'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($updateUrl !== '') : ?>
            <div class="pmd-tabs__panel" role="tabpanel" data-pmd-panel="updateserver">
                <p class="pmd-detail__prose"><?php echo Text::_('COM_PMDOWNLOADS_UPDATESERVER_INTRO'); ?></p>
                <div class="pmd-endpoint-box">
                    <span class="pmd-endpoint-box__label"><?php echo Text::_('COM_PMDOWNLOADS_UPDATESERVER_URL_LABEL'); ?></span>
                    <code><?php echo htmlspecialchars($updateUrl, ENT_QUOTES, 'UTF-8'); ?></code>
                    <button type="button" class="pmd-btn pmd-btn--outline pmd-btn--sm" data-pmd-copy="<?php echo htmlspecialchars($updateUrl, ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="fa-regular fa-copy" aria-hidden="true"></i>
                        <span><?php echo Text::_('COM_PMDOWNLOADS_COPY_URL'); ?></span>
                    </button>
                </div>
                <div class="pmd-endpoint-box">
                    <span class="pmd-endpoint-box__label"><?php echo Text::_('COM_PMDOWNLOADS_CHANGELOG_URL_LABEL'); ?></span>
                    <code><?php echo htmlspecialchars($chgUrl, ENT_QUOTES, 'UTF-8'); ?></code>
                    <button type="button" class="pmd-btn pmd-btn--outline pmd-btn--sm" data-pmd-copy="<?php echo htmlspecialchars($chgUrl, ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="fa-regular fa-copy" aria-hidden="true"></i>
                        <span><?php echo Text::_('COM_PMDOWNLOADS_COPY_URL'); ?></span>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($this->related)) : ?>
        <section class="pmd-related">
            <h2 class="pmd-related__title"><?php echo Text::_('COM_PMDOWNLOADS_RELATED_TITLE'); ?></h2>
            <div class="pmd-related__grid">
                <?php foreach ($this->related as $relItem) :
                    $rUrl = Route::_(RouteHelper::getItemRoute($relItem->slug, (int) $relItem->catid, (string) $relItem->language));
                    ?>
                    <a href="<?php echo htmlspecialchars($rUrl, ENT_QUOTES, 'UTF-8'); ?>" class="pmd-related-card">
                        <div class="pmd-card__badges">
                            <span class="pmd-badge <?php echo $relItem->maintenance_status === 'active' ? 'pmd-badge--brand' : 'pmd-badge--muted'; ?>">
                                <?php echo htmlspecialchars($statOpts[$relItem->maintenance_status] ?? (string) $relItem->maintenance_status, ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                            <?php if (!empty($relItem->current_version)) : ?>
                                <span class="pmd-badge pmd-badge--dark">v<?php echo htmlspecialchars((string) $relItem->current_version, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </div>
                        <h3><?php echo htmlspecialchars((string) $relItem->title, ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p><?php echo htmlspecialchars((string) $relItem->summary, ENT_QUOTES, 'UTF-8'); ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</article>
