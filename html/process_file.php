<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 5/9/2018
 * Time: 4:04 PM
 */
$translate['Menu'] = \App\TranslationManager::getInstance($database)->getSection('Menu');
$translate['ProcessFile'] = \App\TranslationManager::getInstance($database)->getSection('ProcessFile');

if ($user->isLogged()) {
	require_once HEADER;
	//Logged
	//CHECK IF USER HAVE RIGHTS TO ACCESS CURRENT PAGE/MODULE
	//CHECK IF MODULE remote_addr IS ALLOWED FOR USER's REMOTE ARRD
	$script = basename($_SERVER['SCRIPT_FILENAME']);
	if (!\App\UserModuleManager::moduleRemoteRights($user->getModules(), $script)) {
		echo '<div class="page-header" style = "margin-top:60px" ><h1 >' . $translate['system']['missingAccess'] . '</h1 ></div > ';
		require_once FOOTER;
		exit();
	}
} else {
	redirect('/index.php');
}
if ($user->hasVIEW()) {

} else {
	echo '<div class="page-header" style = "margin-top:60px" ><h1 >' . $translate['system']['missingAccess'] . '</h1 ></div > ';
	require_once FOOTER;
	exit();
}

?>
<!-- Bread crumb -->
<div class="row page-titles">
	<div class="col-md-5 align-self-center">
		<h3 class="text-primary"> <?php echo $translate['Menu']['processFile'];?> </h3></div>
	<?php /*
		<div class="col-md-7 align-self-center">
			<ol class="breadcrumb">
				<li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
				<li class="breadcrumb-item active">Dashboard</li>
			</ol>
		</div>
 		*/ ?>
</div>
<!-- End Bread crumb -->