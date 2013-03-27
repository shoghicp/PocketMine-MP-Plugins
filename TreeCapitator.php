<?php
 
/*
__PocketMine Plugin__
name=TreeCapitator
description=Break trees faster breaking the lowest trunk
version=0.0.4
apiversion=5
author=shoghicp
class=TreeCapitator
*/
 
 
class TreeCapitator implements Plugin{
        private $api, $path, $config, $id, $equip;
        public function __construct(ServerAPI $api, $server = false){
                $this->api = $api;
        }
       
        public function init(){
                $this->api->addHandler("player.block.break", array($this, "handle"), 2);
          
               	$this->createConfig();
                
                

        }
       
        public function __destruct(){
       
        }
       
        public function handle(&$data, $event)
        {
                switch($event)
                {
                        case "player.block.break":

                                $this->equip = $this->api->player->get($data['player'])->equipment[0];
                                $this->config = $this->readConfig($this->path);
                                if($this->config["need-item"] === true)
                                {
                                	$this->id = explode(";", $this->config["ItemsID"]);
                                	if(in_array($this->equip, $this->id)) 
                                	{          
                                			if($this->config["break-leaves"] === true)
                                			{
                                				$this->DestroyWith($data);
                                			}
                                			else
                                			{
                                				$this->Destroy($data);
                                			}

                                			break;
                                	}
                                	
                                }
                                else
                                {
                                	if($this->config["break-leaves"] === true)
                                	{
                                		$this->DestroyWith($data);
                                	}
                                	else
                                	{
                                		$this->Destroy($data);
                                	}
                                }
                                break;
                }
        }

        public function createConfig()
        {
                $this->path = $this->api->plugin->createConfig($this, array(
                                "ItemsID" => "258;271;275;279;286",
                                "need-item" => true,
                                "break-leaves" => true,
                        ));
        }

        public function readConfig($path)
        {
                return $this->api->plugin->readYAML($path."config.yml");
        }

        public function DestroyWith($data)
        {
        	$block = $this->api->level->getBlock($data["x"], $data["y"], $data["z"]);
                if($block[0] === 17)
                {
                        $down = $this->api->level->getBlockFace($block, 0);
                        if($down[0] !== 17)
                        {
                                for($y = $block[2][1]; $y <= ($block[2][1] + 10); ++$y)
                                {
                                        for($x = $block[2][0] - 4; $x <= ($block[2][0] + 4); ++$x)
                                        {
                                        	for($z = $block[2][2] - 4; $z <= ($block[2][2] + 4); ++$z)
                                                {
                                                        $b = $this->api->level->getBlock($x, $y, $z);
                                                        if($b[0] === 17 or $b[0] === 18)
                                                        {
                                                       		$this->api->trigger("player.block.break", array(
                                                                "x" => $x,
                                                                "y" => $y,
                                                                "z" => $z,
                                                                "eid" => $data["eid"],
                                                                 ));
                                                            $num = rand(0, 10);
                                                            if($num === 7 || $num === 4)
                                                            {
                                                                $this->api->block->drop(new Vector3($x, $y, $z), new Item(6));
                                                            }     
                                                        }	
                                                }                        
                                        }
                               	}
                                
                                $this->api->block->drop(new Vector3($block[2][0], $block[2][1], $block[2][2]), new Item($block[0], $block[1]);
                                $this->api->trigger("server.chat", $data['player']." used TreeCapitator");
                                return false;
                       	}
                }
       	}
       	public function Destroy($data)
       	{
       		$block = $this->api->level->getBlock($data["x"], $data["y"], $data["z"]);
                if($block[0] === 17)
                {
                        $down = $this->api->level->getBlockFace($block, 0);
                        if($down[0] !== 17)
                        {
                                for($y = $block[2][1]; $y < 128; ++$y)
                                {
                                        $b = $this->api->level->getBlock($block[2][0], $y, $block[2][2]);
                                        if($b[0] !== 17)
                                        {
                                                break;
                                        }
                                                       
                                        $this->api->trigger("player.block.break", array(
                                                "x" => $block[2][0],
                                                "y" => $y,
                                                "z" => $block[2][2],
                                                "eid" => $data["eid"],
                                                ));    
                                }
                                $this->api->block->drop(new Vector3($block[2][0], $block[2][1], $block[2][2]), new Item($block[0], $block[1]);
                                $this->api->trigger("server.chat", $data['player']." used TreeCapitator");
                                return false;
                        }
               
       		}
       	}
 
}
?>