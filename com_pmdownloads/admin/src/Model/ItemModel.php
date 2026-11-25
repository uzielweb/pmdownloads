<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_pmdownloads
 *
 * @copyright   (C) 2026 Ponto Mega
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace PontoMega\Component\Pmdownloads\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\ParameterType;
use PontoMega\Component\Pmdownloads\Administrator\Helper\PmdownloadsHelper;

class ItemModel extends AdminModel
{
    public $typeAlias = 'com_pmdownloads.item';

    protected $associationsContext = 'com_pmdownloads.item';

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_pmdownloads.item', 'item', ['control' => 'jform', 'load_data' => $loadData]);

        return $form ?: false;
    }

    protected function loadFormData()
    {
        $app  = Factory::getApplication();
        $data = $app->getUserState('com_pmdownloads.edit.item.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        $this->preprocessData('com_pmdownloads.item', $data);

        return $data;
    }

    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        if ($item && !empty($item->id)) {
            $db     = $this->getDatabase();
            $itemId = (int) $item->id;

            // Load versions
            $qVer = $db->getQuery(true)
                ->select($db->quoteName('version_code'))
                ->from($db->quoteName('#__pmdownloads_item_versions'))
                ->where($db->quoteName('item_id') . ' = :iid')
                ->bind(':iid', $itemId, ParameterType::INTEGER);
            $item->versions = $db->setQuery($qVer)->loadColumn() ?: [];

            // Load releases into subform structure
            $qRel = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__pmdownloads_releases'))
                ->where($db->quoteName('item_id') . ' = :iid')
                ->order($db->quoteName('is_current') . ' DESC, ' . $db->quoteName('ordering') . ' ASC, ' . $db->quoteName('release_date') . ' DESC')
                ->bind(':iid', $itemId, ParameterType::INTEGER);
            $rels   = $db->setQuery($qRel)->loadAssocList() ?: [];
            $mapped = [];
            foreach ($rels as $idx => $r) {
                $mapped['releases' . $idx] = $r;
            }
            $item->releases = $mapped;

            $item->tags = new TagsHelper();
            $item->tags->getTagIds($itemId, 'com_pmdownloads.item');

            if (Associations::isEnabled()) {
                $associations       = Associations::getAssociations('com_pmdownloads', '#__pmdownloads_items', 'com_pmdownloads.item', $itemId, 'id', 'alias', 'catid');
                $item->associations = [];
                foreach ($associations as $tag => $association) {
                    $item->associations[$tag] = $association->id;
                }
            }
        }

        return $item;
    }

    protected function preprocessForm(Form $form, $data, $group = 'content'): void
    {
        if (Associations::isEnabled()) {
            $languages = LanguageHelper::getContentLanguages(false, false, null, 'ordering', 'asc');
            if (\count($languages) > 1) {
                $addform  = new \SimpleXMLElement('<form />');
                $fields   = $addform->addChild('fields');
                $fields->addAttribute('name', 'associations');
                $fieldset = $fields->addChild('fieldset');
                $fieldset->addAttribute('name', 'item_associations');

                foreach ($languages as $language) {
                    $field = $fieldset->addChild('field');
                    $field->addAttribute('name', $language->lang_code);
                    $field->addAttribute('type', 'sql');
                    $field->addAttribute('label', $language->title);
                    $field->addAttribute('layout', 'joomla.form.field.list-fancy-select');
                    $field->addAttribute('query', 'SELECT id AS value, title AS text FROM #__pmdownloads_items WHERE language = ' . $this->getDatabase()->quote($language->lang_code) . ' AND state >= 0 ORDER BY title ASC');
                    $field->addAttribute('key_field', 'value');
                    $field->addAttribute('value_field', 'text');
                    $opt = $field->addChild('option', 'JGLOBAL_ASSOCIATIONS_NONE');
                    $opt->addAttribute('value', '');
                }

                $form->load($addform, false);
            }
        }

        parent::preprocessForm($form, $data, $group);
    }

    public function save($data): bool
    {
        PluginHelper::importPlugin('finder');
        PluginHelper::importPlugin('content');

        $versions = \is_array($data['versions'] ?? null) ? $data['versions'] : [];
        $releases = \is_array($data['releases'] ?? null) ? array_values($data['releases']) : [];

        // Build searchable text from release titles, versions, filenames, and changelogs
        $searchParts = [];
        foreach ($releases as $rel) {
            if (!\is_array($rel)) {
                continue;
            }
            foreach (['title', 'version', 'file_name', 'changelog'] as $k) {
                if (!empty($rel[$k])) {
                    $searchParts[] = trim((string) $rel[$k]);
                }
            }
        }
        $data['releases_search_text'] = implode("\n", $searchParts);

        $saved = parent::save($data);

        if ($saved) {
            $id = (int) $this->getState($this->getName() . '.id');
            $this->syncVersions($id, $versions);
            $this->syncReleases($id, $releases);
        }

        return $saved;
    }

    public function featured(array $pks, int $value = 0): bool
    {
        $pks = array_filter(array_map('intval', $pks));
        if (empty($pks)) {
            return false;
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__pmdownloads_items'))
            ->set($db->quoteName('featured') . ' = :featured')
            ->whereIn($db->quoteName('id'), $pks)
            ->bind(':featured', $value, ParameterType::INTEGER);
        $db->setQuery($query)->execute();

        $this->cleanCache();

        return true;
    }

    /**
     * Fetches the latest GitHub Release for an item's repo_url and upserts it as the current release.
     */
    public function syncLatestGithubRelease(int $itemId): string
    {
        $db = $this->getDatabase();
        $q  = $db->getQuery(true)
            ->select(['id', 'title', 'repo_url', 'min_php'])
            ->from($db->quoteName('#__pmdownloads_items'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $itemId, ParameterType::INTEGER);
        $item = $db->setQuery($q)->loadObject();

        if (!$item || empty($item->repo_url)) {
            throw new \RuntimeException(Text::_('COM_PMDOWNLOADS_SYNC_NO_REPO'));
        }

        $repo = PmdownloadsHelper::parseGithubRepo((string) $item->repo_url);
        if (!$repo) {
            throw new \RuntimeException(Text::_('COM_PMDOWNLOADS_SYNC_INVALID_REPO'));
        }

        $apiUrl   = sprintf('https://api.github.com/repos/%s/%s/releases/latest', $repo['owner'], $repo['repo']);
        $http     = HttpFactory::getHttp();
        $response = $http->get($apiUrl, [
            'Accept'     => 'application/vnd.github+json',
            'User-Agent' => 'PontoMega-Downloads-Joomla6/1.0',
        ], 15);

        if ((int) $response->code !== 200) {
            throw new \RuntimeException(Text::sprintf('COM_PMDOWNLOADS_SYNC_GITHUB_HTTP_ERR', (int) $response->code));
        }

        $payload = json_decode((string) $response->body, true);
        if (!\is_array($payload) || empty($payload['tag_name'])) {
            throw new \RuntimeException(Text::_('COM_PMDOWNLOADS_SYNC_GITHUB_EMPTY'));
        }

        $tag     = (string) $payload['tag_name'];
        $version = preg_replace('/^.*?v?(\d+\.\d+(?:\.\d+){0,2}).*$/i', '$1', $tag) ?: ltrim($tag, 'v');
        $relDate = !empty($payload['published_at'])
            ? gmdate('Y-m-d H:i:s', strtotime((string) $payload['published_at']))
            : Factory::getDate()->toSql();

        $fileUrl  = sprintf('https://github.com/%s/%s/archive/refs/tags/%s.zip', $repo['owner'], $repo['repo'], rawurlencode($tag));
        $fileName = $repo['repo'] . '-' . $version . '.zip';
        $fileSize = 0;

        foreach (($payload['assets'] ?? []) as $asset) {
            if (!empty($asset['browser_download_url']) && str_ends_with(strtolower((string) $asset['name']), '.zip')) {
                $fileUrl  = (string) $asset['browser_download_url'];
                $fileName = (string) $asset['name'];
                $fileSize = (int) ($asset['size'] ?? 0);
                break;
            }
        }

        // Unset current flag on existing releases
        $u = $db->getQuery(true)
            ->update($db->quoteName('#__pmdownloads_releases'))
            ->set($db->quoteName('is_current') . ' = 0')
            ->where($db->quoteName('item_id') . ' = :iid')
            ->bind(':iid', $itemId, ParameterType::INTEGER);
        $db->setQuery($u)->execute();

        // Check if this version already exists
        $chk = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__pmdownloads_releases'))
            ->where($db->quoteName('item_id') . ' = :iid')
            ->where($db->quoteName('version') . ' = :ver')
            ->bind(':iid', $itemId, ParameterType::INTEGER)
            ->bind(':ver', $version, ParameterType::STRING);
        $existingRelId = (int) $db->setQuery($chk)->loadResult();

        $relObj = (object) [
            'item_id'       => $itemId,
            'title'         => trim((string) ($payload['name'] ?: $item->title . ' v' . $version)),
            'version'       => $version,
            'maturity'      => !empty($payload['prerelease']) ? 'beta' : 'stable',
            'release_date'  => $relDate,
            'changelog'     => trim((string) ($payload['body'] ?? '')),
            'source_type'   => 'github_release',
            'file_path'     => $fileUrl,
            'file_name'     => $fileName,
            'file_size'     => $fileSize,
            'sha256'        => '',
            'joomla_target' => '^[56]\\.',
            'php_minimum'   => (string) ($item->min_php ?: '8.1'),
            'is_current'    => 1,
            'state'         => 1,
            'ordering'      => 1,
        ];

        if ($existingRelId > 0) {
            $relObj->id = $existingRelId;
            $db->updateObject('#__pmdownloads_releases', $relObj, 'id');
        } else {
            $relObj->downloads_count = 0;
            $db->insertObject('#__pmdownloads_releases', $relObj, 'id');
        }

        $this->cleanCache();

        return $version;
    }

    private function syncVersions(int $itemId, array $versions): void
    {
        if ($itemId <= 0) {
            return;
        }

        $db      = $this->getDatabase();
        $allowed = array_keys(PmdownloadsHelper::getVersionOptions());
        $clean   = array_values(array_unique(array_intersect(array_map('strtolower', $versions), $allowed)));

        $del = $db->getQuery(true)
            ->delete($db->quoteName('#__pmdownloads_item_versions'))
            ->where($db->quoteName('item_id') . ' = :iid')
            ->bind(':iid', $itemId, ParameterType::INTEGER);
        $db->setQuery($del)->execute();

        foreach ($clean as $ver) {
            $ins = $db->getQuery(true)
                ->insert($db->quoteName('#__pmdownloads_item_versions'))
                ->columns([$db->quoteName('item_id'), $db->quoteName('version_code')])
                ->values(':iid, :ver')
                ->bind(':iid', $itemId, ParameterType::INTEGER)
                ->bind(':ver', $ver, ParameterType::STRING);
            $db->setQuery($ins)->execute();
        }
    }

    private function syncReleases(int $itemId, array $releases): void
    {
        if ($itemId <= 0) {
            return;
        }

        $db       = $this->getDatabase();
        $keptIds  = [];
        $now      = Factory::getDate()->toSql();
        $ordering = 1;

        foreach ($releases as $row) {
            if (!\is_array($row) || trim((string) ($row['file_path'] ?? '')) === '') {
                continue;
            }

            $relId      = (int) ($row['id'] ?? 0);
            $sourceType = (string) ($row['source_type'] ?? 'local');
            $filePath   = trim((string) $row['file_path']);
            $fileName   = trim((string) ($row['file_name'] ?? ''));
            $fileSize   = (int) ($row['file_size'] ?? 0);
            $sha256     = strtolower(trim((string) ($row['sha256'] ?? '')));

            if ($sourceType === 'local') {
                $real = PmdownloadsHelper::resolveSafeLocalPath($filePath);
                if ($real !== null) {
                    $fileSize = (int) filesize($real);
                    if ($sha256 === '') {
                        $sha256 = (string) hash_file('sha256', $real);
                    }
                    if ($fileName === '') {
                        $fileName = basename($real);
                    }
                }
            } elseif ($fileName === '') {
                $fileName = basename((string) parse_url($filePath, PHP_URL_PATH)) ?: 'package.zip';
            }

            $obj = (object) [
                'item_id'       => $itemId,
                'title'         => trim((string) ($row['title'] ?? $fileName)),
                'version'       => trim((string) ($row['version'] ?? '1.0.0')),
                'maturity'      => (string) ($row['maturity'] ?? 'stable'),
                'release_date'  => !empty($row['release_date']) ? (string) $row['release_date'] : $now,
                'changelog'     => trim((string) ($row['changelog'] ?? '')),
                'source_type'   => $sourceType,
                'file_path'     => $filePath,
                'file_name'     => $fileName,
                'file_size'     => $fileSize,
                'sha256'        => $sha256,
                'joomla_target' => trim((string) ($row['joomla_target'] ?? '^[56]\\.')) ?: '^[56]\\.',
                'php_minimum'   => trim((string) ($row['php_minimum'] ?? '8.1')) ?: '8.1',
                'is_current'    => (int) ($row['is_current'] ?? 0),
                'state'         => 1,
                'ordering'      => $ordering++,
            ];

            if ($relId > 0) {
                $obj->id = $relId;
                $db->updateObject('#__pmdownloads_releases', $obj, 'id');
                $keptIds[] = $relId;
            } else {
                $obj->downloads_count = 0;
                $db->insertObject('#__pmdownloads_releases', $obj, 'id');
                $keptIds[] = (int) $obj->id;
            }
        }

        // Remove deleted subform releases
        $del = $db->getQuery(true)
            ->delete($db->quoteName('#__pmdownloads_releases'))
            ->where($db->quoteName('item_id') . ' = :iid')
            ->bind(':iid', $itemId, ParameterType::INTEGER);
        if (!empty($keptIds)) {
            $del->whereNotIn($db->quoteName('id'), $keptIds);
        }
        $db->setQuery($del)->execute();
    }
}
