<?php declare(strict_types=1);
/*
 * This file is part of Event Generator.
 *
 * (c) Stefan Priebsch <stefan@priebsch.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spriebsch\eventstore\generator;

abstract class AbstractProperty implements Property
{
    private string $name;
    private bool $isNullable = false;

    public static function withName(string $name): static
    {
        return new static($name);
    }

    protected function __construct(string $name)
    {
        $this->name = $name;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function nullable(): self
    {
        $this->isNullable = true;

        return $this;
    }

    public function isValueObject(): bool
    {
        return false;
    }

    public function isEnum(): bool
    {
        return false;
    }

    public function isNullable(): bool
    {
        return $this->isNullable;
    }
}
