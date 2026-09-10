<?php

class ReviewsSummary
{
    private $completedReviews;

    public function __construct(array $completedReviews)
    {
        $this->completedReviews = $completedReviews;
    }

    public function isEmpty(): bool
    {
        return empty($this->completedReviews);
    }

    public function getTotal(): int
    {
        return count($this->completedReviews);
    }

    public function getQualityAverage(): string
    {
        $ratings = [];
        foreach ($this->completedReviews as $completedReview) {
            if (!is_null($completedReview->getQuality())) {
                $ratings[] = $completedReview->getQuality();
            }
        }

        if (empty($ratings)) {
            return '';
        }

        return number_format(array_sum($ratings) / count($ratings), 2, '.', '');
    }

    public function getSubmissionTitles(): array
    {
        $titles = [];
        foreach ($this->completedReviews as $completedReview) {
            $titles[] = $completedReview->getSubmissionTitle();
        }

        return $titles;
    }
}
