<?php

import('plugins.reports.reviewersControlReport.classes.ReviewersControlReport');
import('lib.pkp.tests.PKPTestCase');

class ReviewersControlReportTest extends PKPTestCase
{
    private $reviewersControlReport;

    public function setUp(): void
    {
        parent::setUp();
        $reviewersControlReport = new ReviewersControlReport($this->defineContext());
        $this->report = $reviewersControlReport->assembleReport();
    }

    private function defineContext()
    {
        $journalDAO = DAORegistry::getDAO('JournalDAO');
        $journal = $journalDAO->getByPath('publicknowledge');
        $contextId = $journal ? $journal->getId() : $journalDAO->getAll(true)[0]->getId();
        return $contextId;
    }

    public function testAssembleReportShouldReturnAnArrayOfReviewers()
    {
        $this->assertIsArray($this->report);
        foreach ($this->report as $reviewer) {
            $this->assertInstanceOf(ReviewerDTO::class, $reviewer);
        }
    }

    public function testAssembledReportShouldHaveReviewersUserInformation()
    {
        foreach ($this->report as $reviewer) {
            $this->assertNotNull($reviewer->getFullName());
            $this->assertNotNull($reviewer->getEmail());
            $this->assertNotNull($reviewer->getAffiliation());
            $this->assertNotNull($reviewer->getInterests());
        }
    }

    public function testAssembledReportShouldHaveReviewersEditorialInformation()
    {
        foreach ($this->report as $reviewer) {
            $this->assertNotNull($reviewer->getQualityAverage());
            $this->assertNotNull($reviewer->getTotalReviewedSubmissions());
            $this->assertNotNull($reviewer->getReviewedSubmissionsTitleAndDate());
        }
    }
}
