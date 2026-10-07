<?php

use APP\core\Application;
use APP\facades\Repo;
use APP\journal\Journal;
use APP\publication\Publication;
use APP\submission\Submission;
use PKP\db\DAORegistry;
use PKP\security\Role;
use PKP\submission\reviewAssignment\ReviewAssignment;
use PKP\user\User;

/**
 * Builds the journals, users, submissions and reviews the integration tests
 * work on, so that each test says what it needs instead of copying how to
 * create it. Only the tests that reach the database take it.
 */
trait RCRReportFixtures
{
    private $fixtureLocale = 'en';

    protected function createContext(string $path): int
    {
        $journal = new Journal();
        $journal->setPath($path);
        $journal->setPrimaryLocale($this->fixtureLocale);
        $journal->setEnabled(true);
        $journal->setSequence(1);
        $journal->setName($path, $this->fixtureLocale);

        return DAORegistry::getDAO('JournalDAO')->insertObject($journal);
    }

    protected function createUser(array $overrides = []): int
    {
        $username = $overrides['userName'] ?? 'walter.salles';
        $user = new User();
        $user->setGivenName($overrides['givenName'] ?? 'Walter', $this->fixtureLocale);
        $user->setFamilyName($overrides['familyName'] ?? 'Salles', $this->fixtureLocale);
        $user->setAffiliation($overrides['affiliation'] ?? 'Agência Nacional do Cinema', $this->fixtureLocale);
        $user->setEmail($overrides['email'] ?? $username . '@ancine.com.br');
        $user->setUsername($username);
        $user->setPassword($username);
        $user->setDateRegistered('2026-01-01 00:00:00');

        return Repo::user()->add($user);
    }

    /**
     * Roles live in user groups of a journal, and a journal created by a test
     * has none: the installer seeds them only for the journals it creates.
     */
    protected function giveUserTheRole(int $userId, int $roleId, ?int $contextId = null): void
    {
        $userGroup = Repo::userGroup()->newDataObject([
            // Site administrators hold their role on the site
            'contextId' => $roleId === Role::ROLE_ID_SITE_ADMIN ? Application::CONTEXT_SITE : $contextId,
            'roleId' => $roleId,
            'isDefault' => true,
            'showTitle' => false,
            'permitSelfRegistration' => false,
            'permitMetadataEdit' => false,
            'name' => [$this->fixtureLocale => 'Role ' . $roleId],
            'abbrev' => [$this->fixtureLocale => 'R' . $roleId],
        ]);

        Repo::userGroup()->assignUserToGroup($userId, Repo::userGroup()->add($userGroup));
    }

    protected function createSubmission(int $contextId, string $title): int
    {
        $submission = new Submission();
        $submission->setData('contextId', $contextId);
        $submission->setData('status', Submission::STATUS_QUEUED);
        $submission->setData('locale', $this->fixtureLocale);
        $submissionId = Repo::submission()->dao->insert($submission);

        $publication = new Publication();
        $publication->setData('submissionId', $submissionId);
        $publication->setData('title', $title, $this->fixtureLocale);
        $publicationId = Repo::publication()->add($publication);

        Repo::submission()->edit($submission, ['currentPublicationId' => $publicationId]);

        return $submissionId;
    }

    protected function createCompletedReview(int $submissionId, int $reviewerId, ?string $dateCompleted, array $overrides = []): void
    {
        $reviewRoundId = DAORegistry::getDAO('ReviewRoundDAO')
            ->build($submissionId, WORKFLOW_STAGE_ID_EXTERNAL_REVIEW, 1)
            ->getId();

        $reviewAssignment = new ReviewAssignment();
        $reviewAssignment->setSubmissionId($submissionId);
        $reviewAssignment->setReviewerId($reviewerId);
        $reviewAssignment->setReviewRoundId($reviewRoundId);
        $reviewAssignment->setStageId(WORKFLOW_STAGE_ID_EXTERNAL_REVIEW);
        $reviewAssignment->setRound(1);
        $reviewAssignment->setDateAssigned('2026-01-02 10:00:00');
        $reviewAssignment->setDateResponseDue('2026-01-10 00:00:00');
        $reviewAssignment->setDateDue('2026-01-20 00:00:00');
        $reviewAssignment->setDateCompleted($dateCompleted);
        $reviewAssignment->setQuality($overrides['quality'] ?? 4);
        $reviewAssignment->setRecommendation(
            $overrides['recommendation'] ?? ReviewAssignment::SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT
        );
        $reviewAssignment->setDeclined($overrides['declined'] ?? 0);
        $reviewAssignment->setCancelled($overrides['cancelled'] ?? 0);

        DAORegistry::getDAO('ReviewAssignmentDAO')->insertObject($reviewAssignment);
    }
}
