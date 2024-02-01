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

class TestFixedCorrelationId implements CorrelationId
{
    public static function from(string $id): self
    {
        return new self(UUID::from($id));
    }

    public static function someName(): self
    {
        return new self(UUID::from('eb5c449b-012b-44a4-bd01-5bcdec6a769a'));
    }

    private function __construct(
        private readonly UUID $id
    ) {}

    public function asUUID(): UUID
    {
        return $this->id;
    }
}