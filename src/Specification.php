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

use spriebsch\filesystem\Directory;

final class Specification
{
    private string $namespace;
    /**
     * @var EventSpecification[]
     */
    private array $eventSpecifications    = [];
    private array $nonDefaultConstructors = [];

    public static function inNamespace(string $namespace): self
    {
        return new self($namespace);
    }

    private function __construct(string $namespace)
    {
        $this->namespace = $namespace;
    }

    public function fromEventSpecificationsInDirectory(Directory $directory): self
    {
        foreach ($directory->allFiles() as $file) {
            $this->eventSpecifications[] = $file->require();
        }

        return $this;
    }

    public function fromEventSpecifications(EventSpecification ...$eventSpecifications): self
    {
        $this->eventSpecifications = $eventSpecifications;

        return $this;
    }

    public function usingNonDefaultValueObjectConstructors(array $constructorMap): self
    {
        $this->nonDefaultConstructors = $constructorMap;

        return $this;
    }

    public function writeTo(Directory $targetDirectory): void
    {
        $eventGenerator = new EventGenerator(
            $targetDirectory,
            $this->namespace,
            $this->nonDefaultConstructors
        );

        $map = [];

        foreach ($this->eventSpecifications as $eventSpecification) {
            $map[$eventSpecification->topic()] =
                $eventSpecification->generateWith($eventGenerator);
        };

        $targetDirectory->createFile(
            'events.php',
            sprintf(
                "<?php declare(strict_types=1);\nreturn %s;\n",
                var_export($map, true)
            )
        );
    }
}