<?php

declare(strict_types=1);

use spriebsch\eventstore\generator\Specification;
use spriebsch\filesystem\Filesystem;

return Specification::inNamespace('spriebsch\\eventgenerator\\tests')
                    ->fromEventSpecificationsInDirectory(
                        Filesystem::from(__DIR__ . '/eventSpecifications')
                    );
