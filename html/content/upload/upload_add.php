<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 5/8/2018
 * Time: 4:36 PM
 */
if ($user->isLogged()) {
	//CHECK IF USER HAVE RIGHTS TO ACCESS CURRENT PAGE/MODULE
	//CHECK IF MODULE remote_addr IS ALLOWED FOR USER's REMOTE ARRD
	$script = basename($_SERVER['SCRIPT_FILENAME']);
	if (!\App\UserModuleManager::moduleRemoteRights($user->getModules(), $script)) {
		echo '<div class="page-header" style = "margin-top:60px" ><h1 >' . $translate['system']['missingAccess'] . '</h1 ></div > ';
		require_once FOOTER;
		exit();
	}
} else {
	echo '<div class="page-header" style = "margin-top:60px" ><h1 >' . $translate['system']['notLogged'] . '</h1 ></div > ';
	require_once FOOTER;
	exit();
}

if ($user->hasCRUD()) {

} else {
	echo '<div class="page-header" style = "margin-top:60px" ><h1 >' . $translate['system']['missingAccess'] . '</h1 ></div > ';
	require_once FOOTER;
	exit();
}
?>
<!-- Container fluid  -->
<div class="container-fluid">
	<!-- Start Page Content -->
	<div class="row">
		<div class="col-12">
			<h4><?php echo $translate['Upload']['uploadFile']; ?></h4>

			<div class="card">
				<div class="card-body">

					<div class="row">
						<div class="col-xs-12 col-md-3">
							<label><?php echo $translate['Upload']['date_on_action']; ?></label>

							<div class='input-group date'>
								<input title="Date On Action" name="date_on_action" id="date_on_action" type='text'
									   class="form-control"
									   value="<?php echo date('d.m.Y'); ?>"/>
                					<span class="input-group-addon">
                    				<span class="fa fa-calendar"></span>
                					</span>
							</div>
						</div>
						<div class="col-xs-12 col-md-3">
							<label><?php echo $translate['Upload']['choose_file']; ?></label>
							<label class="custom-file">
								<input type="file" id="file" class="custom-file-input form-control"
									   accept="<?php echo implode(',', $fileTypes); ?>,.csv">
								<span
									class="custom-file-control">&nbsp;</span>
							</label>
						</div>
					</div>

					<div class="row mt-5">
						<div class="col-md-6">
							<div class="form-group">
								<label><?php echo $translate['Upload']['description']; ?></label>
								<textarea style="resize:none;height: 80px;" title="Description" class="form-control"
										  name="description"></textarea>
							</div>
						</div>
					</div>

					<button class="btn btn-success mt-5" id="sbm_btn" type="submit"><i
							class="fa fa-check-circle-o"></i> <?php echo $translate['system']['add']; ?>
					</button>
				</div>
			</div>
		</div>
	</div>
	<!-- End PAge Content -->
</div>
<script>
	var success = "<?php echo $translate['system']['success'];?>";
	var successUpload = "<?php echo $translate['system']['successUpload'];?>"
</script>
<script src="<?php echo BASE_URL; ?>/js/upload/upload_add.js" type="text/javascript"></script>
