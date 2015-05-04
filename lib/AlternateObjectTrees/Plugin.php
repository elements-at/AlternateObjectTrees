<?php

class AlternateObjectTrees_Plugin extends Pimcore_API_Plugin_Abstract implements Pimcore_API_Plugin_Interface {

    const PLUGIN_NAME = "AlternateObjectTrees";

    public static function needsReloadAfterInstall() {
        return true;
    }

    public static function install() {

        $db = Pimcore_Resource::get();

        $db->query("CREATE TABLE `" . AlternateObjectTrees_Config_Resource::TABLE_NAME ."` (
                    `id` INT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `active` TINYINT(1) UNSIGNED NOT NULL,
                    `name` VARCHAR(255) NOT NULL,
                    `label` VARCHAR(255) NOT NULL,
                    `icon` VARCHAR(255) NULL DEFAULT NULL,
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
                AUTO_INCREMENT=0;");

        if (self::isInstalled()) {
            return self::PLUGIN_NAME . " Plugin successfully installed.";
        } else {
            return self::PLUGIN_NAME . " Plugin could not be installed";
        }
    }

    public static function uninstall() {
        $db = Pimcore_Resource::get();

        $db->query("DROP TABLE " . AlternateObjectTrees_Config_Resource::TABLE_NAME);


        if (!self::isInstalled()) {
            return self::PLUGIN_NAME . " Plugin successfully uninstalled.";
        } else {
            return self::PLUGIN_NAME . " Plugin could not be uninstalled";
        }
    }

    public static function isInstalled() {
        try{
            $result = Pimcore_API_Plugin_Abstract::getDb()->describeTable(AlternateObjectTrees_Config_Resource::TABLE_NAME);
        } catch(Exception $e){}
        return !empty($result);
    }

    public static function getTranslationFile($language){
        return "/" . self::PLUGIN_NAME . "/texts/$language.csv";
    }

    public static function getInstallPath() {
        return PIMCORE_PLUGINS_PATH."/" . self::PLUGIN_NAME . "/install";
    }

}
