<?php

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.reviewersControlReport.classes.CompletedReview');
import('plugins.generic.reviewersControlReport.classes.ReviewsReportBuilder');

class ReviewsReportBuilderTest extends PKPTestCase
{
    private $builder;
    private $reviewersPersonalData = [
        11 => ['Walter Salles', 'walter.salles@ancine.com.br', 'Agência Nacional do Cinema', 'Cinema'],
        22 => ['Anna Muylaert', 'anna.muylaert@ancine.com.br', 'Universidade de São Paulo', 'Roteiro'],
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->builder = new ReviewsReportBuilder();
    }

    private function createCompletedReview($reviewerId, $overrides = [])
    {
        $data = array_merge([
            'submissionId' => 100,
            'submissionTitle' => 'Central do Brasil',
            'round' => 1,
            'dateAssigned' => '2026-01-02 10:00:00',
            'dateDue' => '2026-01-20 00:00:00',
            'dateCompleted' => '2026-01-15 14:32:00',
            'recommendation' => SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT,
            'quality' => 4,
        ], $overrides);

        return new CompletedReview(
            $reviewerId,
            $data['submissionId'],
            $data['submissionTitle'],
            $data['round'],
            $data['dateAssigned'],
            $data['dateDue'],
            $data['dateCompleted'],
            $data['recommendation'],
            $data['quality']
        );
    }

    public function testColumnsHaveOneHeaderPerReviewField()
    {
        $expectedColumns = [
            __('plugins.reports.reviewersControlReport.field.submissionId'),
            __('plugins.reports.reviewersControlReport.field.submissionTitle'),
            __('plugins.reports.reviewersControlReport.field.reviewRound'),
            __('plugins.reports.reviewersControlReport.field.reviewerName'),
            __('plugins.reports.reviewersControlReport.field.email'),
            __('plugins.reports.reviewersControlReport.field.affiliation'),
            __('plugins.reports.reviewersControlReport.field.dateAssigned'),
            __('plugins.reports.reviewersControlReport.field.dateDue'),
            __('plugins.reports.reviewersControlReport.field.dateCompleted'),
            __('plugins.reports.reviewersControlReport.field.recommendation'),
            __('plugins.reports.reviewersControlReport.field.qualityRating'),
        ];

        $this->assertEquals($expectedColumns, $this->builder->getColumns());
    }

    public function testEachCompletedReviewBecomesItsOwnRow()
    {
        $completedReviews = [
            $this->createCompletedReview(11),
            $this->createCompletedReview(22, ['submissionId' => 200, 'submissionTitle' => 'Que Horas Ela Volta?']),
        ];

        $rows = $this->builder->getRows($this->reviewersPersonalData, $completedReviews);

        $this->assertCount(2, $rows);
        $this->assertEquals('Central do Brasil', $rows[0][1]);
        $this->assertEquals('Walter Salles', $rows[0][3]);
        $this->assertEquals('Que Horas Ela Volta?', $rows[1][1]);
        $this->assertEquals('Anna Muylaert', $rows[1][3]);
    }

    public function testDatesAreWrittenAsPlainDatesInTheirOwnColumns()
    {
        $completedReviews = [$this->createCompletedReview(11)];

        $row = $this->builder->getRows($this->reviewersPersonalData, $completedReviews)[0];

        $this->assertEquals('2026-01-02', $row[6]);
        $this->assertEquals('2026-01-20', $row[7]);
        $this->assertEquals('2026-01-15', $row[8]);
    }

    public function testMissingDateBecomesAnEmptyCellInsteadOfTheUnixEpoch()
    {
        $completedReviews = [$this->createCompletedReview(11, ['dateDue' => null])];

        $row = $this->builder->getRows($this->reviewersPersonalData, $completedReviews)[0];

        $this->assertEquals('', $row[7]);
    }

    public function testRecommendationIsWrittenAsItsLocalizedLabel()
    {
        $completedReviews = [$this->createCompletedReview(11)];

        $row = $this->builder->getRows($this->reviewersPersonalData, $completedReviews)[0];

        $this->assertEquals(__('reviewer.article.decision.accept'), $row[9]);
    }

    public function testMissingRecommendationBecomesAnEmptyCell()
    {
        $completedReviews = [$this->createCompletedReview(11, ['recommendation' => null])];

        $row = $this->builder->getRows($this->reviewersPersonalData, $completedReviews)[0];

        $this->assertEquals('', $row[9]);
    }

    public function testMissingQualityRatingBecomesAnEmptyCell()
    {
        $completedReviews = [$this->createCompletedReview(11, ['quality' => null])];

        $row = $this->builder->getRows($this->reviewersPersonalData, $completedReviews)[0];

        $this->assertEquals('', $row[10]);
    }

    public function testReportWithoutCompletedReviewsHasNoRows()
    {
        $rows = $this->builder->getRows($this->reviewersPersonalData, []);

        $this->assertEquals([], $rows);
    }
}
