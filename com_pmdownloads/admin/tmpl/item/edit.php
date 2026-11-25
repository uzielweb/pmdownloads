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
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')->useScript('form.validate');
$assoc = Associations::isEnabled();
?>
<form action="<?php echo Route::_('index.php?option=com_pmdownloads&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="item-form" class="form-validate">

    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'details', Text::_('COM_PMDOWNLOADS_TAB_GENERAL')); ?>
        <div class="row">
            <div class="col-lg-8">
                <?php echo $this->form->renderField('title'); ?>
                <?php echo $this->form->renderField('alias'); ?>
                <?php echo $this->form->renderField('subtitle'); ?>
                <?php echo $this->form->renderField('summary'); ?>
                <?php echo $this->form->renderField('description'); ?>
            </div>
            <div class="col-lg-4">
                <?php echo $this->form->renderField('catid'); ?>
                <?php echo $this->form->renderField('item_type'); ?>
                <?php echo $this->form->renderField('maintenance_status'); ?>
                <?php echo $this->form->renderField('versions'); ?>
                <?php echo $this->form->renderField('min_php'); ?>
                <?php echo $this->form->renderField('license'); ?>
                <?php echo $this->form->renderField('cover_image'); ?>
                <?php echo $this->form->renderField('cover_alt'); ?>
                <?php echo $this->form->renderField('repo_url'); ?>
                <?php echo $this->form->renderField('demo_url'); ?>
                <?php echo $this->form->renderField('docs_url'); ?>
                <?php echo $this->form->renderField('support_url'); ?>
                <?php echo $this->form->renderField('tags'); ?>
                <?php echo $this->form->renderField('state'); ?>
                <?php echo $this->form->renderField('featured'); ?>
                <?php echo $this->form->renderField('access'); ?>
                <?php echo $this->form->renderField('language'); ?>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'releases', Text::_('COM_PMDOWNLOADS_TAB_RELEASES')); ?>
        <?php echo $this->form->renderFieldset('releases'); ?>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'updateserver', Text::_('COM_PMDOWNLOADS_TAB_UPDATESERVER')); ?>
        <?php echo $this->form->renderFieldset('updateserver'); ?>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'publishing', Text::_('COM_PMDOWNLOADS_TAB_PUBLISHING')); ?>
        <?php echo $this->form->renderFieldset('publishing'); ?>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php if ($assoc) : ?>
            <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'associations', Text::_('JGLOBAL_FIELDSET_ASSOCIATIONS')); ?>
            <?php echo $this->form->renderFieldset('item_associations'); ?>
            <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php endif; ?>

        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
