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

final class EventSpecification
{
    private readonly string $topic;
    private readonly string $class;
    private ?CorrelationId  $fixedCorrelationId        = null;
    private ?string         $classToUseAsCorrelationId = null;
    private array          $properties                = [];

    public static function forTopic(string $topic): self
    {
        return new self($topic);
    }

    private function __construct(string $topic)
    {
        $this->topic = $topic;
    }

    public function withClass(string $class): self
    {
        $this->class = $class;

        return $this;
    }

    public function withCorrelationIdMappedTo(string $classToUseAsCorrelationId): self
    {
        $this->classToUseAsCorrelationId = $classToUseAsCorrelationId;

        return $this;
    }

    public function withFixedCorrelationId(CorrelationId $correlationId): self
    {
        $this->fixedCorrelationId = $correlationId;

        return $this;
    }

    public function with(Property $property): self
    {
        $this->properties[] = $property;

        return $this;
    }

    public function generateWith(EventGenerator $eventGenerator): string
    {
        return $eventGenerator->generateEvent(
            $this->topic,
            $this->class,
            $this->properties,
            $this->classToUseAsCorrelationId,
            $this->fixedCorrelationId
        );
    }

    public function topic(): string
    {
        return $this->topic;
    }
}
