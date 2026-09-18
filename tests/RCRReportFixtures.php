<?php

use APP\core\Application;
use APP\facades\Repo;
use PKP\db\DAORegistry;
use PKP\security\Role;
use PKP\submission\PKPSubmission;
use PKP\submission\reviewAssignment\ReviewAssignment;
use PKP\userGroup\UserGroup;

/**
 * Builds the journals, users, submissions and reviews the integration tests
 * work on, so that each test says what it needs instead of copying how to
 * create it. Only the tests that reach the database take it.
 */
trait RCRReportFixtures
{
    public const SEEDED_CONTEXT_ID = 1;

    private $fixtureLocale = 'en';

    protected function createContext(string $path): int
    {
        $context = Application::getContextDAO()->newDataObject();
        $context->setData('urlPath', $path);
        $context->setData('enabled', true);
        $context->setData('seq', 1);
        $context->setData('primaryLocale', $this->fixtureLocale);
        $context->setData('supportedLocales', [$this->fixtureLocale]);
        $context->setData('name', [$this->fixtureLocale => $path]);
        $context->setData('contactName', 'Reviewers Control Report');
        $context->setData('contactEmail', 'reviewers-control@example.test');

        return Application::getContextDAO()->insertObject($context);
    }

    protected function createUser(array $overrides = []): int
    {
        $username = $overrides['userName'] ?? 'walter.salles';
        $user = Repo::user()->newDataObject();
        $user->setData('givenName', [$this->fixtureLocale => $overrides['givenName'] ?? 'Walter']);
        $user->setData('familyName', [$this->fixtureLocale => $overrides['familyName'] ?? 'Salles']);
        $user->setData('affiliation', [
            $this->fixtureLocale => $overrides['affiliation'] ?? 'Agência Nacional do Cinema',
        ]);
        $user->setData('email', $overrides['email'] ?? $username . '@ancine.com.br');
        $user->setData('userName', $username);
        $user->setData('password', $username);
        $user->setData('dateRegistered', '2026-01-01 00:00:00');

        return Repo::user()->add($user);
    }

    /**
     * The user groups of a role belong to the journal the installer seeded,
     * so tests that need a role work on that journal. This is the only place
     * that depends on it.
     */
    protected function giveUserTheRole(int $userId, int $roleId): void
    {
        // Site administrators hold their role on the site, which has no
        // context id of its own, so the group is looked up by role alone.
        $userGroup = $roleId === Role::ROLE_ID_SITE_ADMIN
            ? UserGroup::withRoleIds([$roleId])->first()
            : Repo::userGroup()->getByRoleIds([$roleId], self::SEEDED_CONTEXT_ID)->first();

        if (is_null($userGroup)) {
            throw new RuntimeException('The test database has no user group for role ' . $roleId);
        }

        Repo::userGroup()->assignUserToGroup($userId, $userGroup->id);
    }

    protected function createSubmission(int $contextId, string $title): int
    {
        $submission = Repo::submission()->newDataObject([
            'contextId' => $contextId,
            'status' => PKPSubmission::STATUS_QUEUED,
            'locale' => $this->fixtureLocale,
        ]);
        $submissionId = Repo::submission()->dao->insert($submission);

        $publication = Repo::publication()->newDataObject([
            'submissionId' => $submissionId,
            'title' => [$this->fixtureLocale => $title],
        ]);
        $publicationId = Repo::publication()->add($publication);

        Repo::submission()->edit($submission, ['currentPublicationId' => $publicationId]);

        return $submissionId;
    }

    protected function createCompletedReview(int $submissionId, int $reviewerId, ?string $dateCompleted, array $overrides = []): void
    {
        $reviewRoundId = DAORegistry::getDAO('ReviewRoundDAO')
            ->build($submissionId, WORKFLOW_STAGE_ID_EXTERNAL_REVIEW, 1)
            ->getId();

        $reviewAssignment = Repo::reviewAssignment()->newDataObject();
        $reviewAssignment->setSubmissionId($submissionId);
        $reviewAssignment->setReviewerId($reviewerId);
        $reviewAssignment->setReviewRoundId($reviewRoundId);
        $reviewAssignment->setStageId(WORKFLOW_STAGE_ID_EXTERNAL_REVIEW);
        $reviewAssignment->setRound(1);
        $reviewAssignment->setDateAssigned('2026-01-02 10:00:00');
        $reviewAssignment->setDateResponseDue('2026-01-10 00:00:00');
        $reviewAssignment->setDateConfirmed('2026-01-03 00:00:00');
        $reviewAssignment->setDateDue('2026-01-20 00:00:00');
        $reviewAssignment->setDateCompleted($dateCompleted);
        $reviewAssignment->setQuality($overrides['quality'] ?? 4);
        $reviewAssignment->setRecommendation(
            $overrides['recommendation'] ?? ReviewAssignment::SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT
        );
        $reviewAssignment->setDeclined($overrides['declined'] ?? 0);
        $reviewAssignment->setCancelled($overrides['cancelled'] ?? 0);

        Repo::reviewAssignment()->add($reviewAssignment);
    }
}
