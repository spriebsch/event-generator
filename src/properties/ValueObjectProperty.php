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
    private ?array $methods = null;

    public function withClass(string $class): self
    {
        $this->class = $class;

        return $this;
    }

    public function withMethods(...$methods): self
    {
        $this->methods = $methods;

        return $this;
    }

    public function hasMethods(): bool
    {
        return $this->methods !== null;
    }

    public function methods(): array
    {
        if ($this->methods === null) {
            throw new RuntimeException(
                sprintf(
                    'No methods specified for value object "%s".',
                    $this->name()
                )
            );
        }

        return $this->methods;
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
