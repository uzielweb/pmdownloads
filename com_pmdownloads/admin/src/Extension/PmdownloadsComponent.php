<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_pmdownloads
 *
 * @copyright   (C) 2026 Ponto Mega
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace PontoMega\Component\Pmdownloads\Administrator\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Association\AssociationServiceInterface;
use Joomla\CMS\Association\AssociationServiceTrait;
use Joomla\CMS\Categories\CategoryServiceInterface;
use Joomla\CMS\Categories\CategoryServiceTrait;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Joomla\CMS\Tag\TagServiceInterface;
use Joomla\CMS\Tag\TagServiceTrait;
use Psr\Container\ContainerInterface;

class PmdownloadsComponent extends MVCComponent implements
    BootableExtensionInterface,
    CategoryServiceInterface,
    AssociationServiceInterface,
    RouterServiceInterface,
    TagServiceInterface
{
    use AssociationServiceTrait;
    use CategoryServiceTrait;
    use HTMLRegistryAwareTrait;
    use RouterServiceTrait;
    use TagServiceTrait;

    public function boot(ContainerInterface $container): void
    {
    }

    protected function getTableNameForSection(?string $section = null): string
    {
        return 'pmdownloads_items';
    }

    protected function getStateColumnForSection(?string $section = null): string
    {
        return 'state';
    }
}
