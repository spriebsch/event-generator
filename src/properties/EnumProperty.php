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

use RuntimeException;

class EnumProperty extends AbstractProperty
{
    private ?string $enum = null;

    public function withEnum(string $enum): self
    {
        $this->enum = $enum;

        return $this;
    }

    public function type(): string
    {
        if ($this->enum === null) {
            throw new RuntimeException(
                sprintf(
                    'No enum specified for "%s".',
                    $this->name()
                )
            );
        }

        return '\\' . $this->enum;
    }

    public function isEnum(): bool
    {
        return true;
    }
}