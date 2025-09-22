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

class TestValueObjectWithTwoParameters
{
    public static function from(string $string, int $int): self
    {
        return new self($string, $int);
    }

    private function __construct(
        private readonly string $string,
        private readonly int    $int
    ) {}

    public function string(): string
    {
        return $this->string;
    }

    public function int(): int
    {
        return $this->int;
    }
}
