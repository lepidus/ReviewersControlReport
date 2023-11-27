{extends file="layouts/backend.tpl"}

{block name="page"}
    <h1 class="app__pageHeading">
        {translate key="plugins.reports.reviewersControlReport.displayName"}
    </h1>

    <div class="app__contentPanel">
        <pkp-header>
            <template slot="actions">
                <form method="post" action="">
                    <input id="generateReport" class="pkp_button submitFormButton" type="submit" value="{translate key="plugins.reports.reviewersControlReport.toCsv"}" class="button defaultButton" />
                </form>
			</template>
		</pkp-header>
        {capture assign=reviewersUrl}{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.reviewersControlReport.controllers.grid.ReviewersGridHandler" op="fetchGrid" escape=false}{/capture}
        {load_url_in_div id="reviewersReportGridContainer" url=$reviewersUrl}
    </div>
{/block}