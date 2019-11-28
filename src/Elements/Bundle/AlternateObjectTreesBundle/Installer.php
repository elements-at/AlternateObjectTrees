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

use Elements\Bundle\AlternateObjectTreesBundle\Model\Config\Dao;
use Pimcore\Db;
use Pimcore\Extension\Bundle\Installer\AbstractInstaller;

class Installer extends AbstractInstaller
{
    public function isInstalled()
    {
        try {
            $result = Db::get()->fetchAll("SHOW TABLES LIKE '" . Dao::TABLE_NAME . "'");

            return !empty($result) ? true : false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function canBeInstalled()
    {
        return !$this->isInstalled();
    }

    public function install()
    {
        $db = Db::get();

        $db->query('
            CREATE TABLE `' . Dao::TABLE_NAME . "` (
                `id` INT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `active` TINYINT(1) UNSIGNED NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `label` VARCHAR(255) NOT NULL,
                `icon` VARCHAR(255) NULL DEFAULT NULL,
                `customTreeBuilderClass` VARCHAR(255) NULL DEFAULT NULL,
                `o_class` VARCHAR(255) NOT NULL,
                `description` VARCHAR(255) NULL DEFAULT NULL,
                `basepath` VARCHAR(255) NULL DEFAULT NULL,
                `jsonLevelDefinitions` TEXT NULL,
                PRIMARY KEY (`id`),
                UNIQUE INDEX `name` (`name`),
                INDEX `active` (`active`)
            )
            COLLATE='utf8_general_ci'
            ENGINE=InnoDB
            AUTO_INCREMENT=0;
        ")->closeCursor();
    }

    public function canBeUninstalled()
    {
        return $this->isInstalled();
    }

    public function uninstall()
    {
        $db = Db::get();
        $db->query('DROP TABLE IF EXISTS `' . Dao::TABLE_NAME . '`')->closeCursor();
    }
}
