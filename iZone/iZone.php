<?php

/*
__PocketMine Plugin__
name=iZone
description=Protect multiple zone with this plugins
version=1.9
apiversion=10,11,12, 13
author=InusualZ
class=iZone
*/

define("IZONE_VERSION", 2.0);

class iZone implements Plugin{
    private $api, $config, $areas;

    public function __construct(ServerAPI $api, $server = false){
        $this->api  = $api;
        $this->areas = array();
    }

    public function init()
    {
        $this->api->addHandler("player.block.touch", array($this, 'handler'), 15);
        $this->api->addHandler("entity.explosion", array($this, 'handler'), 15);

        $this->api->console->register("izone", "[iZone] Protect multiple zone", array($this, 'commands'));
        $this->api->console->alias("iz",    "izone");
        $this->api->console->alias("izc",   "izone create");
        $this->api->console->alias("izcm",  "izone create me");
        $this->api->console->alias("izd",   "izone delete");
        $this->api->console->alias("izag",  "izone addg");
        $this->api->console->alias("izdg",  "izone deleteg");
        $this->api->console->alias("izpg",  "izone permg");
        $this->api->console->alias("izco",  "izone coord");
        $this->api->console->alias("izh",   "izone help");
        $this->api->console->alias("coord", "izone coord");

        $this->api->schedule(10, array($this, "saveAll"), array(), true); // Auto Save Private Area 1/2 Second

        $this->configInit();
    }

    public function commands($cmd, $params, $issuer)
    {
        if($cmd === "izone")
        {
            switch(array_shift($params))
            {
                case 'create':
                    $data = array(count($params));

                    if($issuer instanceof Player && ($this->config->get("non-op-create") == true || $this->api->ban->isOp($issuer->username)))
                    {
                        for($i = 0; $i < $data[0]; $i++)
                        {
                            $data[] = array_shift($params);
                        }

                        if($data[0] < 1)
                        {
                            $i = intval($this->config->get('default-area-size'));
                            $pos1 =  array($issuer->entity->x - $i, $issuer->entity->y - $i, $issuer->entity->z - $i);
                            $pos2 =  array($issuer->entity->x + $i, $issuer->entity->y + $i, $issuer->entity->z + $i);

                            $this->areas[] = new PArea($this->api, $issuer->level->getName(), $pos1, $pos2, $issuer->username);
                            $issuer->sendChat($this->config->get('private-area-creation-msg'));
                        }
                        elseif($data[0] == 1)
                        {
                            if(is_numeric($data[1]) && intval($data[1]) > 0)
                            {

                                $i = intval($data[1]);
                                $pos1 =  array($issuer->entity->x - $i, $issuer->entity->y - $i, $issuer->entity->z - $i);
                                $pos2 =  array($issuer->entity->x + $i, $issuer->entity->y + $i, $issuer->entity->z + $i);

                                $this->areas[] = new PArea($this->api, $issuer->level->getName(), $pos1, $pos2, $issuer->username);
                                $issuer->sendChat($this->config->get('private-area-creation-msg'));
                            }
                            elseif(is_string($data[1]) && !empty($data[1]))
                            {
                                $i = $this->api->player->get($data[1]);
                                if( $i instanceof Player)
                                {
                                    $pos1 =  array($issuer->entity->x, $issuer->entity->y, $issuer->entity->z);
                                    $pos2 =  array($i->entity->x, $i->entity->y, $i->entity->z);

                                    $this->areas[] = new PArea($this->api, $issuer->level->getName(), $pos1, $pos2, $issuer->username);
                                    $issuer->sendChat($this->config->get('private-area-creation-msg'));
                                }
                            }
                            else
                            {
                                $issuer->sendChat("Usage: /{$cmd} create [int]");
                                $issuer->sendChat("Usage: /{$cmd} create [player]");
                            }
                        }
                        elseif($data[0] == 2)
                        {
                            $u1 = $this->api->player->get($$data[1]);

                            $i = $data[2];
                            if(is_numeric($i) && intval($i) > 0)
                            {
                                $i = intval($i);
                                $pos1 =  array($u1->entity->x - $i, $u1->entity->y - $i, $u1->entity->z - $i);
                                $pos2 =  array($u1->entity->x + $i, $u1->entity->y + $i, $u1->entity->z + $i);

                                $this->areas[] = new PArea($this->api, $issuer->level->getName(), $pos1, $pos2, $u1->username);
                                $u1->sendChat($this->config->get('private-area-creation-msg'));
                                return;
                            }
                            else
                            {
                                $u2 = $this->api->player->get($i);
                                if($u1 instanceof Player && $u2 instanceof Player)
                                {
                                    $pos1 =  array($u1->entity->x, $u1->entity->y, $u1->entity->z);
                                    $pos2 =  array($u2->entity->x, $u2->entity->y, $u2->entity->z);

                                    $this->areas[] = new PArea($this->api, $issuer->level->getName(), $pos1, $pos2, $u1->username);
                                    $u1->sendChat($this->config->get('private-area-creation-msg'));
                                }
                                return;
                            }

                            $issuer->sendChat("Usage: /{$cmd} create [player] [radius]");

                        }
                        elseif($data[0] == 3)
                        {
                            $x = intval($data[1]);
                            $y = intval($data[2]);
                            $z = intval($data[3]);

                            if($x  > 0 && $y > 0 && $z > 0)
                            {
                                $pos1 =  array($issuer->entity->x, $issuer->entity->y, $issuer->entity->z);
                                $pos2 =  array($x, $y, $z);

                                $this->areas[] = new PArea($this->api, $issuer->level->getName(), $pos1, $pos2, $issuer->username);
                                $issuer->sendChat($this->config->get('private-area-creation-msg'));
                            }
                            else
                            {
                                $issuer->sendChat("[iZone] Usage: /{$cmd} create [x] [y] [z]");
                            }
                        }
                        elseif($data[0] == 4)
                        {
                            $t = $this->api->player->get($data[1]);
                            if($t instanceof Player)
                            {
                                $x = intval($data[2]);
                                $y = intval($data[3]);
                                $z = intval($data[4]);

                                if($x > 0 && $y > 0 && $z > 0)
                                {
                                    $pos1 =  array($t->entity->x, $t->entity->y, $t->entity->z);
                                    $pos2 =  array($x, $y, $z);

                                    $this->areas[] = new PArea($this->api, $issuer->level->getName(), $pos1, $pos2, $t->username);
                                    $t->sendChat($this->config->get('private-area-creation-msg'));
                                }
                                else
                                {
                                    $issuer->sendChat("[iZone] Usage: /{$cmd} create [player] [x] [y] [z]");
                                }
                            }
                            else
                            {
                                $issuer->sendChat("[iZone] Usage: /{$cmd} create [player] [x] [y] [z]");
                            }
                        }
                        elseif($data[0] == 6)
                        {
                            $x1 = intval($data[1]);
                            $y1 = intval($data[2]);
                            $z1 = intval($data[3]);

                            $x2 = intval($data[4]);
                            $y2 = intval($data[5]);
                            $z2 = intval($data[6]);

                            if($x1  > 0 && $y1 > 0 && $z1 > 0 && $x2  > 0 && $y2 > 0 && $z2 > 0)
                            {
                                $pos1 =  array($x1, $y1, $z1);
                                $pos2 =  array($x2, $y2, $z2);

                                $this->areas[] = new PArea($this->api, $issuer->level->getName(), $pos1, $pos2, $issuer->username);
                                $issuer->sendChat($this->config->get('private-area-creation-msg'));
                            }
                            else
                            {
                                $issuer->sendChat("[iZone] Usage: /{$cmd} create [x1] [y1] [z1] [x2] [y2] [z2]");
                            }
                        }
                        elseif($data[0] == 7)
                        {
                            $owner = $data[1];
                            if($owner instanceof Player)
                            {
                                $x1 = intval($data[2]);
                                $y1 = intval($data[3]);
                                $z1 = intval($data[4]);

                                $x2 = intval($data[5]);
                                $y2 = intval($data[6]);
                                $z2 = intval($data[7]);

                                if($x1  > 0 && $y1 > 0 && $z1 > 0 && $x2  > 0 && $y2 > 0 && $z2 > 0)
                                {
                                    $pos1 =  array($x1, $y1, $z1);
                                    $pos2 =  array($x2, $y2, $z2);

                                    $this->areas[] = new PArea($this->api, $issuer->level->getName(), $pos1, $pos2, $owner->username);
                                    $owner->sendChat($this->config->get('private-area-creation-msg'));
                                }
                                else
                                {
                                    $issuer->sendChat("[iZone] Usage: /{$cmd} create [owner] [x1] [y1] [z1] [x2] [y2] [z2]");
                                }
                            }
                            else
                            {
                                $issuer->sendChat("[iZone] Usage: /{$cmd} create [owner] [x1] [y1] [z1] [x2] [y2] [z2]");
                            }
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
                        if($ps == 1)
                        {
                            $owner = array_shift($params);

                            $z = $this->getAreaID($owner);
                            if($z != null)
                            {
                                unset($this->areas[$z]);
                                $owner = $this->api->player->get($owner);

                                if($owner instanceof Player)
                                    $owner->sendChat($this->config->get("private-area-removed-msg"));
                                else
                                    $issuer->sendChat($this->config->get("private-area-removed-msg"));
                            }
                            elseif(strtolower($owner) == "all")
                            {
                                if($this->api->ban->isOp($issuer->username))
                                {
                                    unset($this->areas);
                                    $this->areas = array();
                                }
                            }
                            else
                            {
                                $issuer->sendChat("[iZone] Error occured!.");
                            }
                        }
                        elseif($ps == 3)
                        {
                            $x = intval(array_shift($params));
                            $y = intval(array_shift($params));
                            $z = intval(array_shift($params));

                            if($x  > 0 && $y > 0 && $z > 0)
                            {
                                $owner = null;
                                for($i = 0; $i < count($this->areas); $i++)
                                {
                                    $area = $this->areas[$i];
                                    if($area->x1 == $x && $area->y1 == $y && $area->z1 == $z)
                                    {
                                        $owner = $i;
                                        break;
                                    }
                                }

                                if($this->api->ban->isOp($issuer->username) || $this->areas[$owner]->getPerm($issuer->username) == OWNER_PERM)
                                {
                                    if($owner != null || isset($this->areas[$owner]))
                                    {
                                        unset($this->areas[$owner]);
                                    }
                                }
                                else
                                {
                                    $issuer->sendChat("[iZone] You can't execute this command");
                                }

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
                                $owner = null;
                                for($i = 0; $i < count($this->areas); $i++)
                                {
                                    $area = $this->areas[$i];
                                    if($area->x1 == $x1 && $area->y1 == $y1 && $area->z1 == $z1 && $area->x2 == $x2 && $area->y2 == $y2 && $area->z2 == $z2)
                                    {
                                        $owner = $i;
                                        break;
                                    }
                                }

                                if($this->api->ban->isOp($issuer->username) || $this->areas[$owner]->getPerm($issuer->username) == OWNER_PERM)
                                {
                                    if($owner != null || isset($this->areas[$owner]))
                                    {
                                        unset($this->areas[$owner]);
                                    }
                                }
                                else
                                {
                                    $issuer->sendChat("[iZone] You can't execute this command");
                                }
                            }
                            else
                            {
                                $issuer->sendChat("[iZone] Usage: /{$cmd} delete <x1> <y1> <z1> <x2> <y2> <z2>");
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
                            elseif(strtolower($owner) == "all")
                            {
                                unset($this->areas);
                                $this->areas = array();
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
                                console("[iZone] Usage: /{$cmd} delete [x] [y] [z]");
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
                                $issuer->sendChat("[iZone] Usage: /{$cmd} delete [x1] [y1] [z1] [x2] [y2] [z2]");
                            }
                        }
                    }
                    break;

                case "addg":
                    if($issuer instanceof Player)
                    {
                        if($this->getArea($issuer->username) != null)
                        {
                            $user = array_shift($params);
                            if(empty($user) || $user == null || strtolower($issuer->username) == $user)
                            {
                                $issuer->sendChat("[iZone] The player name cannot be empty");
                                return;
                            }

                            $user = $this->api->player->get($user);
                            if($user instanceof Player)
                            {
                                $this->getArea($issuer->username)->addGuest($user, array_shift($params), intval(array_shift($params)));
                                $issuer->sendChat("[iZone] The player has been added!");
                                return;
                            }
                            $issuer->sendChat("[iZone] The player is not online!");
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

                            $user = $this->api->player->get($user);
                            if(!$user instanceof Player)
                            {
                                $issuer->sendChat("The user don't exist or not is online!");
                                return;
                            }

                            foreach($this->areas as $area)
                            {
                                    if($area->getPerm($issuer->username) >= MOD_PERM)
                                    {
                                        $area->addGuest($user, array_shift($params), intval(array_shift($params)));
                                        $issuer->sendChat("[iZone] The player has been added!");
                                        return;
                                    }
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
                        if($this->getArea($issuer->username) != null)
                        {
                            $user = array_shift($params);
                            if(empty($user) || $user == null)
                            {
                                $issuer->sendChat("[iZone] The player name cannot be empty");
                                return;
                            }

                            $user = $this->api->player->get($user);
                            if(!$user instanceof Player)
                            {
                                $issuer->sendChat("The user don't exist or not is online!");
                                return;
                            }

                            $this->getArea($issuer->username)->deleteGuest($user);
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

                            $user = $this->api->player->get($user);
                            if(!$user instanceof Player)
                            {
                                $issuer->sendChat("The user don't exist or not is online!");
                                return;
                            }

                            foreach($this->areas as $area)
                            {
                                if($area->getPerm($issuer->username) >= MOD_PERM)
                                {
                                    $area->deleteGuest($user);
                                    $issuer->sendChat("[iZone] The player has been removed!");
                                    return;
                                }
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

                        if($this->getArea($issuer->username) != null)
                        {
                            $user = array_shift($params);
                            if(empty($user) || $user == null)
                            {
                                $issuer->sendChat("[iZone] The player name cannot be empty");
                                return;
                            }

                            $user = $this->api->player->get($user);
                            if(!$user instanceof Player)
                            {
                                $issuer->sendChat("The user don't exist or not is online!");
                                return;
                            }

                            $this->getArea($issuer->username)->setPerm($user, array_shift($params), intval(array_shift($params)));
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

                            $user = $this->api->player->get($user);
                            if(!$user instanceof Player)
                            {
                                $issuer->sendChat("The user don't exist or not is online!");
                                return;
                            }

                            foreach($this->areas as $area)
                            {
                                if($area->getPerm($issuer->username) >= MOD_PERM)
                                {
                                    $area->setPerm($user, array_shift($params), intval(array_shift($params)));
                                    $issuer->sendChat("[iZone] The permission of the player has been updated!");
                                    return;
                                }
                            }
                        }
                        $issuer->sendChat("[iZone] You don't have private area.");
                    }
                    else
                    {
                        console("[iZone] Run the command in-game.");
                    }
                    break;

                case 'coord':
                    if($issuer instanceof Player)
                    {
                        $issuer->sendChat("[iZone] Coord: X: {$issuer->entity->x} Y: {$issuer->entity->y} Z: {$issuer->entity->z}");
                    }
                    else
                    {
                        console("[iZone] Use this command in-game.");
                    }
                    break;

                case 'help':
                    if($issuer instanceof Player)
                    {
                        $issuer->sendChat("Usage: /{$cmd} <command> [parameters...]");
                        $issuer->sendChat("Usage: /izone create [int] or /izc [int]");
                        $issuer->sendChat("Usage: /izone create [player] or /izc [player]");
                        $issuer->sendChat("Usage: /izone create [player1] [player2] or /izc [player1] [player2]");
                        $issuer->sendChat("Usage: /izone create [x] [y] [z] or /izc [x] [y] [z]");
                        $issuer->sendChat("Usage: /izone create [player] [x] [y] [z] or /izc [player] [x] [y] [z]");
                        $issuer->sendChat("Usage: /izone create [x1] [y1] [z1] [x2] [y2] [z2] or /izc [x1] [y1] [z1] [x2] [y2] [z2]");
                        $issuer->sendChat("Usage: /izone delete [owner] or /izd [owner]");
                        $issuer->sendChat("Usage: /izone delete [x] [y] [z] or /izd [x] [y] [z]");
                        $issuer->sendChat("Usage: /izone delete [x1] [y1] [z1] [x2] [y2] [z2] or /izd [x1] [y1] [z1] [x2] [y2] [z2]");
                        $issuer->sendChat("Usage: /izone addg [player] or /izag [player]");
                        $issuer->sendChat("Usage: /izone addg [player] [rank] or /izag [player] [rank]");
                        $issuer->sendChat("Usage: /izone addg [player] [rank] [time] or /izag [player] [rank] [time]");
                        $issuer->sendChat("Usage: /izone deleteg [player] or /izdg [player]");
                        $issuer->sendChat("Usage: /izone permg [player] [rank] or /izpg [player] [rank]");
                        $issuer->sendChat("Usage: /izone permg [player] [rank] [time] or /izpg [player] [rank] [time]");
                        $issuer->sendChat("Usage: /izone coord or /izco or /coord");
                    }
                    else
                    {
                        console("Usage: /{$cmd} <command> [parameters...]");
                        console("Usage: /izone create [int] or /izc [int]");
                        console("Usage: /izone create [player] or /izc [player]");
                        console("Usage: /izone create [player1] [player2] or /izc [player1] [player2]");
                        console("Usage: /izone create [x] [y] [z] or /izc [x] [y] [z]");
                        console("Usage: /izone create [player] [x] [y] [z] or /izc [player] [x] [y] [z]");
                        console("Usage: /izone create [x1] [y1] [z1] [x2] [y2] [z2] or /izc [x1] [y1] [z1] [x2] [y2] [z2]");
                        console("Usage: /izone delete [owner] or /izd [owner]");
                        console("Usage: /izone delete [x] [y] [z] or /izd [x] [y] [z]");
                        console("Usage: /izone delete [x1] [y1] [z1] [x2] [y2] [z2] or /izd [x1] [y1] [z1] [x2] [y2] [z2]");
                        console("Usage: /izone addg [player] or /izag [player]");
                        console("Usage: /izone addg [player] [rank] or /izag [player] [rank]");
                        console("Usage: /izone addg [player] [rank] [time] or /izag [player] [rank] [time]");
                        console("Usage: /izone deleteg [player] or /izdg [player]");
                        console("Usage: /izone permg [player] [rank] or /izpg [player] [rank]");
                        console("Usage: /izone permg [player] [rank] [time] or /izpg [player] [rank] [time]");
                        console("Usage: /izone coord or /izco or /coord");
                    }
                    break;

            }
        }
    }

    public function handler(&$data, $event)
    {
        switch ($event) {

            case 'player.block.touch':

                if(!$this->api->ban->isOp($data['player']->username))
                {
                    if(count($this->areas) < 1)
                        return;

                    foreach($this->areas as $area)
                    {
                        if($area->isIn($data['target']->x, $data['target']->y, $data['target']->z) && ($area->level == $data['player']->level->getname()))
                        {
                            if($area->getPerm($data['player']) <= SEE_PERM)
                            {
                                if($data['item']->getID() == 323 && $data['type'] == "place")
                                {
                                    return;
                                }
                                $data['player']->eventHandler($this->config->get('private-area-msg'), "server.chat");
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
                    foreach($this->areas as  $area)
                    {
                        if($area->isOnRadius($data['source']->x, $data['source']->y, $data['source']->z, $radius) && $area->level == $data['level']->getName())
                        {
                            $k = $this->api->player->get($area->owner);
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
            "non-op-create"                 => false,
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
                "non-op-create"                 => false,
                "explosion-protection"          => true,
                "explosion-radius-protection"   => 8,
                "default-area-size"             => 10,
                "private-areas"                 => array(),
            ));
        }
        $list = $this->config->get("private-areas");
        if(is_array($list))
        {
            foreach($list as $area)
            {
                $area  = $this->fromString($area);
                $this->areas[] = $area;
            }
        }
    }

    public function saveAll()
    {
        $list = array();
        if(count($this->areas) > 0)
        {
            foreach($this->areas as $area)
            {
                $list[] = serialize($this->toString($area));
            }
        }
        $this->config->set('private-areas', $list);
        $this->config->save();
    }

    public function __destruct(){
        $this->saveAll();
    }

    public function fromString($string)
    {
        $area = unserialize($string);
        $area->api = $this->api;
        return $area;
    }

    public function toString($c)
    {
        $c->api = 1;
        return $c;
    }

    public function &getAreaID($owner)
    {
        for($i = 0; $i < count($this->areas); $i++)
        {
            if($this->areas[$i]->owner === $owner || $this->areas[$i]->getPerm($owner) == OWNER_PERM)
                return $i;
        }
        return null;
    }

    public function &getArea($owner)
    {
        for($i = 0; $i < count($this->areas); $i++)
        {
            if($this->areas[$i]->owner === $owner || $this->areas[$i]->getPerm($owner) == OWNER_PERM)
                return $this->areas[$i];
        }
        return null;
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

    function __construct(ServerAPI $api, $level, $pos1, $pos2, $user)
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
            $this->level = $level;
        else
            $this->level = $this->api->level->getDefault()->getName();
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


    public function setPerm(Player $user, $perm, $time = 0, $reset = false)
    {
        $perm = $this->getPermCode($perm);
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
                $this->api->schedule(20 * $time, array($this, "deleteGuest"), $user, false);
            }
            else
                $this->users[$user->username] = $perm;
        }
        $user->sendChat("[iZone] Your permission on a private area to be been changed");
    }

    public function getPerm(Player $user)
    {
        if(array_key_exists($user->username, $this->users))
        {
            return $this->users[$user->username];
        }
        else
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

    public function addGuest(Player $user, $perm, $time = 0)
    {
        $perm = $this->getPermCode($perm);
        $this->users[$user->username] = $perm;
        $user->sendChat("[iZone] You have been added to a private area");

        if($time > 0)
        {
            $this->api->schedule(20 * $time, array($this, "deleteGuest"), $user, false);
        }

    }

    public function deleteGuest(Player $user)
    {
        unset($this->users[$user->username]);
        $user->sendChat("[iZone] You have been removed from a private area");
    }

}