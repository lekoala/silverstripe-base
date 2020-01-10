<div class="cms-content fill-height flexbox-area-grow cms-tabset center $BaseCSSClasses" data-layout-type="border" data-pjax-fragment="Content" id="ModelAdmin">

	<div class="toolbar toolbar--north cms-content-header vertical-align-items">
		<div class="cms-content-header-info flexbox-area-grow vertical-align-items">
            <a class="btn btn-secondary font-icon-left-open-big toolbar__back-button btn--no-text" href="$GoBackLink">
                <span class="sr-only"><%t SilverStripe\Admin\LeftAndMain.NavigateUp "Navigate up a folder" %></span>
            </a>
			<div class="breadcrumbs-wrapper">
				<span class="cms-panel-link crumb last">
					<% if $SectionTitle %>
						$SectionTitle
					<% else %>
						<%t SilverStripe\Admin\ModelAdmin.Title 'Data Models' %>
					<% end_if %>
				</span>
			</div>
		</div>

		<div class="cms-content-header-tabs cms-tabset-nav-primary ss-ui-tabs-nav">
			<ul class="cms-tabset-nav-primary">
				<% loop $ManagedModelTabs %>
				<li class="tab-$ClassName $LinkOrCurrent<% if $LinkOrCurrent == 'current' %> ui-tabs-active<% end_if %>">
					<a href="$Link" class="cms-panel-link" title="$Title.ATT">$Title</a>
				</li>
				<% end_loop %>
			</ul>
		</div>
	</div>

	<div class="cms-content-fields center ui-widget-content cms-panel-padded fill-height flexbox-area-grow" data-layout-type="border">
		$Tools

		<div class="cms-content-view">
		$Content
		</div>
	</div>

</div>
