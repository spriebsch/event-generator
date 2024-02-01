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

use PhpParser\Builder\Class_;
use PhpParser\Builder\Method;
use PhpParser\BuilderFactory;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\PrettyPrinter\Standard;
use ReflectionClass;
use spriebsch\eventstore\CorrelationId;
use spriebsch\eventstore\Event;
use spriebsch\eventstore\EventId;
use spriebsch\eventstore\EventTrait;
use spriebsch\eventstore\Json;
use spriebsch\eventstore\SerializableEventTrait;
use spriebsch\filesystem\Directory;
use spriebsch\timestamp\Timestamp;
use spriebsch\uuid\UUID;

final class EventGenerator
{
    private string         $namespace;
    private Directory      $targetDirectory;
    private BuilderFactory $builderFactory;
    private array          $nonDefaultConstructors;

    public function __construct(Directory $targetDirectory, string $namespace, array $nonDefaultConstructors)
    {
        $this->namespace = $namespace;
        $this->targetDirectory = $targetDirectory;
        $this->nonDefaultConstructors = $nonDefaultConstructors;
        $this->builderFactory = new BuilderFactory;
    }

    public static function run(Specification $specification, Directory $targetDirectory): void
    {
        if (str_ends_with($targetDirectory->asString(), 'src')) {
            throw new Exception('Cannot make src/ the target of generated events');
        }

        if (str_ends_with($targetDirectory->asString(), 'vendor')) {
            throw new Exception('Cannot make vendor/ the target of generated events');
        }

        $specification->writeTo($targetDirectory);
    }

    public function generateEvent(
        string         $topic,
        string         $eventClass,
        array          $properties,
        ?string        $classToUseAsCorrelationId,
        ?CorrelationId $fixedCorrelationId
    ): string
    {
        foreach ($this->nonDefaultConstructors as $class => $namedConstructor) {
            if (!class_exists($class)) {
                throw new Exception(
                    sprintf('Class %s does not exist', $class)
                );
            }

            if (!method_exists($class, $namedConstructor)) {
                throw new Exception(
                    sprintf('Factory method %s::%s() does not exist', $class, $namedConstructor)
                );
            }

            $reflectMethod = (new ReflectionClass($class))->getMethod($namedConstructor);

            if (!$reflectMethod->isPublic()) {
                throw new Exception(
                    sprintf('Factory method %s::%s() must be public', $class, $namedConstructor)
                );
            }

            if (!$reflectMethod->isStatic()) {
                throw new Exception(
                    sprintf('Factory method %s::%s() must be static', $class, $namedConstructor)
                );
            }

            $reflectParameters = $reflectMethod->getParameters();

            if (count($reflectParameters) !== 1) {
                throw new Exception(
                    sprintf(
                        'Factory method %s::%s() must have exactly one parameter',
                        $class,
                        $namedConstructor
                    )
                );
            }
        }

        if ($classToUseAsCorrelationId === null && $fixedCorrelationId === null) {
            throw new Exception('No correlation ID specified');
        }

        $namespace = $this->builderFactory->namespace($this->namespace);

        $event = $this->builderFactory->class($eventClass);

        $this->useTrait($event, SerializableEventTrait::class);
        $this->useTrait($event, EventTrait::class);
        $this->implementInterface($event, Event::class);

        $this->properties($event, $properties);

        $this->fromMethod($event, $fixedCorrelationId, $classToUseAsCorrelationId, $properties);
        $this->fromJsonMethod($event, $fixedCorrelationId, $classToUseAsCorrelationId, $properties);
        $this->constructor($event, $fixedCorrelationId, $classToUseAsCorrelationId, $properties);
        $this->serializeMethod($event, $fixedCorrelationId, $classToUseAsCorrelationId, $properties);
        $this->topicMethod($event, $topic);

        $this->getters($event, $properties, $classToUseAsCorrelationId, $fixedCorrelationId);

        $event->makeFinal();

        $namespace->addStmt($event);

        $code = $this->addDeclareStrictTypesStatement(
            (new Standard)->prettyPrintFile([$namespace->getNode()])
        );

        $filename = $this->classWithoutNamespace($eventClass) . '.php';

        $this->targetDirectory->deleteFile($filename);
        $this->targetDirectory->createFile($filename, $code);

        return $this->namespace . '\\' . $eventClass;
    }

    private function classWithoutNamespace(string $class): string
    {
        $parts = explode('\\', $class);

        return end($parts);
    }

    private function fromMethod(
        Class_         $event,
        ?CorrelationId $correlationId,
        ?string        $useAsCorrelationId,
        array          $properties
    ): void
    {
        $method = $this->builderFactory->method('from');
        $method->makeStatic();
        $method->makePublic();
        $method->setReturnType('self');

        $this->correlationIdParameter(
            $method,
            $correlationId,
            $useAsCorrelationId
        );
        $this->propertyParameters($method, $properties);

        $this->fromMethodBody(
            $method,
            $correlationId,
            $useAsCorrelationId,
            $properties
        );

        $event->addStmt($method);
    }

    private function fromJsonMethod(
        Class_         $event,
        ?CorrelationId $correlationId,
        ?string        $useAsCorrelationId,
        array          $properties
    ): void
    {
        $method = $this->builderFactory->method('fromJson');
        $method->makeStatic();
        $method->makePublic();
        $method->setReturnType('self');

        $this->fromJsonMethodParameter($method);
        $this->fromJsonMethodBody($method, $correlationId, $useAsCorrelationId, $properties);

        $event->addStmt($method);
    }

    private function topicMethod(Class_ $event, string $topic): void
    {
        $method = $this->builderFactory->method('topic');
        $method->makeStatic();
        $method->makePublic();
        $method->setReturnType('string');

        $method->addStmt(new Return_(new String_($topic)));

        $event->addStmt($method);
    }

    private function constructor(
        Class_         $event,
        ?CorrelationId $correlationId,
        ?string        $useAsCorrelationId,
        array          $properties
    ): void
    {
        $method = $this->builderFactory->method('__construct');
        $method->makePrivate();

        $this->constructorParameters($method, $properties);
        $this->constructorBody($method, $correlationId, $properties);

        $event->addStmt($method);
    }

    private function constructorParameters(Method $method, array $properties): void
    {
        $parameter = $this->builderFactory->param('id');
        $parameter->setType('\\' . EventId::class);
        $method->addParam($parameter);

        $parameter = $this->builderFactory->param('correlationId');
        $parameter->setType('\\' . CorrelationId::class);
        $method->addParam($parameter);

        $parameter = $this->builderFactory->param('timestamp');
        $parameter->setType('\\' . Timestamp::class);
        $method->addParam($parameter);

        foreach ($properties as $property) {
            $parameter = $this->builderFactory->param($property->name());
            $parameter->setType($property->type());
            $method->addParam($parameter);
        }
    }

    private function constructorBody(Method $method, ?CorrelationId $correlationId, array $properties): void
    {
        $method->addStmt(
            new Expr\Assign(
                new PropertyFetch(new Variable('this'), 'id'),
                new Variable('id')
            )
        );

        $method->addStmt(
            new Expr\Assign(
                new PropertyFetch(new Variable('this'), 'correlationId'),
                new Variable('correlationId')
            )
        );

        $method->addStmt(
            new Expr\Assign(
                new PropertyFetch(new Variable('this'), 'timestamp'),
                new Variable('timestamp')
            )
        );

        foreach ($properties as $property) {
            $method->addStmt(
                new Expr\Assign(
                    new PropertyFetch(new Variable('this'), $property->name()),
                    new Variable($property->name())
                )
            );
        }
    }

    private function serializeMethod(
        Class_         $event,
        ?CorrelationId $correlationId,
        ?string        $useAsCorrelationId,
        array          $properties
    ): void
    {
        $method = $this->builderFactory->method('serialize');
        $method->makePublic();
        $method->setReturnType('array');

        $this->serializeMethodBody(
            $method,
            $correlationId,
            $useAsCorrelationId,
            $properties
        );

        $event->addStmt($method);
    }

    private function useTrait(Class_ $event, string $trait): void
    {
        $event->addStmt(
            $this->builderFactory->useTrait('\\' . $trait)
        );
    }

    private function implementInterface(Class_ $event, string $interface): void
    {
        $event->implement('\\' . $interface);
    }

    private function addDeclareStrictTypesStatement(string $code): string
    {
        return str_replace(
            '<?php',
            '<?php declare(strict_types=1);',
            $code
        );
    }

    private function correlationIdParameter(
        Method         $method,
        ?CorrelationId $correlationId,
        ?string        $useAsCorrelationId
    ): void
    {
        if ($useAsCorrelationId !== null) {
            $parameter = $this->builderFactory->param($this->variableNameForCorrelationId($useAsCorrelationId));
            $parameter->setType('\\' . $useAsCorrelationId);
            $method->addParam($parameter);
        }
    }

    private function variableNameForCorrelationId(?string $useAsCorrelationId): string
    {
        if ($useAsCorrelationId === null) {
            return 'correlationId';
        }

        $parts = explode('\\', $useAsCorrelationId);

        return lcfirst(end($parts));
    }

    private function propertyParameters(Method $method, array $properties): void
    {
        foreach ($properties as $property) {
            $parameter = $this->builderFactory->param($property->name());
            $parameter->setType($property->type());
            $method->addParam($parameter);
        }
    }

    private function fromMethodBody(
        Method         $method,
        ?CorrelationId $correlationId,
        ?string        $useAsCorrelationId,
        array          $properties
    ): void
    {
        // Event ID
        $arguments = [
            new Arg(
                new Expr\StaticCall(new Name('\\' . EventId::class), 'generate')
            )
        ];

        // Correlation ID
        /*
        if ($useAsCorrelationId !== null) {
            $arguments[] = $this->builderFactory->methodCall(
                new Variable($this->variableNameForCorrelationId($useAsCorrelationId)),
                'asUUID'
            );
        }
        */

        // Domain ID
        if ($useAsCorrelationId !== null) {
            $arguments[] = new Arg(
                new Variable(
                    $this->variableNameForCorrelationId($useAsCorrelationId)
                )
            );
        } else {
            $arguments[] = new Arg(
                new Expr\StaticCall(
                    new Name('\\' . $correlationId::class),
                    'from',
                    [new Arg(new String_($correlationId->asUUID()->asString()))]
                )
            );
        }

        // Timestamp
        $arguments[] = new Arg(
            new Expr\StaticCall(new Name('\\' . Timestamp::class), 'generate')
        );

        // Properties
        foreach ($properties as $property) {
            $arguments[] = new Arg(new Variable($property->name()));
        }

        $method->addStmt(new Return_($this->builderFactory->new('self', $arguments)));
    }

    private function fromJsonMethodParameter(Method $method): void
    {
        $parameter = $this->builderFactory->param('json');
        $parameter->setType('\\' . Json::class);

        $method->addParam($parameter);
    }

    private function fromJsonMethodBody(
        Method         $method,
        ?CorrelationId $correlationId,
        ?string        $useAsCorrelationId,
        array          $properties
    ): void
    {
        // Event ID
        $arguments = [
            new Arg(
                new Expr\StaticCall(
                    new Name('\\' . EventId::class), 'from',
                    [
                        $this->builderFactory->methodCall(
                            new Expr\Variable('json'),
                            'get',
                            [
                                new String_('id')
                            ]
                        )
                    ]
                )
            )
        ];

        // Correlation ID
        if ($useAsCorrelationId !== null) {
            $arguments[] = new Arg(
                new Expr\StaticCall(
                    new Name('\\' . $useAsCorrelationId), 'from',
                    [
                        $this->builderFactory->methodCall(
                            new Expr\Variable('json'),
                            'get',
                            [
                                new String_('correlationId')
                            ]
                        )
                    ]
                )
            );
        } else {
            $arguments[] = new Arg(
                new Expr\StaticCall(
                    new Name('\\' . $correlationId::class), 'from',
                    [
                        $this->builderFactory->methodCall(
                            new Expr\Variable('json'),
                            'get',
                            [
                                new String_('correlationId')
                            ]
                        )
                    ]
                )
            );
        }

        // Timestamp
        $arguments[] = new Arg(
            new Expr\StaticCall(
                new Name('\\' . Timestamp::class), 'from',
                [
                    $this->builderFactory->methodCall(
                        new Expr\Variable('json'),
                        'get',
                        [
                            new String_('timestamp')
                        ]
                    )
                ]
            )
        );

        foreach ($properties as $property) {
            if ($property->isValueObject()) {
                $arguments[] = new Arg(
                    new Expr\StaticCall(
                        new Name($property->type()), $this->constructorNameOf($property->type()),
                        [
                            $this->builderFactory->methodCall(
                                new Expr\Variable('json'),
                                'get',
                                [
                                    new String_($property->name())
                                ]
                            )
                        ]
                    )
                );
            } else {
                $arguments[] = new Arg(
                    $this->builderFactory->methodCall(
                        new Expr\Variable('json'),
                        'get',
                        [
                            new String_($property->name())
                        ]
                    )
                );
            }
        }

        $method->addStmt(new Return_($this->builderFactory->new('self', $arguments)));
    }

    private function serializeMethodBody(
        Method         $method,
        ?CorrelationId $correlationId,
        ?string        $useAsCorrelationId,
        array          $properties
    ): void
    {
        $values = [];

        foreach ($properties as $property) {
            if ($property->isValueObject()) {
                $values[] = new ArrayItem(
                    $this->builderFactory->methodCall(
                        new PropertyFetch(new Variable('this'), $property->name()),
                        'asString'
                    ),
                    new String_($property->name())
                );
            } else {
                $values[] = new ArrayItem(
                    new PropertyFetch(new Variable('this'), $property->name()),
                    new String_($property->name())
                );
            }
        }

        $method->addStmt(new Return_(new Expr\Array_($values)));
    }

    private function constructorNameOf(string $class): string
    {
        if (str_starts_with($class, '\\')) {
            $class = substr($class, 1);
        }

        if (!isset($this->nonDefaultConstructors[$class])) {
            return 'from';
        }

        return $this->nonDefaultConstructors[$class];
    }

    private function properties(Class_ $event, array $properties): void
    {
        foreach ($properties as $property) {
            $classProperty = new \PhpParser\Builder\Property($property->name());
            $classProperty->makePrivate();
            $classProperty->setType($property->type());
            $classProperty->makeReadonly();

            $event->addStmt($classProperty);
        }
    }

    private function getters(
        Class_ $event,
        array $properties,
        ?string        $classToUseAsCorrelationId,
        ?CorrelationId $fixedCorrelationId
    ): void
    {
        if ($classToUseAsCorrelationId !== null) {
            $name = $this->nameOf($classToUseAsCorrelationId);

            $method = $this->builderFactory->method($name);
            $method->setReturnType('\\' . $classToUseAsCorrelationId);
            $method->makePublic();

            $method->addStmt(
                new Return_(
                    new PropertyFetch(new Variable('this'), 'correlationId')
                )
            );

            $event->addStmt($method);
        }

        foreach ($properties as $property) {
            $method = $this->builderFactory->method($property->name());
            $method->setReturnType($property->type());
            $method->makePublic();

            $method->addStmt(
                new Return_(
                    new PropertyFetch(new Variable('this'), $property->name())
                )
            );

            $event->addStmt($method);
        }
    }

    private function nameOf(string $class): string
    {
        if (str_starts_with($class, '\\')) {
            $class = substr($class, 1);
        }

        $parts = explode('\\', $class);
        $basename = $parts[count($parts) - 1];

        return lcfirst($basename);
    }
}
