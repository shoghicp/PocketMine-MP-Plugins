<?php
 
/*
__PocketMine Plugin__
name=iZone
description=Protect multiple zone with this plugins
version=1.3
apiversion=10,11
author=InusualZ
class=iZone
*/
 
define("IZONE_VERSION", 1.3);

class iZone implements Plugin{
    private $api, $config, $areas;
 
    public function __construct(ServerAPI $api, $server = false){
        $this->api  = $api;
        $this->areas = array();
    }
    
    public function init()
    {
    	$this->api->addHandler("player.block.place", array($this, 'handler'), 15);
        $this->api->addHandler("player.block.break", array($this, 'handler'), 15);
        $this->api->addHandler("player.block.activate", array($this, 'handler'), 15);
        $this->api->addHandler("entity.explosion", array($this, 'handler'), 15);

    	$this->api->console->register("izone", "[iZone] Protect multiple zone", array($this, 'commands'));
        $this->api->console->alias("iz",    "izone");
        $this->api->console->alias("izc",   "izone create");
        $this->api->console->alias("izcm",  "izone create me");
        $this->api->console->alias("izd",   "izone delete");
        $this->api->console->alias("izag",  "izone addg");
        $this->api->console->alias("izdg",  "izone deleteg");
        $this->api->console->alias("izpg",  "izone permg");
        $this->api->console->alias("izh",   "izone help");

        //$this->api->schedule(10, array($this, "saveAll"), array(), true); // Auto Save Private Area 1/2 Second
    	
        $this->configInit();
    }

    public function commands($cmd, $params, $issuer)
    {
        if($cmd === "izone")
        {
            if(count($params) < 1)
            {
                console("Usage: /{$cmd} <command> [parameters...]");
                console("Usage: /izone create <int/Player> or /izc <int/Player>");
                console("Usage: /izone create <player1> <player2> or /izc <player1> <player2>");
                console("Usage: /izone create <x> <y> <z> or /izc <x> <y> <z>");
                console("Usage: /izone create <player> <x> <y> <z> or /izc <player> <x> <y> <z>");
                console("Usage: /izone create <x1> <y1> <z1> <x2> <y2> <z2> or /izc <x1> <y1> <z1> <x2> <y2> <z2>");
                console("Usage: /izone delete <owner> or /izd <owner>");
                console("Usage: /izone delete <x> <y> <z> or /izd <x> <y> <z>");
                console("Usage: /izone delete <x1> <y1> <z1> <x2> <y2> <z2> or /izd <x1> <y1> <z1> <x2> <y2> <z2>");
                console("Usage: /izone addg <player> or /izag <player>");
                console("Usage: /izone addg <player> <rank> or /izag <player> <rank>");
                console("Usage: /izone addg <player> <rank> <time> or /izag <player> <rank> <time>");
                console("Usage: /izone deleteg <player> or /izdg <player>");
                console("Usage: /izone permg <player> <rank> or /izpg <player> <rank>");
                console("Usage: /izone permg <player> <rank> <time> or /izpg <player> <rank> <time>");
                return;
            }

            switch(array_shift($params))
            {
                case 'create':
                    $ps = count($params);
                    if($issuer instanceof Player && $this->api->ban->isOp($issuer->username))
                    {
                        if( $ps  == 1)
                        {
                            $i = array_shift($params);
                            if(is_numeric($i) && intval($i) > 0){
                                $i = intval($i);
                                $pos1 =  array($issuer->entity->x - $i, $issuer->entity->y - $i, $issuer->entity->z - $i);
                                $pos2 =  array($issuer->entity->x + $i, $issuer->entity->y + $i, $issuer->entity->z + $i);

                                $this->areas[$issuer->username] = new PArea($this->api, $issuer->level, $pos1, $pos2, $issuer->username);
                                $issuer->sendChat($this->config->get('private-area-creation-msg'));
                            }
                            elseif(is_string($i) && !empty($i))
                            {
                                $i = $this->api->player->get($i);
                                if( $i instanceof Player)
                                {
                                    $pos1 =  array($issuer->entity->x, $issuer->entity->y, $issuer->entity->z);
                                    $pos2 =  array($i->entity->x, $i->entity->y, $i->entity->z);

                                    $this->areas[$issuer->username] = new PArea($this->api, $issuer->level, $pos1, $pos2, $issuer->username);
                                    $issuer->sendChat($this->config->get('private-area-creation-msg'));
                                }
                            }
                            else
                            {
                                console("Usage: /{$cmd} create <int:player>");
                            }

                        }
                        elseif($ps == 2)
                        {
                            $c = array_shift($params);
                            if(strtolower($c) == 'me' || strtolower($c) == 'i')
                                $u1 = $issuer;
                            else
                                $u1 = $this->api->player->get($c);

                            $i = array_shift($params);
                            if(is_numeric($i) && intval($i) > 0)
                            {
                                $i = intval($i);
                                $pos1 =  array($u1->entity->x - $i, $u1->entity->y - $i, $u1->entity->z - $i);
                                $pos2 =  array($u1->entity->x + $i, $u1->entity->y + $i, $u1->entity->z + $i);

                                $this->areas[$u1->username] = new PArea($this->api, $issuer->level, $pos1, $pos2, $u1->username);
                                $u1->sendChat($this->config->get('private-area-creation-msg'));
                            }
                            else
                            {
                                $u2 = $this->api->player->get($i);
                                if($u1 instanceof Player && $u2 instanceof Player)
                                {
                                    $pos1 =  array($u1->entity->x, $u1->entity->y, $u1->entity->z);
                                    $pos2 =  array($u2->entity->x, $u2->entity->y, $u2->entity->z);

                                    $this->areas[$u1->username] = new PArea($this->api, $issuer->level, $pos1, $pos2, $u1->username);
                                    $u1->sendChat($this->config->get('private-area-creation-msg'));
                                }
                            }
                        }
                        elseif($ps == 3)
                        {
                            $x = intval(array_shift($params));
                            $y = intval(array_shift($params));
                            $z = intval(array_shift($params));

                            if($x  > 0 && $y > 0 && $z > 0)
                            {
                                $pos1 =  array($issuer->entity->x, $issuer->entity->y, $issuer->entity->z);
                                $pos2 =  array($x, $y, $z);

                                $this->areas[$issuer->username] = new PArea($this->api, $issuer->level, $pos1, $pos2, $issuer->username);
                                $issuer->sendChat($this->config->get('private-area-creation-msg'));
                            }
                            else
                            {
                                console("[iZone] Usage: /{$cmd} create <x> <y> <z>");
                            }

                        }
                        elseif($ps == 4)
                        {
                            $t = $this->api->player->get(array_shift($params));
                            if($t instanceof Player)
                            {
                                $x = intval(array_shift($params));
                                $y = intval(array_shift($params));
                                $z = intval(array_shift($params));

                                if($x  > 0 && $y > 0 && $z > 0)
                                {
                                    $pos1 =  array($t->entity->x, $t->entity->y, $t->entity->z);
                                    $pos2 =  array($x, $y, $z);

                                    $this->areas[$t->username] = new PArea($this->api, $issuer->level, $pos1, $pos2, $t->username);
                                    $t->sendChat($this->config->get('private-area-creation-msg'));
                                }
                                else
                                {
                                    console("[iZone] Usage: /{$cmd} create <player> <x> <y> <z>");
                                }
                            }
                            else
                            {
                                console("[iZone] Usage: /{$cmd} create <player> <x> <y> <z>");
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

                            if($x1  > 0 && $y1 > 0 && $z1 > 0 && $x2  > 0 && $y2 > 0 && $z2 > 0)
                            {
                                $pos1 =  array($x1, $y1, $z1);
                                $pos2 =  array($x2, $y2, $z2);

                                $this->areas[$issuer->username] = new PArea($this->api, $issuer->level, $pos1, $pos2, $issuer->username);
                                $issuer->sendChat($this->config->get('private-area-creation-msg'));
                            }
                            else
                            {
                                console("[iZone] Usage: /{$cmd} create <x1> <y1> <z1> <x2> <y2> <z2>");
                            }
                        }
                        elseif($ps == 7)
                        {
                            $owner = array_shift($params);
                            if($owner instanceof Player)
                            {
                                $x1 = intval(array_shift($params));
                                $y1 = intval(array_shift($params));
                                $z1 = intval(array_shift($params));

                                $x2 = intval(array_shift($params));
                                $y2 = intval(array_shift($params));
                                $z2 = intval(array_shift($params));

                                if($x1  > 0 && $y1 > 0 && $z1 > 0 && $x2  > 0 && $y2 > 0 && $z2 > 0)
                                {
                                    $pos1 =  array($x1, $y1, $z1);
                                    $pos2 =  array($x2, $y2, $z2);

                                    $this->areas[$owner->username] = new PArea($this->api, $issuer->level, $pos1, $pos2, $owner->username);
                                    $owner->sendChat($this->config->get('private-area-creation-msg'));
                                }
                                else
                                {
                                    console("[iZone] Usage: /{$cmd} create <owner> <x1> <y1> <z1> <x2> <y2> <z2>");
                                }
                            }
                            else
                            {
                                console("[iZone] Usage: /{$cmd} create <owner> <x1> <y1> <z1> <x2> <y2> <z2>");
                            }
                        }
                        else
                        {
                            $i = intval($this->config->get('default-area-size'));
                            $pos1 =  array($issuer->entity->x - $i, $issuer->entity->y - $i, $issuer->entity->z - $i);
                            $pos2 =  array($issuer->entity->x + $i, $issuer->entity->y + $i, $issuer->entity->z + $i);

                            $this->areas[$issuer->username] = new PArea($this->api, $issuer->level, $pos1, $pos2, $issuer->username);
                            $issuer->sendChat($this->config->get('private-area-creation-msg'));
                        }
                    }
                    else
                    {
                        console("[iZone] Use this command in-game");
                    }
                    break;

                case 'delete':
                    $ps = count($params);
                    if($issuer instanceof Player)
                    {
                        if($this->api->ban->isOp($issuer->username))
                        {
                            if($ps == 1)
                            {
                                $owner = array_shift($params);
                                if(array_key_exists($owner, $this->areas))
                                {
                                    unset($this->areas[$owner]);
                                    $owner = $this->api->player->get($owner);

                                    if($owner instanceof Player)
                                        $owner->sendChat($this->config->get("private-area-removed-msg"));
                                    else
                                        $issuer->sendChat($this->config->get("private-area-removed-msg"));
                                }
                                else
                                {
                                    $issuer->sendChat("[iZone] {$owner} don't have a private area.");
                                }
                            }
                            elseif($ps == 3)
                            {
                                $x = intval(array_shift($params));
                                $y = intval(array_shift($params));
                                $z = intval(array_shift($params));

                                if($x  > 0 && $y > 0 && $z > 0)
                                {
                                    $owner = "l";
                                    foreach($this->areas as $area)
                                    {
                                        if($area->x1 == $x && $area->y1 == $y && $area->z1 == $z)
                                        {
                                            $owner = $area->$owner;
                                            break;
                                        }
                                    }
                                    unset($this->areas[$owner]);
                                }
                                else
                                {
                                    $issuer->sendChat("[iZone] Usage: /{$cmd} delete <x1> <y1> <z1>");
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

                                if($x1  > 0 && $y1 > 0 && $z1 > 0 && $x2  > 0 && $y2 > 0 && $z2 > 0)
                                {
                                    $owner = "l";
                                    foreach($this->areas as $area)
                                    {
                                        if($area->x1 == $x1 && $area->y1 == $y1 && $area->z1 == $z1 && $area->x2 == $x2 && $area->y2 == $y2 && $area->z2 == $z2)
                                        {
                                            $owner = $area->$owner;
                                            break;
                                        }
                                    }
                                    unset($this->areas[$owner]);
                                }
                                else
                                {
                                    $issuer->sendChat("[iZone] Usage: /{$cmd} delete <x1> <y1> <z1> <x2> <y2> <z2>");
                                }
                            }
                        }
                        else
                        {
                            if($ps > 0)
                                $issuer->sendChat("[iZone] You can only remove your private areas...");
                            else
                            {
                                unset($this->areas[$issuer->username]);
                                $issuer->sendChat($this->config->get("private-area-removed-msg"));
                            }
                        }
                    }
                    else
                    {
                        if($ps == 1)
                        {
                            $owner = array_shift($params);
                            if(array_key_exists($owner, $this->areas))
                            {
                                unset($this->areas[$owner]);
                                $owner = $this->api->player->get($owner);

                                if($owner instanceof Player)
                                    $owner->sendChat($this->config->get("private-area-removed-msg"));
                                else
                                    console($this->config->get("private-area-removed-msg"));
                            }
                            else
                            {
                                console("[iZone] {$owner} don't have a private area.");
                            }
                        }
                        elseif($ps == 3)
                        {
                            $x = intval(array_shift($params));
                            $y = intval(array_shift($params));
                            $z = intval(array_shift($params));

                            if($x  > 0 && $y > 0 && $z > 0)
                            {
                                $owner = "l";
                                foreach($this->areas as $area)
                                {
                                    if($area->x1 == $x && $area->y1 == $y && $area->z1 == $z)
                                    {
                                        $owner = $area->$owner;
                                        break;
                                    }
                                }
                                unset($this->areas[$owner]);
                            }
                            else
                            {
                                console("[iZone] Usage: /{$cmd} delete <x1> <y1> <z1>");
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

                            if($x1  > 0 && $y1 > 0 && $z1 > 0 && $x2  > 0 && $y2 > 0 && $z2 > 0)
                            {
                                $owner = "l";
                                foreach($this->areas as $area)
                                {
                                    if($area->x1 == $x1 && $area->y1 == $y1 && $area->z1 == $z1 && $area->x2 == $x2 && $area->y2 == $y2 && $area->z2 == $z2)
                                    {
                                        $owner = $area->$owner;
                                        break;
                                    }
                                }
                                unset($this->areas[$owner]);
                            }
                            else
                            {
                                console("[iZone] Usage: /{$cmd} delete <x1> <y1> <z1> <x2> <y2> <z2>");
                            }
                        }
                    }
                    break;

                case "addg":
                    if($issuer instanceof Player)
                    {
                        if(array_key_exists($issuer->username, $this->areas))
                        {
                            $user = array_shift($params);
                            if(empty($user) || $user == null)
                            {
                                $issuer->sendChat("[iZone] The player name cannot be empty");
                                return;
                            }

                            $this->areas[$issuer->username]->addGuest($user, array_shift($params), intval(array_shift($params)));
                            $issuer->sendChat("[iZone] The player has been added!");
                            return;
                        }
                        else
                        {
                            $user = array_shift($params);
                            if(empty($user) || $user == null)
                            {
                                $issuer->sendChat("[iZone] The player name cannot be empty");
                                return;
                            }

                            foreach($this->areas as $area)
                            {
                                if($area->getPerm($issuer->username) >= MOD_PERM);
                                $area[$issuer->username]->addGuest($user, array_shift($params), intval(array_shift($params)));
                                $issuer->sendChat("[iZone] The player has been added!");
                                return;
                            }
                        }
                        $issuer->sendChat("[iZone] You don't have private area.");
                    }
                    else
                    {
                        console('[iZone] Run the command in-game.');
                    }
                    break;

                case 'deleteg':
                    if($issuer instanceof Player)
                    {
                        if(array_key_exists($issuer->username, $this->areas))
                        {
                            $user = array_shift($params);
                            if(empty($user) || $user == null)
                            {
                                $issuer->sendChat("[iZone] The player name cannot be empty");
                                return;
                            }

                            $this->areas[$issuer->username]->deleteGuest($user);
                            $issuer->sendChat("[iZone] The player has been removed!");
                            return;
                        }
                        else
                        {
                            $user = array_shift($params);
                            if(empty($user) || $user == null)
                            {
                                $issuer->sendChat("[iZone] The player name cannot be empty");
                                return;
                            }

                            foreach($this->areas as $area)
                            {
                                if($area->getPerm($issuer->username) >= MOD_PERM);
                                $area[$issuer->username]->deleteGuest($user);
                                $issuer->sendChat("[iZone] The player has been removed!");
                                return;
                            }
                        }
                        $issuer->sendChat("[iZone] You don't have private area.");
                    }
                    else
                    {
                        console("[iZone] Run the command in-game.");
                    }
                    break;

                case 'permg':
                    if($issuer instanceof Player)
                    {
                        if(array_key_exists($issuer->username, $this->areas))
                        {
                            $user = array_shift($params);
                            if(empty($user) || $user == null)
                            {
                                $issuer->sendChat("[iZone] The player name cannot be empty");
                                return;
                            }

                            $this->areas[$issuer->username]->setPerm($user, array_shift($params), intval(array_shift($params)));
                            $issuer->sendChat("[iZone] The permission of the player has been updated!");
                            return;
                        }
                        else
                        {
                            $user = array_shift($params);
                            if(empty($user) || $user == null)
                            {
                                $issuer->sendChat("[iZone] The player name cannot be empty");
                                return;
                            }

                            foreach($this->areas as $area)
                            {
                                if($area->getPerm($issuer->username) >= MOD_PERM);
                                $area[$issuer->username]->setPerm($user, array_shift($params), intval(array_shift($params)));
                                $issuer->sendChat("[iZone] The permission of the player has been updated!");
                                return;
                            }
                        }
                        $issuer->sendChat("[iZone] You don't have private area.");
                    }
                    else
                    {
                        console("[iZone] Run the command in-game.");
                    }
                    break;

                case 'help':
                    console("Usage: /{$cmd} <command> [parameters...]");
                    console("Usage: /izone create <int/Player> or /izc <int/Player>");
                    console("Usage: /izone create <player1> <player2> or /izc <player1> <player2>");
                    console("Usage: /izone create <x> <y> <z> or /izc <x> <y> <z>");
                    console("Usage: /izone create <player> <x> <y> <z> or /izc <player> <x> <y> <z>");
                    console("Usage: /izone create <x1> <y1> <z1> <x2> <y2> <z2> or /izc <x1> <y1> <z1> <x2> <y2> <z2>");
                    console("Usage: /izone delete <owner> or /izd <owner>");
                    console("Usage: /izone delete <x> <y> <z> or /izd <x> <y> <z>");
                    console("Usage: /izone delete <x1> <y1> <z1> <x2> <y2> <z2> or /izd <x1> <y1> <z1> <x2> <y2> <z2>");
                    console("Usage: /izone addg <player> or /izag <player>");
                    console("Usage: /izone addg <player> <rank> or /izag <player> <rank>");
                    console("Usage: /izone addg <player> <rank> <time> or /izag <player> <rank> <time>");
                    console("Usage: /izone deleteg <player> or /izdg <player>");
                    console("Usage: /izone permg <player> <rank> or /izpg <player> <rank>");
                    console("Usage: /izone permg <player> <rank> <time> or /izpg <player> <rank> <time>");
                    break;

            }
        }
    }

    public function handler(&$data, $event)
    {
        switch ($event) {
            case 'player.block.place':
                if(!$this->api->ban->isOp($data['player']->username))
                {
                    foreach($this->areas as $k => $area)
                    {
                        if($area->isIn($data['block']->x, $data['block']->y, $data['block']->z) && ($area->level  == $data['player']->level))
                        {
                            if($area->getPerm($data['player']->username) <= SEE_PERM)
                            {
                                $data['player']->sendChat($this->config->get('private-area-msg'));
                                return false;
                            }
                        }
                    }
                }
                break;
            
            case 'player.block.break':
                if(!$this->api->ban->isOp($data['player']->username))
                {
                    foreach($this->areas as $k => $area)
                    {
                        if($area->isIn($data['target']->x, $data['target']->y, $data['target']->z) && ($area->level == $data['player']->level))
                        {
                            if($area->getPerm($data['player']->username) <= SEE_PERM)
                            {
                                $data['player']->sendChat($this->config->get('private-area-msg'));
                                return false;
                            }
                        }
                    }
                }
                break;

            case 'player.block.activate':
                if(!$this->api->ban->isOp($data['player']->username))
                {
                    foreach($this->areas as $k => $area)
                    {
                        if($area->isIn($data['target']->x, $data['target']->y, $data['target']->z) && $area->level  == $data['player']->level)
                        {
                            if($area->getPerm($data['player']->username) <= WORK_PERM)
                            {
                                $data['player']->sendChat($this->config->get('private-area-msg'));
                                return false;
                            }
                        }
                    }
                }
                break;

            case 'entity.explosion':
                if($this->config->get('explosion-protection') == true)
                {
                    $radius = $this->config->get('explosion-radius-protection');
                    foreach($this->areas as $k => $area)
                    {
                        if($area->isOnRadius($data['source']->x, $data['source']->y, $data['source']->z, $radius) && $area->level == $data['level'])
                        {
                            $k = $this->api->player->get($k);
                            if($k instanceof Player)
                                $k->sendChat($this->config->get('explosion-protection-msg'));

                            return false;
                        }
                    }
                }
                break;
        }
    }



    public function configInit()
    {
        $this->config = new Config($this->api->plugin->configPath($this) . "config.yml", CONFIG_YAML, array(
                "plugin-version"                => IZONE_VERSION,
                "private-area-msg"              => "[iZone] This is a private area.",
                "explosion-protection-msg"      => "[iZone] Someone is trying to blow up your private area.",
                "private-area-creation-msg"     => "[iZone] The private area has been created.",
                "private-area-removed-msg"      => "[iZone] The private area has been removed.",
                //"walk-on-private-area"          => false,
                "explosion-protection"          => true,
                "explosion-radius-protection"   => 8,
                "default-area-size"             => 10,
                "private-areas"                 => array(),
        ));

        if($this->config->get('plugin-version') != IZONE_VERSION)
        {
            unlink($this->api->plugin->configPath($this) . "config.yml");
            $this->config = new Config($this->api->plugin->configPath($this) . "config.yml", CONFIG_YAML, array(
                "plugin-version"                => IZONE_VERSION,
                "private-area-msg"              => "[iZone] This is a private area.",
                "explosion-protection-msg"      => "[iZone] Someone is trying to blow up your private area.",
                "private-area-creation-msg"     => "[iZone] The private area has been created.",
                "private-area-removed-msg"      => "[iZone] The private area has been removed.",
                //"walk-on-private-area"          => false,
                "explosion-protection"          => true,
                "explosion-radius-protection"   => 8,
                "default-area-size"             => 10,
                "private-areas"                 => array(),
            ));
        }
      //  $list = $this->config->get("private-areas");
      //  if(is_array($list))
      //  {
      //      foreach($list as $area)
      //      {
      //          $area  = PArea::fromString($this->api, $area, $this);
      //          $this->areas[$area->owner] = $area;
      //     }
      //  }
    }

    public function saveAll()
    {
        $list = array();
        foreach($this->areas as $area)
        {
            $list[] = $area->toString();
        }
        $this->config->set('private-areas', $list);
        $this->config->save();
    }

    public function __destruct(){
       // $list = array();
       // foreach($this->areas as $area)
       // {
      //      $list[] = $area->toString();
      //  }
       // $this->config->set('private-areas', $list);
       // $this->config->save();
    }

    public function newPArea($api, $level,  $pos1, $pos2, $users)
    {
        return new PArea($api, $level, $pos1, $pos2, $users);
    }
}

//These permissions can be granted for a specified time (In Seconds)
define("OWNER_PERM", 5.0);
define("MOD_PERM", 3.0); // With this permission you can place, destroy and activate block. You can kick and add guest to the area.
define("FRIEND_PERM", 2.5);// With this permission you can place,destroy and activate block in the area, but you can't add guest to the area
define("WORK_PERM", 2.0); // With this permission you can place and destroy
define("SEE_PERM", 1.0); // With this permission you only can see the area
define("SERV_PLAYER_PERM", 0.0); // A Server User

class PArea
{
    
    public $x1, $y1, $z1;
    public $x2, $y2, $z2;
    public $users = array();
    public $owner;
    public $level;
    public  $api;

    function __construct($api, $level, $pos1, $pos2, $user)
    {
        if($pos1 instanceof Vector3)
        {
            $this->x1 = $pos1->x;
            $this->y1 = $pos1->y;
            $this->z1 = $pos1->z;
        }
        else
        {
            $this->x1 = $pos1[0];
            $this->y1 = $pos1[1];
            $this->z1 = $pos1[2];
        }

        if($pos2 instanceof Vector3)
        {
            $this->x2 = $pos2->x;
            $this->y2 = $pos2->y;
            $this->z2 = $pos2->z;
        }
        else
        {
            $this->x2 = $pos2[0];
            $this->y2 = $pos2[1];
            $this->z2 = $pos2[2];
        }

        if(is_array($user))
        {
            $this->owner = $user[0];
            $this->users = $user;
        }
        else
        {
            $this->owner = $user;
            $this->users[$user] = OWNER_PERM;
        }

        $this->api = $api;

        if(!$level instanceof Level)
        {
            $tmp = $this->api->level->get($level);
            if($tmp instanceof Level)
                $this->level = $tmp;
            else
                $this->level = $this->api->level->getDefault();
        }
    }

    public function isIn($x, $y, $z)
    {
        $min = array(
            min($this->x1, $this->x2),
            min($this->y1, $this->y2),
            min($this->z1, $this->z2)
            );
        $max = array(
            max($this->x1, $this->x2),
            max($this->y1, $this->y2),
            max($this->z1, $this->z2)
            );

        if($min[0] <= $x && $x  <= $max[0])
        {
            if($min[1] <= $y && $y <= $max[1])
            {
                if($min[2] <= $z && $z <= $max[2])
                {
                    return true;
                }
            }
        }
        return false;
    }

    public function isOnRadius($x, $y, $z, $radius)
    {
        $min = array(
            min($this->x1 - $radius, $this->x2 + $radius),
            min($this->y1 - $radius, $this->y2 + $radius),
            min($this->z1 - $radius, $this->z2 + $radius)
        );
        $max = array(
            max($this->x1 - $radius, $this->x2 + $radius),
            max($this->y1 - $radius, $this->y2 + $radius),
            max($this->z1 - $radius, $this->z2 + $radius)
        );

        if($min[0] <= $x && $x  <= $max[0])
        {
            if($min[1] <= $y && $y <= $max[1])
            {
                if($min[2] <= $z && $z <= $max[2])
                {
                    return true;
                }
            }
        }
        return false;
    }


    public function setPerm($user, $perm, $time = 0, $reset = false)
    {
        $perm = $this->getPermCode($perm);
        if($user[0] == '(' && $user[1] == ')')
        {
            unset($user[0], $user[1], $user[strlen($user)]);
            $user = explode(',', $user);
        }
        if(is_array($user))
        {
            foreach($user as $u)
            {
                if(array_key_exists($u, $this->users))
                {
                    if($reset == true && $time > 0)
                    {
                        $lperm = $this->users[$u];
                        $this->users[$u] = $perm;
                        $this->api->schedule(20 * $time, array($this, "setPerm"), array($u, $lperm, 0, false), false);
                    }
                    elseif($time > 0)
                    {
                        $this->users[$u] = $perm;
                        $this->api->schedule(20 * $time, array($this, "deleteGuest"), array($u), false);
                    }
                    else
                        $this->users[$u] = $perm;
                }
                $u = $this->api->player->get($u);
                if($u instanceof Player)
                {
                    $u->sendChat("[iZone] Your permission on a private area to be been changed");
                }
            }
        }
        else
        {
            if($user instanceof Player)
            {
                if(array_key_exists($user->username, $this->users))
                {
                    if($reset == true && $time > 0)
                    {
                        $lperm = $this->users[$user->username];
                        $this->users[$user->username] = $perm;
                        $this->api->schedule(20 * $time, array($this, "setPerm"), array($user, $lperm, 0, false), false);
                    }
                    elseif($time > 0 && $reset == false)
                    {
                        $this->users[$user->username] = $perm;
                        $this->api->schedule(20 * $time, array($this, "deleteGuest"), array($user), false);
                    }
                    else
                        $this->users[$user->username] = $perm;
                }
                $user->sendChat("[iZone] Your permission on a private area to be been changed");
            }
            else
            {
                if(array_key_exists($user, $this->users))
                {
                    if($reset == true && $time > 0)
                    {
                        $lperm = $this->users[$user];
                        $this->users[$user] = $perm;
                        $this->api->schedule(20 * $time, array($this, "setPerm"), array($user, $lperm, 0, false), false);
                    }
                    elseif($time > 0)
                    {
                        $this->users[$user] = $perm;
                        $this->api->schedule(20 * $time, array($this, "deleteGuest"), array($user), false);
                    }
                    else
                        $this->users[$user] = $perm;
                }
                $u = $this->api->player->get($user);
                if($u instanceof Player)
                {
                    $u->sendChat("[iZone] Your permission on a private area to be been changed");
                }
            }
        }
    }

    public function getPerm($user)
    {
        if($user instanceof Player)
        {
            if(array_key_exists($user->username, $this->users))
            {
                return $this->users[$user->username];
            }
            else
                return SERV_PLAYER_PERM;
        }
        else
        {
            if(array_key_exists($user, $this->users))
            {
                return $this->users[$user];
            }
            else
                return SERV_PLAYER_PERM;
        }
        return SERV_PLAYER_PERM;
    }

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

    public function addGuest($user, $perm, $time = 0)
    {
        if($user[0] == '(' && $user[1] == ')')
        {
            unset($user[0], $user[1], $user[strlen($user)]);
            $user = explode(',', $user);
        }

        if(!is_array($user))
        {
            $perm = $this->getPermCode($perm);
            if($user instanceof Player)
            {
                $this->users[$user->username] = $perm;
                if($user instanceof Player)
                {
                    $user->sendChat("[iZone] You have been added to a private area");
                }
            }
            else
            {
                $this->users[$user] = $perm;
                $u = $this->api->player->get($user);
                if($u instanceof Player)
                {
                    $u->sendChat("[iZone] You have been added to a private area");
                }
            }

            if($time > 0)
            {
                $this->api->schedule(20 * $time, array($this, "deleteGuest"), array($user), false);
            }

        }
        else
        {
            $perm = $this->getPermCode($perm);
            foreach($user as $u)
            {
                $this->users[$u] = $perm;

                if($time > 0)
                {
                    $this->api->schedule(20 * $time, array($this, "deleteGuest"), array($u), false);
                }
                $u = $this->api->player->get($u);
                if($u instanceof Player)
                {
                    $u->sendChat("[iZone] You have been added to a private area");
                }
            }
        }
    }

    public function deleteGuest($user)
    {
        if($user[0] == '(' && $user[1] == ')')
        {
            $user[0] = '';$user[1] = '';$user[strlen($user)] = '';
            $user = explode(',', $user);
        }

        if(is_array($user))
        {
            foreach($user as $u)
            {
                unset($this->users[$u]);
                $u = $this->api->player->get($u);
                if($u instanceof Player)
                {
                    $u->sendChat("[iZone] You have been removed from a private area");
                }
            }
        }
        else
        {
            if($user instanceof Player)
            {
                unset($this->users[$user->username]);
                $user->sendChat("[iZone] You have been removed from a private area");
            }
            else
            {
                unset($this->users[$user]);
                $u = $this->api->player->get($user);
                if($u instanceof Player)
                {
                    $u->sendChat("[iZone] You have been removed from a private area");
                }
            }
        }
    }

    public static function fromString($api, $string, $plugin)
    {
        $a = explode('(', $string)[1];
        $a = explode(',', $a);

        $pos1 = array(array_shift($a), array_shift($a), array_shift($a));
        $pos2 = array(array_shift($a), array_shift($a), array_shift($a));
        return $plugin->newPArea($api, $pos1, $pos2, $a);
    }

    public function toString()
    {
        $object =  "PArea(" . "{$this->owner}," . "{$this->x1}," . "{$this->y1}," . "{$this->z1}," .
        "{$this->x2}," . "{$this->y2}," . "{$this->z2}";

        foreach($this->users as $k => $u)
        {
            $object .= ",";
            $object .= $u;
        }
        $object .= ')';

        return $object;
    }

}