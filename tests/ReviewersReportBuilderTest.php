<?php

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.reviewersControlReport.classes.CompletedReview');
import('plugins.generic.reviewersControlReport.classes.ReviewersReportBuilder');

class ReviewersReportBuilderTest extends PKPTestCase
{
    private $builder;
    private $reviewersPersonalData = [
        11 => ['Walter Salles', 'walter.salles@ancine.com.br', 'Agência Nacional do Cinema', 'Cinema'],
        22 => ['Anna Muylaert', 'anna.muylaert@ancine.com.br', 'Universidade de São Paulo', 'Roteiro'],
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->builder = new ReviewersReportBuilder();
    }

    private function createCompletedReview($reviewerId, $submissionId, $title, $dateCompleted, $quality)
    {
        return new CompletedReview(
            $reviewerId,
            $submissionId,
            $title,
            1,
            '2026-01-02 10:00:00',
            '2026-01-20 00:00:00',
            $dateCompleted,
            SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT,
            $quality
        );
    }

    public function testColumnsKeepTheReviewerFieldsAndAddTheReviewPeriodDates()
    {
        $expectedColumns = [
            __('plugins.reports.reviewersControlReport.field.fullName'),
            __('plugins.reports.reviewersControlReport.field.email'),
            __('plugins.reports.reviewersControlReport.field.affiliation'),
            __('plugins.reports.reviewersControlReport.field.interests'),
            __('plugins.reports.reviewersControlReport.field.qualityAverage'),
            __('plugins.reports.reviewersControlReport.field.reviewedSubmissionsTotal'),
            __('plugins.reports.reviewersControlReport.field.firstReviewDate'),
            __('plugins.reports.reviewersControlReport.field.lastReviewDate'),
            __('plugins.reports.reviewersControlReport.field.reviewedSubmissionsTitles'),
        ];

        $this->assertEquals($expectedColumns, $this->builder->getColumns());
    }

    public function testEachReviewerBecomesOneRow()
    {
        $rows = $this->builder->getRows($this->reviewersPersonalData, []);

        $this->assertCount(2, $rows);
        $this->assertEquals('Walter Salles', $rows[0][0]);
        $this->assertEquals('Anna Muylaert', $rows[1][0]);
    }

    public function testAggregatesCountOnlyTheReviewsGivenToTheBuilder()
    {
        $completedReviews = [
            $this->createCompletedReview(11, 101, 'Central do Brasil', '2026-01-15 14:32:00', 4),
            $this->createCompletedReview(11, 102, 'Terra Estrangeira', '2026-03-20 09:00:00', 2),
        ];

        $row = $this->builder->getRows($this->reviewersPersonalData, $completedReviews)[0];

        $this->assertEquals('3.00', $row[4]);
        $this->assertEquals(2, $row[5]);
    }

    public function testQualityAverageIgnoresReviewsWithoutRating()
    {
        $completedReviews = [
            $this->createCompletedReview(11, 101, 'Central do Brasil', '2026-01-15 14:32:00', 4),
            $this->createCompletedReview(11, 102, 'Terra Estrangeira', '2026-03-20 09:00:00', null),
        ];

        $row = $this->builder->getRows($this->reviewersPersonalData, $completedReviews)[0];

        $this->assertEquals('4.00', $row[4]);
        $this->assertEquals(2, $row[5]);
    }

    public function testFirstAndLastReviewDatesComeFromTheReviewsGivenToTheBuilder()
    {
        $completedReviews = [
            $this->createCompletedReview(11, 101, 'Central do Brasil', '2026-03-20 09:00:00', 4),
            $this->createCompletedReview(11, 102, 'Terra Estrangeira', '2026-01-15 14:32:00', 2),
        ];

        $row = $this->builder->getRows($this->reviewersPersonalData, $completedReviews)[0];

        $this->assertEquals('2026-01-15', $row[6]);
        $this->assertEquals('2026-03-20', $row[7]);
    }

    public function testTitlesColumnListsTitlesWithoutTheCompletionDate()
    {
        $completedReviews = [
            $this->createCompletedReview(11, 101, 'Central do Brasil', '2026-01-15 14:32:00', 4),
            $this->createCompletedReview(11, 102, 'Terra Estrangeira', '2026-03-20 09:00:00', 2),
        ];

        $row = $this->builder->getRows($this->reviewersPersonalData, $completedReviews)[0];

        $this->assertEquals("Central do Brasil\nTerra Estrangeira", $row[8]);
        $this->assertStringNotContainsString('2026-01-15', $row[8]);
    }

    public function testReviewerWithoutReviewsInThePeriodIsStillListedWithEmptyReviewFields()
    {
        $completedReviews = [
            $this->createCompletedReview(11, 101, 'Central do Brasil', '2026-01-15 14:32:00', 4),
        ];

        $row = $this->builder->getRows($this->reviewersPersonalData, $completedReviews)[1];

        $this->assertEquals('Anna Muylaert', $row[0]);
        $this->assertEquals('', $row[4]);
        $this->assertEquals('', $row[5]);
        $this->assertEquals('', $row[6]);
        $this->assertEquals('', $row[7]);
        $this->assertEquals('', $row[8]);
    }
}
