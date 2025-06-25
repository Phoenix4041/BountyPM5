<?php

namespace BountyPM;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\event\entity\EntityDamageByEntityEvent;

use onebone\economyapi\EconomyAPI;

class Main extends PluginBase implements Listener {

    /** @var Config */
    private $bounties;

    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveResource("bounties.yml");
        $this->bounties = new Config($this->getDataFolder() . "bounties.yml", Config::YAML);

        $economyAPI = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        if ($economyAPI === null || !$economyAPI instanceof EconomyAPI) {
            $this->getLogger()->error(TextFormat::RED . "EconomyAPI no está cargado. Este plugin no funcionará sin él.");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        $this->getLogger()->info(TextFormat::GREEN . "BountyPM ha sido habilitado!");
    }

    public function onDisable(): void {
        $this->getLogger()->info(TextFormat::RED . "BountyPM ha sido deshabilitado!");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "bounty") {
            // Este comando puede ser ejecutado por la consola para 'remove'
            // Pero para 'create' y 'list' se requiere un jugador.
            // Ajustamos el mensaje de uso general.

            if (!isset($args[0])) {
                $sender->sendMessage(TextFormat::RED . "Uso: /bounty <create|list|remove>");
                return true;
            }

            $subCommand = strtolower($args[0]);

            switch ($subCommand) {
                case "create":
                    if (!$sender instanceof Player) {
                        $sender->sendMessage(TextFormat::RED . "Este comando solo puede ser ejecutado por un jugador.");
                        return true;
                    }
                    if (!$sender->hasPermission("bountypm.command.bounty.create")) {
                        $sender->sendMessage(TextFormat::RED . "No tienes permiso para crear recompensas.");
                        return true;
                    }
                    if (count($args) < 3) {
                        $sender->sendMessage(TextFormat::RED . "Uso: /bounty create <jugador> <cantidad>");
                        return true;
                    }

                    $targetName = strtolower($args[1]);
                    $amount = (int) $args[2];

                    if ($amount <= 0) {
                        $sender->sendMessage(TextFormat::RED . "La cantidad de la recompensa debe ser un número positivo.");
                        return true;
                    }

                    if ($targetName === strtolower($sender->getName())) {
                        $sender->sendMessage(TextFormat::RED . "No puedes poner una recompensa sobre ti mismo.");
                        return true;
                    }

                    $economyAPI = EconomyAPI::getInstance();
                    if ($economyAPI === null) {
                        $sender->sendMessage(TextFormat::RED . "Error: EconomyAPI no está disponible. No se pudo procesar la recompensa.");
                        $this->getLogger()->error("EconomyAPI no está disponible al intentar crear una recompensa.");
                        return true;
                    }

                    $playerMoney = $economyAPI->myMoney($sender); 
                    
                    if (!$this->bounties->exists($targetName)) {
                        if ($playerMoney < $amount) {
                            $sender->sendMessage(TextFormat::RED . "No tienes suficiente dinero para establecer una recompensa de $" . $amount . ". Saldo actual: $" . $playerMoney);
                            return true;
                        }
                        $economyAPI->reduceMoney($sender, $amount);
                        $this->bounties->set($targetName, $amount);
                        $this->bounties->save();
                        $sender->sendMessage(TextFormat::GREEN . "¡Recompensa establecida! Has gastado $" . TextFormat::GOLD . $amount . TextFormat::GREEN . ". " . TextFormat::YELLOW . $args[1] . TextFormat::GREEN . " ahora tiene una recompensa de $" . TextFormat::GOLD . $amount . ".");
                        $this->getServer()->broadcastMessage(TextFormat::GOLD . "¡Una recompensa de $" . $amount . " ha sido establecida por " . TextFormat::YELLOW . $args[1] . TextFormat::GOLD . "!");
                    } else {
                        $currentBounty = $this->bounties->get($targetName);
                        $newBounty = $currentBounty + $amount;
                        
                        if ($playerMoney < $amount) {
                            $sender->sendMessage(TextFormat::RED . "No tienes suficiente dinero para aumentar la recompensa en $" . $amount . ". Saldo actual: $" . $playerMoney);
                            return true;
                        }
                        $economyAPI->reduceMoney($sender, $amount);

                        $this->bounties->set($targetName, $newBounty);
                        $this->bounties->save();
                        $sender->sendMessage(TextFormat::GREEN . "¡Recompensa actualizada! Has gastado $" . TextFormat::GOLD . $amount . TextFormat::GREEN . ". " . TextFormat::YELLOW . $args[1] . TextFormat::GREEN . " ahora tiene una recompensa de $" . TextFormat::GOLD . $newBounty . ".");
                        $this->getServer()->broadcastMessage(TextFormat::GOLD . "¡La recompensa por " . TextFormat::YELLOW . $args[1] . TextFormat::GOLD . " ha sido aumentada a $" . $newBounty . "!");
                    }
                    return true;

                case "list":
                    if (!$sender instanceof Player && !$sender->isOp()) { // Permite a la consola usar /bounty list también
                        $sender->sendMessage(TextFormat::RED . "Este comando solo puede ser ejecutado por un jugador o un operador de consola.");
                        return true;
                    }
                    if (!$sender->hasPermission("bountypm.command.bounty.list")) {
                        $sender->sendMessage(TextFormat::RED . "No tienes permiso para ver la lista de recompensas.");
                        return true;
                    }

                    $allBounties = $this->bounties->getAll();

                    if (empty($allBounties)) {
                        $sender->sendMessage(TextFormat::YELLOW . "Actualmente no hay recompensas activas.");
                        return true;
                    }

                    arsort($allBounties);

                    $sender->sendMessage(TextFormat::AQUA . "--- " . TextFormat::BOLD . "Recompensas Activas" . TextFormat::RESET . TextFormat::AQUA . " ---");
                    $rank = 1;
                    foreach ($allBounties as $playerName => $amount) {
                        $sender->sendMessage(TextFormat::YELLOW . "#" . $rank . ": " . TextFormat::WHITE . ucwords($playerName) . TextFormat::GOLD . " - $" . number_format($amount));
                        $rank++;
                        if ($rank > 10) {
                             $sender->sendMessage(TextFormat::GRAY . "(Mostrando el top 10. Hay más recompensas...)");
                             break;
                        }
                    }
                    $sender->sendMessage(TextFormat::AQUA . "------------------------------");
                    return true;

                case "remove":
                    // Este comando puede ser ejecutado por jugadores y la consola.
                    // Solo los jugadores necesitan verificar el permiso.
                    if ($sender instanceof Player && !$sender->hasPermission("bountypm.command.bounty.remove")) {
                        $sender->sendMessage(TextFormat::RED . "No tienes permiso para remover recompensas.");
                        return true;
                    }
                    // La consola siempre tiene permisos de "administrador" para plugins, pero verificamos su uso.
                    if (count($args) < 2) {
                        $sender->sendMessage(TextFormat::RED . "Uso: /bounty remove <jugador>");
                        return true;
                    }

                    $targetName = strtolower($args[1]);

                    if (!$this->bounties->exists($targetName)) {
                        $sender->sendMessage(TextFormat::RED . "El jugador '" . $args[1] . "' no tiene una recompensa activa.");
                        return true;
                    }

                    $removedAmount = $this->bounties->get($targetName);
                    $this->bounties->remove($targetName);
                    $this->bounties->save();

                    $sender->sendMessage(TextFormat::GREEN . "¡Recompensa eliminada! La recompensa de $" . TextFormat::GOLD . $removedAmount . TextFormat::GREEN . " por " . TextFormat::YELLOW . $args[1] . TextFormat::GREEN . " ha sido eliminada.");
                    $this->getServer()->broadcastMessage(TextFormat::YELLOW . "¡La recompensa por " . TextFormat::BLUE . $args[1] . TextFormat::YELLOW . " de $" . $removedAmount . " ha sido eliminada por un administrador!");
                    return true;

                default:
                    $sender->sendMessage(TextFormat::RED . "Uso: /bounty <create|list|remove>");
                    return true;
            }
        }
        return false;
    }

    public function onPlayerDeath(PlayerDeathEvent $event): void {
        $victim = $event->getPlayer();
        
        $cause = $victim->getLastDamageCause();
        $killer = $cause instanceof EntityDamageByEntityEvent ? $cause->getDamager() : null;

        if ($killer instanceof Player) {
            $victimName = strtolower($victim->getName());

            if ($this->bounties->exists($victimName)) {
                $bountyAmount = $this->bounties->get($victimName);

                $economyAPI = EconomyAPI::getInstance();
                if ($economyAPI !== null) {
                    $economyAPI->givemoney($killer, $bountyAmount);
                    $killer->sendMessage(TextFormat::GREEN . "¡Has reclamado una recompensa! Recibiste $" . TextFormat::GOLD . $bountyAmount . " por matar a " . TextFormat::YELLOW . $victim->getName() . ".");
                    $this->getServer()->broadcastMessage(TextFormat::GOLD . "¡" . TextFormat::YELLOW . $killer->getName() . TextFormat::GOLD . " ha reclamado la recompensa de $" . $bountyAmount . " por matar a " . TextFormat::YELLOW . $victim->getName() . "!");
                } else {
                    $this->getLogger()->warning("EconomyAPI no está disponible para pagar la recompensa.");
                }

                $this->bounties->remove($victimName);
                $this->bounties->save();
            }
        }
    }
}