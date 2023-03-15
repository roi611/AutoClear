<?php
    
namespace roi611\clear;
    
use pocketmine\plugin\PluginBase;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\player\Player;
use pocketmine\Server;

use pocketmine\utils\Config;

use pocketmine\world\World;

use pocketmine\entity\object\ItemEntity;

use pocketmine\nbt\tag\CompoundTag;

use pocketmine\scheduler\Task;
    
class Main extends PluginBase{
    
    public function onEnable():void{

        $config = new Config($this->getDataFolder()."Config.yml",Config::YAML,array(
            "消去間隔(秒)" => 300,
            "？秒前から全て通知する" => 10,
        ));

        date_default_timezone_set('Asia/Tokyo');
        $this->getScheduler()->scheduleRepeatingTask(new Run($config, $this), 20);

    }
    
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{

        if(isset($args[0])){
            if(Server::getInstance()->getWorldManager()->isWorldGenerated($args[0]) === false || Server::getInstance()->getWorldManager()->isWorldLoaded($args[0]) === false){
                $sender->sendMessage("ワールド: §e{$args[0]}§r は存在しないか、読み込まれていません。");
                return true;
            } else {
                $world = Server::getInstance()->getWorldManager()->getWorldByName($args[0]);
            }
        } else {
            $world = Server::getInstance()->getWorldManager()->getWorlds();
        }

        $this->remove($world);

        return true;
        
    }

    public function remove(World|array $world){

        if($world instanceof World){

            $entities = $world->getEntities();
            $count = 0;
            foreach($entities as $entity){
                if($entity instanceof ItemEntity){

                    $drop = $entity->getItem();
                    $nbt = $drop->getNamedTag() ?? new CompoundTag();
                    $data = $nbt->getTag('CanAutoClear');

                    if($data === null){
                        $entity->kill();
                        $count++;
                    } else if($data->getValue() === 1){
                        $entity->kill();
                        $count++;
                    }

                }
            }

            foreach($world->getPlayers() as $player){
                $player->sendPopup("§cドロップアイテムが §e {$count} 個 §c削除されました！");
            }

        } else {

            $count = 0;
            foreach($world as $w){
                
                $entities = $w->getEntities();
                foreach($entities as $entity){
                    if($entity instanceof ItemEntity){

                        $drop = $entity->getItem();
                        $nbt = $drop->getNamedTag() ?? new CompoundTag();
                        $data = $nbt->getTag('CanAutoClear');
                        if($data === null){
                            $entity->kill();
                            $count++;
                        } else if($data->getValue() === 1){
                            $entity->kill();
                            $count++;
                        }
                        
                    }
                }

            }
            
            Server::getInstance()->broadcastPopup("§cドロップアイテムが §e {$count} 個 §c削除されました！");

        }
    }

}



class Run extends Task{
    
    private $count = 0;
    
    function __construct(private Config $config, private Main $main){
        $this->config = $config;
        $this->main = $main;
	}

    function onRun():void{

        $this->count++;

        if($this->count >= (int)$this->config->get("消去間隔(秒)")){
            $this->count = 0;
            $this->main->remove(Server::getInstance()->getWorldManager()->getWorlds());
        } else if(($re = (int)$this->config->get("消去間隔(秒)") - $this->count) <= $this->config->get("？秒前から全て通知する")){
            Server::getInstance()->broadcastPopup("§cアイテム削除まであと§e {$re} 秒§c です。");
        }

    }

}