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

return EventSpecification::forTopic('thephpcc.event-generator.a')
                         ->withClass('A')
                         ->withFixedCorrelationId(TestFixedCorrelationId::someName());
