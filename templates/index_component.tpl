{extends file="layouts/backend.tpl"}

{block name="page"}
    <h1 class="app__pageHeading">
        {translate key="plugins.reports.reviewersControlReport.displayName"}
    </h1>

    <div class="app__contentPanel">
        <form id="reviewersControlReportForm" method="post" action="">
            {csrf}
            {include file="common/formErrors.tpl"}

            <h2>{translate key="plugins.reports.reviewersControlReport.reportType"}</h2>
            <p>{translate key="plugins.reports.reviewersControlReport.reportType.description"}</p>
            <select name="reportType" id="reportType">
                {foreach from=$reportTypes key=reportTypeValue item=reportTypeLabel}
                    <option value="{$reportTypeValue|escape}" {if $reportType == $reportTypeValue}selected="selected"{/if}>{$reportTypeLabel|escape}</option>
                {/foreach}
            </select>

            <h2>{translate key="plugins.reports.reviewersControlReport.dateCompletedInterval"}</h2>
            <p>{translate key="plugins.reports.reviewersControlReport.dateCompletedInterval.description"}</p>
            <label for="startDateInterval">{translate key="common.from"}</label>
            <input type="date" id="startDateInterval" name="startDateInterval" value="{$startDateInterval|escape}" />
            <label for="endDateInterval">{translate key="common.until"}</label>
            <input type="date" id="endDateInterval" name="endDateInterval" value="{$endDateInterval|escape}" />

            <input id="generateReport" class="pkp_button submitFormButton" type="submit" value="{translate key="plugins.reports.reviewersControlReport.toCsv"}" />
        </form>

        {capture assign=reviewersUrl}{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.reviewersControlReport.controllers.grid.ReviewersGridHandler" op="fetchGrid" escape=false}{/capture}
        {load_url_in_div id="reviewersReportGridContainer" url=$reviewersUrl}
    </div>
{/block}
