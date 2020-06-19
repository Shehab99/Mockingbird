<?php

/*


$$\      $$\                     $$\       $$\                     $$\       $$\                 $$\ 
$$$\    $$$ |                    $$ |      \__|                    $$ |      \__|                $$ |
$$$$\  $$$$ | $$$$$$\   $$$$$$$\ $$ |  $$\ $$\ $$$$$$$\   $$$$$$\  $$$$$$$\  $$\  $$$$$$\   $$$$$$$ |
$$\$$\$$ $$ |$$  __$$\ $$  _____|$$ | $$  |$$ |$$  __$$\ $$  __$$\ $$  __$$\ $$ |$$  __$$\ $$  __$$ |
$$ \$$$  $$ |$$ /  $$ |$$ /      $$$$$$  / $$ |$$ |  $$ |$$ /  $$ |$$ |  $$ |$$ |$$ |  \__|$$ /  $$ |
$$ |\$  /$$ |$$ |  $$ |$$ |      $$  _$$<  $$ |$$ |  $$ |$$ |  $$ |$$ |  $$ |$$ |$$ |      $$ |  $$ |
$$ | \_/ $$ |\$$$$$$  |\$$$$$$$\ $$ | \$$\ $$ |$$ |  $$ |\$$$$$$$ |$$$$$$$  |$$ |$$ |      \$$$$$$$ |
\__|     \__| \______/  \_______|\__|  \__|\__|\__|  \__| \____$$ |\_______/ \__|\__|       \_______|
                                                         $$\   $$ |                                  
                                                         \$$$$$$  |                                  
                                                          \______/      
~ Made by @ethaniccc idot </3
Github: https://www.github.com/ethaniccc                             
*/ 

namespace ethaniccc\Mockingbird\cheat;

use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\plugin\Plugin;
use pocketmine\event\Event;
use pocketmine\utils\TextFormat;

class Cheat implements Listener{

    private $cheatName;
    private $cheatType;
    private $enabled;

    private $plugin;

    public function __construct(Plugin $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        $this->cheatName = $cheatName;
        $this->cheatType = $cheatType;
        $this->enabled = $enabled;
        $this->plugin = $plugin;
    }

    public function getName() : string{
        return $this->cheatName;
    }

    public function getType() : string{
        return $this->cheatType;
    }

    public function isEnabled() : bool{
        return $this->enabled;
    }

    public function getCurrentViolations(string $name) : int{
        $database = $this->getPlugin()->getDatabase();
        $currentViolations = $database->query("SELECT * FROM cheatData WHERE playerName = '$name'");
        $result = $currentViolations->fetchArray(SQLITE3_ASSOC);
        if(empty($result)){
            $newData = $database->prepare("INSERT OR REPLACE INTO cheatData (playerName, violations) VALUES (:playerName, :violations);");
            $newData->bindValue(":playerName", $name);
            $newData->bindValue(":violations", 0);
            $newData->execute();
        }
        return empty($result) ? 0 : $result["violations"];
    }

    protected function getPlugin() : Plugin{
        return $this->plugin;
    }

    protected function getServer() : Server{
        return Server::getInstance();
    }

    protected function addViolation(string $name) : void{
        $database = $this->getPlugin()->getDatabase();
        $currentViolations = $this->getCurrentViolations($name);
        $currentViolations++;
        $newData = $database->prepare("INSERT OR REPLACE INTO cheatData (playerName, violations) VALUES (:playerName, :violations)");
        $newData->bindValue(":playerName", $name);
        $newData->bindValue(":violations", $currentViolations);
        $newData->execute();
    }

    protected function notifyStaff(string $name, string $cheat, array $data) : void{
        foreach($this->getServer()->getOnlinePlayers() as $player){
            if($player->hasPermission($this->getPlugin()->getConfig()->get("alert_permission"))){
                $dataReport = TextFormat::DARK_RED . "[";
                foreach($data as $dataName => $info){
                    if(end($data) !== $info) $dataReport .= TextFormat::GRAY . $dataName . ": " . TextFormat::RED . $info . TextFormat::DARK_RED . " | ";
                    else $dataReport .= TextFormat::GRAY . $dataName . ": " . TextFormat::RED . $info;
                }
                $dataReport .= TextFormat::DARK_RED . "]";
                $player->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RESET . TextFormat::RED . $name . TextFormat::GRAY . " has failed the check for " . TextFormat::RED . $cheat . TextFormat::RESET . " $dataReport");
            }
        }
    }

    protected function suppress(Event $event) : bool{
        if($event instanceof \pocketmine\event\Cancellable){
            $event->setCancelled();
            return true;
        } else {
            return false;
        }
    }

}