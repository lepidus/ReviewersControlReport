<?php

trait SubmissionUrl
{
    public function getSubmissionWorkflowUrl($submissionId, $submissionStageId)
    {
        $request = \Application::get()->getRequest();
        $dispatcher = $request->getDispatcher();
        return $dispatcher->url($request, ROUTE_PAGE, null, 'workflow', 'index', array($submissionId, $submissionStageId));
    }
}
