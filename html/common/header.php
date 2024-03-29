<?php
$lang_list = \App\Managers\Translation::getInstance($database)->getLanguageList();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
	<title><?php echo \App\TranslationManager::getInstance($database)->getTranslation('Common', 'title'); ?></title>
	<meta name="author" content="DimitarNatskin@LINKMobility">
	<!-- Favicon icon -->
	<link rel="icon" type="image/png" sizes="16x16" href="<?php echo BASE_URL; ?>/img/link-fav.ico">
	<!-- Bootstrap -->
	<!-- <link href="<?php echo BASE_URL; ?>/css/bootstrap.css" rel="stylesheet"> -->
	<link href="<?php echo BASE_URL; ?>/vendor/elaAdmin/css/bootstrap.min.css" rel="stylesheet">

	<link href="<?php echo BASE_URL; ?>/vendor/elaAdmin/css/helper.css" rel="stylesheet">
	<link href="<?php echo BASE_URL; ?>/vendor/elaAdmin/css/style.css" rel="stylesheet">
	<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
	<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
	<!--[if lt IE 9]>
	<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
	<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
	<![endif]-->

	<!-- JQUERY -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>

	<script src="<?php echo BASE_URL; ?>/vendor/elaAdmin/js/popper.min.js"></script>
	<script src="<?php echo BASE_URL; ?>/vendor/elaAdmin/js/bootstrap.min.js"></script>

	<script src="<?php echo BASE_URL; ?>/vendor/elaAdmin/js/jquery.slimscroll.js"></script>
	<script src="<?php echo BASE_URL; ?>/vendor/elaAdmin/js/sidebarmenu.js"></script>
	<script src="<?php echo BASE_URL; ?>/vendor/elaAdmin/js/sticky-kit.min.js"></script>
	<script src="<?php echo BASE_URL; ?>/vendor/elaAdmin/js/custom.min.js"></script>


	<script src="<?php echo BASE_URL; ?>/js/civem.js"></script>

	<?php //DATEPICKER ?>
	<script src="<?php echo BASE_URL; ?>/js/datepicker/js/bootstrap-datepicker.js"></script>
	<link href="<?php echo BASE_URL; ?>/js/datepicker/css/bootstrap-datepicker.css" rel="stylesheet">
	<script src="<?php echo BASE_URL; ?>/js/datepicker/locales/bootstrap-datepicker.bg.min.js"></script>
	<script src="<?php echo BASE_URL; ?>/js/datepicker/locales/bootstrap-datepicker.en-GB.min.js"></script>

	<!-- FONT AWESOME -->
	<link href="<?php echo BASE_URL; ?>/vendor/font-awesome-4.7.0/css/font-awesome.css" rel="stylesheet">

	<link rel="stylesheet" href="<?php echo BASE_URL; ?>/vendor/bootstrap-select/css/bootstrap-select.css">
	<script src="<?php echo BASE_URL; ?>/vendor/bootstrap-select/js/bootstrap-select.js"></script>

	<!-- dataTables -->
	<link href="<?php echo BASE_URL; ?>/vendor/DataTablesBootstrap4/datatables.css" rel="stylesheet">
	<link href="<?php echo BASE_URL; ?>/vendor/DataTablesBootstrap4/Responsive-2.2.1/css/responsive.bootstrap4.min.css"
		  rel="stylesheet">
	<script src="<?php echo BASE_URL; ?>/vendor/DataTablesBootstrap4/datatables.js"
			type="text/javascript"></script>
	<script src="<?php echo BASE_URL; ?>/vendor/DataTablesBootstrap4/Responsive-2.2.1/js/responsive.bootstrap4.min.js"
			type="text/javascript"></script>
	<script src="<?php echo BASE_URL; ?>/vendor/DataTablesBootstrap4/Responsive-2.2.1/js/responsive.bootstrap.min.js"
			type="text/javascript"></script>

	<!-- BOOTSTAP SWITCH -->
	<link href="<?php echo BASE_URL; ?>/vendor/bootstrap-switch/css/bootstrap-switch.min.css" rel="stylesheet">
	<script src="<?php echo BASE_URL; ?>/vendor/bootstrap-switch/js/bootstrap-switch.min.js"
			type="text/javascript"></script>

	<!-- swal -->
	<link href="<?php echo BASE_URL; ?>/vendor/swal/sweetalert.css" rel="stylesheet">
	<script src="<?php echo BASE_URL; ?>/vendor/swal/sweetalert.min.js" type="text/javascript"></script>


	<script src="<?php echo BASE_URL; ?>/js/language_select.js" type="text/javascript"></script>
	<script>
		var file_accept_template = "<?php implode(',',$fileTypes)?>";
		var base_url = "<?php echo BASE_URL;?>";
		var base_self = "<?php echo $_SERVER['PHP_SELF'];?>";
	</script>
	<!-- UTILS -->
	<script src="<?php echo BASE_URL; ?>/js/utils.js"></script>

	<link href="<?php echo BASE_URL; ?>/css/main.css" rel="stylesheet">

</head>

<body class="fix-header fix-sidebar">
<!-- Preloader - style you can find in spinners.css -->
<div class="preloader">
	<svg class="circular" viewBox="25 25 50 50">
		<circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="2" stroke-miterlimit="10"></circle>
	</svg>
</div>
<div id="spinner" style="display: none;"></div>

<!-- Modal -->
<div class="modal" id="myModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Грешка</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true"><i class="fa fa-close"></i></span>
				</button>
			</div>
			<div class="modal-body">
				<p>Modal body text goes here.</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary"
						data-dismiss="modal"><?php echo $translate['system']['close']; ?></button>
			</div>
		</div>
	</div>
</div>

<!-- Main wrapper  -->
<div id="main-wrapper">
	<!-- header header  -->
	<div class="header">
		<nav class="navbar top-navbar navbar-expand-md navbar-light">
			<!-- Logo -->
			<div class="navbar-header">
				<a class="navbar-brand" href="<?php echo BASE_URL; ?>/home.php">
					<!-- Logo icon -->
					<b><img src="<?php echo BASE_URL; ?>/img/link-fav-menu.jpg" alt="homepage" class="dark-logo"/></b>
					<!--End Logo icon -->
					<!-- Logo text -->
					<span><img src="<?php echo BASE_URL; ?>/img/linkmobility_logo-small.png"
							   style="width:72px;height:13px;"
							   alt="homepage" class="dark-logo"/></span>
				</a>
			</div>
			<!-- End Logo -->
			<div class="navbar-collapse">
				<!-- toggle and nav items -->
				<ul class="navbar-nav mr-auto mt-md-0">
					<!-- This is  -->
					<li class="nav-item"><a class="nav-link nav-toggler hidden-md-up text-muted  "
											href="javascript:void(0)"><i class="mdi mdi-menu"></i></a></li>
					<li class="nav-item m-l-10"><a class="nav-link sidebartoggler hidden-sm-down text-muted  "
												   href="javascript:void(0)"><i class="ti-menu"></i></a></li>
					<!-- Messages -->
					<li class="nav-item dropdown mega-dropdown"><a class="nav-link dropdown-toggle text-muted  "
																   href="#" data-toggle="dropdown" aria-haspopup="true"
																   aria-expanded="false"><i class="fa fa-th-large"></i></a>

						<div class="dropdown-menu animated zoomIn">
							<ul class="mega-dropdown-menu row">


								<li class="col-lg-3  m-b-30">
									<h4 class="m-b-20">CONTACT US</h4>
									<!-- Contact -->
									<form>
										<div class="form-group">
											<input type="text" class="form-control" id="exampleInputname1"
												   placeholder="Enter Name"></div>
										<div class="form-group">
											<input type="email" class="form-control" placeholder="Enter email"></div>
										<div class="form-group">
											<textarea class="form-control" id="exampleTextarea" rows="3"
													  placeholder="Message"></textarea>
										</div>
										<button type="submit" class="btn btn-info">Submit</button>
									</form>
								</li>
							</ul>
						</div>
					</li>
					<!-- End Messages -->
				</ul>
				<!-- User profile and search -->
				<ul class="navbar-nav my-lg-0">

					<?php
					/*
					<!-- Search -->
					<li class="nav-item hidden-sm-down search-box"> <a class="nav-link hidden-sm-down text-muted  " href="javascript:void(0)"><i class="ti-search"></i></a>
						<form class="app-search">
							<input type="text" class="form-control" placeholder="Search here"> <a class="srh-btn"><i class="ti-close"></i></a> </form>
					</li>
					<!-- Comment -->
					<li class="nav-item dropdown">
						<a class="nav-link dropdown-toggle text-muted text-muted  " href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="fa fa-bell"></i>
							<div class="notify"> <span class="heartbit"></span> <span class="point"></span> </div>
						</a>
						<div class="dropdown-menu dropdown-menu-right mailbox animated zoomIn">
							<ul>
								<li>
									<div class="drop-title">Notifications</div>
								</li>
								<li>
									<div class="message-center">
										<!-- Message -->
										<a href="#">
											<div class="btn btn-danger btn-circle m-r-10"><i class="fa fa-link"></i></div>
											<div class="mail-contnet">
												<h5>This is title</h5> <span class="mail-desc">Just see the my new admin!</span> <span class="time">9:30 AM</span>
											</div>
										</a>
										<!-- Message -->
										<a href="#">
											<div class="btn btn-success btn-circle m-r-10"><i class="ti-calendar"></i></div>
											<div class="mail-contnet">
												<h5>This is another title</h5> <span class="mail-desc">Just a reminder that you have event</span> <span class="time">9:10 AM</span>
											</div>
										</a>
										<!-- Message -->
										<a href="#">
											<div class="btn btn-info btn-circle m-r-10"><i class="ti-settings"></i></div>
											<div class="mail-contnet">
												<h5>This is title</h5> <span class="mail-desc">You can customize this template as you want</span> <span class="time">9:08 AM</span>
											</div>
										</a>
										<!-- Message -->
										<a href="#">
											<div class="btn btn-primary btn-circle m-r-10"><i class="ti-user"></i></div>
											<div class="mail-contnet">
												<h5>This is another title</h5> <span class="mail-desc">Just see the my admin!</span> <span class="time">9:02 AM</span>
											</div>
										</a>
									</div>
								</li>
								<li>
									<a class="nav-link text-center" href="javascript:void(0);"> <strong>Check all notifications</strong> <i class="fa fa-angle-right"></i> </a>
								</li>
							</ul>
						</div>
					</li>
					<!-- End Comment -->
					<!-- Messages -->
					<li class="nav-item dropdown">
						<a class="nav-link dropdown-toggle text-muted  " href="#" id="2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="fa fa-envelope"></i>
							<div class="notify"> <span class="heartbit"></span> <span class="point"></span> </div>
						</a>
						<div class="dropdown-menu dropdown-menu-right mailbox animated zoomIn" aria-labelledby="2">
							<ul>
								<li>
									<div class="drop-title">You have 4 new messages</div>
								</li>
								<li>
									<div class="message-center">
										<!-- Message -->
										<a href="#">
											<div class="user-img"> <img src="images/users/5.jpg" alt="user" class="img-circle"> <span class="profile-status online pull-right"></span> </div>
											<div class="mail-contnet">
												<h5>Michael Qin</h5> <span class="mail-desc">Just see the my admin!</span> <span class="time">9:30 AM</span>
											</div>
										</a>
										<!-- Message -->
										<a href="#">
											<div class="user-img"> <img src="images/users/2.jpg" alt="user" class="img-circle"> <span class="profile-status busy pull-right"></span> </div>
											<div class="mail-contnet">
												<h5>John Doe</h5> <span class="mail-desc">I've sung a song! See you at</span> <span class="time">9:10 AM</span>
											</div>
										</a>
										<!-- Message -->
										<a href="#">
											<div class="user-img"> <img src="images/users/3.jpg" alt="user" class="img-circle"> <span class="profile-status away pull-right"></span> </div>
											<div class="mail-contnet">
												<h5>Mr. John</h5> <span class="mail-desc">I am a singer!</span> <span class="time">9:08 AM</span>
											</div>
										</a>
										<!-- Message -->
										<a href="#">
											<div class="user-img"> <img src="images/users/4.jpg" alt="user" class="img-circle"> <span class="profile-status offline pull-right"></span> </div>
											<div class="mail-contnet">
												<h5>Michael Qin</h5> <span class="mail-desc">Just see the my admin!</span> <span class="time">9:02 AM</span>
											</div>
										</a>
									</div>
								</li>
								<li>
									<a class="nav-link text-center" href="javascript:void(0);"> <strong>See all e-Mails</strong> <i class="fa fa-angle-right"></i> </a>
								</li>
							</ul>
						</div>
					</li>
					<!-- End Messages -->
					*/ ?>

					<!-- Language -->
					<li class="nav-item dropdown">
						<a class="nav-link dropdown-toggle text-muted  " href="#" data-toggle="dropdown"
						   aria-haspopup="true"
						   aria-expanded="false"><?php echo $lang_list[array_search($user->getLanguage(), array_column($lang_list, 'id'))]['language']; ?></a>

						<div class="dropdown-menu dropdown-menu-right animated zoomIn">
							<ul class="dropdown-user">
								<?php
								foreach ($lang_list as $value) {
									echo '<li><a href="' . BASE_URL . '/change_language.php?id=' . $value['id'] . '">' . $value['language'] . '</a></li>';
								}
								?>
							</ul>
						</div>
					</li>

					<!-- Profile -->
					<li class="nav-item dropdown">
						<a class="nav-link dropdown-toggle text-muted  " href="#" data-toggle="dropdown"
						   aria-haspopup="true" aria-expanded="false"><span class="fa fa-user"></span></a>

						<div class="dropdown-menu dropdown-menu-right animated zoomIn">
							<ul class="dropdown-user">
								<?php /*
								<li><a href="#"><i class="ti-user"></i> Profile</a></li>
								<li><a href="#"><i class="ti-wallet"></i> Balance</a></li>
								<li><a href="#"><i class="ti-email"></i> Inbox</a></li>
								<li><a href="#"><i class="ti-settings"></i> Setting</a></li>
 								*/ ?>
								<li><a href="<?php echo BASE_URL; ?>/logout.php"><i
											class="fa fa-power-off"></i></span> <?php echo $translate['system']['exit']; ?>
									</a></li>
							</ul>
						</div>
					</li>
				</ul>
			</div>
		</nav>
	</div>
	<!-- End header header -->
	<!-- Left Sidebar  -->
	<div class="left-sidebar">
		<!-- Sidebar scroll-->
		<div class="scroll-sidebar">
			<!-- Sidebar navigation-->
			<nav class="sidebar-nav">
				<ul id="sidebarnav">
					<li class="nav-devider"></li>
					<li><a class="<?php echo (basename($_SERVER['SCRIPT_FILENAME']) == 'home.php') ? 'active' : ''; ?>"
						   href="<?php echo BASE_URL; ?>/home.php" aria-expanded="false"><i class="fa fa-home"></i><span
								class="hide-menu"> Табло </span></a></li>
					<?php \App\UserModuleManager::getInstance($user->getId(), $database)->outputHTMLModules($user->getModules()); ?>
				</ul>
			</nav>
			<!-- End Sidebar navigation -->
		</div>
		<!-- End Sidebar scroll-->
	</div>
	<!-- End Left Sidebar  -->
	<!-- Page wrapper  -->
	<div class="page-wrapper">