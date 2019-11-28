<?php

/**
 * Elements.at
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) elements.at New Media Solutions GmbH (https://www.elements.at)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Elements\Bundle\AlternateObjectTreesBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Installer\InstallerInterface;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

class ElementsAlternateObjectTreesBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait {
        getVersion as protected getComposerVersion;
    }

    const PLUGIN_NAME = 'AlternateObjectTrees';

    public function getNiceName()
    {
        return self::PLUGIN_NAME;
    }

    public function getDescription()
    {
        return 'Alternative object trees.';
    }

    /**
     * @return array
     */
    public function getCssPaths()
    {
        return [
            '/bundles/elementsalternateobjecttrees/css/icons.css'
        ];
    }

    /**
     * @return array
     */
    public function getJsPaths()
    {
        return [
            '/bundles/elementsalternateobjecttrees/js/plugin.js',
            '/bundles/elementsalternateobjecttrees/js/tree.js',
            '/bundles/elementsalternateobjecttrees/js/folder.js',
            '/bundles/elementsalternateobjecttrees/js/search.js',
            '/bundles/elementsalternateobjecttrees/js/gridConfigDialog.js',
            '/bundles/elementsalternateobjecttrees/js/config/item.js',
            '/bundles/elementsalternateobjecttrees/js/config/items.js',
            '/bundles/elementsalternateobjecttrees/js/config/panel.js'
        ];
    }

    /**
     * If the bundle has an installation routine, an installer is responsible of handling installation related tasks
     *
     * @return InstallerInterface|null
     */
    public function getInstaller()
    {
        return new Installer();
    }

    public function getVersion()
    {
        try {
            return $this->getComposerVersion();
        } catch (\Exception $e) {
            return 'unknown';
        }
    }

    /**
     * Returns the composer package name used to resolve the version
     *
     * @return string
     */
    protected function getComposerPackageName()
    {
        return 'elements/alternate-object-trees';
    }
}
