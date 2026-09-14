{**
 * plugins/generic/reviewersControlReport/templates/index_component.tpl
 *
 * Copyright (c) 2014 - 2023 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * Reviewers control report page: the CSV generation form and the reviewers list.
 *}
{extends file="layouts/backend.tpl"}

{block name="page"}
    <h1 class="app__pageHeading">
        {translate key="plugins.reports.reviewersControlReport.displayName"}
    </h1>

    <div class="app__contentPanel reviewersControlReport__panel">
        <h2>{translate key="plugins.reports.reviewersControlReport.generateReport"}</h2>
        <p>{translate key="plugins.reports.reviewersControlReport.generateReport.description"}</p>

        <form class="pkp_form" id="reviewersControlReportForm" method="post" action="">
            {csrf}
            {include file="common/formErrors.tpl"}

            {fbvFormArea id="reportTypeArea" title="plugins.reports.reviewersControlReport.reportType"}
                {fbvFormSection description="plugins.reports.reviewersControlReport.reportType.description"}
                    {fbvElement type="select" id="reportType" name="reportType" from=$reportTypes selected=$reportType size=$fbvStyles.size.MEDIUM translate=false}
                {/fbvFormSection}
            {/fbvFormArea}

            {fbvFormArea id="reportPeriodArea" title="plugins.reports.reviewersControlReport.dateCompletedInterval"}
                {fbvFormSection description="plugins.reports.reviewersControlReport.dateCompletedInterval.description"}
                    {* Plain halves rather than nested form sections: a section
                       carries a bottom margin that staggers the two floats *}
                    <div class="inline pkp_helpers_half">
                        <label for="startDateInterval">{translate key="common.from"}</label>
                        <input type="date" class="field text" id="startDateInterval" name="startDateInterval" value="{$startDateInterval|escape}" />
                    </div>
                    <div class="inline pkp_helpers_half">
                        <label for="endDateInterval">{translate key="common.until"}</label>
                        <input type="date" class="field text" id="endDateInterval" name="endDateInterval" value="{$endDateInterval|escape}" />
                    </div>
                {/fbvFormSection}
            {/fbvFormArea}

            {fbvFormSection class="formButtons form_buttons"}
                <span class="pkp_spinner"></span>
                {fbvElement type="submit" class="pkp_button_primary submitFormButton" id="generateReport" name="submitFormButton" label="plugins.reports.reviewersControlReport.toCsv"}
            {/fbvFormSection}
        </form>
    </div>

    <div class="app__contentPanel reviewersControlReport__panel">
        <h2>{translate key="plugins.reports.reviewersControlReport.reviewersList"}</h2>
        <p>{translate key="plugins.reports.reviewersControlReport.reviewersList.description"}</p>

        {capture assign=reviewersUrl}{url router=PKP\core\PKPApplication::ROUTE_COMPONENT component="plugins.generic.reviewersControlReport.controllers.grid.ReviewersGridHandler" op="fetchGrid" escape=false}{/capture}
        {load_url_in_div id="reviewersReportGridContainer" url=$reviewersUrl}
    </div>
{/block}
