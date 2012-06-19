<?
	$breadcrumb[] = array("link" => "settings/edit/".end($bigtree["path"])."/", "title" => "Edit Setting");
	
	$item = $admin->getSetting(end($bigtree["path"]));
	if ($item["encrypted"]) {
		$item["value"] = "";
	}
	
	if ($item["system"] || ($item["locked"] && $admin->Level < 2)) {
		die("<p>Unauthorized request.</p>");
	}
?>
<h1><span class="settings"></span>Edit Setting</h1>
<? include BigTree::path("admin/layouts/_tinymce.php"); ?>
<div class="form_container">
	<header>
		<h2><?=$item["name"]?></h2>
	</header>
	<? if ($item["encrypted"]) { ?>
	<aside>This setting is encrypted.  The current value cannot be shown.</aside>
	<? } ?>
	<form class="module" action="<?=ADMIN_ROOT?>settings/update/" method="post">	
		<input type="hidden" name="id" value="<?=htmlspecialchars(end($bigtree["path"]))?>" />
		<section>
			<?
				$htmls = array();
				$simplehtmls = array();
				$dates = array();
				$times = array();
				
				echo $item["description"];
				
				$t = $item["type"];
				$title = "";
				$value = $item["value"];
				$key = $item["id"];
				$input_validation_class = "";
				
				include BigTree::path("admin/form-field-types/draw/".$t.".php");
			?>
		</section>
		<footer>
			<input type="submit" class="button blue" value="Update" />		
		</footer>
	</form>
</div>
<?
	if (count($htmls) || count($simplehtmls)) {
		$mce_width = 898;
		$mce_height = 365;
		include BigTree::path("admin/layouts/_tinymce.php"); 
				
		if (count($htmls)) {
			include BigTree::path("admin/layouts/_tinymce_specific.php");
		}
		if (count($simplehtmls)) {
			include BigTree::path("admin/layouts/_tinymce_specific_simple.php");
		}
	}
	
	if (count($dates) || count($times)) {
?>
<script type="text/javascript">
	<?
		foreach ($dates as $id) {
	?>
	$("#<?=$id?>").datepicker({ durration: 200, showAnim: "slideDown" });
	<?
		}

		foreach ($times as $id) {
	?>
	$("#<?=$id?>").timepicker({ durration: 200, showAnim: "slideDown", ampm: true, hourGrid: 6,	minuteGrid: 10 });
	<?
		}
	?>
</script>
<?
	}
?>