<?php

use APP\facades\Repo;
use APP\plugins\generic\reviewersControlReport\classes\RCRCompletedReview;
use APP\plugins\generic\reviewersControlReport\classes\ReviewersControlReportDAO;
use APP\plugins\generic\reviewersControlReport\classes\ReviewersControlReportForm;
use Illuminate\Support\Facades\DB;
use PKP\db\DBResultRange;
use PKP\security\Role;
use PKP\submission\reviewAssignment\ReviewAssignment;

require_once __DIR__ . '/../ReviewersControlReportTestCase.php';
require_once __DIR__ . '/../RCRReportFixtures.php';

class ReviewerDataForReportsTest extends ReviewersControlReportTestCase
{
    use RCRReportFixtures;

    private $contextId;
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
        $this->contextId = $this->createContext('rcr-reviewer-data');
        $this->reviewerId = $this->createUser([
            'email' => $this->email,
            'userName' => $this->username,
        ]);
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testGetsPersonalDataOfTheReviewersOfTheGivenReviews()
    {
        $form = new ReviewersControlReportForm(null, 'en', ['en']);
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

        $reviewersPersonalData = $form->getReviewersPersonalDataOfReviews(
            [$completedReview, $completedReview],
            'en'
        );

        $this->assertCount(1, $reviewersPersonalData);
        $this->assertEquals(
            $this->givenName . ' ' . $this->familyName,
            $reviewersPersonalData[$this->reviewerId][0]
        );
    }

    public function testGetsEmptyPersonalDataWhenTheReviewerNoLongerExists()
    {
        $form = new ReviewersControlReportForm(null, 'en', ['en']);

        $this->assertEquals(['', '', '', ''], $form->getReviewerPersonalData(999999, 'en'));
    }

    public function testGetsReviewerPersonalData()
    {
        $form = new ReviewersControlReportForm(null, 'en', ['en']);

        $reviewerPersonalData = $form->getReviewerPersonalData($this->reviewerId, 'en');
        $emptyInterests = '';
        $expectedPersonalData = [
            $this->givenName . ' ' . $this->familyName,
            $this->email,
            $this->affiliation,
            $emptyInterests
        ];

        $this->assertEquals($expectedPersonalData, $reviewerPersonalData);
    }

    public function testDisabledReviewerRemainsAvailableToBothReportsAndGrid()
    {
        $reviewer = Repo::user()->get($this->reviewerId);
        Repo::user()->edit($reviewer, ['disabled' => true]);

        $this->giveUserTheRole($this->reviewerId, Role::ROLE_ID_REVIEWER, $this->contextId);

        $dao = new ReviewersControlReportDAO();
        $this->assertContains($this->reviewerId, $dao->getReviewersIds($this->contextId));
        $this->assertArrayHasKey($this->reviewerId, $dao->getReviewers($this->contextId));

        $firstPage = $dao->getReviewersPage($this->contextId, new DBResultRange(1, 1));
        $this->assertCount(1, $firstPage->toArray());
        $this->assertGreaterThanOrEqual(1, $firstPage->getCount());
    }
}
