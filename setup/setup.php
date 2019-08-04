<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 20.6.2018 г.
 * Time: 11:32
 */

function getRootDir()
{
	return dirname(__DIR__);
}
require dirname(__DIR__) . '/vendor/autoload.php';

//LOAD ENVIRONMENT VARIABLES
$dotenv = new Dotenv\Dotenv(dirname(__DIR__));
$dotenv->load();

$data = [
	'dbhost' => getenv('DB_HOST'),
	'dbport' => getenv('DB_PORT'),
	'dbuser' => getenv('DB_USER'),
	'dbpass' => getenv('DB_PASS'),
	'dbname' => getenv('DB_NAME')
];

$database = new dbaccess($data);

$base_url =  dirname(dirname("http://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'])) . '/public';

//LOAD SESSION
$session_h = new session($data);

session_set_save_handler(
	array($session_h, 'open'),
	array($session_h, 'close'),
	array($session_h, 'read'),
	array($session_h, 'write'),
	array($session_h, 'destroy'),
	array($session_h, 'gc')
);
session_name("vcstats");
session_start();


\App\Managers\Translation::initialize(1, $database);

$translate['common'] = \App\Managers\Translation::getInstance($database)->getSection('Common');
$translate['system'] = \App\Managers\Translation::getInstance($database)->getSection('System');
$translate['Password'] = \App\Managers\Translation::getInstance($database)->getSection('Password');
$translate['Menu'] = \App\Managers\Translation::getInstance($database)->getSection('Menu');

$msg = '';
if (isset($_SESSION['msg'])) {
	$msg = $_SESSION['msg'];
	unset($_SESSION['msg']);
}
$_SESSION['person_id'] = 0;
$url = 'setup.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	if ($_POST['password'] !== $_POST['pass2']) {
		$_SESSION['msg'] = $translate['Password']['expiredPassword'];
		header("Location: " . $url);
		die("redirecting");
	}

	if (!(\App\Utils::validatePassword($_POST['password']))) {
		$_SESSION['msg'] = $translate['Password']['invalidPassword'];
		header("Location: " . $url);
		die("redirecting");
	}

	$obj = \App\DBManagers\DBUser::loadFromId(1, $database);
	if ($obj) {
		if (password_verify($_POST['password'], $obj->password)) {
			$_SESSION['msg'] = $translate['Password']['duplicatePassword'];
			header("Location: " . $url);
			die("redirecting");
		}

		$obj->password = password_hash($_POST['password'], PASSWORD_BCRYPT);
		$obj->pass_date_expire = new DateTime();

		if ($obj->save()) {

			$_SESSION['msg'] = $translate['system']['successEdit'];
			header("Location: " . $url);
			die("redirecting");

		} else {
			$_SESSION['msg'] = $translate['system']['systemError'];
			header("Location: " . $url);
			die("redirecting");
		}

	} else {
		$_SESSION['msg'] = $translate['system']['systemError'];
		header("Location: " . $url);
		die("redirecting");
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
	<title><?php echo $translate['common']['title']; ?></title>

	<!-- Bootstrap -->
	<link href="<?php echo $base_url ?>/vendor/elaAdmin/css/bootstrap.min.css" rel="stylesheet">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
	<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
	<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
	<!--[if lt IE 9]>
	<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
	<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
	<![endif]-->
	<script src="<?php echo $base_url; ?>/vendor/elaAdmin/js/bootstrap.min.js"></script>

	<!-- UTILS -->
	<script src="<?php echo $base_url; ?>/js/utils.js"></script>

	<link href="<?php echo $base_url; ?>/css/main.css" rel="stylesheet">

	<script>
		var base_url = "<?php echo $base_url;?>";
		var base_self = "<?php echo $_SERVER['PHP_SELF'];?>";

		function pophide(that) {
			$(that).parent().parent().popover('hide');
		}

		$(document).ready(function () {
			$('[data-toggle="popover"]').popover();

			if (location.hash !== '') {
				$('.nav-pills a[href="' + location.hash + '"]').tab('show');
			} else {
				$('.nav-pills a:first').tab('show');
			}
			$('.nav-pills a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
				window.location.hash = e.target.hash.substr(1);
			});
		});
	</script>

</head>
<body>

<nav class="navbar navbar-default navbar-fixed-top">
	<div class="container">
		<div class="navbar-header">
			<a class="navbar-brand" href="<?php echo $base_url; ?>/index.php"><img
					src="<?php echo $base_url; ?>/img/linkmobility_logo.png" style="width: 160px;margin-top: -14px;"/></a>
		</div>
	</div>
</nav>

<div class="container" style="min-height: 100%;">
	<div id="spinner" style="display: none;"></div>
	<!-- Modal -->
	<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="VcStat" style="overflow:auto">
		<div class="modal-dialog modal-lg" role="document" style="display:table;margin: 0px auto;">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
							aria-hidden="true">&times;</span></button>
					<h4 class="modal-title" id="myModalLabel"><?php echo $translate['Password']['errorData']; ?></h4>
				</div>
				<div class="modal-body">

				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default"
							data-dismiss="modal"><?php echo $translate['system']['close'] ?></button>
				</div>
			</div>
		</div>
	</div>

	<div style="padding: 60px 15px">
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
			<div class="row">
				<div class="col-md-6 center-block" style="float:none;">
					<h4><?php echo $translate['Password']['changePassword']; ?></h4>

					<div class="well well-sm">
						<span class="fa fa-info"
							  aria-hidden="true"></span> <?php echo $translate['Password']['passwordRequirements']; ?>
						<ul>
							<li><?php echo $translate['Password']['passReqLatin']; ?></li>
							<li><?php echo $translate['Password']['passReqSymCnt']; ?></li>
							<li><?php echo $translate['Password']['passReqLowLet']; ?></li>
							<li><?php echo $translate['Password']['passReqUpLet']; ?></li>
							<li><?php echo $translate['Password']['passReqNum']; ?></li>
						</ul>
					</div>

					<?php
					if ($msg != '') {
						?>
						<div class="alert alert-warning alert-dismissible" role="alert">
							<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
									aria-hidden="true">&times;</span></button>
							<strong><?php echo $msg; ?></strong>
						</div>
						<?php
					}
					?>

					<div class="form-group">
						<label for="password"><?php echo $translate['Password']['pass']; ?></label>
						<input required type="password" name="password" id="password" class="form-control">
						<label for="pass2"><?php echo $translate['Password']['passAgain']; ?></label>
						<input required type="password" name="pass2" id="pass2" class="form-control">
					</div>
					<button type="submit"
							class="btn btn-primary center-block"><?php echo $translate['Password']['confirm']; ?></button>
				</div>
			</div>
		</form>

	</div>

	<footer class="footer">
		<div class="container-fluid text-center">

			<?php echo \App\Managers\Translation::getInstance($database)->getTranslation('Footer', 'footerText');?>

		</div>
	</footer>

</div>
</body>
</html>