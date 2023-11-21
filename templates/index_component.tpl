{extends file="layouts/backend.tpl"}

{block name="page"}
    <h1 class="app__pageHeading">
        {translate key="plugins.reports.reviewersControlReport.displayName"}
    </h1>

    <div class="app__contentPanel">
        {capture assign=reviewersUrl}{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.reviewersControlReport.controllers.grid.ReviewersGridHandler" op="fetchGrid" escape=false}{/capture}
        {load_url_in_div id="reviewersReportGridContainer" url=$reviewersUrl}
    </div>
{/block}