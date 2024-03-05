<?php declare(strict_types=1);

namespace spriebsch\eventstore\generator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(EnumProperty::class)]
#[CoversClass(AbstractProperty::class)]
class EnumPropertyTest extends TestCase
{
    #[Group('exception')]
    #[Test]
    public function enum_must_be_specified(): void
    {
        $property = EnumProperty::withName('theName');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No enum');

        $property->type();
    }
}
