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

class TestValueObjectWithNamedConstructor
{
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    private function __construct(private readonly string $value) {}

    public function asString(): string
    {
        return $this->value;
    }
}