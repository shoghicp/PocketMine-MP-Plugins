<?php

namespace iZone;

use pocketmine\event\Listener;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\Player;

class EventManager implements Listener
{
    private $plugin;

	public function __construct(MainClass $base)
	{
        $this->plugin = $base;
	}


    /**
     * @param BlockPlaceEvent $event
     *
     * @priority        HIGH
     * @ignoreCancelled true
     */
    public function OnBlockPlace(BlockPlaceEvent $event)
    {
        $list = $this->plugin->getAllZones();
        $player = $event->getPlayer();

        foreach($list as $user => $zone)
        {
            if($user == $player->getDisplayName() || $zone->getPerm($player) > SEE_PERM)
                return;

            if($zone->isIn($event->getBlock()))
            {
                $event->setCancelled();
                return false;
            }

        }
    }

    /**
     * @param BlockBreakEvent $event
     *
     * @priority        HIGH
     * @ignoreCancelled true
     */
    public function OnBlockBreak(BlockBreakEvent $event)
    {
        $list = $this->plugin->getAllZones();
        $player = $event->getPlayer();

        foreach($list as $user => $zone)
        {
            if($user == $player->getDisplayName() || $zone->getPerm($player) > SEE_PERM)
                return;

            if($zone->isIn($event->getBlock()))
            {
                $event->setCancelled();
                return false;
            }

        }
    }


    /**
     * @param EntityExplodeEvent $event
     *
     * @priority        HIGH
     * @ignoreCancelled true
     */
    public function OnEntityExplode(EntityExplodeEvent $event)
    {
        if($this->plugin->getConfig()->get("explosion-protection") != true)
            return true;

        $radius = $this->plugin->getConfig()->get("explosion-radius-protection");

        foreach($this->plugin->getAllZones() as $k => $v)
        {
            if($v->isOnRadius($event->getPosition(), $radius))
            {
                $k = Player::get($k);
                if($k instanceof Player)
                    $k->sendChat($this->plugin->getConfig()->get('explosion-protection-msg'));

                $event->setCancelled();
                return false;
            }
        }

    }

    /**
     * @param PlayerInteractEvent $event
     *
     * @priority        HIGH
     * @ignoreCancelled true
     */
    public function OnPlayerInteract(PlayerInteractEvent $event)
    {
        $list = $this->plugin->getAllZones();
        $player = $event->getPlayer();

        foreach($list as $user => $zone)
        {
            if($user == $player->getDisplayName() || $zone->getPerm($player) > SEE_PERM)
                return;

            if($zone->isIn($event->getBlock()))
            {
                $event->setCancelled();
                return false;
            }

        }
    }

}