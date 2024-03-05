<?php declare(strict_types=1);

namespace spriebsch\eventstore\generator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(ValueObjectProperty::class)]
#[CoversClass(AbstractProperty::class)]
class ValueObjectPropertyTest extends TestCase
{
    #[Group('exception')]
    #[Test]
    public function value_object_must_be_specified(): void
    {
        $property = ValueObjectProperty::withName('theName');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No class specified');

        $property->type();
    }
}
