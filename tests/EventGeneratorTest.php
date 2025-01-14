<?php declare(strict_types=1);

namespace spriebsch\eventstore\generator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use spriebsch\eventstore\CorrelationId;
use spriebsch\eventstore\Event;
use spriebsch\eventstore\EventId;
use spriebsch\eventstore\Json;
use spriebsch\filesystem\FakeDirectory;
use spriebsch\uuid\UUID;

#[CoversClass(EventGenerator::class)]
#[CoversClass(EventSpecification::class)]
#[CoversClass(Specification::class)]
#[CoversClass(AbstractProperty::class)]
#[CoversClass(ValueObjectProperty::class)]
#[CoversClass(EnumProperty::class)]
#[CoversClass(ArrayProperty::class)]
#[CoversClass(BoolProperty::class)]
#[CoversClass(Specification::class)]
#[CoversClass(FloatProperty::class)]
#[CoversClass(StringProperty::class)]
#[CoversClass(IntegerProperty::class)]
class EventGeneratorTest extends TestCase
{
    #[Group('feature')]
    #[Test]
    public function test_writes_to_target_directory(): void
    {
        $targetDirectory = new FakeDirectory('the-target-directory');
        $specification = require __DIR__ . '/fixtures/eventSpecifications.php';

        EventGenerator::run($specification, $targetDirectory);

        $map = $targetDirectory->file('events.php')->require();

        $this->assertSame(
            [
                'thephpcc.event-generator.a' => 'spriebsch\\eventgenerator\\tests\\A',
                'thephpcc.event-generator.b' => 'spriebsch\\eventgenerator\\tests\\B'
            ],
            $map
        );
    }

    #[Group('feature')]
    public function test_generates_event_with_mapped_correlation_id(): void
    {
        $targetDirectory = new FakeDirectory('the-target-directory');
        $namespace = 'spriebsch\\eventgenerator\\tests\\' . __FUNCTION__;
        $class = $namespace . '\\TestEvent';

        $eventGenerator = new EventGenerator($targetDirectory, $namespace, []);

        $eventGenerator->generateEvent(
            'the-topic',
            'TestEvent',
            [],
            TestMappedCorrelationId::class,
            null
        );

        $code = $targetDirectory->allFiles()[0]->load();

        eval(substr($code, strlen('<?php')));

        $this->assertEventWorksAsExpected(
            $class::from(TestMappedCorrelationId::from(UUID::generate()->asString()))
        );
    }

    #[Group('feature')]
    public function test_generates_event_with_fixed_correlation_id(): void
    {
        $targetDirectory = new FakeDirectory('the-target-directory');
        $namespace = 'spriebsch\\eventgenerator\\tests\\' . __FUNCTION__;
        $class = $namespace . '\\TestEvent';

        $eventGenerator = new EventGenerator(
            $targetDirectory,
            $namespace,
            []
        );

        $correlationId = TestFixedCorrelationId::someName();

        $eventGenerator->generateEvent(
            'the-topic',
            'TestEvent',
            [],
            null,
            $correlationId
        );

        $code = $targetDirectory->allFiles()[0]->load();

        eval(substr($code, strlen('<?php')));

        $event = $class::from();

        $this->assertEventWorksAsExpected($event);
        $this->assertEquals(
            $correlationId->asUUID()->asString(), $event->correlationId()->asUUID()->asString()
        );
    }

    #[Group('feature')]
    public function test_generates_event_with_string_property(): void
    {
        $targetDirectory = new FakeDirectory('the-target-directory');
        $namespace = 'spriebsch\\eventgenerator\\tests\\' . __FUNCTION__;
        $class = $namespace . '\\TestEvent';
        $value = 'the-value';

        $eventGenerator = new EventGenerator($targetDirectory, $namespace, []);

        $eventGenerator->generateEvent(
            'the-topic',
            'TestEvent',
            [
                StringProperty::withName('theProperty')
            ],
            TestMappedCorrelationId::class,
            null
        );

        $code = $targetDirectory->allFiles()[0]->load();

        eval(substr($code, strlen('<?php')));

        $event = $class::from(
            TestMappedCorrelationId::generate(),
            $value
        );

        $this->assertEventWorksAsExpected($event);
        $this->assertSame($value, $event->theProperty());
    }

    #[Group('feature')]
    public function test_generates_event_with_integer_property(): void
    {
        $targetDirectory = new FakeDirectory('the-target-directory');
        $namespace = 'spriebsch\\eventgenerator\\tests\\' . __FUNCTION__;
        $class = $namespace . '\\TestEvent';

        $value = 23;

        $eventGenerator = new EventGenerator($targetDirectory, $namespace, []);

        $eventGenerator->generateEvent(
            'the-topic',
            'TestEvent',
            [
                IntegerProperty::withName('theProperty')
            ],
            TestMappedCorrelationId::class,
            null
        );

        $code = $targetDirectory->allFiles()[0]->load();

        eval(substr($code, strlen('<?php')));

        $event = $class::from(
            TestMappedCorrelationId::generate(),
            $value
        );

        $this->assertEventWorksAsExpected($event);
        $this->assertSame($value, $event->theProperty());
    }

    #[Group('feature')]
    public function test_generates_event_with_float_property(): void
    {
        $targetDirectory = new FakeDirectory('the-target-directory');
        $namespace = 'spriebsch\\eventgenerator\\tests\\' . __FUNCTION__;
        $class = $namespace . '\\TestEvent';

        $value = 23.5;

        $eventGenerator = new EventGenerator($targetDirectory, $namespace, []);

        $eventGenerator->generateEvent(
            'the-topic',
            'TestEvent',
            [
                FloatProperty::withName('theProperty')
            ],
            TestMappedCorrelationId::class,
            null
        );

        $code = $targetDirectory->allFiles()[0]->load();

        eval(substr($code, strlen('<?php')));

        $event = $class::from(
            TestMappedCorrelationId::generate(),
            $value
        );

        $this->assertEventWorksAsExpected($event);
        $this->assertSame($value, $event->theProperty());
    }

    #[Group('feature')]
    public function test_generates_event_with_bool_property(): void
    {
        $targetDirectory = new FakeDirectory('the-target-directory');
        $namespace = 'spriebsch\\eventgenerator\\tests\\' . __FUNCTION__;
        $class = $namespace . '\\TestEvent';

        $value = true;

        $eventGenerator = new EventGenerator($targetDirectory, $namespace, []);

        $eventGenerator->generateEvent(
            'the-topic',
            'TestEvent',
            [
                BoolProperty::withName('theProperty')
            ],
            TestMappedCorrelationId::class,
            null
        );

        $code = $targetDirectory->allFiles()[0]->load();

        eval(substr($code, strlen('<?php')));

        $event = $class::from(
            TestMappedCorrelationId::generate(),
            $value
        );

        $this->assertEventWorksAsExpected($event);
        $this->assertSame($value, $event->theProperty());
    }

    #[Group('feature')]
    public function test_generates_event_with_array_property(): void
    {
        $targetDirectory = new FakeDirectory('the-target-directory');
        $namespace = 'spriebsch\\eventgenerator\\tests\\' . __FUNCTION__;
        $class = $namespace . '\\TestEvent';

        $value = ['a', 'b', 'c'];

        $eventGenerator = new EventGenerator($targetDirectory, $namespace, []);

        $eventGenerator->generateEvent(
            'the-topic',
            'TestEvent',
            [
                ArrayProperty::withName('theProperty')
            ],
            TestMappedCorrelationId::class,
            null
        );

        $code = $targetDirectory->allFiles()[0]->load();

        eval(substr($code, strlen('<?php')));

        $event = $class::from(
            TestMappedCorrelationId::generate(),
            $value
        );

        $this->assertEventWorksAsExpected($event);
        $this->assertSame($value, $event->theProperty());
    }

    #[Group('feature')]
    public function test_generates_event_with_value_object_property(): void
    {
        $targetDirectory = new FakeDirectory('the-target-directory');
        $namespace = 'spriebsch\\eventgenerator\\tests\\' . __FUNCTION__;
        $class = $namespace . '\\TestEvent';

        $value = TestValueObject::from('the-value');

        $eventGenerator = new EventGenerator($targetDirectory, $namespace, []);

        $eventGenerator->generateEvent(
            'the-topic',
            'TestEvent',
            [
                ValueObjectProperty::withName('theProperty')->withClass(TestValueObject::class)
            ],
            TestMappedCorrelationId::class,
            null
        );

        $code = $targetDirectory->allFiles()[0]->load();

        eval(substr($code, strlen('<?php')));

        $event = $class::from(
            TestMappedCorrelationId::generate(),
            $value
        );

        $this->assertEventWorksAsExpected($event);
        $this->assertSame($value->asString(), $event->theProperty()->asString());
    }

    #[Group('feature')]
    public function test_generates_event_with_nullable_value_object_property(): void
    {
        $targetDirectory = new FakeDirectory('the-target-directory');
        $namespace = 'spriebsch\\eventgenerator\\tests\\' . __FUNCTION__;
        $class = $namespace . '\\TestEvent';

        $eventGenerator = new EventGenerator($targetDirectory, $namespace, []);

        $eventGenerator->generateEvent(
            'the-topic',
            'TestEvent',
            [
                ValueObjectProperty::withName('theProperty')->withClass(TestValueObject::class)->nullable()
            ],
            TestMappedCorrelationId::class,
            null
        );

        $code = $targetDirectory->allFiles()[0]->load();

        // file_put_contents('hugo.php', $code);die;
        // print $code; die;

        eval(substr($code, strlen('<?php')));

        $event = $class::from(
            TestMappedCorrelationId::generate(),
            null
        );

        // var_dump($event);die;

        $this->assertEventWorksAsExpected($event);

        $this->assertNull($event->theProperty());
    }

    #[Group('feature')]
    public function test_generates_event_with_int_value_object_property(): void
    {
        $targetDirectory = new FakeDirectory('the-target-directory');
        $namespace = 'spriebsch\\eventgenerator\\tests\\' . __FUNCTION__;
        $class = $namespace . '\\TestEvent';

        $value = TestIntegerValueObject::from(42);

        $eventGenerator = new EventGenerator($targetDirectory, $namespace, []);

        $eventGenerator->generateEvent(
            'the-topic',
            'TestEvent',
            [
                ValueObjectProperty::withName('theProperty')->withClass(TestIntegerValueObject::class)
            ],
            TestMappedCorrelationId::class,
            null
        );

        $code = $targetDirectory->allFiles()[0]->load();

        eval(substr($code, strlen('<?php')));

        $event = $class::from(
            TestMappedCorrelationId::generate(),
            $value
        );

        $this->assertEventWorksAsExpected($event);
        $this->assertSame($value->asInt(), $event->theProperty()->asInt());
    }

    #[Group('feature')]
    public function test_generates_event_with_enum_property(): void
    {
        $targetDirectory = new FakeDirectory('the-target-directory');
        $namespace = 'spriebsch\\eventgenerator\\tests\\' . __FUNCTION__;
        $class = $namespace . '\\TestEvent';

        $value = TestEnum::from('A');

        $eventGenerator = new EventGenerator($targetDirectory, $namespace, []);

        $eventGenerator->generateEvent(
            'the-topic',
            'TestEvent',
            [
                EnumProperty::withName('theProperty')->withEnum(TestEnum::class)
            ],
            TestMappedCorrelationId::class,
            null
        );

        $code = $targetDirectory->allFiles()[0]->load();

        eval(substr($code, strlen('<?php')));

        $event = $class::from(
            TestMappedCorrelationId::generate(),
            $value
        );

        $this->assertEventWorksAsExpected($event);
        $this->assertSame($value->value, $event->theProperty()->value);
    }

    #[Group('feature')]
    public function test_generates_event_with_value_object_property_that_has_non_default_constructor(): void
    {
        $targetDirectory = new FakeDirectory('the-target-directory');
        $namespace = 'spriebsch\\eventgenerator\\tests\\' . __FUNCTION__;
        $class = $namespace . '\\TestEvent';

        $value = TestValueObjectWithNamedConstructor::fromString('the-value');

        $eventGenerator = new EventGenerator(
            $targetDirectory,
            $namespace,
            [
                TestValueObjectWithNamedConstructor::class => 'fromString'
            ]
        );

        $eventGenerator->generateEvent(
            'the-topic',
            'TestEvent',
            [
                ValueObjectProperty::withName('theProperty')
                                   ->withClass(TestValueObjectWithNamedConstructor::class)
            ],
            TestMappedCorrelationId::class,
            null
        );

        $code = $targetDirectory->allFiles()[0]->load();

        eval(substr($code, strlen('<?php')));

        $event = $class::from(
            TestMappedCorrelationId::generate(),
            $value
        );

        $this->assertEventWorksAsExpected($event);
        $this->assertSame($value->asString(), $event->theProperty()->asString());
    }

    #[Group('exception')]
    public function test_class_does_not_exist_for_non_default_constructor_of_value_object(): void
    {
        $targetDirectory = new FakeDirectory('the-target-directory');
        $namespace = 'spriebsch\\eventgenerator\\tests\\' . __FUNCTION__;

        $eventGenerator = new EventGenerator(
            $targetDirectory,
            $namespace,
            [
                'classDoesNotExist' => 'theMethod'
            ]
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('does not exist');

        $eventGenerator->generateEvent(
            'the-topic',
            'TestEvent',
            [],
            TestMappedCorrelationId::class,
            null
        );
    }

    #[Group('exception')]
    public function test_named_constructor_does_not_exist_for_non_default_constructor_of_value_object(): void
    {
        $targetDirectory = new FakeDirectory('the-target-directory');
        $namespace = 'spriebsch\\eventgenerator\\tests\\' . __FUNCTION__;

        $eventGenerator = new EventGenerator(
            $targetDirectory,
            $namespace,
            [
                TestValueObjectWithNamedConstructor::class => 'nonExistingMethod'
            ]
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('::nonExistingMethod() does not exist');

        $eventGenerator->generateEvent(
            'the-topic',
            'TestEvent',
            [],
            TestMappedCorrelationId::class,
            null
        );
    }

    #[Group('exception')]
    public function test_parameterless_named_constructor_of_non_default_constructor_value_object(): void
    {
        $targetDirectory = new FakeDirectory('the-target-directory');
        $namespace = 'spriebsch\\eventgenerator\\tests\\' . __FUNCTION__;

        $eventGenerator = new EventGenerator(
            $targetDirectory,
            $namespace,
            [
                TestValueObjectWithParameterlessNamedConstructor::class => 'fromString'
            ]
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('must have exactly one parameter');

        $eventGenerator->generateEvent(
            'the-topic',
            'TestEvent',
            [],
            TestMappedCorrelationId::class,
            null
        );
    }

    #[Group('exception')]
    public function test_non_static_named_constructor_of_non_default_constructor_value_object(): void
    {
        $targetDirectory = new FakeDirectory('the-target-directory');
        $namespace = 'spriebsch\\eventgenerator\\tests\\' . __FUNCTION__;

        $eventGenerator = new EventGenerator(
            $targetDirectory,
            $namespace,
            [
                TestValueObjectWithNonStaticNamedConstructor::class => 'fromString'
            ]
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('must be static');

        $eventGenerator->generateEvent(
            'the-topic',
            'TestEvent',
            [],
            TestMappedCorrelationId::class,
            null
        );
    }

    #[Group('exception')]
    public function test_non_public_named_constructor_of_non_default_constructor_value_object(): void
    {
        $targetDirectory = new FakeDirectory('the-target-directory');
        $namespace = 'spriebsch\\eventgenerator\\tests\\' . __FUNCTION__;

        $eventGenerator = new EventGenerator(
            $targetDirectory,
            $namespace,
            [
                TestValueObjectWithNonPublicNamedConstructor::class => 'fromString'
            ]
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('must be public');

        $eventGenerator->generateEvent(
            'the-topic',
            'TestEvent',
            [],
            TestMappedCorrelationId::class,
            null
        );
    }

    private function assertEventWorksAsExpected(Event $event): void
    {
        $this->assertInstanceOf(Event::class, $event);
        $this->assertEquals('the-topic', $event::topic());
        $this->assertInstanceOf(EventId::class, $event->id());
        $this->assertInstanceOf(CorrelationId::class, $event->correlationId());

        $json = json_encode($event);

        $class = $event::class;
        $recreated = $class::fromJson(Json::from($json));

        $this->assertEquals($event, $recreated);
    }
}
