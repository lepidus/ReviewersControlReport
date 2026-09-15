<?php

namespace APP\plugins\generic\reviewersControlReport\classes;

use APP\core\Application;
use APP\facades\Repo;
use APP\plugins\generic\reviewersControlReport\classes\traits\RCRStringLength;
use Illuminate\Support\Facades\DB;
use PKP\core\VirtualArrayIterator;
use PKP\db\DBResultRange;
use PKP\facades\Locale;
use PKP\security\Role;
use PKP\user\Collector as UserCollector;

class ReviewersControlReportDAO
{
    use RCRStringLength;

    private ?int $contextId = null;

    public function getReviewersIds(int $contextId): array
    {
        return Repo::user()->getCollector()
            ->filterByContextIds([$contextId])
            ->filterByRoleIds([Role::ROLE_ID_REVIEWER])
            ->filterByStatus(UserCollector::STATUS_ALL)
            ->getIds()
            ->all();
    }

    public function getReviewers(?int $contextId = null, ?DBResultRange $dbResultRange = null)
    {
        if ($contextId === null) {
            return [];
        }

        $this->contextId = $contextId;
        $collector = Repo::user()->getCollector()
            ->filterByContextIds([$contextId])
            ->filterByRoleIds([Role::ROLE_ID_REVIEWER])
            ->filterByStatus(UserCollector::STATUS_ALL)
            ->orderBy(UserCollector::ORDERBY_FAMILYNAME, UserCollector::ORDER_DIR_ASC);

        $totalCount = $collector->getCount();
        if ($dbResultRange?->isValid()) {
            $count = $dbResultRange->getCount();
            $offset = $dbResultRange->getOffset() + max(0, ($dbResultRange->getPage() - 1) * $count);
            $collector->limit($count)->offset($offset);
        }

        $reviewers = [];
        foreach ($collector->getMany() as $reviewerUser) {
            $locale = Locale::getLocale();
            $completedReviews = $this->getCompletedReviews($contextId, null, $reviewerUser->getId());
            $reviewsSummary = new RCRReviewsSummary($completedReviews);
            $reviewer = new RCRReviewerDTO(
                $reviewerUser->getId(),
                $reviewerUser->getEmail(),
                $reviewerUser->getFullName(true, false, $locale),
                (string) $reviewerUser->getLocalizedAffiliation(),
                $reviewerUser->getInterestString(),
                $reviewsSummary->getQualityAverage(),
                $reviewsSummary->getTotal(),
                $this->getReviewsGridCells($completedReviews)
            );
            $reviewers[$reviewer->getId()] = $reviewer;
        }

        if ($dbResultRange?->isValid()) {
            return new VirtualArrayIterator(
                $reviewers,
                $totalCount,
                $dbResultRange->getPage(),
                $dbResultRange->getCount()
            );
        }

        return $reviewers;
    }

    /**
     * The expandable rows the grid shows under a reviewer: one submission
     * title, linked to its workflow, plus the date the review was completed.
     *
     * @return list<list<string>>
     */
    private function getReviewsGridCells(array $completedReviews): array
    {
        $gridCells = [];

        foreach ($completedReviews as $completedReview) {
            $submissionUrl = $this->getSubmissionWorkflowUrl($completedReview->getSubmissionId());
            $escapedUrl = htmlspecialchars($submissionUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $escapedTitle = htmlspecialchars(
                $this->formatStringLength($this->getPlainTextTitle($completedReview->getSubmissionTitle()), 40),
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );
            $dateCompleted = date('Y-m-d', strtotime($completedReview->getDateCompleted()));

            $gridCells[] = ['<td style="width: 200pt;" colspan="2"><a href="'
                . $escapedUrl . '">' . $escapedTitle . '</a></td><td colspan="2">'
                . __('common.completed.date', ['dateCompleted' => $dateCompleted]) . '</td>'];
        }

        return $gridCells;
    }

    /**
     * Titles may carry inline markup such as <i>, which the grid shows as
     * plain text: truncating markup could leave a tag open.
     */
    private function getPlainTextTitle(string $title): string
    {
        return html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Only editorial roles see the grid, so every link goes to the editorial
     * workflow, which enforces its own access. Resolving the URL by the roles
     * of the user would cost several queries per listed review.
     */
    protected function getSubmissionWorkflowUrl(int $submissionId): string
    {
        $request = Application::get()->getRequest();
        $dispatcher = $request->getDispatcher();
        if (!$dispatcher) {
            return '';
        }

        return $dispatcher->url(
            $request,
            Application::ROUTE_PAGE,
            null,
            'dashboard',
            'editorial',
            null,
            ['workflowSubmissionId' => $submissionId]
        );
    }

    /**
     * Completed reviews of a context: everything the reports and the grid
     * summarize. Equivalent to filtering review assignments by the statuses
     * RECEIVED, COMPLETE and THANKED, but without going through
     * ReviewAssignment::getStatus(), whose isRead() check costs several
     * queries per assignment.
     *
     * @return list<RCRCompletedReview>
     */
    public function getCompletedReviews(int $contextId, ?RCRClosedDateInterval $interval = null, ?int $reviewerId = null): array
    {
        $query = DB::table('review_assignments as ra')
            ->join('submissions as s', 's.submission_id', '=', 'ra.submission_id')
            ->where('s.context_id', $contextId)
            ->whereNotNull('ra.date_completed')
            ->where('ra.declined', 0)
            ->where('ra.cancelled', 0)
            ->select([
                'ra.reviewer_id',
                'ra.submission_id',
                'ra.round',
                'ra.date_assigned',
                'ra.date_due',
                'ra.date_completed',
                'ra.recommendation',
                'ra.quality',
                's.stage_id as submission_stage_id',
                'ra.review_id',
            ])
            ->orderBy('ra.date_completed')
            ->orderBy('ra.review_id');

        if ($reviewerId !== null) {
            $query->where('ra.reviewer_id', $reviewerId);
        }
        if ($interval !== null) {
            $query->whereBetween('ra.date_completed', [
                $interval->getBeginningDate(),
                $interval->getEndDate(),
            ]);
        }

        $rows = $query->get();
        $titles = $this->getSubmissionTitles($rows->pluck('submission_id')->all());

        $completedReviews = [];
        foreach ($rows as $row) {
            $completedReviews[] = new RCRCompletedReview(
                $row->reviewer_id,
                $row->submission_id,
                $titles[$row->submission_id] ?? '',
                $row->round,
                $row->date_assigned,
                $row->date_due,
                $row->date_completed,
                $row->recommendation,
                $row->quality,
                $row->submission_stage_id
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
        $submissionIds = array_values(array_unique(array_map('intval', $submissionIds)));
        if (empty($submissionIds)) {
            return [];
        }

        $rows = DB::table('submissions as s')
            ->join('publications as p', 'p.publication_id', '=', 's.current_publication_id')
            ->join('publication_settings as ps', function ($join) {
                $join->on('ps.publication_id', '=', 'p.publication_id')
                    ->where('ps.setting_name', '=', 'title');
            })
            ->whereIn('s.submission_id', $submissionIds)
            ->select(['s.submission_id', 's.locale as submission_locale', 'ps.locale', 'ps.setting_value'])
            ->get();

        $titlesByLocale = [];
        $submissionLocales = [];
        foreach ($rows as $row) {
            $titlesByLocale[$row->submission_id][$row->locale] = $row->setting_value;
            $submissionLocales[$row->submission_id] = $row->submission_locale;
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
