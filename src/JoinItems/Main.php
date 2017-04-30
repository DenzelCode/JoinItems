<?php

    /*
     * Plugin created by Denzel Code
     */

    namespace JoinItems;
    
    // Plugin
    use pocketmine\plugin\PluginBase;
    use pocketmine\utils\TextFormat as Color;
    use pocketmine\utils\Config;
    // Basic
    use pocketmine\Player;
    use pocketmine\tile\Chest;
    use pocketmine\item\Item;
    use pocketmine\item\enchantment\Enchantment;
    // Commands
    use pocketmine\command\Command;
    use pocketmine\command\CommandSender;
    use pocketmine\command\CommandExecuter;
    use pocketmine\command\ConsoleCommandSender;
    // Events
    use pocketmine\event\Listener;
    use pocketmine\event\player\PlayerJoinEvent;
    use pocketmine\event\player\PlayerItemHeldEvent;
    use pocketmine\event\player\PlayerInteractEvent;
    use pocketmine\event\player\PlayerRespawnEvent;
    use pocketmine\event\block\BlockPlaceEvent;
    use pocketmine\event\player\PlayerDropItemEvent;
    use pocketmine\event\block\BlockBreakEvent;

    /**
     * Main Class
     *
     * @author Denzel Code
     */
    class Main extends PluginBase implements Listener {
        
        private $prefix;
        private $config;
        private $defaultConfig;
        public $configItems;
        private $allowPlace;
        private $principalCommand;
        private $shortCommand;
        
        public function onLoad() {
            $this->getLogger()->info(Color::BLUE . 'Loading plugin');
        }
        
        public function onEnable() {
            if (!is_dir($this->getDataFolder())) {
                @mkdir($this->getDataFolder());
            }
            
            $this->prefix = Color::GRAY . '[' . Color::BLUE . 'Join' . Color::RED . 'Items' . Color::GRAY . ']' . Color::RESET . ' ';
            $this->principalCommand = 'joinitems';
            $this->shortCommand = 'ji';
            $this->config = new Config($this->getDataFolder() . 'config.json', Config::JSON);
            $this->config->save();
            $this->allowPlace = $this->config->get('allowedPlayersPlace');
            if ($this->allowPlace == null) {
                $this->allowPlace = array();
            }
            $this->defaultConfig = $this->config->get('defaultConfig');
            $this->configItems = $this->config->get('items');
            
            $defaultItems = array (
                'example' => [
                    'item' => 151,
                    'title' => "&l&fExample Item&r",
                    'command' => 'joinitems',
                    'slot' => 0,
                    'sound' => false,
                    'joinSpawnShow' => true
                ]
            );
            
            $defaultConfig = array (
                'onJoin' => true,
                'onRespawn' => false
            );
            
            $this->setConfig('defaultConfig', $defaultConfig);
            $this->setConfig('items', $defaultItems);
            
            $this->getServer()->getPluginManager()->registerEvents($this ,$this);
            $this->getLogger()->info(Color::GREEN . 'Plugin enabled');
        }
        
        public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
            switch ($command->getName()) {
                case $this->principalCommand:
                    if ($sender instanceof Player) {
                        if ($args[0] == 'toggleme') {
                            if ($sender->hasPermission('joinitems.perm') || $sender->hasPermission('joinitems.allowplace')) {
                                if (!$args[1]) {
                                    if (!in_array($sender->getName(), $this->allowPlace)) {
                                        $sender->sendMessage($this->prefix . Color::RED . 'JoinBlock desactivado');
                                        $sender->getInventory()->clearAll();
                                        array_push($this->allowPlace, $sender->getName());
                                        $this->config->set('allowedPlayersPlace', $this->allowPlace);
                                    } else {
                                        foreach (array_keys($this->allowPlace, $sender->getName()) as $key) {
                                            unset($this->allowPlace[$key]);
                                        }
                                        $this->config->set('allowedPlayersPlace', $this->allowPlace);
                                        $this->setItems($sender, ['joinSpawnShow' => true]);
                                        $sender->sendMessage($this->prefix . Color::GREEN . 'JoinBlock activado');
                                    }
                                } else {
                                    $player = $this->getServer()->getPlayer($args[1]);
                                    if ($player) {
                                        if (!in_array($player->getName(), $this->allowPlace)) {
                                            $sender->sendMessage($this->prefix . Color::RED . 'JoinBlock desactivado para ' . Color::YELLOW . $player->getName());
                                            array_push($this->allowPlace, $player->getName());
                                            $this->config->set('allowedPlayersPlace', $this->allowPlace);
                                        } else {
                                            foreach (array_keys($this->allowPlace, $player->getName()) as $key) {
                                                unset($this->allowPlace[$key]);
                                            }
                                            $this->config->set('allowedPlayersPlace', $this->allowPlace);
                                            $sender->sendMessage($this->prefix . Color::GREEN . 'JoinBlock activado para ' . Color::YELLOW . $player->getName());
                                        }
                                    } else {
                                        $sender->sendMessage($this->prefix . Color::RED . 'Usuario no encontrado');
                                    }
                                }
                                $this->config->save();
                            } else {
                                $sender->sendMessage($this->prefix . Color::RED . 'No tienes permiso para utilizar este comando');
                            }
                        } else if ($args[0] == 'help') {
                            $sender->sendMessage($this->prefix . Color::GREEN . "Comandos disponibles: \n" . Color::RESET . "- /" . $this->principalCommand . " help -> Lista de comandos\n" . "- /" . $this->principalCommand . " toggleme [player] -> Desabilitar/habilitar JoinBlock\n" . "- /" . $this->principalCommand . " fill -> Llenar inventario\n" . "- /" . $this->principalCommand . " unfill -> Vaciar inventario\n");
                        } else if ($args[0] == 'fill') {
                            $this->setItems($sender, ['joinSpawnShow' => true]);
                        } else if ($args[0] == 'unfill') {
                            $this->removeItems($sender, ['joinSpawnShow' => true]);
                        } else {
                            $sender->sendMessage($this->prefix . Color::RED . 'Utilize: ' . Color::GREEN . '/' . $this->principalCommand . 'help' . Color::RED . ' para ver la lista de comandos');
                        }
                    } else {
                        $sender->sendMessage($this->prefix . Color::RED . 'Ejecuta el comando desde el juego');
                    }
                    break;
                case $this->shortCommand:
                    if ($sender instanceof Player) {
                        if ($args[0] == 'toggleme') {
                            if ($sender->hasPermission('joinitems.perm') || $sender->hasPermission('joinitems.allowplace')) {
                                if (!$args[1]) {
                                    if (!in_array($sender->getName(), $this->allowPlace)) {
                                        $sender->sendMessage($this->prefix . Color::RED . 'JoinBlock desactivado');
                                        $sender->getInventory()->clearAll();
                                        array_push($this->allowPlace, $sender->getName());
                                        $this->config->set('allowedPlayersPlace', $this->allowPlace);
                                    } else {
                                        foreach (array_keys($this->allowPlace, $sender->getName()) as $key) {
                                            unset($this->allowPlace[$key]);
                                        }
                                        $this->config->set('allowedPlayersPlace', $this->allowPlace);
                                        $this->setItems($sender, ['joinSpawnShow' => true]);
                                        $sender->sendMessage($this->prefix . Color::GREEN . 'JoinBlock activado');
                                    }
                                } else {
                                    $player = $this->getServer()->getPlayer($args[1]);
                                    if ($player) {
                                        if (!in_array($player->getName(), $this->allowPlace)) {
                                            $sender->sendMessage($this->prefix . Color::RED . 'JoinBlock desactivado para ' . Color::YELLOW . $player->getName());
                                            array_push($this->allowPlace, $player->getName());
                                            $this->config->set('allowedPlayersPlace', $this->allowPlace);
                                        } else {
                                            foreach (array_keys($this->allowPlace, $player->getName()) as $key) {
                                                unset($this->allowPlace[$key]);
                                            }
                                            $this->config->set('allowedPlayersPlace', $this->allowPlace);
                                            $sender->sendMessage($this->prefix . Color::GREEN . 'JoinBlock activado para ' . Color::YELLOW . $player->getName());
                                        }
                                    } else {
                                        $sender->sendMessage($this->prefix . Color::RED . 'Usuario no encontrado');
                                    }
                                }
                                $this->config->save();
                            } else {
                                $sender->sendMessage($this->prefix . Color::RED . 'No tienes permiso para utilizar este comando');
                            }
                        } else if ($args[0] == 'help') {
                            $sender->sendMessage($this->prefix . Color::GREEN . "Comandos disponibles: \n" . Color::RESET . "- /" . $this->principalCommand . " help -> Lista de comandos\n" . "- /" . $this->principalCommand . " toggleme [player] -> Desabilitar/habilitar JoinBlock\n" . "- /" . $this->principalCommand . " fill -> Llenar inventario\n" . "- /" . $this->principalCommand . " unfill -> Vaciar inventario\n");
                        } else if ($args[0] == 'fill') {
                            $this->setItems($sender, ['joinSpawnShow' => true]);
                        } else if ($args[0] == 'unfill') {
                            $this->removeItems($sender, ['joinSpawnShow' => true]);
                        } else {
                            $sender->sendMessage($this->prefix . Color::RED . 'Utilize: ' . Color::GREEN . '/' . $this->principalCommand . 'help' . Color::RED . ' para ver la lista de comandos');
                        }
                    } else {
                        $sender->sendMessage($this->prefix . Color::RED . 'Ejecuta el comando desde el juego');
                    }
                    break;
            }
        }
        
        public function onJoinEvent(PlayerJoinEvent $event) {
            if ($this->defaultConfig['onJoin'] == true) {
                $player = $event->getPlayer();
                $player->getInventory()->clearAll();
                $this->setItems($player, ['joinSpawnShow' => true]);
            }
        }
        
        public function setConfig($name, $value) {
            $configName = $this->config->get($name);
            if (empty($configName)) {
                $this->config->set($name, $value);
                $this->config->save();
            } else {
               // Code
            }
        }
        
        public function removeItems(Player $player, array $itemData) {
            if ($itemData['name']) {
                foreach ($this->configItems as $key => $value) {
                    if ($itemData['name'] == $key) {
                        if (!$itemData['joinSpawnShow']) {
                            if (!$value['joinSpawnShow']) {
                                $item = new Item($value['item']);
                                $player->getInventory()->remove($item);
                            }
                        } else {
                            if ($value['joinSpawnShow']) {
                                $item = new Item($value['item']);
                                $player->getInventory()->remove($item);
                            }
                        }
                    }
                }
            } else { 
                foreach ($this->configItems as $key => $value) {
                    if (!$itemData['joinSpawnShow']) {
                        if (!$value['joinSpawnShow']) {
                            $item = new Item($value['item']);
                            $player->getInventory()->remove($item);
                        }
                    } else {
                        if ($value['joinSpawnShow']) {
                            $item = new Item($value['item']);
                            $player->getInventory()->remove($item);
                        }
                    }
                }
            }
        }
        
        public function setItems(Player $player, array $itemData) {
            if ($itemData['name']) {
                foreach ($this->configItems as $key => $value) {
                    if ($itemData['name'] == $key) {
                        if (!$itemData['joinSpawnShow']) {
                            if (!$value['joinSpawnShow']) {
                                if (!$value['enchantment']) {
                                    $player->getInventory()->setItem($value['slot'], Item::get($value['item'], 0, 1));
                                } else {
                                    $item = Item::get($value['item'], 0, 1);
                                    $enchantment = Enchantment::getEnchantment($value['enchantment']);
                                    $item->addEnchantment($enchantment);
                                    $player->getInventory()->addItem($item);
                                }
                            }
                        } else {
                            if ($value['joinSpawnShow']) {
                                if (!$value['enchantment']) {
                                    $player->getInventory()->setItem($value['slot'], Item::get($value['item'], 0, 1));
                                } else {
                                    $item = Item::get($value['item'], 0, 1);
                                    $enchantment = Enchantment::getEnchantment($value['enchantment']);
                                    $item->addEnchantment($enchantment);
                                    $player->getInventory()->addItem($item);
                                }
                            }
                        }
                    }
                }
            } else {
                foreach ($this->configItems as $key => $value) {
                    if (!$itemData['joinSpawnShow']) {
                        if (!$value['joinSpawnShow']) {
                            if (!$value['enchantment']) {
                                $player->getInventory()->setItem($value['slot'], Item::get($value['item'], 0, 1));
                            } else {
                                $item = Item::get($value['item'], 0, 1);
                                $enchantment = Enchantment::getEnchantment($value['enchantment']);
                                $item->addEnchantment($enchantment);
                                $player->getInventory()->addItem($item);
                            }
                        }
                    } else {
                        if ($value['joinSpawnShow']) {
                            if (!$value['enchantment']) {
                                $player->getInventory()->setItem($value['slot'], Item::get($value['item'], 0, 1));
                            } else {
                                $item = Item::get($value['item'], 0, 1);
                                $enchantment = Enchantment::getEnchantment($value['enchantment']);
                                $item->addEnchantment($enchantment);
                                $player->getInventory()->addItem($item);
                            }
                        }
                    }
                }
            }
            
            $player->getInventory()->sendArmorContents($player);
            $player->getInventory()->sendContents($player);
            $player->getInventory()->sendHeldItem($player);
        }
        
        public function onRespawnEvent(PlayerRespawnEvent $event) {
            if ($this->defaultConfig['onRespawn'] == true) {
                $player = $event->getPlayer();
                $player->getInventory()->clearAll();
                $this->setItems($player, ['joinSpawnShow' => true]);
            }
        }
        
        public function onBlockPlace(BlockPlaceEvent $event) {
            $player = $event->getPlayer();
            $item = $event->getItem();
            if (!in_array($player->getName(), $this->allowPlace)) {
                foreach ($this->configItems as $key => $value) {
                    if ($value['item'] == $item->getId()) {
                        $event->setCancelled(true);
                    }
                }
            }
        }
        
        public function onItemHeld(PlayerItemHeldEvent $event) {
            $player = $event->getPlayer();
            $item = $event->getItem();
            if (!in_array($player->getName(), $this->allowPlace)) {
                foreach ($this->configItems as $key => $value) {
                    if ($value['item'] == $item->getId()) {
                        $title = str_replace('&', '§', $value['title']);
                        $player->sendPopup($title);
                    }
                }
            }
        }
        
        public function onInteract(PlayerInteractEvent $event) {
            $player = $event->getPlayer();
            $item = $event->getItem();
            if (!in_array($player->getName(), $this->allowPlace)) {
                foreach ($this->configItems as $key => $value) {
                    if ($value['item'] == $item->getId()) {
                        $this->getServer()->dispatchCommand($player, $value['command']);
                        if ($value['sound']) {
                            $level = $player->getLevel();
                            $sound = "pocketmine\\level\\sound\\{$value['sound']}";
                            $level->addSound(new $sound($player));
                        }
                    }
                }
            }
        }
        
        public function onBreakBlock(BlockBreakEvent $event) {
            $player = $event->getPlayer();
            $item = $event->getItem();
            if (!in_array($player->getName(), $this->allowPlace)) {
                foreach ($this->configItems as $key => $value) {
                    if ($value['item'] == $item->getId()) {
                        $event->setCancelled(true);
                    }
                }
            }
        }
        
        public function onGiveBlockEvent(PlayerDropItemEvent $event) {
            $player = $event->getPlayer();
            $item = $event->getItem();
            if (!in_array($player->getName(), $this->allowPlace)) {
                foreach ($this->configItems as $key => $value) {
                    if ($value['item'] == $item->getId()) {
                        $event->setCancelled(true);
                    }
                }
            }
        }
        
        public function onDisable() {
            $this->config->set('allowedPlayersPlace', array());
            $this->config->save();
            $this->getLogger()->info(Color::RED . 'Plugin disabled');
        }
    }

?>