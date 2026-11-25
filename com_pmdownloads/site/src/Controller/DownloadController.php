<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_pmdownloads
 *
 * @copyright   (C) 2026 Ponto Mega
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace PontoMega\Component\Pmdownloads\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use PontoMega\Component\Pmdownloads\Administrator\Helper\PmdownloadsHelper;

class DownloadController extends BaseController
{
    public function file(): void
    {
        $app       = Factory::getApplication();
        $releaseId = $app->getInput()->getInt('id', 0);

        if ($releaseId <= 0) {
            throw new \RuntimeException(Text::_('COM_PMDOWNLOADS_ERROR_FILE_NOT_FOUND'), 404);
        }

        $db     = Factory::getContainer()->get(DatabaseInterface::class);
        $user   = $app->getIdentity();
        $groups = $user ? $user->getAuthorisedViewLevels() : [1];

        $q = $db->getQuery(true)
            ->select([
                'r.*',
                $db->quoteName('i.access', 'item_access'),
                $db->quoteName('i.state', 'item_state'),
            ])
            ->from($db->quoteName('#__pmdownloads_releases', 'r'))
            ->join('INNER', $db->quoteName('#__pmdownloads_items', 'i'), $db->quoteName('i.id') . ' = ' . $db->quoteName('r.item_id'))
            ->where($db->quoteName('r.id') . ' = :rid')
            ->where($db->quoteName('r.state') . ' = 1')
            ->where($db->quoteName('i.state') . ' = 1')
            ->whereIn($db->quoteName('i.access'), $groups)
            ->bind(':rid', $releaseId, ParameterType::INTEGER);

        $release = $db->setQuery($q)->loadObject();

        if (!$release) {
            throw new \RuntimeException(Text::_('COM_PMDOWNLOADS_ERROR_FILE_NOT_FOUND'), 404);
        }

        // Anti-flood download counter per session (1 count per release per 60s)
        $session    = $app->getSession();
        $sessionKey = 'com_pmdownloads.dl.' . $releaseId;
        $lastTs     = (int) $session->get($sessionKey, 0);
        if (time() - $lastTs >= 60) {
            $session->set($sessionKey, time());

            $uRel = $db->getQuery(true)
                ->update($db->quoteName('#__pmdownloads_releases'))
                ->set($db->quoteName('downloads_count') . ' = ' . $db->quoteName('downloads_count') . ' + 1')
                ->where($db->quoteName('id') . ' = :rid')
                ->bind(':rid', $releaseId, ParameterType::INTEGER);
            $db->setQuery($uRel)->execute();

            $itemId = (int) $release->item_id;
            $uItem  = $db->getQuery(true)
                ->update($db->quoteName('#__pmdownloads_items'))
                ->set($db->quoteName('downloads_count') . ' = ' . $db->quoteName('downloads_count') . ' + 1')
                ->where($db->quoteName('id') . ' = :iid')
                ->bind(':iid', $itemId, ParameterType::INTEGER);
            $db->setQuery($uItem)->execute();
        }

        $sourceType = (string) $release->source_type;
        $filePath   = trim((string) $release->file_path);

        // External / GitHub release redirect
        if ($sourceType !== 'local' || preg_match('#^https?://#i', $filePath)) {
            if (!preg_match('#^https?://#i', $filePath)) {
                throw new \RuntimeException(Text::_('COM_PMDOWNLOADS_ERROR_FILE_NOT_FOUND'), 404);
            }

            $app->redirect($filePath, 302);

            return;
        }

        // Protected local file streaming
        $realPath = PmdownloadsHelper::resolveSafeLocalPath($filePath);
        if ($realPath === null) {
            throw new \RuntimeException(Text::_('COM_PMDOWNLOADS_ERROR_FILE_NOT_FOUND'), 404);
        }

        $downloadName = trim((string) $release->file_name) ?: basename($realPath);
        $downloadName = preg_replace('/[^A-Za-z0-9._-]/', '_', $downloadName) ?: 'download.zip';
        $size         = (int) filesize($realPath);
        $ext          = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
        $mimeMap      = [
            'zip'  => 'application/zip',
            'gz'   => 'application/gzip',
            'tgz'  => 'application/gzip',
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'webp' => 'image/webp',
            'xml'  => 'application/xml',
        ];
        $mime = $mimeMap[$ext] ?? 'application/octet-stream';

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Length: ' . $size);
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=0, must-revalidate');

        $fh = fopen($realPath, 'rb');
        if ($fh !== false) {
            while (!feof($fh)) {
                echo fread($fh, 65536);
                flush();
            }
            fclose($fh);
        }

        $app->close();
    }
}
