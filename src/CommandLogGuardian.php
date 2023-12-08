<?php

/*
 *       _      _           _                ___   ___ ____
 *      (_)    | |         | |              / _ \ / _ \___ \
 * _ __  _  ___| |__   ___ | | __ _ ___ ___| | | | | | |__) |
 *| '_ \| |/ __| '_ \ / _ \| |/ _` / __/ __| | | | | | |__ <
 *| | | | | (__| | | | (_) | | (_| \__ \__ \ |_| | |_| |__) |
 *|_| |_|_|\___|_| |_|\___/|_|\__,_|___/___/\___/ \___/____/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author nicholass003
 * @link https://github.com/nicholass003/
 *
 */

declare(strict_types=1);

namespace nicholass003\commandlogguardian;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\event\server\CommandEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as T;

class CommandLogGuardian extends PluginBase implements Listener{

    /** @var string[] */
    private array $protectedCommands = [];

    /** @var string[] */
    private array $protectedPlayers = [];

    protected function onEnable() : void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $this->protectedCommands = $this->getProtectedCommands();
        $this->protectedPlayers = $this->getProtectedPlayers();
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if($command->getName() === "commandlogguardian"){
            if(!$sender->hasPermission("commandlogguardian.command")){
                $sender->sendMessage(T::RED . "You do not have permission to run this command.");
                return true;
            }
            switch(strtolower($args[0])){
                case "active":
                case "enable":
                case "on":
                    $this->handleActivation($sender, true, "enabled");
                    break;
                case "deactive":
                case "disable":
                case "off":
                    $this->handleActivation($sender, false, "disabled");
                    break;
            }
        }
        return false;
    }

    private function handleActivation(CommandSender $sender, bool $value, string $action) : void{
        $currentState = $this->isActive();
        if($currentState === $value){
            $sender->sendMessage(T::RED . "CommandLogGuardian was previously $action.");
        }else{
            $this->setActive($value);
            $sender->sendMessage(T::GREEN . "CommandLogGuardian has been $action.");
        }
    }

    private function setActive(bool $value) : void{
        $this->getConfig()->set("commandlogguardian", $value);
    }

    private function isActive() : bool{
        return $this->getConfig()->get("commandlogguardian", true);
    }

    private function isTrackConsole() : bool{
        return $this->getConfig()->get("console");
    }

    private function getProtectedCommands() : array{
        return $this->getConfig()->get("protected-commands", [
            "ban",
            "kick"
        ]);
    }

    private function getProtectedPlayers() : array{
        return $this->getConfig()->get("protected-players", [
            "Alex",
            "Steve"
        ]);
    }

    public function onCommandExecuted(CommandEvent $event) : void{
        $executer = $event->getSender();
        $command = $event->getCommand();
        $executerName = $executer instanceof Player ? $executer->getName() : "Console";

        if(in_array($command, $this->protectedCommands) || !$this->isActive()) return;

        if(($executer instanceof Player && in_array($executerName, $this->protectedPlayers)) || ($executerName === "Console" && !$this->isTrackConsole())) return;

        $this->getLogger()->info("($executerName) executed command: /$command");
    }
}
