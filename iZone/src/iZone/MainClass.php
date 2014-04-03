<?php

namespace iZone;

use pocketMine\command\Command;
use pocketMine\command\CommandSender;
use pocketMine\plugin\PluginBase;
use pocketmine\Player;


class MainClass extends PluginBase
{
    private $zones;

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents(new EventManager($this), $this);
	}

	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		switch($command->getName()){
			case "izone":
                safe_var_dump($args);
				return true;
			default:
				return false;
		}
	}

    public function &getZone(Player $owner)
    {
        return isset($this->zones[$owner->getDisplayName()]) == true ? $this->zones[$owner->getDisplayName()] : false;
    }

    public function &getAllZones()
    {
        return $this->zones;
    }

    public function removeZone(Player $owner)
    {
        if(isset($this->zones[$owner->getDisplayName()]))
        {
            unset($this->zones[$owner->getDisplayName()]);
            return true;
        }
        else
            return false;
    }

}