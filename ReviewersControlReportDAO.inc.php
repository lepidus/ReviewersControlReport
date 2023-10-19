<?php

import('lib.pkp.classes.db.DAO');

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Collection;

class ReviewersControlReportDAO extends DAO
{
    public function getReviewersIds($journalId)
    {
        $roleDao = DAORegistry::getDAO('RoleDAO'); /* @var $roleDao RoleDAO */

        $reviewers = $roleDao
            ->getUsersByRoleId(ROLE_ID_REVIEWER, $journalId)
            ->toAssociativeArray();
        $allUserReviewersIds = array_keys($reviewers);

        return $allUserReviewersIds;
    }

    public function getReviewerUser($reviewerId)
    {
        $userDao = DAORegistry::getDAO('UserDAO'); /* @var $userDao UserDAO */
        $user = $userDao->getById($reviewerId);
        return $user;
    }

    public function getQualityAverage($reviewerId)
    {
        $reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
        $reviewAssignments = $reviewAssignmentDao->getByUserId($reviewerId);
        $qualityRatings = array();
        foreach ($reviewAssignments as $reviewAssignment) {
            $qualityRatings[] = $reviewAssignment->getQuality();
        }
        $qualityAverage = array_sum($qualityRatings) / count($qualityRatings);
        return $qualityAverage;
    }

    public function getReviewedSubmissionsTitleAndDate($reviewerId)
    {
        $reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
        $reviewAssignments = $reviewAssignmentDao->getByUserId($reviewerId);
        $reviewedSubmissions = [];

        foreach ($reviewAssignments as $reviewAssignment) {
            $submission = Services::get('submission')->get($reviewAssignment->getSubmissionId());
            $submissionTitle = $submission->getLocalizedTitle();
            $dateCompleted = $reviewAssignment->getDateCompleted();
            $reviewedSubmissions[] = [$submissionTitle, $dateCompleted];
        }
        return $reviewedSubmissions;
    }
}
