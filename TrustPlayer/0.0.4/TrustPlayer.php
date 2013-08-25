<?php
 
/*
__PocketMine Plugin__
name=TrustPlayer
description=You can't place or break anithing if the admin don't accept you on the server.
version=0.0.4
apiversion=8,9
author=InusualZ
class=TrustPlayer
*/
 
class TrustPlayer implements Plugin{
    private $api, $path, $config;
 
    public function __construct(ServerAPI $api, $server = false){
        $this->api  = $api;
    }
     
    public function init(){                    
        $this->api->addhandler('player.block.touch', array($this, 'handler'), 15);
        $this->config = new Config($this->api->plugin->configPath($this)."config.yml", CONFIG_YAML, array(
            "user-list"         => array(),
            "send-msg"          => true,
            "msg-break"     => "[TrustPlayer] You can't break block, you are not a trust player.",
            "msg-place"     => "[TrustPlayer] You can't place block, you are not a trust player.",
            "msg-pickup"    => "[TrustPlayer] You can't pickup anything, you are not a trust player.",
            "msg-default"   => "[TrustPlayer] You are not a trust player."
         ));
        $this->api->console->register('trust', '[TrustPlayer] Trust a player.', array($this, 'command'));
        $this->api->console->register('detrust', '[TrustPlayer] Destrust a player.', array($this, 'command'));
    }
 
    public function __destruct()
    {
    }
 
    public function handler(&$data, $event) 
    {
        switch ($event) 
        {
            case "player.block.touch":
                $this->config->reload();
                $username = $data['player']->username;
                if (!$this->usernameExist($username)) 
                {
                    if ($this->config->get('send-msg') === true) 
                    {
                        switch ($data["type"]) 
                        {
                            case "place":
                                $data["player"]->eventHandler($this->config->get('msg-place'), "server.chat");
                                 break;
                            case "break":
                                $data["player"]->eventHandler($this->config->get('msg-break'), "server.chat");
                                break;
                            case "pickup":
                                $data["player"]->eventHandler($this->config->get('msg-pickup'), "server.chat");
                                break;
                            case "active":
                                $data["player"]->seventHandler($this->config->get('msg-default'), "server.chat");
                                break;
                            }
                    }                        
                    return false;
                }
                return true;
                break;
        }
    }        
 
    public function command($cmd, $params)
    {
        switch ($cmd)
        {
            case "trust":
                $username = array_shift($params);
                if(empty($username) || $username == NULL)
                {
                    console('[INFO] Usage: /trust <player>');
                }
                else
                {
                    if($this->usernameExist($username))
                    {
                        console('[TrustPlayer] Username already exist.');
                    }
                    else
                    {
                        $this->trust($username);
                    }
                    
                }
            break;
            
            case "detrust":
                $username = array_shift($params);
                if(empty($username) || $username == NULL)
                {
                    console('[INFO] Usage: /detrust <player>');
                }
                else
                {
                    if($this->usernameExist($username))
                    {
                        $this->detrust($username);
                        console('[TrustPlayer] The username has been deleted.');
                    }
                    else
                    {
                        console("[TrustPlayer] Username don't exist");
                    }
                }
            break;
        }
    }
    public function trust($username) 
    {
        $this->config->reload();
        if (!in_array($username, $this->config->get("user-list"))) 
        {
            $c = $this->config->get("user-list");
            array_push($c, $username);
            $this->config->set("user-list", $c);
            $this->config->save();
            $this->config->reload();
            return;
        } 
        console('The username alredy exist on the list.');

    }

    public function detrust($username) 
    {
        $this->config->reload();
        $c = $this->config->get("user-list");
        $key = array_search($username, $c);
        unset($c[$key]);
        $this->config->set("user-list", $c);
        $this->config->save();
        $this->config->reload();

    }

    public function usernameExist($name)
    {
        $this->config->reload();
        return in_array($name, $this->config->get("user-list"));
    }
}
?>