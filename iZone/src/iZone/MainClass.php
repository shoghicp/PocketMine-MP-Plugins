<?php

namespace iZone;

use pocketMine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketMine\command\CommandSender;
use pocketmine\level\Position;
use pocketMine\plugin\PluginBase;
use pocketmine\Player;


/**
 * Class MainClass
 * @package iZone
 */
class MainClass extends PluginBase implements CommandExecutor
{
    /**
     * @var Zone[]
     */
    private $zones;

    /**
     *
     */
    public function onLoad()
    {
        $this->saveDefaultConfig();
        $this->getResource("config.yml");
    }

    /**
     *
     */
    public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents(new EventManager($this), $this);
	}

    /**
     * @param CommandSender $sender
     * @param Command $command
     * @param string $label
     * @param array $args
     *
     * @return bool
     */public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		if($command->getName() != "izone" || ($sender instanceof Player) == false)
            return false;

        switch(array_shift($args))
        {
            case "create":

                if($this->getConfig()->get("non-op-create") == true || $this->getServer()->isOp($sender->getName()))
                {
                    if(count($args) < 1)
                    {
                        $i = intval($this->getConfig()->get('default-area-size'));
                        $pos1 =  new Position($sender->x - $i, $sender->y - $i, $sender->z - $i, $sender->level);
                        $pos2 =  new Position($sender->x + $i, $sender->y + $i, $sender->z + $i, $sender->level);

                        foreach($this->zones as $zone)
                        {
                            if($zone->isIn($pos1) || $zone->isIn($pos2))
                                return false;
                        }

                        $this->zones[$sender->getName()] = new Zone($this, $sender, $pos1, $pos2);
                        $sender->sendMessage($this->getConfig()->get('private-area-creation-msg'));
                    }
                    elseif(count($args) == 1)
                    {
                        if(is_numeric($args[0]) && intval($args[0]) > 0)
                        {

                            $i = intval($args[0]);
                            $pos1 =  new Position($sender->x - $i, $sender->y - $i, $sender->z - $i, $sender->level);
                            $pos2 =  new Position($sender->x + $i, $sender->y + $i, $sender->z + $i, $sender->level);

                            $this->zones[$sender->getName()] = new Zone($this, $sender, $pos1, $pos2);
                            $sender->sendMessage($this->getConfig()->get('private-area-creation-msg'));
                        }
                        elseif(is_string($args[0]) && !empty($args[0]))
                        {
                            $i = $this->getServer()->getPlayer($args[0]);
                            if( $i instanceof Player)
                            {
                                $pos1 =  new Position($sender->x, $sender->y, $sender->z, $sender->level);
                                $pos2 =  new Position($i->x, $i->y, $i->z, $i->level);

                                $this->zones[$sender->getName()] = new Zone($this, $sender, $pos1, $pos2);
                                $sender->sendMessage($this->getConfig()->get('private-area-creation-msg'));
                            }
                        }
                        else
                        {
                            $sender->sendMessage("For Help Type: /izone help]");
                        }
                    }
                    elseif(count($args) == 2)
                    {
                        $u1 = $this->getServer()->getPlayer($args[0]);

                        $i = $args[1];
                        if(is_numeric($i) && intval($i) > 0 && $u1 instanceof Player)
                        {
                            $i = intval($i);
                            $pos1 =  new Position($u1->x - $i, $u1->y - $i, $u1->z - $i, $u1->level);
                            $pos2 =  new Position($u1->x + $i, $u1->y + $i, $u1->z + $i, $u1->level);

                            $this->zones[$u1->getName()] = new Zone($this, $sender, $pos1, $pos2);
                            $u1->sendMessage($this->getConfig()->get('private-area-creation-msg'));
                            return true;
                        }
                        else
                        {
                            $u2 = $this->getServer()->getPlayer($i);
                            if($u1 instanceof Player && $u2 instanceof Player)
                            {
                                $pos1 =  array($u1->x, $u1->y, $u1->z, $u1->level);
                                $pos2 =  array($u2->x, $u2->y, $u2->z, $u2->level);

                                $this->zones[$u1->getName()] = new Zone($this, $sender, $pos1, $pos2);
                                $u1->sendMessage($this->getConfig()->get('private-area-creation-msg'));
                                 return true;
                            }
                            return false;
                        }
                        return false;

                    }
                    elseif(count($args) == 3)
                    {
                        $x = intval($args[0]);
                        $y = intval($args[1]);
                        $z = intval($args[2]);

                        if($sender->level->level->getBlock($x, $y, $z) == false)
                            return false;

                        $pos1 =  new Position($sender->x, $sender->y, $sender->z, $sender->level);
                        $pos2 =  new Position($x, $y, $z);

                        $this->zones[$sender->getName()] = new Zone($this, $sender, $pos1, $pos2);
                        $sender->sendMessage($this->getConfig()->get('private-area-creation-msg'));
                    }
                    elseif(count($args) == 4)
                    {
                        $t = $this->getServer()->getPlayer($args[0]);
                        if($t instanceof Player)
                        {
                            $x = intval($args[1]);
                            $y = intval($args[2]);
                            $z = intval($args[3]);

                            if($t->level->level->getBlock($x, $y, $z) == false)
                                return false;

                            $pos2 =  new Position($x, $y, $z, $t->level);

                            $this->zones[$t->getName()] = new Zone($this, $t, $t, $pos2);
                            $t->sendMessage($this->getConfig()->get('private-area-creation-msg'));

                            return true;
                        }
                        else
                            $sender->sendMessage("For Help Type: /izone help");

                        return false;
                    }
                    elseif(count($args) == 6)
                    {
                        $x1 = intval($args[0]);
                        $y1 = intval($args[1]);
                        $z1 = intval($args[2]);

                        $x2 = intval($args[3]);
                        $y2 = intval($args[4]);
                        $z2 = intval($args[5]);

                        $pos1 =  new Position($x1, $y1, $z1, $sender->level);
                        $pos2 =  new Position($x2, $y2, $z2, $sender->level);

                        if($sender->level->level->getBlock($pos1) == false || $sender->level->level->getBlock($pos2) == false)
                            return false;

                        $this->zones[$sender->getName()] = new Zone($this, $sender, $pos1, $pos2);
                        $sender->sendMessage($this->getConfig()->get('private-area-creation-msg'));
                        return true;
                    }
                    elseif(count($args) == 7)
                    {
                        $owner = $this->getServer()->getPlayer($args[0]);
                        if($owner instanceof Player)
                        {
                            $x1 = intval($args[1]);
                            $y1 = intval($args[2]);
                            $z1 = intval($args[3]);

                            $x2 = intval($args[4]);
                            $y2 = intval($args[5]);
                            $z2 = intval($args[6]);

                            $pos1 =  new Position($x1, $y1, $z1, $owner->level);
                            $pos2 =  new Position($x2, $y2, $z2, $owner->level);

                            if($sender->level->level->getBlock($pos1) == false || $sender->level->level->getBlock($pos2) == false)
                                return false;

                            $this->zones[$owner->getName()] = new Zone($this, $owner, $pos1, $pos2);
                            $owner->sendMessage($this->getConfig()->get('private-area-creation-msg'));
                            return true;
                        }
                        else
                        {
                            $sender->sendMessage("For Help Type: /izone help");
                        }
                    }
                }
                break;

            case 'delete':
                $ps = count($args);
                if($ps == 1)
                {
                    $owner = array_shift($params);
                    if(array_key_exists($owner, $this->zones))
                    {
                        if($this->zone[$owner]->getPermission($sender->getName()) == OWNER_PERM || $this->getServer()->isOp($sender->getName()))
                        {
                            unset($this->zones[$owner]);
                            $owner = $this->getServer()->getPlayer($owner);

                            if($owner instanceof Player)
                                $owner->sendMessage($this->getConfig()->get("private-area-removed-msg"));
                            else
                                $sender->sendMessage($this->getConfig()->get("private-area-removed-msg"));
                        }
                    }
                    elseif(strtolower($owner) == "all")
                    {
                        if($this->getServer()->isOp($sender->getName()))
                        {
                            unset($this->areas);
                            $this->areas = array();
                        }
                    }
                    else
                    {
                        $sender->sendMessage("[iZone] For Help Type: /izone help");
                    }
                }
                elseif($ps == 3)
                {
                    $x = intval(array_shift($params));
                    $y = intval(array_shift($params));
                    $z = intval(array_shift($params));

                    $pos = new Position($x, $y, $z, $sender->level);

                    $owner = "l";
                    foreach($this->zones as $area)
                    {
                        if($area->pos1 === $pos || $area->pos2 === $pos)
                        {
                            $owner = $area->$owner;
                            break;
                        }
                    }

                    if($owner instanceof Player)
                    {
                        if($this->getServer()->isOp($sender->getName()) || $this->zones[$owner->getName()]->getPermission($sender->getName()) == OWNER_PERM)
                        {
                            unset($this->zones[$owner->getName()]);
                        }
                        else
                        {
                            $sender->sendMessage("[iZone] You can't execute this command");
                        }
                    }
                }
                elseif($ps == 6)
                {
                    $x1 = intval(array_shift($params));
                    $y1 = intval(array_shift($params));
                    $z1 = intval(array_shift($params));

                    $x2 = intval(array_shift($params));
                    $y2 = intval(array_shift($params));
                    $z2 = intval(array_shift($params));

                    $pos1 = new Position($x1, $y1, $z1, $sender->level);
                    $pos2 = new Position($x2, $y2, $z2, $sender->level);

                    $owner = "l";
                    foreach($this->zones as $area)
                    {
                        if($area->pos1 === $pos1 && $area->pos2 === $pos2)
                        {
                            $owner = $area->$owner;
                            break;
                        }
                    }

                    if($owner instanceof Player)
                    {
                        if($this->getServer()->isOp($sender->getName()) || $this->zones[$owner->getName()]->getPermission($sender->getName()) == OWNER_PERM)
                        {
                            unset($this->zones[$owner->getName()]);
                        }
                        else
                        {
                            $sender->sendMessage("[iZone] You can't execute this command");
                        }
                    }
                }
                else
                {
                    unset($this->zones[$sender->getName()]);
                    $sender->sendMessage($this->getConfig()->get("private-area-removed-msg"));
                }
                break;

            case "addg":
                if(array_key_exists($sender->getName(), $this->zones))
                {
                    $user = array_shift($params);
                    if(empty($user) || $user == null || strtolower($sender->getName()) == strtolower($user))
                    {
                        $sender->sendMessage("[iZone] The player name cannot be empty");
                        return false;
                    }

                    $user = $this->getServer()->getPlayer($user);
                    if($user instanceof Player)
                    {
                        $this->zones[$sender->getName()]->addGuest($user, array_shift($params), intval(array_shift($params)));
                        $sender->sendMessage("[iZone] The player has been added!");
                        return true;
                    }
                    $sender->sendMessage("[iZone] The player is not online!");
                    return false;
                }
                else
                {
                    $user = array_shift($params);
                    if(empty($user) || $user == null)
                    {
                        $sender->sendMessage("[iZone] The player name cannot be empty");
                        return false;
                    }

                    $user = $this->getServer()->getPlayer($user);
                    if(!$user instanceof Player && $user->getName() == $sender->getName())
                    {
                        $sender->sendMessage("The user don't exist or not is online!");
                        return false;
                    }

                    foreach($this->zones as $area)
                    {
                        if($area->getPermission($sender->getName()) >= MOD_PERM)
                        {
                            $area->addGuest($user, array_shift($params), intval(array_shift($params)));
                            $sender->sendMessage("[iZone] The player has been added!");
                            return true;
                        }
                    }
                }
                $sender->sendMessage("[iZone] You don't have private area.");
                break;

            case 'deleteg':
                if(array_key_exists($sender->getName, $this->zones))
                {
                    $user = array_shift($params);
                    if(empty($user) || $user == null)
                    {
                        $sender->sendMessage("[iZone] The player name cannot be empty");
                        return false;
                    }

                    $user = $this->getServer()->getPlayer($user);
                    if(!$user instanceof Player)
                    {
                        $sender->sendMessage("The user don't exist or not is online!");
                        return false;
                    }

                    $this->zones[$sender->getName()]->deleteGuest($user);
                    $sender->sendMessage("[iZone] The player has been removed!");
                    return true;
                }
                else
                {
                    $user = array_shift($params);
                    if(empty($user) || $user == null)
                    {
                        $sender->sendMessage("[iZone] The player name cannot be empty");
                        return false;
                    }

                    $user = $this->getServer()->getPlayer($user);
                    if(!$user instanceof Player)
                    {
                        $sender->sendMessage("The user don't exist or not is online!");
                        return false;
                    }

                    foreach($this->areas as $area)
                    {
                        if($area->getPerm($sender->getName()) >= MOD_PERM)
                        {
                            $area->deleteGuest($user);
                            $sender->sendMessage("[iZone] The player has been removed!");
                            return true;
                        }
                    }
                }
                $sender->sendMessage("[iZone] You don't have private area.");
                break;

            case 'permg':
                if(array_key_exists($sender->getName(), $this->zones))
                {
                    $user = array_shift($params);
                    if(empty($user) || $user == null)
                    {
                        $sender->sendMessage("[iZone] The player name cannot be empty");
                        return false;
                    }

                    $user = $this->getServer()->getPlayer($user);
                    if(!$user instanceof Player)
                    {
                        $sender->sendMessage("The user don't exist or not is online!");
                        return false;
                    }

                    $this->zones[$sender->getName()]->setPermission($user, array_shift($params), intval(array_shift($params)));
                    $sender->sendMessage("[iZone] The permission of the player has been updated!");
                    return true;
                }
                else
                {
                    $user = array_shift($params);
                    if(empty($user) || $user == null)
                    {
                        $sender->sendMessage("[iZone] The player name cannot be empty");
                        return false;
                    }

                    $user = $this->getServer()->getPlayer($user);
                    if(!$user instanceof Player)
                    {
                        $sender->sendMessage("The user don't exist or not is online!");
                        return false;
                    }

                    foreach($this->zones as $area)
                    {
                        if($area->getPerm($sender->getName()) >= MOD_PERM)
                        {
                            $perm = $area->getPermCode(array_shift($params));
                            if($perm > MOD_PERM && $area->getPermission($sender) == MOD_PERM)
                            {
                                $sender->sendMessage("[iZone] You can't override you rank!");
                                return false;
                            }

                            $area->setPermission($user, $perm, intval(array_shift($params)));
                            $sender->sendMessage("[iZone] The permission of the player has been updated!");
                            return true;
                        }
                    }
                }
                $sender->sendMessage("[iZone] You don't have private area.");
                break;

            case 'coord':
                    $sender->sendMessage("[iZone] Coord: X: {$sender->x} Y: {$sender->y} Z: {$sender->z}");
                break;

            case 'help':
                $sender->sendMessage("Usage: /izone <command> [parameters...]");
                $sender->sendMessage("Usage: /izone create [int] or /izc [int]");
                $sender->sendMessage("Usage: /izone create [player] or /izc [player]");
                $sender->sendMessage("Usage: /izone create [player1] [player2] or /izc [player1] [player2]");
                $sender->sendMessage("Usage: /izone create [x] [y] [z] or /izc [x] [y] [z]");
                $sender->sendMessage("Usage: /izone create [player] [x] [y] [z] or /izc [player] [x] [y] [z]");
                $sender->sendMessage("Usage: /izone create [x1] [y1] [z1] [x2] [y2] [z2] or /izc [x1] [y1] [z1] [x2] [y2] [z2]");
                $sender->sendMessage("Usage: /izone delete [owner] or /izd [owner]");
                $sender->sendMessage("Usage: /izone delete [x] [y] [z] or /izd [x] [y] [z]");
                $sender->sendMessage("Usage: /izone delete [x1] [y1] [z1] [x2] [y2] [z2] or /izd [x1] [y1] [z1] [x2] [y2] [z2]");
                $sender->sendMessage("Usage: /izone addg [player] or /izag [player]");
                $sender->sendMessage("Usage: /izone addg [player] [rank] or /izag [player] [rank]");
                $sender->sendMessage("Usage: /izone addg [player] [rank] [time] or /izag [player] [rank] [time]");
                $sender->sendMessage("Usage: /izone deleteg [player] or /izdg [player]");
                $sender->sendMessage("Usage: /izone permg [player] [rank] or /izpg [player] [rank]");
                $sender->sendMessage("Usage: /izone permg [player] [rank] [time] or /izpg [player] [rank] [time]");
                $sender->sendMessage("Usage: /izone coord or /izco or /coord");
                break;
        }

        return false;
	}

    /**
     * @param Player $owner
     *
     * @return bool
     */
    public function &getZone(Player $owner)
    {
        return isset($this->zones[$owner->getName()]) == true ? $this->zones[$owner->getName()] : false;
    }

    /**
     * @return mixed
     */
    public function &getAllZones()
    {
        return $this->zones;
    }

    /**
     * @param Player $owner
     *
     * @return bool
     */
    public function removeZone(Player $owner)
    {
        if(isset($this->zones[$owner->getName()]))
        {
            unset($this->zones[$owner->getName()]);
            return true;
        }
        else
            return false;
    }

}