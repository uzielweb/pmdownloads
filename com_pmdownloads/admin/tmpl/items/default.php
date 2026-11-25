<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_pmdownloads
 *
 * @copyright   (C) 2026 Ponto Mega
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use PontoMega\Component\Pmdownloads\Administrator\Helper\PmdownloadsHelper;

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$typeOpts  = PmdownloadsHelper::getItemTypeOptions();
$statOpts  = PmdownloadsHelper::getStatusOptions();
?>
<form action="<?php echo Route::_('index.php?option=com_pmdownloads&view=items'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table itemList">
                        <thead>
                            <tr>
                                <td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
                                <th class="w-5 text-center"><?php echo Text::_('JSTATUS'); ?></th>
                                <th><?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?></th>
                                <th class="w-10"><?php echo Text::_('COM_PMDOWNLOADS_COL_VERSION'); ?></th>
                                <th class="w-10"><?php echo Text::_('COM_PMDOWNLOADS_FIELD_ITEM_TYPE_LABEL'); ?></th>
                                <th class="w-10"><?php echo Text::_('COM_PMDOWNLOADS_FIELD_STATUS_LABEL'); ?></th>
                                <th class="w-10 text-center"><?php echo HTMLHelper::_('searchtools.sort', 'COM_PMDOWNLOADS_FIELD_DOWNLOADS_LABEL', 'a.downloads_count', $listDirn, $listOrder); ?></th>
                                <th class="w-10"><?php echo Text::_('JGRID_HEADING_LANGUAGE'); ?></th>
                                <th class="w-5 text-end"><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->items as $i => $item) : ?>
                                <tr>
                                    <td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'items.', true); ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo Route::_('index.php?option=com_pmdownloads&task=item.edit&id=' . (int) $item->id); ?>">
                                            <?php echo $this->escape($item->title); ?>
                                        </a>
                                        <?php if ((int) $item->featured === 1) : ?>
                                            <span class="badge bg-success ms-1"><?php echo Text::_('JFEATURED'); ?></span>
                                        <?php endif; ?>
                                        <div class="small text-muted">
                                            <?php echo Text::_('JCATEGORY'); ?>: <?php echo $this->escape($item->category_title ?: '-'); ?>
                                            | <?php echo Text::sprintf('COM_PMDOWNLOADS_RELEASES_QTY', (int) $item->releases_count); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <code><?php echo $this->escape($item->current_version ?: '-'); ?></code>
                                    </td>
                                    <td><?php echo $this->escape($typeOpts[$item->item_type] ?? $item->item_type); ?></td>
                                    <td>
                                        <span class="badge <?php echo $item->maintenance_status === 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $this->escape($statOpts[$item->maintenance_status] ?? $item->maintenance_status); ?>
                                        </span>
                                    </td>
                                    <td class="text-center"><?php echo (int) $item->downloads_count; ?></td>
                                    <td><?php echo $this->escape($item->language_title ?: $item->language); ?></td>
                                    <td class="text-end"><?php echo (int) $item->id; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php echo $this->pagination->getListFooter(); ?>
                <?php endif; ?>

                <input type="hidden" name="task" value="">
                <input type="hidden" name="boxchecked" value="0">
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>
