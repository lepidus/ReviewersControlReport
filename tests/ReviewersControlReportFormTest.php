<?php

import('lib.pkp.tests.DatabaseTestCase');
import('lib.pkp.classes.user.User');
import('plugins.generic.reviewersControlReport.classes.RCRCompletedReview');
import('plugins.generic.reviewersControlReport.classes.ReviewersControlReportForm');

class ReviewersControlReportFormTest extends DatabaseTestCase
{
    private $reviewerId;
    private $locale = 'en_US';
    private $givenName = 'Walter';
    private $familyName = 'Salles';
    private $username = 'walter.salles';
    private $email = 'walter.salles@ancine.com.br';
    private $affiliation = 'Agência Nacional do Cinema';

    public function setUp(): void
    {
        parent::setUp();
        $this->reviewerId = $this->createUser();
    }

    protected function getAffectedTables()
    {
        return ['users', 'user_settings'];
    }

    private function createUser()
    {
        $user = new User();
        $user->setData('givenName', [$this->locale => $this->givenName]);
        $user->setData('familyName', [$this->locale => $this->familyName]);
        $user->setData('affiliation', [$this->locale => $this->affiliation]);
        $user->setData('email', $this->email);
        $user->setData('username', $this->username);
        $user->setData('password', $this->username);

        return DAORegistry::getDAO('UserDAO')->insertObject($user);
    }

    public function testGetsPersonalDataOfTheReviewersOfTheGivenReviews()
    {
        $form = new ReviewersControlReportForm();
        $completedReview = new RCRCompletedReview(
            $this->reviewerId,
            100,
            'Central do Brasil',
            1,
            '2026-01-02 10:00:00',
            '2026-01-20 00:00:00',
            '2026-01-15 14:32:00',
            SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT,
            4
        );

        $reviewersPersonalData = $form->getReviewersPersonalDataOfReviews([$completedReview, $completedReview]);

        $this->assertCount(1, $reviewersPersonalData);
        $this->assertEquals(
            $this->givenName . ' ' . $this->familyName,
            $reviewersPersonalData[$this->reviewerId][0]
        );
    }

    public function testGetsEmptyPersonalDataWhenTheReviewerNoLongerExists()
    {
        $form = new ReviewersControlReportForm();

        $this->assertEquals(['', '', '', ''], $form->getReviewerPersonalData(999999));
    }

    public function testGetsReviewerPersonalData()
    {
        $form = new ReviewersControlReportForm();

        $reviewerPersonalData = $form->getReviewerPersonalData($this->reviewerId);
        $emptyInterests = '';
        $expectedPersonalData = [
            $this->givenName . ' ' . $this->familyName,
            $this->email,
            $this->affiliation,
            $emptyInterests
        ];

        $this->assertEquals($expectedPersonalData, $reviewerPersonalData);
    }
}
