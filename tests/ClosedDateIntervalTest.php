<?php

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.reviewersControlReport.classes.ClosedDateInterval');

class ClosedDateIntervalTest extends PKPTestCase
{
    public function testIntervalIsValidWhenBeginningComesBeforeEnd()
    {
        $interval = new ClosedDateInterval('2026-01-01', '2026-12-31');

        $this->assertTrue($interval->isValid());
    }

    public function testIntervalIsValidWhenBeginningAndEndAreTheSameDay()
    {
        $interval = new ClosedDateInterval('2026-05-10', '2026-05-10');

        $this->assertTrue($interval->isValid());
    }

    public function testIntervalIsInvalidWhenBeginningComesAfterEnd()
    {
        $interval = new ClosedDateInterval('2026-12-31', '2026-01-01');

        $this->assertFalse($interval->isValid());
    }

    public function testIntervalCoversTheWholeOfBothBoundaryDays()
    {
        $interval = new ClosedDateInterval('2026-05-10', '2026-05-12');

        $this->assertEquals('2026-05-10 00:00:00', $interval->getBeginningDate());
        $this->assertEquals('2026-05-12 23:59:59', $interval->getEndDate());
    }
}
