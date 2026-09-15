<?php

namespace APP\plugins\generic\reviewersControlReport\classes;

class RCRCompletedReview
{
    private $reviewerId;
    private $submissionId;
    private $submissionTitle;
    private $round;
    private $dateAssigned;
    private $dateDue;
    private $dateCompleted;
    private $recommendation;
    private $quality;
    private $submissionStageId;

    public function __construct(
        $reviewerId,
        $submissionId,
        $submissionTitle,
        $round,
        $dateAssigned,
        $dateDue,
        $dateCompleted,
        $recommendation,
        $quality,
        $submissionStageId = null
    ) {
        $this->reviewerId = (int) $reviewerId;
        $this->submissionId = (int) $submissionId;
        $this->submissionTitle = $submissionTitle;
        $this->round = (int) $round;
        $this->dateAssigned = $dateAssigned;
        $this->dateDue = $dateDue;
        $this->dateCompleted = $dateCompleted;
        $this->recommendation = $recommendation;
        $this->quality = $quality;
        $this->submissionStageId = $submissionStageId;
    }

    public function getReviewerId(): int
    {
        return $this->reviewerId;
    }

    public function getSubmissionId(): int
    {
        return $this->submissionId;
    }

    public function getSubmissionTitle()
    {
        return $this->submissionTitle;
    }

    public function getRound(): int
    {
        return $this->round;
    }

    public function getDateAssigned()
    {
        return $this->dateAssigned;
    }

    public function getDateDue()
    {
        return $this->dateDue;
    }

    public function getDateCompleted()
    {
        return $this->dateCompleted;
    }

    public function getRecommendation()
    {
        return $this->recommendation;
    }

    public function getQuality()
    {
        return $this->quality;
    }

    public function getSubmissionStageId()
    {
        return $this->submissionStageId;
    }
}
