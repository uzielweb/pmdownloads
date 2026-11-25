<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_pmdownloads
 *
 * @copyright   (C) 2026 Ponto Mega
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \PontoMega\Component\Pmdownloads\Site\View\Items\HtmlView $this */
$q       = (string) $this->state->get('filter.q', '');
$catid   = (int) $this->state->get('filter.catid', 0);
$status  = (string) $this->state->get('filter.status', '');
$type    = (string) $this->state->get('filter.type', '');
$version = (string) $this->state->get('filter.version', '');
$sort    = (string) $this->state->get('filter.sort', 'featured');

$stats      = $this->facets['stats'] ?? (object) ['total_items' => 0, 'active_items' => 0, 'total_downloads' => 0, 'total_hits' => 0];
$categories = $this->facets['categories'] ?? [];
$versions   = $this->facets['versions'] ?? [];
$types      = $this->facets['types'] ?? [];
$statusRows = $this->facets['statuses'] ?? [];

$activeCount = (int) ($statusRows['active'] ?? 0);
$legacyCount = (int) ($statusRows['legacy'] ?? 0);

$actionUrl = Route::_('index.php?option=com_pmdownloads&view=items');
$ajaxUrl   = Route::_('index.php?option=com_pmdownloads&view=items&format=json', false);

$hasActiveFilters = ($q !== '' || $catid > 0 || $status !== '' || $type !== '' || $version !== '');
$heading          = $this->params->get('show_page_heading') && $this->params->get('page_heading')
    ? (string) $this->params->get('page_heading')
    : Text::_('COM_PMDOWNLOADS_CATALOG_TITLE');
?>
<div class="com-pmdownloads pmd-catalog"
     data-pmd-catalog
     data-ajax-url="<?php echo htmlspecialchars($ajaxUrl, ENT_QUOTES, 'UTF-8'); ?>">

    <header class="pmd-catalog__header">
        <div class="pmd-catalog__header-main">
            <span class="pmd-eyebrow"><?php echo Text::_('COM_PMDOWNLOADS_CATALOG_EYEBROW'); ?></span>
            <h1 class="pmd-catalog__title"><?php echo $this->escape($heading); ?></h1>
            <p class="pmd-catalog__lead"><?php echo Text::_('COM_PMDOWNLOADS_CATALOG_SUBTITLE'); ?></p>
        </div>

        <div class="pmd-catalog__stats">
            <div class="pmd-stat">
                <span class="pmd-stat__value"><?php echo (int) $stats->total_items; ?></span>
                <span class="pmd-stat__label"><?php echo Text::_('COM_PMDOWNLOADS_STAT_PROJECTS'); ?></span>
            </div>
            <div class="pmd-stat">
                <span class="pmd-stat__value"><?php echo (int) $stats->active_items; ?></span>
                <span class="pmd-stat__label"><?php echo Text::_('COM_PMDOWNLOADS_STAT_ACTIVE'); ?></span>
            </div>
            <div class="pmd-stat">
                <span class="pmd-stat__value"><?php echo number_format((int) $stats->total_hits + (int) $stats->total_downloads, 0, ',', '.'); ?></span>
                <span class="pmd-stat__label"><?php echo Text::_('COM_PMDOWNLOADS_STAT_REACH'); ?></span>
            </div>
        </div>
    </header>

    <form action="<?php echo htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8'); ?>"
          method="get"
          class="pmd-filter-panel"
          data-pmd-filter-form>

        <div class="pmd-status-tabs" role="group" aria-label="<?php echo Text::_('COM_PMDOWNLOADS_FIELD_STATUS_LABEL'); ?>">
            <button type="button" class="pmd-status-tab<?php echo $status === '' ? ' is-active' : ''; ?>" data-pmd-status="">
                <?php echo Text::_('JALL'); ?>
                <span><?php echo (int) $stats->total_items; ?></span>
            </button>
            <button type="button" class="pmd-status-tab<?php echo $status === 'active' ? ' is-active' : ''; ?>" data-pmd-status="active">
                <?php echo Text::_('COM_PMDOWNLOADS_TAB_ACTIVE_MODERN'); ?>
                <span><?php echo $activeCount; ?></span>
            </button>
            <button type="button" class="pmd-status-tab<?php echo $status === 'legacy' ? ' is-active' : ''; ?>" data-pmd-status="legacy">
                <?php echo Text::_('COM_PMDOWNLOADS_TAB_LEGACY_ARCHIVE'); ?>
                <span><?php echo $legacyCount; ?></span>
            </button>
        </div>

        <div class="pmd-search-row">
            <div class="pmd-search-field">
                <label for="pmd-search-input" class="visually-hidden"><?php echo Text::_('COM_PMDOWNLOADS_SEARCH_LABEL'); ?></label>
                <i class="fa-solid fa-magnifying-glass pmd-search-field__icon" aria-hidden="true"></i>
                <input type="search"
                       id="pmd-search-input"
                       name="q"
                       value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>"
                       placeholder="<?php echo htmlspecialchars(Text::_('COM_PMDOWNLOADS_SEARCH_PLACEHOLDER'), ENT_QUOTES, 'UTF-8'); ?>"
                       class="pmd-search-field__input"
                       autocomplete="off"
                       data-pmd-search>
            </div>
            <button type="submit" class="pmd-btn pmd-btn--primary">
                <?php echo Text::_('COM_PMDOWNLOADS_SEARCH_SUBMIT'); ?>
            </button>
        </div>

        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>" data-pmd-status-input>

        <div class="pmd-filters-grid">
            <div class="pmd-filter-group">
                <label for="pmd-filter-catid" class="pmd-filter-label"><?php echo Text::_('COM_PMDOWNLOADS_FILTER_CATEGORY'); ?></label>
                <select id="pmd-filter-catid" name="catid" class="pmd-select" data-pmd-filter>
                    <option value="0"><?php echo Text::_('COM_PMDOWNLOADS_FILTER_ALL_CATEGORIES'); ?></option>
                    <?php foreach ($categories as $cat) : ?>
                        <option value="<?php echo (int) $cat->id; ?>" <?php echo $catid === (int) $cat->id ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat->title . ' (' . (int) $cat->qty . ')', ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="pmd-filter-group">
                <label for="pmd-filter-version" class="pmd-filter-label"><?php echo Text::_('COM_PMDOWNLOADS_FILTER_VERSION'); ?></label>
                <select id="pmd-filter-version" name="version" class="pmd-select" data-pmd-filter>
                    <option value=""><?php echo Text::_('COM_PMDOWNLOADS_FILTER_ALL_VERSIONS'); ?></option>
                    <?php foreach ($versions as $ver) : ?>
                        <option value="<?php echo htmlspecialchars($ver->code, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $version === $ver->code ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ver->label . ' (' . (int) $ver->qty . ')', ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="pmd-filter-group">
                <label for="pmd-filter-type" class="pmd-filter-label"><?php echo Text::_('COM_PMDOWNLOADS_FILTER_TYPE'); ?></label>
                <select id="pmd-filter-type" name="type" class="pmd-select" data-pmd-filter>
                    <option value=""><?php echo Text::_('COM_PMDOWNLOADS_FILTER_ALL_TYPES'); ?></option>
                    <?php foreach ($types as $t) : ?>
                        <option value="<?php echo htmlspecialchars($t->code, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $type === $t->code ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t->label . ' (' . (int) $t->qty . ')', ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="pmd-filter-group">
                <label for="pmd-filter-sort" class="pmd-filter-label"><?php echo Text::_('COM_PMDOWNLOADS_FILTER_SORT'); ?></label>
                <select id="pmd-filter-sort" name="sort" class="pmd-select" data-pmd-filter>
                    <option value="featured" <?php echo $sort === 'featured' ? 'selected' : ''; ?>><?php echo Text::_('COM_PMDOWNLOADS_SORT_FEATURED'); ?></option>
                    <option value="downloads" <?php echo $sort === 'downloads' ? 'selected' : ''; ?>><?php echo Text::_('COM_PMDOWNLOADS_SORT_DOWNLOADS'); ?></option>
                    <option value="recent" <?php echo $sort === 'recent' ? 'selected' : ''; ?>><?php echo Text::_('COM_PMDOWNLOADS_SORT_RECENT'); ?></option>
                    <option value="hits" <?php echo $sort === 'hits' ? 'selected' : ''; ?>><?php echo Text::_('COM_PMDOWNLOADS_SORT_HITS'); ?></option>
                    <option value="alpha" <?php echo $sort === 'alpha' ? 'selected' : ''; ?>><?php echo Text::_('COM_PMDOWNLOADS_SORT_ALPHA'); ?></option>
                </select>
            </div>
        </div>

        <?php if (!empty($categories)) : ?>
            <div class="pmd-quick-cats" role="group" aria-label="<?php echo Text::_('COM_PMDOWNLOADS_FILTER_CATEGORY'); ?>">
                <button type="button" class="pmd-quick-cat<?php echo $catid === 0 ? ' is-active' : ''; ?>" data-pmd-quick-cat="0">
                    <?php echo Text::_('JALL'); ?>
                    <span><?php echo (int) $stats->total_items; ?></span>
                </button>
                <?php foreach ($categories as $cat) : ?>
                    <button type="button" class="pmd-quick-cat<?php echo $catid === (int) $cat->id ? ' is-active' : ''; ?>" data-pmd-quick-cat="<?php echo (int) $cat->id; ?>">
                        <?php echo htmlspecialchars($cat->title, ENT_QUOTES, 'UTF-8'); ?>
                        <span><?php echo (int) $cat->qty; ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="pmd-toolbar-meta">
            <div class="pmd-results-summary" data-pmd-summary aria-live="polite">
                <?php echo Text::sprintf('COM_PMDOWNLOADS_RESULTS_SUMMARY', \count($this->items), (int) $this->pagination->total); ?>
            </div>
            <button type="button" class="pmd-reset-btn<?php echo $hasActiveFilters ? '' : ' d-none'; ?>" data-pmd-reset>
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                <?php echo Text::_('COM_PMDOWNLOADS_CLEAR_FILTERS'); ?>
            </button>
        </div>
    </form>

    <div class="pmd-results-region" data-pmd-results>
        <?php echo $this->loadTemplate('items'); ?>
    </div>

    <div class="pmd-pagination-region" data-pmd-pagination>
        <?php if ($this->pagination->pagesTotal > 1) : ?>
            <?php echo $this->pagination->getPagesLinks(); ?>
        <?php endif; ?>
    </div>
</div>
