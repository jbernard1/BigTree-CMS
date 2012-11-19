<?
	include BigTree::path($relative_path."_check.php");
	$breadcrumb[] = array("link" => "dashboard/vitals-statistics/analytics/service-providers/", "title" => "Service Providers");
	
	$cache = json_decode(file_get_contents(SERVER_ROOT."cache/analytics.cache"),true);
	
	if (!$cache) {
		BigTree::redirect("setup/");
	}
?>
<h1>
	<span class="analytics"></span>Service Providers
	<? include BigTree::path("admin/modules/dashboard/vitals-statistics/_jump.php"); ?>
</h1>
<? include BigTree::path($relative_path."_nav.php") ?>
<div class="table">
	<summary>
		<p>This report shows the service providers for your visitors in the past month.</p>
	</summary>
	<header>
		<span class="analytics_metric_name">Service Provider</span>
		<span class="analytics_visit_count">Visit Count</span>
		<span class="analytics_view_count">View Count</span>
	</header>
	<ul id="service_providers">
		<?
			if (is_array($cache["service_providers"])) {
				foreach ($cache["service_providers"] as $provider) {
		?>
		<li>
			<section class="analytics_metric_name"><?=ucwords($provider["name"])?></section>
			<section class="analytics_visit_count"><?=$provider["visits"]?></section>
			<section class="analytics_view_count"><?=$provider["views"]?></section>
		</li>
		<?
				}
			} else {
		?>
		<li class="no_content">We have no data yet.</li>
		<?		
			}
		?>
	</ul>
</div>