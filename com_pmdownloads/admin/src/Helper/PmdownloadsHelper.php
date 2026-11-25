<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_pmdownloads
 *
 * @copyright   (C) 2026 Ponto Mega
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace PontoMega\Component\Pmdownloads\Administrator\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\Path;

final class PmdownloadsHelper
{
    public static function getVersionOptions(): array
    {
        return [
            'j6'    => Text::_('COM_PMDOWNLOADS_VERSION_J6'),
            'j5'    => Text::_('COM_PMDOWNLOADS_VERSION_J5'),
            'j4'    => Text::_('COM_PMDOWNLOADS_VERSION_J4'),
            'j3'    => Text::_('COM_PMDOWNLOADS_VERSION_J3'),
            'j25'   => Text::_('COM_PMDOWNLOADS_VERSION_J25'),
            'other' => Text::_('COM_PMDOWNLOADS_VERSION_OTHER'),
        ];
    }

    public static function getVersionShortLabel(string $code): string
    {
        $map = [
            'j6'    => Text::_('COM_PMDOWNLOADS_VERSION_SHORT_J6'),
            'j5'    => Text::_('COM_PMDOWNLOADS_VERSION_SHORT_J5'),
            'j4'    => Text::_('COM_PMDOWNLOADS_VERSION_SHORT_J4'),
            'j3'    => Text::_('COM_PMDOWNLOADS_VERSION_SHORT_J3'),
            'j25'   => Text::_('COM_PMDOWNLOADS_VERSION_SHORT_J25'),
            'other' => Text::_('COM_PMDOWNLOADS_VERSION_SHORT_OTHER'),
        ];

        return $map[$code] ?? strtoupper($code);
    }

    public static function getItemTypeOptions(): array
    {
        return [
            'template'      => Text::_('COM_PMDOWNLOADS_TYPE_TEMPLATE'),
            'language_pack' => Text::_('COM_PMDOWNLOADS_TYPE_LANGUAGE_PACK'),
            'package'       => Text::_('COM_PMDOWNLOADS_TYPE_PACKAGE'),
            'component'     => Text::_('COM_PMDOWNLOADS_TYPE_COMPONENT'),
            'module'        => Text::_('COM_PMDOWNLOADS_TYPE_MODULE'),
            'plugin'        => Text::_('COM_PMDOWNLOADS_TYPE_PLUGIN'),
            'tool'          => Text::_('COM_PMDOWNLOADS_TYPE_TOOL'),
            'asset'         => Text::_('COM_PMDOWNLOADS_TYPE_ASSET'),
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            'active'      => Text::_('COM_PMDOWNLOADS_STATUS_ACTIVE'),
            'maintenance' => Text::_('COM_PMDOWNLOADS_STATUS_MAINTENANCE'),
            'legacy'      => Text::_('COM_PMDOWNLOADS_STATUS_LEGACY'),
        ];
    }

    public static function getMaturityOptions(): array
    {
        return [
            'stable' => Text::_('COM_PMDOWNLOADS_MATURITY_STABLE'),
            'rc'     => Text::_('COM_PMDOWNLOADS_MATURITY_RC'),
            'beta'   => Text::_('COM_PMDOWNLOADS_MATURITY_BETA'),
            'alpha'  => Text::_('COM_PMDOWNLOADS_MATURITY_ALPHA'),
            'legacy' => Text::_('COM_PMDOWNLOADS_MATURITY_LEGACY'),
        ];
    }

    public static function formatFileSize(int $bytes): string
    {
        if ($bytes <= 0) {
            return '';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size  = (float) $bytes;
        $idx   = 0;

        while ($size >= 1024 && $idx < \count($units) - 1) {
            $size /= 1024;
            $idx++;
        }

        return ($idx === 0 ? (string) $bytes : number_format($size, $size >= 10 ? 1 : 2, '.', '')) . ' ' . $units[$idx];
    }

    /**
     * Resolves a local file path safely against allowed download directories.
     * Prevents directory traversal outside storage_path or images/downloads.
     */
    public static function resolveSafeLocalPath(string $relativePath): ?string
    {
        $relativePath = ltrim(str_replace('\\', '/', trim($relativePath)), '/');

        if ($relativePath === '' || str_contains($relativePath, '..') || str_contains($relativePath, "\0")) {
            return null;
        }

        $params      = ComponentHelper::getParams('com_pmdownloads');
        $storageRel  = trim(str_replace('\\', '/', (string) $params->get('storage_path', 'files/pmdownloads')), '/');
        $allowedDirs = [
            realpath(JPATH_ROOT . '/' . $storageRel),
            realpath(JPATH_ROOT . '/images/downloads'),
        ];

        $candidates = [
            JPATH_ROOT . '/' . $storageRel . '/' . $relativePath,
            JPATH_ROOT . '/' . $relativePath,
        ];

        foreach ($candidates as $cand) {
            $real = realpath(Path::clean($cand));
            if ($real === false || !is_file($real) || !is_readable($real)) {
                continue;
            }

            foreach ($allowedDirs as $base) {
                if ($base !== false && str_starts_with($real, $base . DIRECTORY_SEPARATOR)) {
                    return $real;
                }
            }
        }

        return null;
    }

    /**
     * Extracts GitHub owner/repo from a repository URL.
     */
    public static function parseGithubRepo(string $repoUrl): ?array
    {
        if (preg_match('#^https?://(?:www\.)?github\.com/([A-Za-z0-9_.-]+)/([A-Za-z0-9_.-]+?)(?:\.git|/.*)?$#', trim($repoUrl), $m)) {
            return ['owner' => $m[1], 'repo' => $m[2]];
        }

        return null;
    }

    /**
     * Resolves the canonical GitHub Update Server XML URL for an item.
     */
    public static function resolveUpdateServerUrl(object $item): string
    {
        $explicit = trim((string) ($item->updateserver_url ?? ''));
        if ($explicit !== '' && preg_match('#^https?://#i', $explicit)) {
            return $explicit;
        }

        $gh = self::parseGithubRepo((string) ($item->repo_url ?? ''));
        if ($gh !== null) {
            return 'https://raw.githubusercontent.com/' . $gh['owner'] . '/' . $gh['repo'] . '/main/updates.xml';
        }

        return '';
    }

    /**
     * Resolves the canonical GitHub Changelog XML URL for an item.
     */
    public static function resolveChangelogUrl(object $item): string
    {
        $explicit = trim((string) ($item->changelog_url ?? ''));
        if ($explicit !== '' && preg_match('#^https?://#i', $explicit)) {
            return $explicit;
        }

        // Do not guess changelog.xml when an explicit custom updateserver_url is configured without a changelog_url
        if (trim((string) ($item->updateserver_url ?? '')) !== '') {
            return '';
        }

        $gh = self::parseGithubRepo((string) ($item->repo_url ?? ''));
        if ($gh !== null) {
            return 'https://raw.githubusercontent.com/' . $gh['owner'] . '/' . $gh['repo'] . '/main/changelog.xml';
        }

        return '';
    }

    /**
     * Normalizes changelog text lines into clean list entries.
     *
     * @return  array<int, string>
     */
    public static function parseChangelogLines(string $changelog): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($changelog)) ?: [];
        $out   = [];

        foreach ($lines as $line) {
            $clean = trim(preg_replace('/^[-*•]\s*/u', '', trim($line)));
            if ($clean !== '') {
                $out[] = $clean;
            }
        }

        return $out;
    }
}
