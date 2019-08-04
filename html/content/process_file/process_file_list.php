<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 5/9/2018
 * Time: 3:57 PM
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

if ($user->hasVIEW()) {

} else {
	echo '<div class="page-header" style = "margin-top:60px" ><h1 >' . $translate['system']['missingAccess'] . '</h1 ></div > ';
	require_once FOOTER;
	exit();
}

//Get current Running Task
$query = "SELECT * FROM " . \App\DBManagers\DBProcessFile::$DB_NAME . " WHERE process_status != '" . PROCESS_FILES_STATUS_FINISH . "'";
$res = $database->execute($query);

if ($database->error()) {
	echo '<script>showModalWarnings("' . $language->getTranslation('System', 'systemError') . '")</script>';
}

if ($database->rows() > 1) {
	echo '<script>showModalWarnings("' . $language->getTranslation('System', 'systemError') . '")</script>';
} elseif ($database->rows() == 1) {
	$obj = new \App\DBManagers\DBProcessFile($database);
	$obj->saveToModel($res[0]);

	$obj_file = \App\DBManagers\DBUpload::loadFromId($obj->file_id, $database);

}

?>
<!-- Container fluid  -->
<div class="container-fluid">
	<!-- Start Page Content -->
	<div class="row">
		<div class="col-12">
			<h4><?php echo $translate['ProcessFile']['title']; ?></h4>

			<div class="card">
				<div class="card-body">
					<?php if (isset($obj) && $obj instanceof \App\DBManagers\DBProcessFile) { ?>
						<div class="row">
							<div class="col-sm-8">
								<div class="card-title">
									<div class="media">
										<?php echo (isset($obj_file) && $obj_file instanceof \App\DBManagers\DBUpload) ? $obj_file->icon : '<i class="fa fa-file"></i>' ?>

										<div class="media-body p-l-10">
											<h5 class="mt-0"><?php echo (isset($obj_file) && $obj_file instanceof \App\DBManagers\DBUpload) ? $obj_file->filename . '.' . $obj_file->extension : 'unknown' ?></h5>
										</div>
									</div>
								</div>
							</div>
							<div class="col-sm-4">
								<button
									class="btn btn-outline-success <?php echo (in_array($obj->process_status, array(PROCESS_FILE_STATUS_STARTED_NOT, PROCESS_FILES_STATUS_PAUSED))) ? '' : 'd-none' ?>">
									<i class="fa fa-play"></i></button>
								<button
									class="btn btn-outline-danger <?php echo (in_array($obj->process_status, array(PROCESS_FILES_STATUS_STARTED_YES))) ? '' : 'd-none' ?>">
									<i class="fa fa-pause"></i></button>
								<button
									class="btn btn-outline-warning <?php echo (in_array($obj->process_status, array(PROCESS_FILES_STATUS_STARTED_YES))) ? '' : 'd-none' ?>">
									<i class="fa fa-stop"></i></button>
								<button
									class="btn btn-outline-dark <?php echo(!in_array($obj->process_status, array(PROCESS_FILES_STATUS_FINISH))) ?>">
									<i class="fa fa-trash"></i></button>

							</div>
						</div>
						<div class="row">
							<div class="col">
								<div class="card bg-faded">
									<div class="card-title"></div>
									<div class="card-body">

										<h5 class="m-t-30"><?php echo $obj->chunk_start . ' / ' . $obj->chunk_end;?><span class="pull-right"><?php echo $obj->percent?>%</span></h5>

										<div class="progress-bar bg-success wow animated progress-animated"
											 aria-valuenow="<?php echo $obj->percent?>"
											 aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $obj->percent?>%; height:10px;"
											 role="progressbar"><span class="sr-only"><?php echo $obj->percent?>%</span>
										</div>
									</div>
								</div>
							</div>
						</div>

					<?php } else { ?>
						<h2 class="text-center"><?php echo $language->getTranslation('ProcessFile', 'noTask') ?></h2>
					<?php } ?>
				</div>
			</div>

			<div class="card">
				<div class="card-title">
					<?php echo $language->getTranslation('ProcessFile', 'uploadedFiles'); ?>
				</div>
				<div class="card-body">
					<div class="row">
						<div class="col">
							<table id="process_list"
								   class="table table-striped table-bordered dataTable display responsive no-warp"
								   cellspacing="0"
								   width="100%">
								<thead>
								<tr>
									<th class="all"><?php echo $language->getTranslation('Upload', 'filename'); ?></th>
									<th class="all"><?php echo $translate['system']['DateCreate']; ?></th>
									<th class="all"><?php echo $language->getTranslation('Upload', 'description'); ?></th>
									<th class="all">&nbsp;</th>
								</tr>
								</thead>
							</table>
						</div>
					</div>
				</div>
			</div>

		</div>
	</div>
</div>

<script>
	var success = "<?php echo $translate['system']['success']?>";
	var successStart = "<?php echo $translate['system']['successStart']?>";
</script>
<script src="<?php echo BASE_URL; ?>/js/process_file/process_file_list.js" type="text/javascript"></script>
