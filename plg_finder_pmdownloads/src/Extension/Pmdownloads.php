<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Finder.pmdownloads
 *
 * @copyright   (C) 2026 Ponto Mega
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace PontoMega\Plugin\Finder\Pmdownloads\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\Finder as FinderEvent;
use Joomla\Component\Finder\Administrator\Indexer\Adapter;
use Joomla\Component\Finder\Administrator\Indexer\Helper;
use Joomla\Component\Finder\Administrator\Indexer\Indexer;
use Joomla\Component\Finder\Administrator\Indexer\Result;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\QueryInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;
use PontoMega\Component\Pmdownloads\Site\Helper\RouteHelper;

final class Pmdownloads extends Adapter implements SubscriberInterface
{
    use DatabaseAwareTrait;

    protected $context = 'Pmdownloads';

    protected $extension = 'com_pmdownloads';

    protected $layout = 'item';

    protected $type_title = 'Download';

    protected $table = '#__pmdownloads_items';

    protected $state_field = 'state';

    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return array_merge(parent::getSubscribedEvents(), [
            'onFinderCategoryChangeState' => 'onFinderCategoryChangeState',
            'onFinderChangeState'         => 'onFinderChangeState',
            'onFinderAfterDelete'         => 'onFinderAfterDelete',
            'onFinderBeforeSave'          => 'onFinderBeforeSave',
            'onFinderAfterSave'           => 'onFinderAfterSave',
        ]);
    }

    protected function setup(): bool
    {
        return true;
    }

    public function onFinderCategoryChangeState(FinderEvent\AfterCategoryChangeStateEvent $event): void
    {
        if ($event->getExtension() === 'com_pmdownloads') {
            $this->categoryStateChange($event->getPks(), $event->getValue());
        }
    }

    public function onFinderAfterDelete(FinderEvent\AfterDeleteEvent $event): void
    {
        $context = $event->getContext();
        $table   = $event->getItem();

        if ($context === 'com_pmdownloads.item') {
            $id = $table->id;
        } elseif ($context === 'com_finder.index') {
            $id = $table->link_id;
        } else {
            return;
        }

        $this->remove($id);
    }

    public function onFinderAfterSave(FinderEvent\AfterSaveEvent $event): void
    {
        $context = $event->getContext();
        $row     = $event->getItem();
        $isNew   = $event->getIsNew();

        if ($context === 'com_pmdownloads.item') {
            if (!$isNew && $this->old_access != $row->access) {
                $this->itemAccessChange($row);
            }
            $this->reindex($row->id);
        }

        if ($context === 'com_categories.category' && !$isNew && $this->old_cataccess != $row->access) {
            $this->categoryAccessChange($row);
        }
    }

    public function onFinderBeforeSave(FinderEvent\BeforeSaveEvent $event): void
    {
        $context = $event->getContext();
        $row     = $event->getItem();
        $isNew   = $event->getIsNew();

        if ($context === 'com_pmdownloads.item' && !$isNew) {
            $this->checkItemAccess($row);
        }

        if ($context === 'com_categories.category' && !$isNew) {
            $this->checkCategoryAccess($row);
        }
    }

    public function onFinderChangeState(FinderEvent\AfterChangeStateEvent $event): void
    {
        $context = $event->getContext();
        $pks     = $event->getPks();
        $value   = $event->getValue();

        if ($context === 'com_pmdownloads.item') {
            $this->itemStateChange($pks, $value);
        }

        if ($context === 'com_plugins.plugin' && $value === 0) {
            $this->pluginDisable($pks);
        }
    }

    protected function index(Result $item): void
    {
        $item->setLanguage();

        if (ComponentHelper::isEnabled($this->extension) === false) {
            return;
        }

        $item->context  = 'com_pmdownloads.item';
        $item->params   = clone ComponentHelper::getParams('com_pmdownloads', true);
        $item->metadata = new Registry($item->metadata);

        $descText   = trim(strip_tags((string) ($item->body ?? '')));
        $relText    = trim((string) ($item->releases_search_text ?? ''));
        $item->body = trim($descText . "\n\n" . $relText);

        $item->url   = $this->getUrl($item->id, $this->extension, $this->layout);
        $item->route = RouteHelper::getItemRoute($item->slug, (int) $item->catid, (string) $item->language);

        $item->addInstruction(Indexer::META_CONTEXT, 'metakey');
        $item->addInstruction(Indexer::META_CONTEXT, 'metadesc');
        $item->addInstruction(Indexer::META_CONTEXT, 'item_type');
        $item->addInstruction(Indexer::META_CONTEXT, 'maintenance_status');

        $item->state = $this->translateState($item->state, $item->cat_state);

        $item->addTaxonomy('Type', $this->type_title);

        if (!empty($item->catid)) {
            $categories = $this->getApplication()->bootComponent('com_pmdownloads')->getCategory(['published' => false, 'access' => false]);
            $category   = $categories ? $categories->get($item->catid) : null;
            if ($category) {
                $item->addNestedTaxonomy('Category', $category, $this->translateState($category->published), $category->access, $category->language);
            }
        }

        if (!empty($item->versions_csv)) {
            $vLabels = [
                'j6'    => 'Joomla 6',
                'j5'    => 'Joomla 5',
                'j4'    => 'Joomla 4',
                'j3'    => 'Joomla 3',
                'j25'   => 'Joomla 2.5',
                'other' => 'Other Platform',
            ];
            foreach (array_filter(explode(',', (string) $item->versions_csv)) as $vc) {
                $item->addTaxonomy('Joomla Version', $vLabels[$vc] ?? strtoupper($vc));
            }
        }

        if (!empty($item->item_type)) {
            $item->addTaxonomy('Download Type', ucwords(str_replace('_', ' ', (string) $item->item_type)));
        }

        if (!empty($item->maintenance_status)) {
            $item->addTaxonomy('Maintenance Status', ucfirst((string) $item->maintenance_status));
        }

        $item->addTaxonomy('Language', $item->language);

        Helper::getContentExtras($item);

        $this->indexer->index($item);
    }

    protected function getListQuery($query = null): QueryInterface
    {
        $db = $this->getDatabase();

        $query = $query instanceof QueryInterface ? $query : $db->createQuery()
            ->select('a.id, a.title, a.alias, a.summary, a.description AS body, a.releases_search_text')
            ->select('a.item_type, a.maintenance_status')
            ->select('a.state, a.catid, a.created AS start_date, a.created_by')
            ->select('a.modified, a.modified_by, a.metakey, a.metadesc, a.metadata, a.language, a.access, a.ordering')
            ->select('a.publish_up AS publish_start_date, a.publish_down AS publish_end_date')
            ->select('c.title AS category, c.published AS cat_state, c.access AS cat_access')
            ->select('(SELECT GROUP_CONCAT(v.version_code ORDER BY v.version_code DESC SEPARATOR \',\') FROM #__pmdownloads_item_versions AS v WHERE v.item_id = a.id) AS versions_csv');

        $caseWhenAlias = ' CASE WHEN ' . $query->charLength('a.alias', '!=', '0') . ' THEN ';
        $aId           = $query->castAs('CHAR', 'a.id');
        $caseWhenAlias .= $query->concatenate([$aId, 'a.alias'], ':') . ' ELSE ' . $aId . ' END AS slug';
        $query->select($caseWhenAlias)
            ->from('#__pmdownloads_items AS a')
            ->join('LEFT', '#__categories AS c ON c.id = a.catid');

        return $query;
    }
}
