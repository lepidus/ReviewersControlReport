<?php

import('classes.journal.Journal');
import('classes.submission.Submission');
import('classes.publication.Publication');
import('lib.pkp.classes.user.User');
import('lib.pkp.classes.submission.reviewAssignment.ReviewAssignment');

/**
 * Builds the journals, users, submissions and reviews the integration tests
 * work on, so that each test says what it needs instead of copying how to
 * create it. Only the tests that reach the database take it.
 */
trait RCRReportFixtures
{
    private $fixtureLocale = 'en_US';

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
        $user->setData('givenName', [$this->fixtureLocale => $overrides['givenName'] ?? 'Walter']);
        $user->setData('familyName', [$this->fixtureLocale => $overrides['familyName'] ?? 'Salles']);
        $user->setData('affiliation', [
            $this->fixtureLocale => $overrides['affiliation'] ?? 'Agência Nacional do Cinema',
        ]);
        $user->setData('email', $overrides['email'] ?? $username . '@ancine.com.br');
        $user->setData('username', $username);
        $user->setData('password', $username);

        return DAORegistry::getDAO('UserDAO')->insertObject($user);
    }

    /**
     * Roles live in user groups of a journal, and a journal created by a test
     * has none: the installer seeds them only for the journals it creates.
     */
    protected function giveUserTheRole(int $userId, int $roleId, ?int $contextId = null): void
    {
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');
        $userGroup = $userGroupDao->newDataObject();
        // Site administrators hold their role on the site
        $userGroup->setContextId($roleId === ROLE_ID_SITE_ADMIN ? CONTEXT_SITE : $contextId);
        $userGroup->setRoleId($roleId);
        $userGroup->setDefault(true);
        $userGroup->setShowTitle(false);
        $userGroup->setPermitSelfRegistration(false);
        $userGroup->setPermitMetadataEdit(false);

        $userGroupDao->assignUserToGroup($userId, $userGroupDao->insertObject($userGroup));
    }

    protected function createSubmission(int $contextId, string $title): int
    {
        $submission = new Submission();
        $submission->setData('contextId', $contextId);
        $submission->setData('status', STATUS_QUEUED);
        $submission->setData('locale', $this->fixtureLocale);
        $submissionId = DAORegistry::getDAO('SubmissionDAO')->insertObject($submission);

        $publication = new Publication();
        $publication->setData('submissionId', $submissionId);
        $publication->setData('title', $title, $this->fixtureLocale);
        $publicationId = DAORegistry::getDAO('PublicationDAO')->insertObject($publication);

        $submission->setData('currentPublicationId', $publicationId);
        DAORegistry::getDAO('SubmissionDAO')->updateObject($submission);

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
        $reviewAssignment->setDateDue('2026-01-20 00:00:00');
        $reviewAssignment->setDateCompleted($dateCompleted);
        $reviewAssignment->setQuality($overrides['quality'] ?? 4);
        $reviewAssignment->setRecommendation($overrides['recommendation'] ?? SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT);
        $reviewAssignment->setDeclined($overrides['declined'] ?? 0);
        $reviewAssignment->setCancelled($overrides['cancelled'] ?? 0);

        DAORegistry::getDAO('ReviewAssignmentDAO')->insertObject($reviewAssignment);
    }
}
