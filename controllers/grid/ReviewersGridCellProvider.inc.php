<?php

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class ReviewersGridCellProvider extends GridCellProvider
{
    public function getTemplateVarsFromRowColumn($row, $column)
    {
        $reviewer = $row->getData();
        $columnId = $column->getId();

        switch ($columnId) {
            case 'email':
                return array('label' => $reviewer->getEmail());
            case 'fullName':
                return array('label' => $reviewer->getFullName());
            case 'affiliation':
                return array('label' => $reviewer->getAffiliation());
            case 'interests':
                return array('label' => $reviewer->getAffiliation());
            case 'score':
                return array('label' => $reviewer->getQualityAverage());
            case 'totalReviews':
                return array('label' => $reviewer->getTotalReviewedSubmissions());
            case 'reviews':
                return array('label' => $reviewer->getReviewedSubmissionsTitleAndDate());
            case 'edit':
                return array('label' => $reviewer->getFullName());
            default:
                break;
        }

        return parent::getTemplateVarsFromRowColumn($row, $column);
    }
}
