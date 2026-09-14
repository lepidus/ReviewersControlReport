<?php

namespace APP\plugins\generic\reviewersControlReport\classes;

use APP\facades\Repo;
use APP\plugins\generic\reviewersControlReport\classes\traits\RCRStringLength;
use APP\plugins\generic\reviewersControlReport\classes\traits\RCRSubmissionUrl;
use PKP\db\DAO;
use PKP\core\VirtualArrayIterator;
use PKP\facades\Locale;
use PKP\security\Role;
use PKP\user\Collector;

/** @class */
class ReviewersControlReportDAO extends DAO
{
    use RCRSubmissionUrl;
    use RCRStringLength;

    private $contextId;

    public function getReviewersIds($journalId)
    {
        return Repo::user()->getCollector()
            ->filterByStatus(Collector::STATUS_ALL)
            ->filterByRoleIds([Role::ROLE_ID_REVIEWER])
            ->filterByContextIds([(int) $journalId])
            ->getIds()
            ->all();
    }

    public function getReviewers($contextId, $rangeInfo = null)
    {
        $this->contextId = (int) $contextId;
        $collector = Repo::user()->getCollector()
            ->filterByStatus(Collector::STATUS_ALL)
            ->filterByRoleIds([Role::ROLE_ID_REVIEWER])
            ->filterByContextIds([$this->contextId])
            ->orderBy(Collector::ORDERBY_FAMILYNAME, Collector::ORDER_DIR_ASC, [Locale::getLocale()]);

        $totalCount = $collector->getCount();
        if ($rangeInfo) {
            $collector
                ->limit($rangeInfo->getCount())
                ->offset($rangeInfo->getOffset() + max(0, $rangeInfo->getPage() - 1) * $rangeInfo->getCount());
        }

        $reviewers = [];
        foreach ($collector->getMany() as $user) {
            $reviewers[$user->getId()] = $this->createReviewer($user);
        }

        if (!$rangeInfo) {
            return $reviewers;
        }

        return new VirtualArrayIterator(
            $reviewers,
            $totalCount,
            $rangeInfo->getPage(),
            $rangeInfo->getCount()
        );
    }

    public function getReviewerUser($reviewerId)
    {
        return Repo::user()->get((int) $reviewerId);
    }

    private function createReviewer($reviewerUser): RCRReviewerDTO
    {
        $completedReviews = $this->getCompletedReviews($this->contextId, null, $reviewerUser->getId());
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

        $currentLocale = Locale::getLocale();
        $titles = [];
        foreach ($titlesByLocale as $submissionId => $localizedTitles) {
            $titles[$submissionId] = $localizedTitles[$currentLocale]
                ?? $localizedTitles[$submissionLocales[$submissionId]]
                ?? reset($localizedTitles);
        }

        return $titles;
    }
}
