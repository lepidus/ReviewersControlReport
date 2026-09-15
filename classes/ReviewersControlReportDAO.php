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

    /** @return list<list<string>> */
    private function getReviewsGridCells(array $completedReviews): array
    {
        $gridCells = [];

        foreach ($completedReviews as $completedReview) {
            $submissionUrl = $this->getSubmissionWorkflowUrl($completedReview->getSubmissionId());
            $escapedUrl = htmlspecialchars($submissionUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $escapedTitle = htmlspecialchars(
                $this->formatStringLength($completedReview->getSubmissionTitle(), 40),
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

    /** @return list<RCRCompletedReview> */
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

        $completedReviews = [];
        foreach ($query->get() as $row) {
            $submission = Repo::submission()->get((int) $row->submission_id);
            if (!$submission) {
                continue;
            }
            $publication = $submission->getCurrentPublication();
            $localizedTitles = (array) ($publication?->getData('title') ?? []);
            $title = $localizedTitles[Locale::getLocale()]
                ?? $localizedTitles[$submission->getData('locale')]
                ?? reset($localizedTitles)
                ?: '';

            $completedReviews[] = new RCRCompletedReview(
                $row->reviewer_id,
                $row->submission_id,
                $title,
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
}
