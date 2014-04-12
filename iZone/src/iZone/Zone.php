<?php
namespace iZone;

use iZone\Task\DeleteMember;
use iZone\Task\PermissionMember;

use pocketmine\Player;
use pocketmine\level\Position;

//These permissions can be granted for a specified time (In Seconds)
define("OWNER_PERM", 5.0);
define("MOD_PERM", 3.0); // With this permission you can place, destroy and activate block. You can kick and add guest to the area.
define("FRIEND_PERM", 2.5);// With this permission you can place,destroy and activate block in the area, but you can't add guest to the area
define("WORK_PERM", 2.0); // With this permission you can place and destroy
define("SEE_PERM", 1.0); // With this permission you only can see the area
define("SERV_PLAYER_PERM", 0.0); // A Server User

/**
 * Class Zone
 * @package iZone
 */
class Zone
{
    public $plugin, $pos1, $pos2, $owner, $users,$setup = false;

    /**
     * @param MainClass $plugin
     * @param Player $owner
     * @param Position $pos1
     * @param Position $pos2
     */
    function __construct(MainClass $plugin, Player $owner, Position $pos1, Position $pos2)
    {
        $this->plugin = $plugin;

        if($pos1->level === $pos2->level)
        {
            $this->pos1 = $pos1;
            $this->pos2 = $pos2;
        }
        else
            return;

        $this->owner = $owner;
        $this->setup = true;
    }

    /**
     * @param Position $position
     *
     * @return bool
     */
    public function isIn(Position $position)
    {
        if($this->setup == false)
            return false;

        $min = array(
            min($this->pos1->x, $this->pos2->x),
            min($this->pos1->y, $this->pos2->y),
            min($this->pos1->z, $this->pos2->z)
        );
        $max = array(
            max($this->pos1->x, $this->pos2->x),
            max($this->pos1->y, $this->pos2->y),
            max($this->pos1->z, $this->pos2->z)
        );

        if($min[0] <= $position->x && $position->x  <= $max[0])
        {
            if($min[1] <= $position->y && $position->y <= $max[1])
            {
                if($min[2] <= $position->z && $position->z <= $max[2])
                {
                    if($position->level === $this->pos1->level)
                        return true;
                }
            }
        }
        return false;
    }

    /**
     * @param Position $position
     * @param $radius
     *
     * @return bool
     */
    public function isOnRadius(Position $position, $radius)
    {
        if($this->setup == false)
            return false;

        $min = array(
            min($this->pos1->x - $radius, $this->pos2->x + $radius),
            min($this->pos1->y - $radius, $this->pos2->y + $radius),
            min($this->pos1->z - $radius, $this->pos2->z + $radius)
        );
        $max = array(
            max($this->pos1->x - $radius, $this->pos2->x + $radius),
            max($this->pos1->y - $radius, $this->pos2->y + $radius),
            max($this->pos1->z - $radius, $this->pos2->z + $radius)
        );

        if($min[0] <= $position->x && $position->x  <= $max[0])
        {
            if($min[1] <= $position->y && $position->y <= $max[1])
            {
                if($min[2] <= $position->z && $position->z <= $max[2])
                {
                    if($position->level === $this->pos1->level)
                        return true;
                }
            }
        }
        return false;
    }


    /**
     * @param Player $user
     * @param $perm
     * @param int $time
     * @param bool $reset
     */
    public function setPermission(Player $user, $perm, $time = 0, $reset = false)
    {

        $perm = $this->getPermCode($perm);
        if(array_key_exists($user->getName(), $this->users))
        {
            if($reset === true && $time > 0)
            {
                $lperm = $this->users[$user->getName()];
                $this->users[$user->getName()] = $perm;
                $this->plugin->getServer()->getScheduler()->scheduleDelayedTask(new PermissionMember($this, $user, $lperm), 20 * $time);
                //$this->api->schedule(20 * $time, array($this, "setPermission"), array($user, $lperm, 0, false), false);
            }
            elseif($time > 0 && $reset === false)
            {
                $this->users[$user->getName()] = $perm;
                $this->plugin->getServer()->getScheduler()->scheduleDelayedTask(new DeleteMember($this, $user), 20 * $time);
                //$this->api->schedule(20 * $time, array($this, "deleteGuest"), array($user), false);
            }
            else
                $this->users[$user->getName()] = $perm;
        }
        //$user->sendMessage("[iZone] You have been ranked: " . $perm . ". In the private area of: ". $this->owner->getName());
    }

    /**
     * @param Player $user
     *
     * @return float
     */
    public function getPermission(Player $user)
    {
        if(array_key_exists($user->getName(), $this->users))
            return $this->users[$user->getName()];
        else
            return SERV_PLAYER_PERM;
    }

    /**
     * @param $perm
     *
     * @return float
     */
    public function getPermCode($perm)
    {
        switch(strtolower($perm))
        {
            case 'administrator':
            case 'admin':
            case 'owner':
            case 'own':
            case 'o':
            case 'a':
                return OWNER_PERM;
                break;

            case 'moderator':
            case 'mod':
            case 'm':
                return MOD_PERM;
                break;

            case 'friend':
            case 'f':
                return FRIEND_PERM;
                break;

            case 'worker':
            case 'work':
            case 'w':
                return WORK_PERM;
                break;

            case 'spectator':
            case 'spec':
            case 'see':
            case 's':
            default:
                return SEE_PERM;
                break;

        }
    }

    /**
     * @param Player $user
     * @param $perm
     * @param int $time
     */
    public function addGuest(Player $user, $perm, $time = 0)
    {
        $perm = $this->getPermCode($perm);
        $this->users[$user->getName()] = $perm;
        $user->sendMessage("[iZone] You have been added to the private area of: " . $this->owner->getName());

        if($time > 0)
            $this->plugin->getServer()->getScheduler()->scheduleDelayedTask(new DeleteMember($this, $user), 20 * $time);
    }

    /**
     * @param Player $user
     */
    public function deleteGuest(Player $user)
    {
        if(array_key_exists($user->getName(), $this->users))
        {
            unset($this->users[$user->getName()]);
            $user->sendMessage("[iZone] You have been removed from the private area of: " . $this->owner->getName());
        }
    }

} 