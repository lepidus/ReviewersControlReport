<?php

import('lib.pkp.classes.db.DAO');
import('plugins.generic.reviewersControlReport.classes.traits.RCRSubmissionUrl');
import('plugins.generic.reviewersControlReport.classes.traits.RCRStringLength');
import('plugins.generic.reviewersControlReport.classes.RCRReviewerDTO');
import('plugins.generic.reviewersControlReport.classes.RCRClosedDateInterval');
import('plugins.generic.reviewersControlReport.classes.RCRCompletedReview');
import('plugins.generic.reviewersControlReport.classes.RCRReviewsSummary');

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Collection;

/** @class */
class ReviewersControlReportDAO extends DAO
{
    use RCRSubmissionUrl;
    use RCRStringLength;

    public $userDao;
    private $contextId;

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
        $this->contextId = $contextId;
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
        $completedReviews = $this->getCompletedReviews($this->contextId, null, $row['user_id']);
        $reviewsSummary = new RCRReviewsSummary($completedReviews);

        $reviewer = new RCRReviewerDTO(
            $reviewerUser->getId(),
            $reviewerUser->getEmail(),
            $reviewerUser->getFullName(),
            $reviewerUser->getLocalizedAffiliation(),
            $reviewerUser->getInterestString(),
            $reviewsSummary->getQualityAverage(),
            $reviewsSummary->getTotal(),
            $this->getReviewsGridCells($completedReviews)
        );
        return $reviewer;
    }

    /**
     * The expandable rows the grid shows under a reviewer: one submission
     * title, linked to its workflow, plus the date the review was completed.
     */
    private function getReviewsGridCells(array $completedReviews): array
    {
        $gridCells = [];

        foreach ($completedReviews as $completedReview) {
            $submissionUrl = htmlspecialchars($this->getSubmissionWorkflowUrl(
                $completedReview->getSubmissionId(),
                $completedReview->getSubmissionStageId()
            ), ENT_QUOTES, 'UTF-8');
            $submissionTitle = htmlspecialchars(
                $this->formatStringLength($completedReview->getSubmissionTitle(), 40),
                ENT_QUOTES,
                'UTF-8'
            );
            $dateCompleted = date('Y-m-d', strtotime($completedReview->getDateCompleted()));

            $gridCells[] = ["<td style='width: 200pt;' colspan='2'><a href=\"" . $submissionUrl . "\">" . $submissionTitle . "</a></td><td colspan='2'>" . __('common.completed.date', ['dateCompleted' => $dateCompleted]) . "</td>"];
        }

        return $gridCells;
    }

    /**
     * Completed reviews of a context: everything the reports and the grid
     * summarize. Equivalent to filtering review assignments by the statuses
     * RECEIVED, COMPLETE and THANKED, but without going through
     * ReviewAssignment::getStatus(), whose isRead() check costs several
     * queries per assignment.
     */
    public function getCompletedReviews($contextId, $interval = null, $reviewerId = null): array
    {
        $params = [(int) $contextId];
        $sql = 'SELECT ra.reviewer_id, ra.submission_id, ra.round,
                    ra.date_assigned, ra.date_due, ra.date_completed,
                    ra.recommendation, ra.quality, s.stage_id AS submission_stage_id
                FROM review_assignments ra
                    JOIN submissions s ON (s.submission_id = ra.submission_id)
                WHERE s.context_id = ?
                    AND ra.date_completed IS NOT NULL
                    AND ra.declined = 0
                    AND ra.cancelled = 0';

        if (!is_null($reviewerId)) {
            $sql .= ' AND ra.reviewer_id = ?';
            $params[] = (int) $reviewerId;
        }

        if (!is_null($interval)) {
            $sql .= ' AND ra.date_completed BETWEEN ? AND ?';
            $params[] = $interval->getBeginningDate();
            $params[] = $interval->getEndDate();
        }

        $sql .= ' ORDER BY ra.date_completed, ra.review_id';

        $rows = [];
        foreach ($this->retrieve($sql, $params) as $row) {
            $rows[] = (array) $row;
        }

        $titles = $this->getSubmissionTitles(array_column($rows, 'submission_id'));

        $completedReviews = [];
        foreach ($rows as $row) {
            $completedReviews[] = new RCRCompletedReview(
                $row['reviewer_id'],
                $row['submission_id'],
                $titles[$row['submission_id']] ?? '',
                $row['round'],
                $row['date_assigned'],
                $row['date_due'],
                $row['date_completed'],
                $row['recommendation'],
                $row['quality'],
                $row['submission_stage_id']
            );
        }

        return $completedReviews;
    }

    /**
     * Titles of the current publication of each submission, in one query
     * instead of one submission fetch per review.
     */
    private function getSubmissionTitles(array $submissionIds): array
    {
        $submissionIds = array_unique(array_map('intval', $submissionIds));
        if (empty($submissionIds)) {
            return [];
        }

        $placeholders = substr(str_repeat('?,', count($submissionIds)), 0, -1);
        $result = $this->retrieve(
            'SELECT s.submission_id, s.locale AS submission_locale, ps.locale, ps.setting_value
            FROM submissions s
                JOIN publications p ON (p.publication_id = s.current_publication_id)
                JOIN publication_settings ps ON (ps.publication_id = p.publication_id AND ps.setting_name = ?)
            WHERE s.submission_id IN (' . $placeholders . ')',
            array_merge(['title'], array_values($submissionIds))
        );

        $titlesByLocale = [];
        $submissionLocales = [];
        foreach ($result as $row) {
            $row = (array) $row;
            $titlesByLocale[$row['submission_id']][$row['locale']] = $row['setting_value'];
            $submissionLocales[$row['submission_id']] = $row['submission_locale'];
        }

        $currentLocale = AppLocale::getLocale();
        $titles = [];
        foreach ($titlesByLocale as $submissionId => $localizedTitles) {
            $titles[$submissionId] = $localizedTitles[$currentLocale]
                ?? $localizedTitles[$submissionLocales[$submissionId]]
                ?? reset($localizedTitles);
        }

        return $titles;
    }
}
