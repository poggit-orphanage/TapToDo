<?php

declare(strict_types = 1);

namespace taptodo\utils;

use pocketmine\utils\Config;
use taptodo\TapToDo;

/**
 * Class ConfigUpdater
 * @package taptodo\utils
 */
class ConfigUpdater {

    public const CONFIG_VERSION = 2;
    public const JSON_OPTIONS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    public const OLD_FILE = "blocks.yml";
    public const NEW_FILE = "blocks.json";

    /** @var TapToDo */
    private $tapToDo;
    /** @var string */
    private $dir;
    /** @ver Config */
    private $config;
    /** @var int */
    private $version;

    public function __construct(TapToDo $tapToDo) {
        $this->tapToDo = $tapToDo;
        $this->dir = $tapToDo->getDataFolder();
        $this->checkDirectory();
    }

    private function checkDirectory() {
        if (file_exists($this->dir . self::OLD_FILE)) {
            $this->config = new Config($this->dir . self::OLD_FILE, Config::YAML);
        }else {
            $this->tapToDo->saveResource(self::NEW_FILE);
            $this->config = new Config($this->dir . self::NEW_FILE, Config::JSON);
            $this->config->enableJsonOption(self::JSON_OPTIONS);
        }
        $this->version = $this->config->get("version", -1);
    }

    public function update(): ?Config {
        if ($this->version > self::CONFIG_VERSION) {
            $this->tapToDo->getLogger()->error("The config loaded is not supported. It may not function correctly.");
            $this->config = null;
        }else {// $this->version <= self::CONFIG_VERSION
            $dir = $this->tapToDo->getDataFolder();
            while ($this->version < self::CONFIG_VERSION) {
                switch ($this->version) {
                    case 0:// v1.x
                        $this->tapToDo->getLogger()->notice("Updating config from version 0 to 1...");
                        $blocks = $this->config->getAll();
                        if (!empty($blocks)) {
                            foreach ($blocks as $id => $block) {
                                if (isset($block["commands"])) {
                                    foreach ($block["commands"] as $i => $command) {
                                        if (stripos($command, "%safe") === false && stripos($command, "%op") === false) {
                                            $command .= "%pow";
                                        }
                                        $blocks["commands"][$i] = str_replace("%safe", "", $command);
                                    }
                                    $blocks[$id] = $block;
                                }
                            }
                        }
                        unlink($dir . self::OLD_FILE);
                        $this->tapToDo->saveResource(self::OLD_FILE);
                        $this->config = new Config($this->tapToDo->getDataFolder() . self::OLD_FILE, Config::YAML);
                        $this->config->set("blocks", $blocks);
                        $this->config->save();
                        $this->version = 1;
                        break;

                    case 1:// <= v2.3
                        $this->tapToDo->getLogger()->notice("Updating config from version 1 to 2...");
                        $data = $this->config->get("blocks", []);
                        unlink($dir . self::OLD_FILE);
                        $this->tapToDo->saveResource(self::NEW_FILE);
                        $this->config = new Config($dir . self::NEW_FILE, Config::JSON);
                        $this->config->enableJsonOption(self::JSON_OPTIONS);
                        $this->config->set("blocks", $data);
                        $this->config->save();
                        $this->version = 2;
                        break;
                }
            }
        }
        return $this->config;
    }

    public function getVersion(): int {
        return $this->version;
    }
}