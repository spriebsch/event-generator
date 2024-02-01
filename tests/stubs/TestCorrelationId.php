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

use spriebsch\eventstore\CorrelationId;
use spriebsch\uuid\UUID;

class TestCorrelationId implements CorrelationId
{
    public static function from(string $id): self
    {
        return new self(UUID::from($id));
    }

    public static function generate(): self
    {
        return new self(UUID::generate());
    }

    private function __construct(
        private readonly UUID $id
    ) {}

    public function asUUID(): UUID
    {
        return $this->id;
    }
}