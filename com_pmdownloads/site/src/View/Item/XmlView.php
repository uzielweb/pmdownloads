<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_pmdownloads
 *
 * @copyright   (C) 2026 Ponto Mega
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace PontoMega\Component\Pmdownloads\Site\View\Item;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\AbstractView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use PontoMega\Component\Pmdownloads\Administrator\Helper\PmdownloadsHelper;
use PontoMega\Component\Pmdownloads\Site\Helper\RouteHelper;

class XmlView extends AbstractView
{
    public function display($tpl = null): void
    {
        $app = Factory::getApplication();

        if ((int) ComponentHelper::getParams('com_pmdownloads')->get('enable_updateserver', 1) !== 1) {
            throw new \RuntimeException('Update Server disabled', 404);
        }

        /** @var \PontoMega\Component\Pmdownloads\Site\Model\ItemModel $model */
        $model  = $this->getModel();
        $item   = $model->getItem();
        $layout = strtolower($app->getInput()->getCmd('layout', 'updateserver'));

        if ((int) $item->enable_updateserver !== 1 || empty($item->element_name)) {
            throw new \RuntimeException('Update Server not enabled for this item', 404);
        }

        // Always redirect to the canonical GitHub raw XML when available
        if ($layout === 'changelog') {
            $externalChg = PmdownloadsHelper::resolveChangelogUrl($item);
            if ($externalChg !== '') {
                $app->redirect($externalChg, 301);

                return;
            }
        } else {
            $externalUpd = PmdownloadsHelper::resolveUpdateServerUrl($item);
            if ($externalUpd !== '') {
                $app->redirect($externalUpd, 301);

                return;
            }
        }

        $origin = Uri::getInstance()->toString(['scheme', 'host', 'port']);
        $doc    = $this->getDocument();
        $doc->setMimeEncoding('application/xml');

        if ($layout === 'changelog') {
            $xml = $this->buildChangelogXml($item);
        } else {
            $xml = $this->buildUpdateServerXml($item, $origin);
        }

        echo $xml;
    }

    private function buildUpdateServerXml(object $item, string $origin): string
    {
        $infoUrl      = $origin . Route::_(RouteHelper::getItemRoute($item->slug, (int) $item->catid, (string) $item->language), false);
        $changelogUrl = $origin . Route::_(RouteHelper::getChangelogRoute($item->slug, (string) $item->language), false);

        $dom               = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElement('updates');
        $dom->appendChild($root);

        foreach ($item->releases as $rel) {
            $update = $dom->createElement('update');

            $update->appendChild($dom->createElement('name', htmlspecialchars((string) $item->title, ENT_XML1, 'UTF-8')));
            $update->appendChild($dom->createElement('description', htmlspecialchars((string) $item->summary, ENT_XML1, 'UTF-8')));
            $update->appendChild($dom->createElement('element', htmlspecialchars((string) $item->element_name, ENT_XML1, 'UTF-8')));
            $update->appendChild($dom->createElement('type', htmlspecialchars((string) ($item->extension_type ?: 'package'), ENT_XML1, 'UTF-8')));

            if ($item->extension_type === 'plugin' && !empty($item->plugin_folder)) {
                $update->appendChild($dom->createElement('folder', htmlspecialchars((string) $item->plugin_folder, ENT_XML1, 'UTF-8')));
            }

            $update->appendChild($dom->createElement('client', htmlspecialchars((string) ($item->client_type ?: 'site'), ENT_XML1, 'UTF-8')));
            $update->appendChild($dom->createElement('version', htmlspecialchars((string) $rel->version, ENT_XML1, 'UTF-8')));

            $infourlEl = $dom->createElement('infourl', htmlspecialchars($infoUrl, ENT_XML1, 'UTF-8'));
            $infourlEl->setAttribute('title', (string) $item->title);
            $update->appendChild($infourlEl);

            $update->appendChild($dom->createElement('changelogurl', htmlspecialchars($changelogUrl, ENT_XML1, 'UTF-8')));

            $downloads = $dom->createElement('downloads');
            $dlUrl     = ($rel->source_type !== 'local' && preg_match('#^https?://#i', (string) $rel->file_path))
                ? (string) $rel->file_path
                : ($origin . Route::_(RouteHelper::getDownloadRoute((int) $rel->id), false));

            $dlEl = $dom->createElement('downloadurl', htmlspecialchars($dlUrl, ENT_XML1, 'UTF-8'));
            $dlEl->setAttribute('type', 'full');
            $dlEl->setAttribute('format', 'zip');
            $downloads->appendChild($dlEl);
            $update->appendChild($downloads);

            if (!empty($rel->sha256)) {
                $update->appendChild($dom->createElement('sha256', (string) $rel->sha256));
            }

            $tagsEl = $dom->createElement('tags');
            $tagsEl->appendChild($dom->createElement('tag', htmlspecialchars((string) ($rel->maturity ?: 'stable'), ENT_XML1, 'UTF-8')));
            $update->appendChild($tagsEl);

            $update->appendChild($dom->createElement('maintainer', 'Ponto Mega'));
            $update->appendChild($dom->createElement('maintainerurl', 'https://pontomega.com.br'));

            $tp = $dom->createElement('targetplatform');
            $tp->setAttribute('name', 'joomla');
            $tp->setAttribute('version', (string) ($rel->joomla_target ?: '^[56]\.'));
            $update->appendChild($tp);

            if (!empty($rel->php_minimum)) {
                $update->appendChild($dom->createElement('php_minimum', htmlspecialchars((string) $rel->php_minimum, ENT_XML1, 'UTF-8')));
            }

            $root->appendChild($update);
        }

        return (string) $dom->saveXML();
    }

    private function buildChangelogXml(object $item): string
    {
        $dom               = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElement('changelogs');
        $dom->appendChild($root);

        foreach ($item->releases as $rel) {
            $cl = $dom->createElement('changelog');
            $cl->appendChild($dom->createElement('element', htmlspecialchars((string) $item->element_name, ENT_XML1, 'UTF-8')));
            $cl->appendChild($dom->createElement('type', htmlspecialchars((string) ($item->extension_type ?: 'package'), ENT_XML1, 'UTF-8')));
            $cl->appendChild($dom->createElement('version', htmlspecialchars((string) $rel->version, ENT_XML1, 'UTF-8')));

            $lines = PmdownloadsHelper::parseChangelogLines((string) ($rel->changelog ?? ''));
            if (!empty($lines)) {
                $changeEl = $dom->createElement('change');
                foreach ($lines as $line) {
                    $changeEl->appendChild($dom->createElement('item', htmlspecialchars($line, ENT_XML1, 'UTF-8')));
                }
                $cl->appendChild($changeEl);
            }

            $root->appendChild($cl);
        }

        return (string) $dom->saveXML();
    }
}
