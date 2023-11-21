<?php

import('lib.pkp.classes.db.DAO');
import('plugins.generic.reviewersControlReport.classes.traits.SubmissionUrl');
import('plugins.generic.reviewersControlReport.classes.traits.StringLength');
import('plugins.generic.reviewersControlReport.classes.ReviewerDTO');

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Collection;

/** @class */
class ReviewersControlReportDAO extends DAO
{
    use SubmissionUrl;
    use StringLength;

    public $userDao;

    public function __construct()
    {
        parent::__construct();
        $this->userDao = DAORegistry::getDAO('UserDAO');
    }

    public function getReviewersIds($journalId)
    {
        $roleDao = DAORegistry::getDAO('RoleDAO'); /* @var $roleDao RoleDAO */

        $reviewers = $roleDao
            ->getUsersByRoleId(ROLE_ID_REVIEWER, $journalId)
            ->toAssociativeArray();
        $allUserReviewersIds = array_keys($reviewers);

        return $allUserReviewersIds;
    }

    public function getReviewers($contextId = null, $searchType = null, $search = null, $searchMatch = null, $dbResultRange = null)
    {
        $paramArray = array(ASSOC_TYPE_USER, 'interest', IDENTITY_SETTING_GIVENNAME, IDENTITY_SETTING_FAMILYNAME);
        $paramArray = array_merge($paramArray, $this->userDao->getFetchParameters());
        $roleId = ROLE_ID_REVIEWER;
        $paramArray[] = (int) $roleId;
        if (isset($contextId)) {
            $paramArray[] = (int) $contextId;
        }
        if ($contextId === null && $roleId === null) {
            return null;
        }

        $searchSql = '';

        $searchTypeMap = array(
            IDENTITY_SETTING_GIVENNAME => 'usgs.setting_value',
            IDENTITY_SETTING_FAMILYNAME => 'usfs.setting_value',
            USER_FIELD_USERNAME => 'u.username',
            USER_FIELD_EMAIL => 'u.email',
            USER_FIELD_INTERESTS => 'cves.setting_value'
        );

        if (!empty($search) && isset($searchTypeMap[$searchType])) {
            $fieldName = $searchTypeMap[$searchType];
            switch ($searchMatch) {
                case 'is':
                    $searchSql = "AND LOWER($fieldName) = LOWER(?)";
                    $paramArray[] = $search;
                    break;
                case 'contains':
                    $searchSql = "AND LOWER($fieldName) LIKE LOWER(?)";
                    $paramArray[] = '%' . $search . '%';
                    break;
                case 'startsWith':
                    $searchSql = "AND LOWER($fieldName) LIKE LOWER(?)";
                    $paramArray[] = $search . '%';
                    break;
            }
        } elseif (!empty($search)) {
            switch ($searchType) {
                case USER_FIELD_USERID:
                    $searchSql = 'AND u.user_id=?';
                    $paramArray[] = $search;
                    break;
            }
        }

        $searchSql .= ' ' . $this->userDao->getOrderBy();

        $sql = 'SELECT DISTINCT u.*,
        ' . $this->userDao->getFetchColumns() . '
        FROM users AS u
        LEFT JOIN user_user_groups uug ON (uug.user_id = u.user_id)
        LEFT JOIN user_groups ug ON (ug.user_group_id = uug.user_group_id)
        LEFT JOIN controlled_vocabs cv ON (cv.assoc_type = ? AND cv.assoc_id = u.user_id AND cv.symbolic = ?)
        LEFT JOIN user_settings usgs ON (usgs.user_id = u.user_id AND usgs.setting_name = ?)
        LEFT JOIN user_settings usfs ON (usfs.user_id = u.user_id AND usfs.setting_name = ?)
        LEFT JOIN controlled_vocab_entries cve ON (cve.controlled_vocab_id = cv.controlled_vocab_id)
        LEFT JOIN controlled_vocab_entry_settings cves ON (cves.controlled_vocab_entry_id = cve.controlled_vocab_entry_id)
        ' . $this->userDao->getFetchJoins() . '
        WHERE 1=1 AND ug.role_id = ?' . (isset($contextId) ? ' AND ug.context_id = ?' : '') . ' ' . $searchSql;
        $result = $this->retrieveRange(
            $sql,
            $paramArray,
            $dbResultRange
        );

        return new DAOResultFactory($result, $this, 'returnReviewerFromRow', [], $sql, $paramArray, $dbResultRange);
    }

    public function getReviewerUser($reviewerId)
    {
        $userDao = DAORegistry::getDAO('UserDAO'); /* @var $userDao UserDAO */
        $user = $userDao->getById($reviewerId);
        return $user;
    }

    public function returnReviewerFromRow($row)
    {
        $reviewerUser = $this->getReviewerUser($row['user_id']);
        $reviewer = new ReviewerDTO(
            $reviewerUser->getId(),
            $reviewerUser->getEmail(),
            $reviewerUser->getFullName(),
            $reviewerUser->getLocalizedAffiliation(),
            $reviewerUser->getInterestString(),
            $this->getQualityAverage($row['user_id']),
            $this->getTotalReviewedSubmissions($row['user_id']),
            $this->getReviewedSubmissionsTitleAndDate($row['user_id'])
        );
        return $reviewer;
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
                $submissionTitle = $this->formatStringLength($submission->getLocalizedTitle(), 40);
                $dateCompleted = $reviewAssignment->getDateCompleted();
                $dateCompleted = date("Y-m-d", strtotime($dateCompleted));
                $submissionUrl = $this->getSubmissionWorkflowUrl($submission->getId(), $submission->getStageId());
                $reviewedSubmissions[] = ["<td style='width: 200pt;' colspan='2'><a href=" . $submissionUrl . ">" . $submissionTitle . "</a></td><td colspan='2'>" . __('common.completed.date', ['dateCompleted' => $dateCompleted]) . "</td>"];
            }
        }
        return $reviewedSubmissions;
    }
}
