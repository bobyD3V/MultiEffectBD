# multiEffectBD

A PocketMine-MP plugin (Bedrock server-side) that gives you a Form-UI menu for:

1. **30 quick effect buttons** — tap one to tag + apply an effect to whatever item you're holding.
   Includes potion-style effects (Speed, Strength, Regeneration, Poison, Wither, Absorption,
   Levitation, Conduit Power, etc.) plus special scripted effects: **Lightning Strike**,
   **TNT / Boom (explosion)**, and **Set On Fire**.
2. **Custom Enchantments menu** — tap any vanilla enchantment (Sharpness, Protection, Efficiency,
   Mending, Fortune, Unbreaking, etc.), then type in any level you want from **1 up to 10000**
   (no vanilla level cap).

The applied effect/enchant name is written straight into the item's **description (lore)**,
and blue/red form styling + small-caps subtitles are baked in, as requested.

## How it works in-game

- `/meb` (op only by default) opens the main menu.
- Blue buttons = the 30 effects, plus a blue "Custom Enchantments" button.
- Red button at the bottom = Back / Close.
- Tapping an effect button instantly:
  - Adds a lore line like `Effect: Speed` to the item in your hand.
  - Applies that effect to you right away as feedback.
  - Tags the item internally so using it again later re-triggers the effect
    (this is how Lightning / Explosion / Fire actually "do something" when you use the item,
    since those aren't normal potion effects).
- Tapping "Custom Enchantments" opens the enchant list → tapping an enchantment opens a text box
  where you type the level (e.g. `100`, `1000`, `10000`). It's clamped to 1–10000 and applied to
  the held item immediately, with the level shown in the item's lore too.

## Installation

1. Copy the whole `multiEffectBD` folder into your server's `plugins/` directory.
2. Restart or reload the server.
3. Grant/verify the `multieffectbd.use` permission (defaults to **op**).
4. Run `/meb` in-game.

## Notes / things to double-check on your server build

- Written for **PocketMine-MP API 5.x**. If your server runs a different API version, a few
  class/method names (`VanillaEffects::*`, `VanillaEnchantments::*`, `Explosion`, particle/sound
  classes) may need small tweaks to match that exact version — PMMP renames these occasionally
  between releases.
- "Lightning Strike" is implemented as a thunder sound + particle burst (cosmetic), since PMMP
  core doesn't have a single stable "summon lightning entity" API across versions. If you want an
  actual lightning **entity** (with real strike damage/fire), that needs either a specific PMMP
  version's lightning entity class or a small plugin dependency — happy to wire that in if you
  tell me your exact server software/version.
- "TNT / Boom" uses PocketMine's built-in `Explosion` class (real block damage + knockback) rather
  than spawning a primed TNT entity, so it triggers instantly with no fuse delay.
- Only 30 effect buttons are wired up for now, per the first phase you asked for. The list of
  ~28 enchantments covers all the standard vanilla ones — say the word and I'll extend either
  list further (e.g. add the newer 1.21+ effects/enchants, or split effects into multiple pages).
