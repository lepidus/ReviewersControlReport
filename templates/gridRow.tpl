{if !is_null($row->getId())}
	{assign var=rowIdPrefix value="component-"|concat:$row->getGridId()}
	{assign var=rowId value=$rowIdPrefix|concat:"-row-":$row->getId()}
{else}
	{assign var=rowId value=""}
{/if}

{assign var="row_class" value="gridRow"}
{if $row->getActions($smarty.const.GRID_ACTION_POSITION_DEFAULT)}
	{assign var="row_class" value=$row_class|cat:' has_extras'}
{/if}

{assign var="row_id" value=$rowId|escape|replace:" ":"_"}

<script>
    $(function() {ldelim}
        $('#{$row_id}').find('a.show_extras').on('click',
			function() {ldelim}
                var $reviewsRow = $('#{$row_id}').siblings('.row_review');
                $reviewsRow.toggle();
            {rdelim})
	{rdelim});
</script>

<tr {if $rowId}id="{$row_id}" {/if} class="{$row_class}">
	{foreach name=columnLoop from=$columns key=columnId item=column}
		{* @todo indent columns should be killed at their source *}
		{if $column->hasFlag('indent')}
			{continue}
		{/if}

		{assign var=col_class value=""}
		{if $column->hasFlag('firstColumn')}
			{assign var="col_class" value=$col_class|cat:'first_column'}
		{/if}

		{if $column->hasFlag('alignment')}
			{assign var="col_class" value=$col_class|cat:' pkp_helpers_text_'}
			{assign var="col_class" value=$col_class|cat:$column->getFlag('alignment')}
		{/if}

		<td{if $col_class} class="{$col_class}" {/if}>
			{if $row->hasActions() && $column->hasFlag('firstColumn')}
				{if $row->getActions($smarty.const.GRID_ACTION_POSITION_DEFAULT)}
					<a href="#" class="show_extras">
						<span class="pkp_screen_reader">{translate key="grid.settings"}</span>
					</a>
				{/if}
				{$cells[$smarty.foreach.columnLoop.index]}
				<div class="row_actions">
					{if $row->getActions($smarty.const.GRID_ACTION_POSITION_ROW_LEFT)}
						{foreach from=$row->getActions($smarty.const.GRID_ACTION_POSITION_ROW_LEFT) item=action}
							{include file="linkAction/linkAction.tpl" action=$action contextId=$rowId|replace:" ":"_"}
						{/foreach}
					{/if}
				</div>
			{else}
				{$cells[$smarty.foreach.columnLoop.index]}
			{/if}
		</td>
	{/foreach}
</tr>
{if $row->getActions($smarty.const.GRID_ACTION_POSITION_DEFAULT)}
	<tr id="{$rowId|escape|replace:" ":"_"}-control-row" class="row_controls">
		<td colspan="{$grid->getColumnsCount('indent')}">
			{if $row->getActions($smarty.const.GRID_ACTION_POSITION_DEFAULT)}
				{foreach from=$row->getActions($smarty.const.GRID_ACTION_POSITION_DEFAULT) item=action}
					{include file="linkAction/linkAction.tpl" action=$action contextId=$rowId|replace:" ":"_"}
				{/foreach}
			{/if}
		</td>
	</tr>
{/if}
{if $row->getReviews()}
	<tr id="{$rowId|escape|replace:" ":"_"}-control-row" class="row_review">
		<td colspan="{$grid->getColumnsCount('indent')}">
			{foreach from=$row->getReviews() item=reviews}
				{$reviews}
			{/foreach}
		</td>
	</tr>
{/if}
