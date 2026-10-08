<?php

declare(strict_types=1);

namespace MultiEffectBD\BobyDev;

use pocketmine\form\Form;
use pocketmine\player\Player;

/**
 * A minimal, dependency-free "Simple Form" (button list) wrapper.
 * Works with PocketMine-MP's built-in form system (no FormAPI virion needed).
 */
class SimpleForm implements Form {

    /** @var callable(Player, int):void */
    private $onSubmit;

    /** @var array<int, array<string, mixed>> */
    private array $buttons = [];

    public function __construct(
        private string $title,
        private string $content,
        callable $onSubmit
    ) {
        $this->onSubmit = $onSubmit;
    }

    public function addButton(string $text): void {
        $this->buttons[] = ["text" => $text];
    }

    public function jsonSerialize(): mixed {
        return [
            "type"    => "form",
            "title"   => $this->title,
            "content" => $this->content,
            "buttons" => $this->buttons
        ];
    }

    public function handleResponse(Player $player, $data): void {
        if ($data === null) {
            // player closed the form, do nothing
            return;
        }
        ($this->onSubmit)($player, (int) $data);
    }
}
