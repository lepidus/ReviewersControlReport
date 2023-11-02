<?php

import('lib.pkp.classes.db.DAO');

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Collection;

/** @class */
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
        $qualityRatings = array_filter(
            $qualityRatings,
            function ($value) {
                return $value != null;
            }
        );
        $ratingsCount = count($qualityRatings);
        $qualityAverage = ($ratingsCount != 0) ? (array_sum(array_values($qualityRatings)) / $ratingsCount) : 0;
        return $qualityAverage;
    }

    public function getTotalReviewedSubmissions($reviewerId)
    {
        $reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
        $reviewAssignments = $reviewAssignmentDao->getByUserId($reviewerId);
        $completedReviewAssignments = array();
        foreach ($reviewAssignments as $reviewAssignment) {
            if (in_array($reviewAssignment->getStatus(), [REVIEW_ASSIGNMENT_STATUS_RECEIVED, REVIEW_ASSIGNMENT_STATUS_COMPLETE, REVIEW_ASSIGNMENT_STATUS_THANKED])) {
                $completedReviewAssignments[] = $reviewAssignment;
            }
        }

        return count($completedReviewAssignments);
    }

    public function getReviewedSubmissionsTitleAndDate($reviewerId)
    {
        $reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
        $reviewAssignments = $reviewAssignmentDao->getByUserId($reviewerId);
        $reviewedSubmissions = [];

        foreach ($reviewAssignments as $reviewAssignment) {
            if (in_array($reviewAssignment->getStatus(), [REVIEW_ASSIGNMENT_STATUS_RECEIVED, REVIEW_ASSIGNMENT_STATUS_COMPLETE, REVIEW_ASSIGNMENT_STATUS_THANKED])) {
                $submission = Services::get('submission')->get($reviewAssignment->getSubmissionId());
                $submissionTitle = $submission->getLocalizedTitle();
                $dateCompleted = $reviewAssignment->getDateCompleted();
                $dateCompleted = date("Y-m-d", strtotime($dateCompleted));
                $reviewedSubmissions[] = [$submissionTitle, $dateCompleted];
            }
        }
        return $reviewedSubmissions;
    }
}
