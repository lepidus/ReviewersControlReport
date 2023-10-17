{**
 * plugins/reports/counter/templates/index.tpl
 *
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 *}
{extends file="layouts/backend.tpl"}

{block name="page"}
    <h1 class="app__pageHeading">
        {translate key="plugins.reports.reviewersControlReport.displayName"}
    </h1>

    <div class="app__contentPanel">
        <p>{translate key="plugins.reports.reviewersControlReport.description"}</p>
        <p>Reviewers:
        {$report}</p>
    </div>
{/block}