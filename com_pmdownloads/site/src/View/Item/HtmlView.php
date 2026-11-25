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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use PontoMega\Component\Pmdownloads\Administrator\Helper\PmdownloadsHelper;
use PontoMega\Component\Pmdownloads\Site\Helper\RouteHelper;

class HtmlView extends BaseHtmlView
{
    public $item;
    public $state;
    public $params;
    public array $related = [];

    public function display($tpl = null): void
    {
        /** @var \PontoMega\Component\Pmdownloads\Site\Model\ItemModel $model */
        $model        = $this->getModel();
        $this->item   = $model->getItem();
        $this->state  = $model->getState();
        $this->params = $this->state->get('params');

        $model->hit((int) $this->item->id);

        if ((int) ComponentHelper::getParams('com_pmdownloads')->get('enable_related', 1) === 1) {
            $this->related = $model->getRelated($this->item, 3);
        }

        $this->prepareDocument();

        parent::display($tpl);
    }

    protected function prepareDocument(): void
    {
        $app = Factory::getApplication();
        $doc = $this->getDocument();
        $wa  = $doc->getWebAssetManager();
        $wa->getRegistry()->addExtensionRegistryFile('com_pmdownloads');

        $wa->useStyle('com_pmdownloads.site')
            ->useScript('com_pmdownloads.app');

        Text::script('COM_PMDOWNLOADS_COPIED');
        Text::script('COM_PMDOWNLOADS_COPY_HASH');
        Text::script('COM_PMDOWNLOADS_COPY_URL');

        $this->setDocumentTitle((string) $this->item->title);

        $desc = trim((string) ($this->item->metadesc ?: $this->item->summary));
        if ($desc !== '') {
            $doc->setDescription($desc);
            $doc->setMetaData('og:description', $desc, 'property');
        }

        $doc->setMetaData('og:title', (string) $this->item->title, 'property');
        $doc->setMetaData('og:type', 'website', 'property');

        if (!empty($this->item->cover_image)) {
            $cleanImg = HTMLHelper::cleanImageURL((string) $this->item->cover_image)->url;
            if ($cleanImg !== '') {
                $absImg = str_starts_with($cleanImg, 'http') ? $cleanImg : Uri::root() . ltrim($cleanImg, '/');
                $doc->setMetaData('og:image', $absImg, 'property');
            }
        }

        if (!empty($this->item->metakey)) {
            $doc->setMetaData('keywords', (string) $this->item->metakey);
        }

        $baseOrigin = Uri::getInstance()->toString(['scheme', 'host', 'port']);
        $canonical  = $baseOrigin . Route::_(RouteHelper::getItemRoute($this->item->slug, (int) $this->item->catid, (string) $this->item->language), false);
        $doc->addHeadLink($canonical, 'canonical');

        $app->getPathway()->addItem((string) $this->item->title);

        if ((int) ComponentHelper::getParams('com_pmdownloads')->get('enable_schema_jsonld', 1) === 1) {
            $rel    = $this->item->current_release;
            $schema = [
                '@context'            => 'https://schema.org',
                '@type'               => 'SoftwareApplication',
                'name'                => (string) $this->item->title,
                'description'         => $desc,
                'applicationCategory' => 'DeveloperApplication',
                'operatingSystem'     => 'Joomla! CMS',
                'url'                 => $canonical,
                'license'             => (string) ($this->item->license ?: 'GPL-2.0-or-later'),
                'offers'              => [
                    '@type'         => 'Offer',
                    'price'         => '0',
                    'priceCurrency' => 'BRL',
                ],
                'author'              => [
                    '@type' => 'Organization',
                    'name'  => (string) $app->get('sitename', 'Ponto Mega'),
                    'url'   => Uri::root(),
                ],
            ];

            if ($rel) {
                $schema['softwareVersion'] = (string) $rel->version;
                $schema['datePublished']   = (string) $rel->release_date;
                $schema['downloadUrl']     = $baseOrigin . Route::_(RouteHelper::getDownloadRoute((int) $rel->id), false);
                if ((int) $rel->file_size > 0) {
                    $schema['fileSize'] = PmdownloadsHelper::formatFileSize((int) $rel->file_size);
                }
            }

            $doc->addScriptDeclaration(
                json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG),
                'application/ld+json'
            );
        }
    }
}
