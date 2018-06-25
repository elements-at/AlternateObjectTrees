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

class ElementsAlternateObjectTreesBundle extends AbstractPimcoreBundle
{
    const PLUGIN_NAME = 'AlternateObjectTrees';

    public function getNiceName()
    {
        return self::PLUGIN_NAME;
    }

    public function getDescription()
    {
        return 'Alternative object trees.';
    }

    public function getVersion()
    {
        return '1.0';
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
            '/bundles/elementsalternateobjecttrees/js/config/item.js',
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
}
