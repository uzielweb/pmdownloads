<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Content.pmdownloads
 *
 * @copyright   (C) 2026 Ponto Mega
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace PontoMega\Plugin\Content\Pmdownloads\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Event\SubscriberInterface;
use PontoMega\Component\Pmdownloads\Administrator\Helper\PmdownloadsHelper;
use PontoMega\Component\Pmdownloads\Site\Helper\RouteHelper;

final class Pmdownloads extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return [
            'onContentPrepare' => 'onContentPrepare',
        ];
    }

    public function onContentPrepare(ContentPrepareEvent $event): void
    {
        if ($event->getContext() === 'com_finder.indexer') {
            return;
        }

        $article = $event->getItem();
        if (!isset($article->text) || !str_contains((string) $article->text, '{pmdownload')) {
            return;
        }

        $app = $this->getApplication();
        if (!$app->isClient('site')) {
            return;
        }

        $assetsLoaded = false;

        $article->text = preg_replace_callback(
            '/\{pmdownload\s+([^}]+)\}/i',
            function (array $matches) use ($app, &$assetsLoaded): string {
                $attrs = $this->parseAttributes($matches[1]);
                $item  = $this->loadItem($attrs);

                if (!$item) {
                    return '';
                }

                if (!$assetsLoaded) {
                    $app->getLanguage()->load('com_pmdownloads', JPATH_SITE);
                    $wa = $app->getDocument()->getWebAssetManager();
                    $wa->getRegistry()->addExtensionRegistryFile('com_pmdownloads');
                    $wa->useStyle('com_pmdownloads.site');
                    $assetsLoaded = true;
                }

                $itemUrl = Route::_(RouteHelper::getItemRoute($item->id . ':' . $item->alias, (int) $item->catid, (string) $item->language));
                $dlUrl   = !empty($item->release_id) ? Route::_(RouteHelper::getDownloadRoute((int) $item->release_id)) : $itemUrl;
                $sizeStr = !empty($item->file_size) ? PmdownloadsHelper::formatFileSize((int) $item->file_size) : '';

                $html  = '<div class="pmd-embed">';
                $html .= '<div class="pmd-embed__info">';
                $html .= '<div class="pmd-card__badges">';
                if (!empty($item->version)) {
                    $html .= '<span class="pmd-badge pmd-badge--brand">v' . htmlspecialchars((string) $item->version, ENT_QUOTES, 'UTF-8') . '</span>';
                }
                if ($sizeStr !== '') {
                    $html .= '<span class="pmd-badge pmd-badge--muted">' . htmlspecialchars($sizeStr, ENT_QUOTES, 'UTF-8') . '</span>';
                }
                $html .= '</div>';
                $html .= '<div class="pmd-embed__title">' . htmlspecialchars((string) $item->title, ENT_QUOTES, 'UTF-8') . '</div>';
                $html .= '<p class="pmd-embed__summary">' . htmlspecialchars((string) $item->summary, ENT_QUOTES, 'UTF-8') . '</p>';
                $html .= '</div>';
                $html .= '<div class="pmd-embed__actions">';
                $html .= '<a href="' . htmlspecialchars($dlUrl, ENT_QUOTES, 'UTF-8') . '" class="pmd-btn pmd-btn--primary pmd-btn--sm">';
                $html .= '<i class="fa-solid fa-download" aria-hidden="true"></i> ' . Text::_('COM_PMDOWNLOADS_BTN_DOWNLOAD') . '</a>';
                $html .= '<a href="' . htmlspecialchars($itemUrl, ENT_QUOTES, 'UTF-8') . '" class="pmd-btn pmd-btn--outline pmd-btn--sm">';
                $html .= Text::_('COM_PMDOWNLOADS_BTN_DETAILS') . ' &rarr;</a>';
                $html .= '</div></div>';

                return $html;
            },
            (string) $article->text
        );
    }

    private function parseAttributes(string $raw): array
    {
        $attrs = [];
        if (preg_match_all('/(\w+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s]+))/', $raw, $m, PREG_SET_ORDER)) {
            foreach ($m as $pair) {
                $attrs[strtolower($pair[1])] = $pair[2] !== '' ? $pair[2] : ($pair[3] !== '' ? $pair[3] : ($pair[4] ?? ''));
            }
        }

        return $attrs;
    }

    private function loadItem(array $attrs): ?object
    {
        $db     = $this->getDatabase();
        $user   = $this->getApplication()->getIdentity();
        $groups = $user ? $user->getAuthorisedViewLevels() : [1];

        $q = $db->getQuery(true)
            ->select([
                'a.id, a.catid, a.title, a.alias, a.summary, a.language',
                '(SELECT r.id FROM ' . $db->quoteName('#__pmdownloads_releases', 'r') . ' WHERE r.item_id = a.id AND r.state = 1 ORDER BY r.is_current DESC, r.ordering ASC LIMIT 1) AS release_id',
                '(SELECT r.version FROM ' . $db->quoteName('#__pmdownloads_releases', 'r') . ' WHERE r.item_id = a.id AND r.state = 1 ORDER BY r.is_current DESC, r.ordering ASC LIMIT 1) AS version',
                '(SELECT r.file_size FROM ' . $db->quoteName('#__pmdownloads_releases', 'r') . ' WHERE r.item_id = a.id AND r.state = 1 ORDER BY r.is_current DESC, r.ordering ASC LIMIT 1) AS file_size',
            ])
            ->from($db->quoteName('#__pmdownloads_items', 'a'))
            ->where($db->quoteName('a.state') . ' = 1')
            ->whereIn($db->quoteName('a.access'), $groups);

        if (!empty($attrs['id'])) {
            $id = (int) $attrs['id'];
            $q->where($db->quoteName('a.id') . ' = :id')->bind(':id', $id, ParameterType::INTEGER);
        } elseif (!empty($attrs['alias'])) {
            $alias = trim((string) $attrs['alias']);
            $q->where($db->quoteName('a.alias') . ' = :alias')->bind(':alias', $alias, ParameterType::STRING);
            if (Multilanguage::isEnabled()) {
                $langTag = $this->getApplication()->getLanguage()->getTag();
                $q->whereIn($db->quoteName('a.language'), [$langTag, '*'], ParameterType::STRING);
            }
        } else {
            return null;
        }

        return $db->setQuery($q, 0, 1)->loadObject() ?: null;
    }
}
