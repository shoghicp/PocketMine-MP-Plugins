<?php
 
/*
__PocketMine Plugin__
name=TrustPlayer
description=You can't place or break anithing if the admin don't accept you on the server.
version=0.0.3
apiversion=5
author=InusualZ
class=TrustPlayer
*/
 
class TrustPlayer implements Plugin{
    private $api, $path, $conf, $list, $sqli;
 
    public function __construct(ServerAPI $api, $server = false){
        $this->api  = $api;
    }
     
    public function init(){                    
        $this->api->addhandler('player.block.touch', array($this, 'handler'), 15);
        $this->createConfig();
        $this->api->console->register('trust', '[TrustPlayer] Trust a player.', array($this, 'command'));
        $this->api->console->register('detrust', '[TrustPlayer] Destrust a player.', array($this, 'command'));
        $this->readConfig();
       
    }
 
    public function __destruct()
    {
        $this->sqli->close();
    }
 
    public function handler(&$data, $event) 
    {
        switch ($event) 
        {
            case "player.block.touch":
                $username = $data['player'];
                $this->readConfig();
                if (!$this->usernameExist($username)) 
                {
                    if ($this->conf['send-msg'] === true) 
                    {
                        switch ($data["type"]) 
                        {
                            case "place":
                                $this->api->player->get($data["player"])->eventHandler($this->conf['msg-place'], "server.chat");
                                 break;
                            case "break":
                                $this->api->player->get($data["player"])->eventHandler($this->conf['msg-break'], "server.chat");
                                break;
                            case "pickup":
                                $this->api->player->get($data["player"])->eventHandler($this->conf['msg-pickup'], "server.chat");
                                break;
                            case "active":
                                $this->api->player->get($data["player"])->eventHandler($this->conf['msg-default'], "server.chat");
                                break;
                            }
                    }                        
                    return false;
                }
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
 
   
    public function createConfig() {
        $default = array(
            "user-list"     => ";",
            "send-msg"      => true,
            "msg-break"     => "[TrustPlayer] You can't break block, you are not a trust player.",
            "msg-place"     => "[TrustPlayer] You can't place block, you are not a trust payer.",
            "msg-pickup"    => "[TrustPlayer] You can't pickup anything, you are not a trust payer.",
            "msg-default"   => "[TrustPlayer] You are not a trust player.",
         );
        $this->path = $this->api->plugin->createConfig($this, $default);
        $this->conf = $this->readConfig();
    }
 
    public function readConfig() {
        return $this->api->plugin->readYAML($this->path . "config.yml");
    }
 
    public function writeConfig($data) 
    {
        $this->api->plugin->writeYAML($this->path."config.yml", $data);
        
    }


    public function trust($username) 
    {
        $this->conf = $this->readConfig();
        if (!in_array($username, explode(';', $config['user-list']))) 
        {
            $userli = $config['user-list'] . $username . ";";
            $this->conf['user-list'] = $userli;
            if ($this->writeConfig($this->conf)) 
            {
                console('The username has been added to the list.');
            } 
            else 
            {
                console("The username can't be added to the list.");
            }
        } 
        else 
        {
            console('The username alredy exist on the list.');
        }
        $this->conf = $this->readConfig();
    }

    public function detrust($username) 
    {
        $this->conf = $this->readConfig();
        $exp = explode(';', $this->conf['user-list']);
        $key = array_search($username, $exp);
        unset($exp[$key]);
        $this->conf['user-list'] = implode(';', $exp);
        $this->writeConfig($this->conf);
        $this->conf = $this->readConfig();
    }

    public function usernameExist($name)
    {
        $this->conf = $this->readConfig();
        $exp = explode(';', $this->conf['user-list']);
        return in_array($name, $exp);
    }


}
?>
