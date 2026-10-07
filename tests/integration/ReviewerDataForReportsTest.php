<?php

use APP\facades\Repo;
use APP\plugins\generic\reviewersControlReport\classes\RCRCompletedReview;
use APP\plugins\generic\reviewersControlReport\classes\ReviewersControlReportForm;
use Illuminate\Support\Facades\DB;
use PKP\submission\reviewAssignment\ReviewAssignment;
use PKP\user\User;

require_once __DIR__ . '/../ReviewersControlReportTestCase.php';

class ReviewerDataForReportsTest extends ReviewersControlReportTestCase
{
    private $reviewerId;
    private $locale = 'en';
    private $givenName = 'Walter';
    private $familyName = 'Salles';
    private $username = 'walter.salles';
    private $email = 'walter.salles@ancine.com.br';
    private $affiliation = 'Agência Nacional do Cinema';

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->reviewerId = $this->createUser();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function createUser()
    {
        $suffix = uniqid();
        $this->username = 'rcr' . $suffix;
        $this->email = 'rcr.' . $suffix . '@example.test';
        $user = new User();
        $user->setGivenName($this->givenName, $this->locale);
        $user->setFamilyName($this->familyName, $this->locale);
        $user->setAffiliation($this->affiliation, $this->locale);
        $user->setEmail($this->email);
        $user->setUsername($this->username);
        $user->setPassword($this->username);
        $user->setDateRegistered('2026-01-01 00:00:00');

        return Repo::user()->add($user);
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
            ReviewAssignment::SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT,
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
