<?php

class ReviewerDTO
{
    private $fullName;
    private $email;
    private $affiliation;
    private $interests;
    private $qualityAverage;
    private $totalReviewedSubmissions;
    private $reviewedSubmissionsTitleAndDate;

    public function __construct($email, $fullName, $affiliation, $interests, $qualityAverage, $totalReviewedSubmissions, $reviewedSubmissionsTitleAndDate)
    {
        $this->fullName = $fullName;
        $this->email = $email;
        $this->affiliation = $affiliation;
        $this->interests = $interests;
        $this->qualityAverage = $qualityAverage;
        $this->totalReviewedSubmissions = $totalReviewedSubmissions;
        $this->reviewedSubmissionsTitleAndDate = $reviewedSubmissionsTitleAndDate;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getAffiliation(): string
    {
        return $this->affiliation;
    }

    public function getInterests(): string
    {
        return $this->interests;
    }

    public function getQualityAverage(): float
    {
        return number_format($this->qualityAverage, 2, '.', '');
    }

    public function getTotalReviewedSubmissions(): int
    {
        return $this->totalReviewedSubmissions;
    }

    public function getReviewedSubmissionsTitleAndDate(): array
    {
        return $this->reviewedSubmissionsTitleAndDate;
    }
}
