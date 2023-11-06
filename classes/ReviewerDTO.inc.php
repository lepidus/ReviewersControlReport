<?php

import('lib.pkp.classes.linkAction.request.AjaxModal');

class ReviewerDTO
{
    private $id;
    private $fullName;
    private $email;
    private $affiliation;
    private $interests;
    private $qualityAverage;
    private $totalReviewedSubmissions;
    private $reviewedSubmissionsTitleAndDate;

    public function __construct($id, $email, $fullName, $affiliation, $interests, $qualityAverage, $totalReviewedSubmissions, $reviewedSubmissionsTitleAndDate)
    {
        $this->id = (int) $id;
        $this->fullName = $fullName;
        $this->email = $email;
        $this->affiliation = $affiliation;
        $this->interests = $interests;
        $this->qualityAverage = $qualityAverage;
        $this->totalReviewedSubmissions = $totalReviewedSubmissions;
        $this->reviewedSubmissionsTitleAndDate = $reviewedSubmissionsTitleAndDate;
    }

    private function getId(): int
    {
        return $this->id;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getAffiliation()
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

    public function getLinkActionByReviewerId()
    {
        $request = \Application::get()->getRequest();
        $dispatcher = $request->getDispatcher();
        $editUserAction = new LinkAction(
            'edit',
            new AjaxModal(
                $dispatcher->url(
                    $request,
                    ROUTE_COMPONENT,
                    null,
                    'grid.settings.user.UserGridHandler',
                    'editUser',
                    null,
                    ['rowId' => $this->getId()]
                ),
                __('grid.user.edit'),
                'modal_edit',
                true
            ),
            __('grid.action.edit'),
            'edit'
        );
        return $editUserAction;
    }
}
