<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>

<nav class="navbar navbar-dark bg-dark bg-gradient navbar-expand-lg navbar-expand-md my-3">
	<div class="container-fluid">
		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>
		<div class="collapse navbar-collapse" id="navbarNav">
			<ul class="nav navbar-nav menus">
				<li class="nav-item"><a class="nav-link fw-bold" href="index.php" id="index_menu">ANJUNG</a></li>
				<li class="nav-item"><a class="nav-link fw-bold" href="searchCar.php" id="searchCar_menu">CARIAN KENDERAAN</a></li>
				<li class="nav-item"><a class="nav-link fw-bold" href="staffcar.php" id="staffcar_menu">STAF</a></li>
				<li class="nav-item"><a class="nav-link fw-bold" href="studentcar.php" id="studentcar_menu">PELAJAR</a></li>
				<li class="nav-item"><a class="nav-link fw-bold" href="Visitorcar.php" id="visitorcar_menu">PELAWAT</a></li>
				<li class="nav-item"><a class="nav-link fw-bold" href="contractorcar.php" id="contractorcar_menu">KONTRAKTOR</a></li>
				<li class="nav-item"><a class="nav-link fw-bold" href="user.php" id="user_menu">PENGGUNA</a></li>
				<li class="nav-item"><a class="nav-link fw-bold" href="admin.php" id="admin_menu">ADMIN</a></li>
			</ul>
		</div>
		<ul class="nav navbar-nav">
			<li class="dropdown position-relative">
				<button type="button" class="badge bg-light border px-3 text-dark rounded-pill dropdown-toggle" id="dropdownMenuButton1" data-bs-toggle="dropdown" data-bs-toggle="dropdown" aria-expanded="false">
					<span class="badge badge-pill bg-danger count"></span>
					<?= $_SESSION['email_Admin'] ?? '' ?>
				</button>
				<ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
					<li><a class="dropdown-item" href="actionAdmin.php?action=logout">Keluar</a></li>
				</ul>
			</li>
		</ul>
	</div>
</nav>