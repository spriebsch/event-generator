<?php
// @codingStandardsIgnoreFile
// @codeCoverageIgnoreStart
// this is an autogenerated file - do not edit
spl_autoload_register(
    function($class) {
        static $classes = null;
        if ($classes === null) {
            $classes = array(
                'spriebsch\\eventstore\\generator\\enumpropertytest' => '/EnumPropertyTest.php',
                'spriebsch\\eventstore\\generator\\eventgeneratortest' => '/EventGeneratorTest.php',
                'spriebsch\\eventstore\\generator\\testcorrelationid' => '/stubs/TestCorrelationId.php',
                'spriebsch\\eventstore\\generator\\testenum' => '/stubs/TestEnum.php',
                'spriebsch\\eventstore\\generator\\testfixedcorrelationid' => '/stubs/TestFixedCorrelationId.php',
                'spriebsch\\eventstore\\generator\\testintegervalueobject' => '/stubs/TestIntegerValueObject.php',
                'spriebsch\\eventstore\\generator\\testmappedcorrelationid' => '/stubs/TestMappedCorrelationId.php',
                'spriebsch\\eventstore\\generator\\testvalueobject' => '/stubs/TestValueObject.php',
                'spriebsch\\eventstore\\generator\\testvalueobjectwithnamedconstructor' => '/stubs/TestValueObjectWithNamedConstructor.php',
                'spriebsch\\eventstore\\generator\\testvalueobjectwithnonpublicnamedconstructor' => '/stubs/TestValueObjectWithNonPublicNamedConstructor.php',
                'spriebsch\\eventstore\\generator\\testvalueobjectwithnonstaticnamedconstructor' => '/stubs/TestValueObjectWithNonStaticNamedConstructor.php',
                'spriebsch\\eventstore\\generator\\testvalueobjectwithparameterlessnamedconstructor' => '/stubs/TestValueObjectWithParameterlessNamedConstructor.php',
                'spriebsch\\eventstore\\generator\\valueobjectpropertytest' => '/ValueObjectPropertyTest.php'
            );
        }
        $cn = strtolower($class);
        if (isset($classes[$cn])) {
            require __DIR__ . $classes[$cn];
        }
    },
    true,
    false
);
// @codeCoverageIgnoreEnd
