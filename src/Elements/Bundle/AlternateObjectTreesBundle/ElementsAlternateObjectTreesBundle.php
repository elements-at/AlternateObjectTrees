<?php

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
        return "Alternative object trees.";
    }

    public function getVersion()
    {
        return "1.0";
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
