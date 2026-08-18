<?php

declare(strict_types=1);

namespace MultiEffectBD\BobyDev;

use pocketmine\form\Form;
use pocketmine\player\Player;

/**
 * A minimal, dependency-free "Custom Form" (input fields) wrapper.
 */
class CustomForm implements Form {

    /** @var callable(Player, array):void */
    private $onSubmit;

    /** @var array<int, array<string, mixed>> */
    private array $elements = [];

    public function __construct(
        private string $title,
        callable $onSubmit
    ) {
        $this->onSubmit = $onSubmit;
    }

    public function addLabel(string $text): void {
        $this->elements[] = ["type" => "label", "text" => $text];
    }

    public function addInput(string $text, string $placeholder = "", string $default = ""): void {
        $this->elements[] = [
            "type"        => "input",
            "text"        => $text,
            "placeholder" => $placeholder,
            "default"     => $default
        ];
    }

    public function jsonSerialize(): mixed {
        return [
            "type"    => "custom_form",
            "title"   => $this->title,
            "content" => $this->elements
        ];
    }

    public function handleResponse(Player $player, $data): void {
        if ($data === null) {
            return;
        }
        ($this->onSubmit)($player, (array) $data);
    }
}
