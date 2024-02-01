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

class ValueObjectProperty extends AbstractProperty
{
    private ?string $class = null;

    public function withClass(string $class): self
    {
        $this->class = $class;

        return $this;
    }

    public function type(): string
    {
        if ($this->class === null) {
            throw new RuntimeException(
                sprintf(
                    'No class specified for value object "%s".',
                    $this->name()
                )
            );
        }

        return '\\' . $this->class;
    }

    public function isValueObject(): bool
    {
        return true;
    }
}