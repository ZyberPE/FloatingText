<?php

namespace FloatingText;

use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\world\Position;
use pocketmine\utils\Config;
use pocketmine\entity\Location;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;

class Main extends PluginBase{

    private Config $data;
    private array $entities = [];

    public function onEnable(): void{
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();

        $this->data = new Config($this->getDataFolder() . "config.yml", Config::YAML);

        $this->loadTexts();
    }

    private function loadTexts(): void{
        foreach($this->data->get("texts", []) as $id => $textData){

            $world = $this->getServer()->getWorldManager()->getWorldByName($textData["world"]);
            if($world === null) continue;

            $pos = new Position(
                $textData["x"],
                $textData["y"],
                $textData["z"],
                $world
            );

            $this->spawnText($id, $pos, $textData["text"]);
        }
    }

    private function spawnText(string $id, Position $pos, string $text): void{

        $text = str_replace("/n", "\n", $text);
        $text = str_replace("&", "§", $text);

        $nbt = new CompoundTag();
        $loc = new Location($pos->x, $pos->y, $pos->z, $pos->getWorld(), 0, 0);

        $entity = new class($loc, $nbt) extends Entity{
            protected function getInitialSizeInfo(): \pocketmine\entity\EntitySizeInfo{
                return new \pocketmine\entity\EntitySizeInfo(0.01, 0.01);
            }

            public function getName(): string{
                return "FloatingText";
            }
        };

        $entity->setNameTag($text);
        $entity->setNameTagAlwaysVisible();
        $entity->setNameTagVisible();
        $entity->spawnToAll();

        $this->entities[$id] = $entity;
    }

    private function saveText(string $id, Position $pos, string $text): void{

        $data = $this->data->get("texts");
        $data[$id] = [
            "x" => $pos->x,
            "y" => $pos->y,
            "z" => $pos->z,
            "world" => $pos->getWorld()->getFolderName(),
            "text" => $text
        ];

        $this->data->set("texts", $data);
        $this->data->save();
    }

    private function removeText(string $id): void{

        if(isset($this->entities[$id])){
            $this->entities[$id]->flagForDespawn();
            unset($this->entities[$id]);
        }

        $data = $this->data->get("texts");
        unset($data[$id]);

        $this->data->set("texts", $data);
        $this->data->save();
    }

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool{

        if(!$sender instanceof Player){
            $sender->sendMessage("Run in game");
            return true;
        }

        if(!isset($args[0])){
            $sender->sendMessage("§e/ft create <id> <text>");
            $sender->sendMessage("§e/ft remove <id>");
            $sender->sendMessage("§e/ft move <id>");
            $sender->sendMessage("§e/ft list");
            return true;
        }

        switch($args[0]){

            case "create":

                if(!isset($args[1])) return false;

                $id = $args[1];
                $text = implode(" ", array_slice($args, 2));

                $pos = $sender->getPosition();

                $this->spawnText($id, $pos, $text);
                $this->saveText($id, $pos, $text);

                $sender->sendMessage("§aFloating text created");
            break;

            case "remove":

                if(!isset($args[1])) return false;

                $this->removeText($args[1]);
                $sender->sendMessage("§cFloating text removed");
            break;

            case "move":

                if(!isset($args[1])) return false;

                $id = $args[1];

                if(!isset($this->entities[$id])){
                    $sender->sendMessage("§cText not found");
                    return true;
                }

                $entity = $this->entities[$id];
                $pos = $sender->getPosition();

                $entity->teleport($pos);

                $texts = $this->data->get("texts");
                $texts[$id]["x"] = $pos->x;
                $texts[$id]["y"] = $pos->y;
                $texts[$id]["z"] = $pos->z;

                $this->data->set("texts", $texts);
                $this->data->save();

                $sender->sendMessage("§aFloating text moved");
            break;

            case "list":

                foreach(array_keys($this->data->get("texts")) as $id){
                    $sender->sendMessage("§e- " . $id);
                }

            break;
        }

        return true;
    }
}
