<tr class="reviewersControlReport__reviewsHeader row_review-{$rowId}">
	<th scope="col" colspan="{$columnsCount-2}">{translate key="plugins.reports.reviewersControlReport.field.submissionTitle"}</th>
	<th scope="col" colspan="2">{translate key="plugins.reports.reviewersControlReport.field.dateCompleted"}</th>
</tr>
{foreach from=$reviews item=review}
	<tr class="reviewersControlReport__review row_review-{$rowId}">
		<td colspan="{$columnsCount-2}"><a href="{$review.url|escape}">{$review.title|escape}</a></td>
		<td colspan="2">{$review.dateCompleted|date_format:"d/m/Y"}</td>
	</tr>
{/foreach}
