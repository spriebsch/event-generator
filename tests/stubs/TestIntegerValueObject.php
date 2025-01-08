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

class TestIntegerValueObject
{
    public static function from(int $value): self
    {
        return new self($value);
    }

    private function __construct(private readonly int $value) {}

    public function asInt(): int
    {
        return $this->value;
    }
}