# MultiEffectBD

> MultiEffectBD adds a server-side Bedrock form interface for binding configurable effects to held items in PocketMine-MP. Players can choose from 30 potion-style or scripted effects, set amounts from 1–100, and have tagged effects refreshed only while the item is held. A separate menu applies configured vanilla enchantments at custom levels from 1–10,000, with lore feedback; no external form plugin is required.

**Author:** BobyDev
**Plugin name:** `MultiEffectBD`
**Version:** `1.1.0`
**API target:** `["5.0.0"]`

## Overview

A PocketMine-MP API 5 plugin that provides an in-game form menu for applying one of 30 configured potion-style or scripted effects to the item held by a player, plus custom-level vanilla enchantments. Effect metadata is stored in the item’s lore and NBT; tagged effects are re-applied while the item remains held and can be re-triggered on item use. The source implements lightning as thunder/particle cosmetics and TNT/Boom as area entity damage without block destruction.

## Highlights

- Opens a button-based main menu through /meb with 27 potion-style effects and 3 scripted effects: Lightning Strike, TNT/Boom, and Set On Fire.
- Accepts effect amounts from 1 to 100, clamps out-of-range values, and applies potion effect amplifiers derived from the selected amount.
- Stores the selected effect ID and amount in the held item’s named tag and writes effect details plus an active-while-held note to item lore.
- Refreshes tagged effects every second only while the tagged item is in the player’s hand, allowing effects to fade after switching items.
- Re-triggers tagged effects when the player uses the item via PlayerItemUseEvent.
- Provides a custom-enchantment menu covering the configured vanilla enchantments, accepts levels from 1 to 10000, and adds the enchantment and level to the held item’s lore.
- Implements scripted effects: cosmetic thunder sound and flame particles for Lightning, radius/damage-scaled nearby Living-entity damage for TNT/Boom, and up to 20 seconds of fire for Set On Fire.

## Commands

- /meb — opens the MultiEffectBD menu in-game; requires the multieffectbd.use permission, which defaults to op. Console and non-player senders are rejected.

## Requirements and integrations

- PocketMine-MP server with API 5.0.0 compatibility, as declared in plugin.yml.
- PHP runtime compatible with the installed PocketMine-MP API.
- No external plugins or FormAPI dependency; the plugin includes its own SimpleForm and CustomForm wrappers using PocketMine-MP’s built-in form system.

## Installation

1. Download or clone this repository.
2. Copy the plugin folder into your PocketMine-MP/Altay `plugins/` directory, keeping the included source layout and configuration files intact.
3. Install the external dependencies listed above when the plugin requires them.
4. Restart the server and review the console for any missing dependency or configuration errors.

## Source analysis

This README was generated from a source and manifest review of the supplied archive. It intentionally distinguishes verified source behavior from external integrations that must be provided by the server.

## License

See the files included in this repository for any license supplied with the plugin.
