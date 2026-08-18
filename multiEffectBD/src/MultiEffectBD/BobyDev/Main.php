<?php

declare(strict_types=1);

namespace MultiEffectBD\BobyDev;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\particle\FlameParticle;
use pocketmine\world\particle\HugeExplodeParticle;
use pocketmine\world\sound\ExplodeSound;
use pocketmine\world\sound\ThunderSound;

class Main extends PluginBase implements Listener {

    /** Colour / style helpers -------------------------------------------------- */
    private const BLUE  = "\xc2\xa7b";   // §b
    private const RED   = "\xc2\xa7c";   // §c
    private const WHITE = "\xc2\xa7f";   // §f
    private const GRAY  = "\xc2\xa7\x37"; // §7
    private const BOLD  = "\xc2\xa7l";   // §l

    // small-caps unicode subtitle, as requested ("ʜʜʜʜʜ" style small font)
    private const SUB_TITLE = "ʜᴏʟᴅ ᴀɴ ɪᴛᴇᴍ ᴛʜᴇɴ ᴛᴀᴘ ᴀɴ ᴇꜰꜰᴇᴄᴛ";
    private const SUB_BACK  = "ʙᴀᴄᴋ";
    private const SUB_ENCH  = "ᴄᴜsᴛᴏᴍ ᴇɴᴄʜᴀɴᴛᴍᴇɴᴛs";
    private const SUB_CLOSE = "ᴄʟᴏsᴇ";

    /** Minimum / maximum for the "effect amount" input (e.g. Jump Boost 10, 20 ... 100 max). */
    private const AMOUNT_MIN = 1;
    private const AMOUNT_MAX = 100;

    /** How often (in ticks) the "hold to activate" checker runs. 20 ticks = 1 second. */
    private const HOLD_CHECK_PERIOD = 20;

    /**
     * First 30 effect buttons.
     * type: "potion"  -> applies a real potion effect + tags the item
     *       "special" -> triggers a scripted action (lightning sound / tnt-style damage / fire)
     */
    private const EFFECTS = [
        ["id" => "speed",        "name" => "Speed",             "type" => "potion", "method" => "SPEED"],
        ["id" => "slowness",     "name" => "Slowness",          "type" => "potion", "method" => "SLOWNESS"],
        ["id" => "haste",        "name" => "Haste",             "type" => "potion", "method" => "HASTE"],
        ["id" => "fatigue",      "name" => "Mining Fatigue",    "type" => "potion", "method" => "MINING_FATIGUE"],
        ["id" => "strength",     "name" => "Strength",          "type" => "potion", "method" => "STRENGTH"],
        ["id" => "heal",         "name" => "Instant Health",    "type" => "potion", "method" => "INSTANT_HEALTH"],
        ["id" => "harm",         "name" => "Instant Damage",    "type" => "potion", "method" => "INSTANT_DAMAGE"],
        ["id" => "jump",         "name" => "Jump Boost",        "type" => "potion", "method" => "JUMP_BOOST"],
        ["id" => "nausea",       "name" => "Nausea",            "type" => "potion", "method" => "NAUSEA"],
        ["id" => "regen",        "name" => "Regeneration",      "type" => "potion", "method" => "REGENERATION"],
        ["id" => "resistance",   "name" => "Resistance",        "type" => "potion", "method" => "RESISTANCE"],
        ["id" => "fireres",      "name" => "Fire Resistance",   "type" => "potion", "method" => "FIRE_RESISTANCE"],
        ["id" => "waterbreath",  "name" => "Water Breathing",   "type" => "potion", "method" => "WATER_BREATHING"],
        ["id" => "invis",        "name" => "Invisibility",      "type" => "potion", "method" => "INVISIBILITY"],
        ["id" => "blind",        "name" => "Blindness",         "type" => "potion", "method" => "BLINDNESS"],
        ["id" => "nightvision",  "name" => "Night Vision",      "type" => "potion", "method" => "NIGHT_VISION"],
        ["id" => "hunger",       "name" => "Hunger",            "type" => "potion", "method" => "HUNGER"],
        ["id" => "weakness",     "name" => "Weakness",          "type" => "potion", "method" => "WEAKNESS"],
        ["id" => "poison",       "name" => "Poison",            "type" => "potion", "method" => "POISON"],
        ["id" => "wither",       "name" => "Wither",            "type" => "potion", "method" => "WITHER"],
        ["id" => "healthboost",  "name" => "Health Boost",      "type" => "potion", "method" => "HEALTH_BOOST"],
        ["id" => "absorption",   "name" => "Absorption",        "type" => "potion", "method" => "ABSORPTION"],
        ["id" => "saturation",   "name" => "Saturation",        "type" => "potion", "method" => "SATURATION"],
        ["id" => "levitation",   "name" => "Levitation",        "type" => "potion", "method" => "LEVITATION"],
        ["id" => "slowfall",     "name" => "Slow Falling",      "type" => "potion", "method" => "SLOW_FALLING"],
        ["id" => "conduit",      "name" => "Conduit Power",     "type" => "potion", "method" => "CONDUIT_POWER"],
        ["id" => "fatalpoison",  "name" => "Fatal Poison",      "type" => "potion", "method" => "FATAL_POISON"],
        ["id" => "lightning",    "name" => "Lightning Strike",  "type" => "special"],
        ["id" => "explosion",    "name" => "TNT / Boom",        "type" => "special"],
        ["id" => "fire",         "name" => "Set On Fire",       "type" => "special"],
    ];

    /** Custom-level enchantments (levels are player-typed, 1 - 10000). */
    private const ENCHANTS = [
        ["id" => "protection",        "name" => "Protection",           "method" => "PROTECTION"],
        ["id" => "fire_protection",   "name" => "Fire Protection",      "method" => "FIRE_PROTECTION"],
        ["id" => "feather_falling",   "name" => "Feather Falling",      "method" => "FEATHER_FALLING"],
        ["id" => "blast_protection",  "name" => "Blast Protection",     "method" => "BLAST_PROTECTION"],
        ["id" => "proj_protection",   "name" => "Projectile Protection","method" => "PROJECTILE_PROTECTION"],
        ["id" => "thorns",            "name" => "Thorns",               "method" => "THORNS"],
        ["id" => "respiration",       "name" => "Respiration",          "method" => "RESPIRATION"],
        ["id" => "aqua_affinity",     "name" => "Aqua Affinity",        "method" => "AQUA_AFFINITY"],
        ["id" => "sharpness",         "name" => "Sharpness",            "method" => "SHARPNESS"],
        ["id" => "smite",             "name" => "Smite",                "method" => "SMITE"],
        ["id" => "bane",              "name" => "Bane of Arthropods",   "method" => "BANE_OF_ARTHROPODS"],
        ["id" => "knockback",         "name" => "Knockback",            "method" => "KNOCKBACK"],
        ["id" => "fire_aspect",       "name" => "Fire Aspect",          "method" => "FIRE_ASPECT"],
        ["id" => "looting",           "name" => "Looting",              "method" => "LOOTING"],
        ["id" => "efficiency",        "name" => "Efficiency",           "method" => "EFFICIENCY"],
        ["id" => "silk_touch",        "name" => "Silk Touch",           "method" => "SILK_TOUCH"],
        ["id" => "unbreaking",        "name" => "Unbreaking",           "method" => "UNBREAKING"],
        ["id" => "fortune",           "name" => "Fortune",              "method" => "FORTUNE"],
        ["id" => "power",             "name" => "Power",                "method" => "POWER"],
        ["id" => "punch",             "name" => "Punch",                "method" => "PUNCH"],
        ["id" => "flame",             "name" => "Flame",                "method" => "FLAME"],
        ["id" => "infinity",          "name" => "Infinity",             "method" => "INFINITY"],
        ["id" => "luck_of_the_sea",   "name" => "Luck of the Sea",      "method" => "LUCK_OF_THE_SEA"],
        ["id" => "lure",              "name" => "Lure",                 "method" => "LURE"],
        ["id" => "frost_walker",      "name" => "Frost Walker",         "method" => "FROST_WALKER"],
        ["id" => "mending",           "name" => "Mending",              "method" => "MENDING"],
        ["id" => "curse_binding",     "name" => "Curse of Binding",     "method" => "CURSE_OF_BINDING"],
        ["id" => "curse_vanishing",   "name" => "Curse of Vanishing",   "method" => "VANISHING"],
    ];

    /**
     * Returns only the potion effects that this build of the server actually implements
     * (all "special" entries are always available). This keeps the menu in sync automatically
     * if a future Altay/PocketMine update adds or removes vanilla effects, instead of crashing
     * with "Call to undefined method" when a removed/not-yet-implemented effect is tapped.
     *
     * @return array<int, array<string, string>>
     */
    private function getAvailableEffects(): array {
        return array_values(array_filter(self::EFFECTS, function (array $effect): bool {
            if ($effect["type"] !== "potion") return true;
            return method_exists(VanillaEffects::class, $effect["method"]);
        }));
    }

    /**
     * Returns only the enchantments that this build of the server actually implements, for the
     * same reason as {@see getAvailableEffects()}.
     *
     * @return array<int, array<string, string>>
     */
    private function getAvailableEnchants(): array {
        return array_values(array_filter(self::ENCHANTS, function (array $ench): bool {
            return method_exists(VanillaEnchantments::class, $ench["method"]);
        }));
    }

    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        // "hold to activate": every second, re-check every online player's held item
        // and (re)apply its tagged effect only while it is still in their hand.
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
            foreach ($this->getServer()->getOnlinePlayers() as $player) {
                $this->tickHeldItemEffect($player);
            }
        }), self::HOLD_CHECK_PERIOD);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "meb") {
            if (!($sender instanceof Player)) {
                $sender->sendMessage("This command can only be used in-game.");
                return true;
            }
            $this->openMainMenu($sender);
            return true;
        }
        return false;
    }

    /** ---------------------------------------------------------------- MENUS */

    public function openMainMenu(Player $player): void {
        $effects = $this->getAvailableEffects();

        $content = self::GRAY . self::SUB_TITLE . "\n";
        $form = new SimpleForm(
            self::BLUE . self::BOLD . "MultiEffectBD",
            $content,
            function (Player $player, int $index) use ($effects): void {
                $count = count($effects);
                if ($index < $count) {
                    $this->openAmountForm($player, $effects[$index]);
                    return;
                }
                if ($index === $count) { // Custom Enchantments button
                    $this->openEnchantMenu($player);
                    return;
                }
                // last button = close, do nothing
            }
        );

        foreach ($effects as $effect) {
            $form->addButton(self::BLUE . $effect["name"]);
        }
        $form->addButton(self::BLUE . self::BOLD . self::SUB_ENCH);
        $form->addButton(self::RED . self::BOLD . self::SUB_CLOSE);

        $player->sendForm($form);
    }

    /**
     * Ask the player how strong the effect should be (1 - 100), e.g. Jump Boost 10, 20 ... 100 max.
     */
    public function openAmountForm(Player $player, array $effect): void {
        $form = new CustomForm(
            self::BLUE . self::BOLD . $effect["name"],
            function (Player $player, array $data) use ($effect): void {
                $raw = $data[1] ?? ($data[0] ?? "10");
                if (!is_numeric($raw)) {
                    $player->sendMessage(self::RED . "Please enter a valid number (" . self::AMOUNT_MIN . " - " . self::AMOUNT_MAX . ").");
                    $this->openAmountForm($player, $effect);
                    return;
                }
                $amount = (int) $raw;
                if ($amount < self::AMOUNT_MIN) $amount = self::AMOUNT_MIN;
                if ($amount > self::AMOUNT_MAX) $amount = self::AMOUNT_MAX;

                $this->applyEffect($player, $effect, $amount);
            }
        );
        $form->addLabel(self::GRAY . "ᴇɴᴛᴇʀ ᴀɴ ᴀᴍᴏᴜɴᴛ, ᴇ.ɢ. 10, 20 ... ᴜᴘ ᴛᴏ 100 (ᴍᴀx)");
        $form->addInput("Effect amount (" . self::AMOUNT_MIN . "-" . self::AMOUNT_MAX . ")", "e.g. 10 / 20 / 100", "10");

        $player->sendForm($form);
    }

    public function openEnchantMenu(Player $player): void {
        $enchants = $this->getAvailableEnchants();

        $content = self::GRAY . "ᴛᴀᴘ ᴀɴ ᴇɴᴄʜᴀɴᴛᴍᴇɴᴛ, ᴛʜᴇɴ ᴛʏᴘᴇ ᴀ ʟᴇᴠᴇʟ (1-10000)";
        $form = new SimpleForm(
            self::BLUE . self::BOLD . "Custom Enchantments",
            $content,
            function (Player $player, int $index) use ($enchants): void {
                $count = count($enchants);
                if ($index < $count) {
                    $this->openLevelForm($player, $enchants[$index]);
                    return;
                }
                // back button
                $this->openMainMenu($player);
            }
        );

        foreach ($enchants as $ench) {
            $form->addButton(self::BLUE . $ench["name"]);
        }
        $form->addButton(self::RED . self::BOLD . self::SUB_BACK);

        $player->sendForm($form);
    }

    public function openLevelForm(Player $player, array $ench): void {
        $form = new CustomForm(
            self::BLUE . self::BOLD . $ench["name"],
            function (Player $player, array $data) use ($ench): void {
                $raw = $data[1] ?? ($data[0] ?? "100");
                if (!is_numeric($raw)) {
                    $player->sendMessage(self::RED . "Please enter a valid number (1 - 10000).");
                    $this->openLevelForm($player, $ench);
                    return;
                }
                $level = (int) $raw;
                if ($level < 1) $level = 1;
                if ($level > 10000) $level = 10000;

                $this->applyEnchant($player, $ench, $level);
            }
        );
        $form->addLabel(self::GRAY . "ᴇɴᴛᴇʀ ᴀ ʟᴇᴠᴇʟ, ᴇ.ɢ. 100, 1000, ᴏʀ ᴜᴘ ᴛᴏ 10000 (ᴍᴀx)");
        $form->addInput("Enchantment level", "e.g. 100 / 1000 / 10000", "100");

        $player->sendForm($form);
    }

    /** ---------------------------------------------------------------- ACTIONS */

    private function applyEffect(Player $player, array $effect, int $amount): void {
        $item = $player->getInventory()->getItemInHand();
        if ($item->isNull()) {
            $player->sendMessage(self::RED . "Hold an item in your hand first!");
            return;
        }

        // Tag + label the held item so the effect name and amount show in its description (lore).
        $item->setLore([
            self::BLUE . self::BOLD . "Effect: " . self::WHITE . $effect["name"],
            self::BLUE . self::BOLD . "Amount: " . self::WHITE . $amount,
            self::GRAY . "ᴀᴄᴛɪᴠᴇ ᴏɴʟʏ ᴡʜɪʟᴇ ʜᴇʟᴅ"
        ]);
        $named = $item->getNamedTag();
        $named->setString("mebEffect", $effect["id"]);
        $named->setInt("mebAmount", $amount);
        $item->setNamedTag($named);
        $player->getInventory()->setItemInHand($item);

        // Give immediate feedback by triggering the effect right away too.
        $this->triggerEffect($player, $effect, $amount, 20 * 5);
        $player->sendMessage(self::BLUE . "Applied " . self::WHITE . $effect["name"] . " (" . $amount . ")" . self::BLUE . " to your held item! It stays active only while you hold it.");
    }

    private function applyEnchant(Player $player, array $ench, int $level): void {
        $item = $player->getInventory()->getItemInHand();
        if ($item->isNull()) {
            $player->sendMessage(self::RED . "Hold an item in your hand first!");
            return;
        }

        if (!method_exists(VanillaEnchantments::class, $ench["method"])) {
            $player->sendMessage(self::RED . $ench["name"] . " isn't available on this server version.");
            return;
        }
        $enchantment = VanillaEnchantments::{$ench["method"]}();
        $item->addEnchantment(new EnchantmentInstance($enchantment, $level));

        $lore = $item->getLore();
        $lore[] = self::BLUE . $ench["name"] . " " . self::WHITE . $level;
        $item->setLore($lore);

        $player->getInventory()->setItemInHand($item);
        $player->sendMessage(self::BLUE . "Applied " . self::WHITE . $ench["name"] . " " . $level . self::BLUE . " to your held item!");
    }

    /**
     * Instant re-trigger when the player right-clicks / uses the tagged item.
     */
    public function onItemUse(PlayerItemUseEvent $event): void {
        $player = $event->getPlayer();
        $item = $event->getItem();
        if ($item->isNull()) return;

        $named = $item->getNamedTag();
        if ($named->getTag("mebEffect") === null) return;
        $id = $named->getString("mebEffect", "");
        $amount = $named->getInt("mebAmount", 10);

        foreach (self::EFFECTS as $effect) {
            if ($effect["id"] !== $id) continue;
            $this->triggerEffect($player, $effect, $amount, 20 * 2);
            return;
        }
    }

    /**
     * Runs every second for every online player. The effect on a tagged item is only
     * (re)applied while that exact item is currently in the player's hand — if they
     * switch away from it, the effect is simply not refreshed and fades out on its own.
     */
    private function tickHeldItemEffect(Player $player): void {
        $item = $player->getInventory()->getItemInHand();
        if ($item->isNull()) return;

        $named = $item->getNamedTag();
        if ($named->getTag("mebEffect") === null) return;
        $id = $named->getString("mebEffect", "");
        $amount = $named->getInt("mebAmount", 10);

        foreach (self::EFFECTS as $effect) {
            if ($effect["id"] !== $id) continue;
            // Short duration - just enough to bridge to the next check, so the
            // effect naturally disappears the moment the item is no longer held.
            $this->triggerEffect($player, $effect, $amount, self::HOLD_CHECK_PERIOD * 2);
            return;
        }
    }

    /**
     * Central place that actually fires an effect (potion or special) for a player.
     */
    private function triggerEffect(Player $player, array $effect, int $amount, int $durationTicks): void {
        if ($effect["type"] === "potion") {
            if (!method_exists(VanillaEffects::class, $effect["method"])) {
                // This effect isn't implemented on this server build (e.g. an item tagged
                // before a server downgrade/update). Fail silently instead of crashing.
                return;
            }
            $vanillaEffect = VanillaEffects::{$effect["method"]}();
            $amplifier = max(0, min(255, $amount - 1)); // amount 1 = level I (amplifier 0)
            $player->getEffects()->add(new EffectInstance($vanillaEffect, $durationTicks, $amplifier, true));
            return;
        }

        switch ($effect["id"]) {
            case "lightning":
                $this->doLightningSound($player, $amount);
                break;
            case "explosion":
                $this->doTntEffect($player, $amount);
                break;
            case "fire":
                $seconds = max(1, min(20, (int) round($amount / 5)));
                $player->setOnFire($seconds);
                break;
        }
    }

    /**
     * Lightning is cosmetic-only: thunder sound + spark particles at the player.
     * (No real lightning entity is summoned, and no blocks/mobs are struck.)
     */
    private function doLightningSound(Player $player, int $amount): void {
        $world = $player->getWorld();
        $pos = $player->getPosition();
        $world->addSound($pos, new ThunderSound());

        $particles = max(1, (int) round(($amount / self::AMOUNT_MAX) * 6));
        for ($i = 0; $i < $particles; $i++) {
            $world->addParticle($pos, new FlameParticle());
        }
    }

    /**
     * TNT-style effect: explosion sound, particle, and area damage to nearby
     * living entities (scaled by amount). It never breaks or removes blocks.
     */
    private function doTntEffect(Player $player, int $amount): void {
        $world = $player->getWorld();
        $pos = $player->getPosition();

        $world->addSound($pos, new ExplodeSound());
        $world->addParticle($pos, new HugeExplodeParticle());

        $radius = 2.0 + ($amount / self::AMOUNT_MAX) * 6.0;      // 2 - 8 blocks
        $maxDamage = 4.0 + ($amount / self::AMOUNT_MAX) * 16.0;  // 4 - 20 HP

        $bb = $player->getBoundingBox()->expandedCopy($radius, $radius, $radius);
        foreach ($world->getNearbyEntities($bb, $player) as $entity) {
            if (!($entity instanceof Living)) continue;

            $distance = $entity->getPosition()->distance($pos);
            if ($distance > $radius) continue;

            $damage = max(1.0, $maxDamage * (1 - ($distance / $radius)));
            $entity->attack(new EntityDamageEvent($entity, EntityDamageEvent::CAUSE_BLOCK_EXPLOSION, $damage));
        }
    }
}
