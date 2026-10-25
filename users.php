<?php
// Menghubungkan halaman dengan database nilai mahasiswa.
require_once __DIR__ . "/connect.php";

// pencarian nama dan filter predikat (GET).
$keyword = trim((string) ($_GET['keyword'] ?? ''));
$filterPredikat = trim((string) ($_GET['predikat'] ?? ''));
// Tahap 2 UTS: hanya dua nilai sorting yang diizinkan.
$urutNilai = strtoupper(trim((string) ($_GET['urut'] ?? 'DESC')));
if (!in_array($urutNilai, ['ASC', 'DESC'], true)) {
  $urutNilai = 'DESC';
}
// Klik header akan membalik arah pengurutan.
$urutBerikutnya = $urutNilai === 'DESC' ? 'ASC' : 'DESC';
// Daftar pilihan filter mengikuti predikat yang benar-benar ada di database.
$pilihanPredikat = [];
$queryPredikat = mysqli_query($conn, "SELECT DISTINCT predikat FROM nilaimhsw WHERE predikat IS NOT NULL AND predikat <> '' ORDER BY predikat ASC");
if ($queryPredikat) {
  while ($item = mysqli_fetch_assoc($queryPredikat)) {
    $pilihanPredikat[] = $item['predikat'];
  }
  mysqli_free_result($queryPredikat);
}

// Hanya izinkan predikat yang tersedia pada database.
if ($filterPredikat !== '' && !in_array($filterPredikat, $pilihanPredikat, true)) {
  $filterPredikat = '';
}

// Revisi ketentuan dosen: hanya 5, 10, atau 15 record per halaman.
$perHalaman = filter_var($_GET['limit'] ?? 5, FILTER_VALIDATE_INT);
if (!in_array($perHalaman, [5, 10, 15], true)) {
  $perHalaman = 5;
}
$queryUrut = http_build_query([
  'keyword' => $keyword,
  'predikat' => $filterPredikat,
  'urut' => $urutBerikutnya,
  'limit' => $perHalaman,
]);
$halamanDiminta = filter_var($_GET['halaman'] ?? 1, FILTER_VALIDATE_INT);
$halamanDiminta = ($halamanDiminta !== false && $halamanDiminta > 0) ? $halamanDiminta : 1;

$where = " WHERE 1=1";
$parameter = [];
$tipeParameter = '';
if ($keyword !== '') {
  $where .= " AND namamhsw LIKE ?";
  $parameter[] = '%' . $keyword . '%';
  $tipeParameter .= 's';
}
if ($filterPredikat !== '') {
  $where .= " AND predikat = ?";
  $parameter[] = $filterPredikat;
  $tipeParameter .= 's';
}

// Hitung total SETELAH pencarian/filter agar jumlah halaman akurat.
$stmtHitung = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM nilaimhsw" . $where);
if (!$stmtHitung) {
  die('Gagal menyiapkan jumlah data mahasiswa.');
}
if ($parameter) {
  mysqli_stmt_bind_param($stmtHitung, $tipeParameter, ...$parameter);
}
mysqli_stmt_execute($stmtHitung);
$hasilHitung = mysqli_stmt_get_result($stmtHitung);
$totalData = (int) mysqli_fetch_assoc($hasilHitung)['total'];
mysqli_free_result($hasilHitung);
mysqli_stmt_close($stmtHitung);

$totalHalaman = max(1, (int) ceil($totalData / $perHalaman));
$halaman = min($halamanDiminta, $totalHalaman);
$offset = ($halaman - 1) * $perHalaman;
$awalData = $totalData > 0 ? $offset + 1 : 0;
$akhirData = min($offset + $perHalaman, $totalData);

// ASC/DESC tetap dibatasi whitelist di bagian atas file.
$sql = "SELECT nim, namamhsw, foto, nilaiakhir, nilaihuruf, predikat,
               created_date, modified_date
        FROM nilaimhsw" . $where .
  " ORDER BY nilaiakhir {$urutNilai}, nim ASC LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
  die('Gagal menyiapkan pencarian data mahasiswa.');
}
$parameterHalaman = [...$parameter, $perHalaman, $offset];
mysqli_stmt_bind_param($stmt, $tipeParameter . 'ii', ...$parameterHalaman);
if (!mysqli_stmt_execute($stmt)) {
  die('Gagal menjalankan pencarian data mahasiswa.');
}
$result = mysqli_stmt_get_result($stmt);
if (!$result) {
  die('Gagal mengambil hasil pencarian data mahasiswa.');
}

// Pertahankan keyword, predikat dan urutan saat pindah halaman.
function urlHalaman(int $nomor, string $keyword, string $predikat, string $urut, int $limit): string
{
  return 'users.php?' . http_build_query([
    'keyword' => $keyword,
    'predikat' => $predikat,
    'urut' => $urut,
    'limit' => $limit,
    'halaman' => $nomor,
  ]);
}

function kelasWarnaNilai($huruf)
{
  return match (strtoupper(trim((string) $huruf))) {
    'A' => 'text-bg-success',
    'B' => 'text-bg-primary',
    'C' => 'text-bg-info',
    'D' => 'text-bg-warning',
    'E' => 'text-bg-danger',
    default => 'text-bg-secondary',
  };
}

?>


<!doctype html>
<html lang="id">
<!--begin::Head-->

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>Daftar Nilai Mahasiswa</title>

  <!--begin::Theme Init (prevents flash of incorrect theme on load, #6043)-->
  <script>
    (() => {
      'use strict';
      const root = document.documentElement;

      // Applications with their own theming opt out of AdminLTE's color mode
      // entirely, here as well as in the bundle.
      if (root.getAttribute('data-lte-color-mode') === 'off') {
        return;
      }

      const STORAGE_KEY = 'lte-theme';
      let stored = null;
      try {
        stored = localStorage.getItem(STORAGE_KEY);
      } catch {
        // localStorage may be unavailable (private mode, sandboxed iframe).
      }
      // Mirror the precedence in color-mode.ts: the visitor's stored choice
      // wins, then a theme this page declared itself, then the OS preference.
      const authored = root.getAttribute('data-bs-theme');
      let resolved = 'light';
      if (stored === 'dark' || stored === 'light') {
        resolved = stored;
      } else if (authored === 'dark' || authored === 'light') {
        resolved = authored;
      } else if (globalThis.matchMedia('(prefers-color-scheme: dark)').matches) {
        resolved = 'dark';
      }
      root.setAttribute('data-bs-theme', resolved);
      root.style.colorScheme = resolved;
      // Flag values computed here, so the bundle does not mistake them for a
      // theme the page declared and stop following the OS preference.
      if (resolved !== authored) {
        root.setAttribute('data-lte-theme-resolved', '');
      }
    })();
  </script>
  <!--end::Theme Init-->

  <!--begin::Accessibility Meta Tags-->
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
  <meta name="color-scheme" content="light dark" />
  <meta name="theme-color" content="#007bff" media="(prefers-color-scheme: light)" />
  <meta name="theme-color" content="#1a1a1a" media="(prefers-color-scheme: dark)" />
  <!--end::Accessibility Meta Tags-->

  <!--begin::Primary Meta Tags-->
  <meta name="title" content="Daftar Nilai Mahasiswa" />
  <meta name="author" content="ColorlibHQ" />
  <meta name="description"
    content="AdminLTE is a free Bootstrap 5 admin dashboard template with almost 50 example pages, built with vanilla JS and designed with accessibility in mind." />
  <meta name="keywords"
    content="bootstrap 5, bootstrap, bootstrap 5 admin dashboard, bootstrap 5 dashboard, bootstrap 5 charts, bootstrap 5 calendar, bootstrap 5 datepicker, bootstrap 5 tables, bootstrap 5 datatable, vanilla js datatable, colorlibhq, colorlibhq dashboard, colorlibhq admin dashboard, accessible admin panel" />
  <!--end::Primary Meta Tags-->

  <!--begin::Accessibility Features-->
  <!-- Skip links will be dynamically added by accessibility.js -->
  <meta name="supported-color-schemes" content="light dark" />
  <link rel="preload" href="./css/adminlte.css" as="style" />
  <!--end::Accessibility Features-->

  <!--begin::Fonts-->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
    integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q=" crossorigin="anonymous" media="print"
    onload="this.media = 'all'" />
  <!--end::Fonts-->

  <!--begin::Third Party Plugin(OverlayScrollbars)-->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css"
    crossorigin="anonymous" />
  <!--end::Third Party Plugin(OverlayScrollbars)-->

  <!--begin::Third Party Plugin(Bootstrap Icons)-->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
    crossorigin="anonymous" />
  <!--end::Third Party Plugin(Bootstrap Icons)-->

  <!--begin::Required Plugin(AdminLTE)-->
  <link rel="stylesheet" href="./css/adminlte.css" />
  <!--end::Required Plugin(AdminLTE)-->
</head>
<!--end::Head-->
<!--begin::Body-->

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
  <!--begin::App Wrapper-->
  <div class="app-wrapper">
    <!--begin::Header-->
    <nav class="app-header navbar navbar-expand bg-body">
      <!--begin::Container-->
      <div class="container-fluid">
        <!--begin::Start Navbar Links-->
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="Toggle sidebar">
              <i class="bi bi-list"></i>
            </a>
          </li>

          <li class="nav-item d-none d-md-block">
            <a href="./index.html" class="nav-link">
              <i class="bi bi-grid-1x2 me-1" aria-hidden="true"></i>
              Live preview
            </a>
          </li>
          <li class="nav-item d-none d-md-block">
            <a href="./docs/introduction.html" class="nav-link">
              <i class="bi bi-book me-1" aria-hidden="true"></i>
              Documentation
            </a>
          </li>
        </ul>
        <!--end::Start Navbar Links-->

        <!--begin::Navbar Search-->
        <form class="navbar-search d-none d-md-block ms-3" role="search" action="./pages/search-results.html">
          <label for="navbar-search-input" class="visually-hidden">Search</label>
          <div class="navbar-search-field">
            <input type="search" id="navbar-search-input" name="q" class="form-control" placeholder="Search…"
              autocomplete="off" />
            <button class="navbar-search-submit" type="submit" aria-label="Submit search">
              <i class="bi bi-search" aria-hidden="true"></i>
            </button>
          </div>
        </form>
        <!--end::Navbar Search-->

        <!--begin::End Navbar Links-->
        <ul class="navbar-nav ms-auto">
          <!--begin::Search (small screens: the field above is hidden, so link to the search page)-->
          <li class="nav-item d-md-none">
            <a class="nav-link" href="./pages/search-results.html" aria-label="Search">
              <i class="bi bi-search" aria-hidden="true"></i>
            </a>
          </li>
          <!--end::Search-->
          <!--begin::Messages Dropdown Menu-->
          <li class="nav-item dropdown">
            <a class="nav-link" data-bs-toggle="dropdown" href="#" aria-label="Messages: 3 unread">
              <i class="bi bi-chat-text"></i>
              <span class="navbar-badge badge text-bg-danger">3</span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
              <a href="#" class="dropdown-item">
                <!--begin::Message-->
                <div class="d-flex">
                  <div class="flex-shrink-0">
                    <img src="./assets/img/user1-128x128.jpg" alt="" class="img-size-50 rounded-circle me-3" />
                  </div>
                  <div class="flex-grow-1">
                    <p class="dropdown-item-title">
                      Brad Diesel
                      <span class="float-end fs-7 text-danger"><i class="bi bi-star-fill"></i></span>
                    </p>
                    <p class="fs-7">Call me whenever you can...</p>
                    <p class="fs-7 text-secondary">
                      <i class="bi bi-clock-fill me-1"></i> 4 Hours Ago
                    </p>
                  </div>
                </div>
                <!--end::Message-->
              </a>
              <div class="dropdown-divider"></div>
              <a href="#" class="dropdown-item">
                <!--begin::Message-->
                <div class="d-flex">
                  <div class="flex-shrink-0">
                    <img src="./assets/img/user8-128x128.jpg" alt="" class="img-size-50 rounded-circle me-3" />
                  </div>
                  <div class="flex-grow-1">
                    <p class="dropdown-item-title">
                      John Pierce
                      <span class="float-end fs-7 text-secondary">
                        <i class="bi bi-star-fill"></i>
                      </span>
                    </p>
                    <p class="fs-7">I got your message bro</p>
                    <p class="fs-7 text-secondary">
                      <i class="bi bi-clock-fill me-1"></i> 4 Hours Ago
                    </p>
                  </div>
                </div>
                <!--end::Message-->
              </a>
              <div class="dropdown-divider"></div>
              <a href="#" class="dropdown-item">
                <!--begin::Message-->
                <div class="d-flex">
                  <div class="flex-shrink-0">
                    <img src="./assets/img/user3-128x128.jpg" alt="" class="img-size-50 rounded-circle me-3" />
                  </div>
                  <div class="flex-grow-1">
                    <p class="dropdown-item-title">
                      Nora Silvester
                      <span class="float-end fs-7 text-warning">
                        <i class="bi bi-star-fill"></i>
                      </span>
                    </p>
                    <p class="fs-7">The subject goes here</p>
                    <p class="fs-7 text-secondary">
                      <i class="bi bi-clock-fill me-1"></i> 4 Hours Ago
                    </p>
                  </div>
                </div>
                <!--end::Message-->
              </a>
              <div class="dropdown-divider"></div>
              <a href="#" class="dropdown-item dropdown-footer">See All Messages</a>
            </div>
          </li>
          <!--end::Messages Dropdown Menu-->

          <!--begin::Notifications Dropdown Menu-->
          <li class="nav-item dropdown">
            <a class="nav-link" data-bs-toggle="dropdown" href="#" aria-label="Notifications: 15 unread">
              <i class="bi bi-bell-fill"></i>
              <span class="navbar-badge badge text-bg-warning">15</span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
              <span class="dropdown-item dropdown-header">15 Notifications</span>
              <div class="dropdown-divider"></div>
              <a href="#" class="dropdown-item">
                <i class="bi bi-envelope me-2"></i> 4 new messages
                <span class="float-end text-secondary fs-7">3 mins</span>
              </a>
              <div class="dropdown-divider"></div>
              <a href="#" class="dropdown-item">
                <i class="bi bi-people-fill me-2"></i> 8 friend requests
                <span class="float-end text-secondary fs-7">12 hours</span>
              </a>
              <div class="dropdown-divider"></div>
              <a href="#" class="dropdown-item">
                <i class="bi bi-file-earmark-fill me-2"></i> 3 new reports
                <span class="float-end text-secondary fs-7">2 days</span>
              </a>
              <div class="dropdown-divider"></div>
              <a href="#" class="dropdown-item dropdown-footer"> See All Notifications </a>
            </div>
          </li>
          <!--end::Notifications Dropdown Menu-->

          <!--begin::Language Menu-->
          <!-- Markup only: swapping the locale is the application's job. The docs
           Recipes page shows how to wire this to a real locale switch. -->
          <li class="nav-item dropdown">
            <a class="nav-link" href="#" id="language-menu" data-bs-toggle="dropdown" aria-expanded="false"
              aria-label="Change language, current language English">
              <i class="bi bi-translate" aria-hidden="true"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="language-menu">
              <li>
                <a class="dropdown-item active" href="#" hreflang="en" aria-current="true">
                  English
                  <i class="bi bi-check-lg ms-2" aria-hidden="true"></i>
                </a>
              </li>
              <li><a class="dropdown-item" href="#" hreflang="es">Español</a></li>
              <li><a class="dropdown-item" href="#" hreflang="fr">Français</a></li>
              <li><a class="dropdown-item" href="#" hreflang="de">Deutsch</a></li>
              <li><a class="dropdown-item" href="#" hreflang="ar">العربية</a></li>
            </ul>
          </li>
          <!--end::Language Menu-->

          <!--begin::Fullscreen Toggle-->
          <li class="nav-item">
            <a class="nav-link" href="#" data-lte-toggle="fullscreen" aria-label="Toggle fullscreen">
              <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
              <i data-lte-icon="minimize" class="bi bi-fullscreen-exit d-none"></i>
            </a>
          </li>
          <!--end::Fullscreen Toggle-->

          <!--begin::Color Mode Toggle (#6010)-->
          <li class="nav-item dropdown">
            <a class="nav-link" href="#" id="bd-theme" aria-label="Toggle color scheme" data-bs-toggle="dropdown"
              aria-expanded="false">
              <i class="bi bi-sun-fill" data-lte-theme-icon="light"></i>
              <i class="bi bi-moon-fill d-none" data-lte-theme-icon="dark"></i>
              <i class="bi bi-circle-half d-none" data-lte-theme-icon="auto"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="bd-theme"
              style="--bs-dropdown-min-width: 8rem">
              <li>
                <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="light"
                  aria-pressed="false">
                  <i class="bi bi-sun-fill me-2"></i>
                  Light
                  <i class="bi bi-check-lg ms-auto d-none"></i>
                </button>
              </li>
              <li>
                <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="dark"
                  aria-pressed="false">
                  <i class="bi bi-moon-fill me-2"></i>
                  Dark
                  <i class="bi bi-check-lg ms-auto d-none"></i>
                </button>
              </li>
              <li>
                <button type="button" class="dropdown-item d-flex align-items-center active" data-bs-theme-value="auto"
                  aria-pressed="true">
                  <i class="bi bi-circle-half me-2"></i>
                  Auto
                  <i class="bi bi-check-lg ms-auto d-none"></i>
                </button>
              </li>
            </ul>
          </li>
          <!--end::Color Mode Toggle-->

          <!--begin::User Menu Dropdown-->
          <li class="nav-item dropdown user-menu">
            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
              <img src="./assets/img/user2-160x160.jpg" class="user-image rounded-circle shadow"
                alt="Inka Audya Miranti" />
              <span class="d-none d-md-inline">Inka Audya Miranti</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
              <!--begin::User Image-->
              <li class="user-header text-bg-primary">
                <img src="./assets/img/user2-160x160.jpg" class="rounded-circle shadow" alt="Inka Audya Miranti" />
                <p>
                  Inka Audya Miranti - Web Developer
                  <small>Member since Nov. 2023</small>
                </p>
              </li>
              <!--end::User Image-->
              <!--begin::Menu Body-->
              <li class="user-body">
                <!--begin::Row-->
                <div class="row">
                  <div class="col-4 text-center">
                    <a href="#">Followers</a>
                  </div>
                  <div class="col-4 text-center">
                    <a href="#">Sales</a>
                  </div>
                  <div class="col-4 text-center">
                    <a href="#">Friends</a>
                  </div>
                </div>
                <!--end::Row-->
              </li>
              <!--end::Menu Body-->
              <!--begin::Menu Footer-->
              <li class="user-footer">
                <a href="#" class="btn btn-outline-secondary">Profile</a>
                <a href="#" class="btn btn-outline-danger float-end">Sign out</a>
              </li>
              <!--end::Menu Footer-->
            </ul>
          </li>
          <!--end::User Menu Dropdown-->
        </ul>
        <!--end::End Navbar Links-->
      </div>
      <!--end::Container-->
    </nav>
    <!--end::Header-->
    <!--begin::Sidebar-->
    <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
      <!--begin::Sidebar Brand-->
      <div class="sidebar-brand">
        <!--begin::Brand Link-->
        <a href="./index.html" class="brand-link">
          <!--begin::Brand Image-->
          <img src="./assets/img/AdminLTELogo.png" alt="AdminLTE Logo" class="brand-image opacity-75 shadow" />
          <!--end::Brand Image-->
          <!--begin::Brand Text-->
          <span class="brand-text fw-light">AdminLTE 4</span>
          <!--end::Brand Text-->
        </a>
        <!--end::Brand Link-->
      </div>
      <!--end::Sidebar Brand-->
      <!--begin::Sidebar Search-->
      <div class="sidebar-search" role="search">
        <label for="sidebar-search-input" class="visually-hidden">Filter menu</label>
        <input type="search" id="sidebar-search-input" class="form-control form-control-sm" placeholder="Filter menu…"
          autocomplete="off" data-lte-toggle="sidebar-search" data-lte-target="#navigation" />
        <p class="fs-7 text-secondary mt-2 mb-0" data-lte-search-empty role="status" hidden>
          No matching pages.
        </p>
      </div>
      <!--end::Sidebar Search-->
      <!--begin::Sidebar Wrapper-->
      <div class="sidebar-wrapper">
        <nav class="mt-2" aria-label="Main navigation">
          <!--begin::Sidebar Menu-->
          <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" data-accordion="false" id="navigation">
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon bi bi-speedometer"></i>
                <p>
                  Dashboard
                  <i class="nav-arrow bi bi-chevron-right"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="./index.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Dashboard v1</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./index2.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Dashboard v2</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./index3.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Dashboard v3</p>
                  </a>
                </li>
              </ul>
            </li>
            <li class="nav-item">
              <a href="./starter.html" class="nav-link">
                <i class="nav-icon bi bi-file-earmark"></i>
                <p>Starter Page</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="./generate/theme.html" class="nav-link">
                <i class="nav-icon bi bi-palette"></i>
                <p>Theme Generate</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon bi bi-box-seam-fill"></i>
                <p>
                  Widgets
                  <i class="nav-arrow bi bi-chevron-right"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="./widgets/small-box.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Small Box</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./widgets/info-box.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>info Box</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./widgets/cards.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Cards</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./widgets/social.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Social &amp; Post</p>
                  </a>
                </li>
              </ul>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon bi bi-clipboard-fill"></i>
                <p>
                  Layout Options
                  <span class="nav-badge badge text-bg-secondary me-3">12</span>
                  <i class="nav-arrow bi bi-chevron-right"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="./layout/unfixed-sidebar.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Default Sidebar</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./layout/fixed-sidebar.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Fixed Sidebar</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./layout/fixed-header.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Fixed Header</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./layout/fixed-footer.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Fixed Footer</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./layout/fixed-complete.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Fixed Complete</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./layout/layout-custom-area.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Layout <small>+ Custom Area </small></p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./layout/sidebar-mini.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Sidebar Mini</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./layout/collapsed-sidebar.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Sidebar Mini <small>+ Collapsed</small></p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./layout/collapsed-sidebar-without-hover.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Sidebar Mini <small>+ Collapsed + No Hover</small></p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./layout/logo-switch.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Sidebar Mini <small>+ Logo Switch</small></p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./layout/top-nav.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Top Nav <small>+ No Sidebar</small></p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./layout/layout-rtl.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Layout RTL</p>
                  </a>
                </li>
              </ul>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon bi bi-tree-fill"></i>
                <p>
                  UI Elements
                  <i class="nav-arrow bi bi-chevron-right"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="./UI/general.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>General</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./UI/icons.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Icons</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./UI/timeline.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Timeline</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./UI/ribbons.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Ribbons</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./UI/colors.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Colors</p>
                  </a>
                </li>
              </ul>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon bi bi-envelope"></i>
                <p>
                  Mailbox
                  <i class="nav-arrow bi bi-chevron-right"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="./mailbox/inbox.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Inbox</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./mailbox/read.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Read Message</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./mailbox/compose.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Compose</p>
                  </a>
                </li>
              </ul>
            </li>

            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon bi bi-pencil-square"></i>
                <p>
                  Forms
                  <i class="nav-arrow bi bi-chevron-right"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="./forms/elements.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Elements</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./forms/layout.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Layout</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./forms/validation.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Validation</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./forms/wizard.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Wizard</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./forms/advanced.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Advanced Elements</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./forms/editors.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Editors</p>
                  </a>
                </li>
              </ul>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon bi bi-table"></i>
                <p>
                  Tables
                  <i class="nav-arrow bi bi-chevron-right"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="./tables/simple.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Simple Tables</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./tables/data.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Data Tables</p>
                  </a>
                </li>
              </ul>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon bi bi-graph-up"></i>
                <p>
                  Charts
                  <i class="nav-arrow bi bi-chevron-right"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="./charts/chartjs.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Chart.js</p>
                  </a>
                </li>
              </ul>
            </li>

            <li class="nav-header">PAGES</li>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon bi bi-file-earmark-text"></i>
                <p>
                  Pages
                  <i class="nav-arrow bi bi-chevron-right"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="./pages/profile.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Profile</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./pages/settings.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Settings</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./pages/invoice.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Invoice</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./pages/calendar.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Calendar</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./pages/kanban.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Kanban</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./pages/chat.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Chat</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./pages/file-manager.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>File Manager</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./pages/projects.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Projects</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./pages/gallery.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Gallery</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./pages/search-results.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Search Results</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./pages/pricing.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Pricing</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./pages/faq.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>FAQ</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="#" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>
                      Error
                      <i class="nav-arrow bi bi-chevron-right"></i>
                    </p>
                  </a>
                  <ul class="nav nav-treeview">
                    <li class="nav-item">
                      <a href="./pages/404.html" class="nav-link">
                        <i class="nav-icon bi bi-circle"></i>
                        <p>404</p>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="./pages/500.html" class="nav-link">
                        <i class="nav-icon bi bi-circle"></i>
                        <p>500</p>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="./pages/maintenance.html" class="nav-link">
                        <i class="nav-icon bi bi-circle"></i>
                        <p>Maintenance</p>
                      </a>
                    </li>
                  </ul>
                </li>
              </ul>
            </li>
            <li class="nav-item">
              <a href="./users.php" class="nav-link active">
                <i class="nav-icon bi bi-people"></i>
                <p>Nilai Mahasiswa</p>
              </a>
            </li>

            <li class="nav-header">EXAMPLES</li>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon bi bi-box-arrow-in-right"></i>
                <p>
                  Auth
                  <i class="nav-arrow bi bi-chevron-right"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="#" class="nav-link">
                    <i class="nav-icon bi bi-box-arrow-in-right"></i>
                    <p>
                      Version 1
                      <i class="nav-arrow bi bi-chevron-right"></i>
                    </p>
                  </a>
                  <ul class="nav nav-treeview">
                    <li class="nav-item">
                      <a href="./examples/login.html" class="nav-link">
                        <i class="nav-icon bi bi-circle"></i>
                        <p>Login</p>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="./examples/register.html" class="nav-link">
                        <i class="nav-icon bi bi-circle"></i>
                        <p>Register</p>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="./examples/forgot-password.html" class="nav-link">
                        <i class="nav-icon bi bi-circle"></i>
                        <p>Forgot Password</p>
                      </a>
                    </li>
                  </ul>
                </li>
                <li class="nav-item">
                  <a href="#" class="nav-link">
                    <i class="nav-icon bi bi-box-arrow-in-right"></i>
                    <p>
                      Version 2
                      <i class="nav-arrow bi bi-chevron-right"></i>
                    </p>
                  </a>
                  <ul class="nav nav-treeview">
                    <li class="nav-item">
                      <a href="./examples/login-v2.html" class="nav-link">
                        <i class="nav-icon bi bi-circle"></i>
                        <p>Login</p>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="./examples/register-v2.html" class="nav-link">
                        <i class="nav-icon bi bi-circle"></i>
                        <p>Register</p>
                      </a>
                    </li>
                  </ul>
                </li>
                <li class="nav-item">
                  <a href="./examples/lockscreen.html" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Lockscreen</p>
                  </a>
                </li>
              </ul>
            </li>

            <li class="nav-header">MULTI LEVEL EXAMPLE</li>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon bi bi-circle-fill"></i>
                <p>Level 1</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon bi bi-circle-fill"></i>
                <p>
                  Level 1
                  <i class="nav-arrow bi bi-chevron-right"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="#" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Level 2</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="#" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>
                      Level 2
                      <i class="nav-arrow bi bi-chevron-right"></i>
                    </p>
                  </a>
                  <ul class="nav nav-treeview">
                    <li class="nav-item">
                      <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-record-circle-fill"></i>
                        <p>Level 3</p>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-record-circle-fill"></i>
                        <p>Level 3</p>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-record-circle-fill"></i>
                        <p>Level 3</p>
                      </a>
                    </li>
                  </ul>
                </li>
                <li class="nav-item">
                  <a href="#" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Level 2</p>
                  </a>
                </li>
              </ul>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon bi bi-circle-fill"></i>
                <p>Level 1</p>
              </a>
            </li>

            <li class="nav-header">LABELS</li>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon bi bi-circle text-danger"></i>
                <p class="text">Important</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon bi bi-circle text-warning"></i>
                <p>Warning</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon bi bi-circle text-info"></i>
                <p>Informational</p>
              </a>
            </li>
          </ul>
          <!--end::Sidebar Menu-->

          <!-- Docs CTA (bottom of sidebar) -->
          <div class="p-3 mt-3 border-top border-secondary border-opacity-25">
            <a href="./docs/introduction.html"
              class="btn btn-sm btn-outline-light w-100 d-flex align-items-center justify-content-center gap-2">
              <i class="bi bi-book" aria-hidden="true"></i>
              View documentation
            </a>
          </div>
        </nav>
      </div>
      <!--end::Sidebar Wrapper-->
    </aside>
    <!--end::Sidebar-->
    <!--begin::App Main-->
    <main class="app-main">
      <!--begin::App Content Header-->
      <div class="app-content-header">
        <!--begin::Container-->
        <div class="container-fluid">
          <!--begin::Row-->
          <div class="row">
            <div class="col-sm-6">
              <h1 class="mb-0 fs-3">Nilai Mahasiswa</h1>
            </div>
            <div class="col-sm-6">
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb float-sm-end">
                  <li class="breadcrumb-item"><a href="#">Home</a></li>
                  <li class="breadcrumb-item active" aria-current="page">Nilai Mahasiswa</li>
                </ol>
              </nav>
            </div>
          </div>
          <!--end::Row-->
        </div>
        <!--end::Container-->
      </div>
      <!--end::App Content Header-->
      <!--begin::App Content-->
      <div class="app-content">
        <!--begin::Container-->
        <div class="container-fluid">
          <!--begin::Row-->
          <div class="row">
            <div class="col-12">
              <!--begin::Card-->
              <div class="card mb-4">
                <!--begin::Card Header-->
                <div class="card-header">
                  <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-4">
                      <h3 class="card-title">Direktori Nilai Mahasiswa</h3>
                    </div>
                    <div class="col-12 col-md-8">
                      <div class="d-flex flex-wrap justify-content-md-end gap-2">
                        <form id="formFilterNilai" action="users.php" method="get"
                          class="d-flex flex-wrap justify-content-md-end gap-2">
                          <input type="hidden" name="urut"
                            value="<?= htmlspecialchars($urutNilai, ENT_QUOTES, 'UTF-8') ?>">
                          <input type="hidden" name="limit" value="<?= $perHalaman ?>">
                          <div class="input-group input-group-sm w-auto">
                            <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                            <input type="search" id="user-search" name="keyword" class="form-control"
                              placeholder="Cari nama mahasiswa" aria-label="Cari berdasarkan nama mahasiswa"
                              style="width: 180px" value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>">
                          </div>
                          <select id="user-role-filter" name="predikat" class="form-select form-select-sm w-auto"
                            aria-label="Filter berdasarkan predikat" onchange="this.form.submit()">
                            <option value="">Semua Predikat</option>
                            <?php foreach ($pilihanPredikat as $opsiPredikat): ?>
                              <option value="<?= htmlspecialchars($opsiPredikat, ENT_QUOTES, 'UTF-8') ?>"
                                <?= $filterPredikat === $opsiPredikat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($opsiPredikat, ENT_QUOTES, 'UTF-8') ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                          <button type="submit" class="btn btn-sm btn-outline-secondary" title="Jalankan pencarian">
                            <i class="bi bi-search"></i>
                          </button>
                          <?php if ($keyword !== '' || $filterPredikat !== ''): ?>
                            <a href="users.php" class="btn btn-sm btn-outline-secondary" title="Reset pencarian">Reset</a>
                          <?php endif; ?>
                        </form>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                          data-bs-target="#modalTambahNilai">
                          <i class="bi bi-person-plus-fill me-1" aria-hidden="true"></i>
                          Tambah Data Nilai
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
                <!--end::Card Header-->
                <!--begin::Card Body-->
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table table-hover align-middle m-0">
                      <thead>
                        <tr>
                          <th>NIM</th>
                          <th>Nama Mahasiswa</th>
                          <th>
                            <a href="users.php?<?= htmlspecialchars($queryUrut, ENT_QUOTES, 'UTF-8') ?>"
                              class="text-reset text-decoration-none d-inline-flex align-items-center gap-1"
                              title="Urutkan nilai <?= $urutBerikutnya === 'ASC' ? 'terendah ke tertinggi' : 'tertinggi ke terendah' ?>"
                              aria-label="Urutkan nilai angka <?= $urutBerikutnya === 'ASC' ? 'menaik' : 'menurun' ?>">
                              Nilai Angka
                              <i class="bi <?= $urutNilai === 'ASC' ? 'bi-sort-numeric-up' : 'bi-sort-numeric-down' ?>"
                                aria-hidden="true"></i>
                            </a>
                          </th>
                          <th>Nilai Huruf</th>
                          <th>Predikat</th>
                          <th>Dibuat Tanggal</th>
                          <th>Diubah Tanggal</th>
                          <th>Aksi</th>
                        </tr>
                      </thead>


                      <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                          <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                              <td><?= htmlspecialchars($row["nim"]) ?></td>
                              <td>
                                <div class="d-flex align-items-center gap-2">
                                  <?php if (!empty($row['foto'])): ?>
                                    <img src="<?= htmlspecialchars($row['foto'], ENT_QUOTES, 'UTF-8') ?>" alt="Foto mahasiswa"
                                      class="rounded-circle object-fit-cover border"
                                      style="width:38px;height:38px;object-fit:cover">
                                  <?php else: ?>
                                    <span
                                      class="rounded-circle bg-body-secondary d-inline-flex align-items-center justify-content-center border"
                                      style="width:38px;height:38px"><i
                                        class="bi bi-person-fill text-body-secondary"></i></span>
                                  <?php endif; ?>
                                  <span><?= htmlspecialchars($row["namamhsw"]) ?></span>
                                </div>
                              </td>
                              <td><?= htmlspecialchars($row["nilaiakhir"]) ?></td>
                              <td>
                                <span class="badge <?= kelasWarnaNilai($row['nilaihuruf']) ?>">
                                  <?= htmlspecialchars($row["nilaihuruf"]) ?>
                                </span>
                              </td>
                              <td>
                                <?= htmlspecialchars($row["predikat"]) ?>
                              </td>
                              <td><?= htmlspecialchars($row["created_date"]) ?></td>
                              <td><?= htmlspecialchars($row["modified_date"]) ?></td>

                              <td>
                                <!-- Tombol Edit -->
                                <a href="edit_nilai.php?nim=<?= urlencode($row['nim']) ?>" class="btn btn-sm btn-warning"
                                  title="Edit data">
                                  <i class="bi bi-pencil-square"></i>
                                </a>

                                <!-- Tombol Hapus -->
                                <form action="hapus_nilai.php" method="POST" class="d-inline form-hapus-nilai">
                                  <input type="hidden" name="nim"
                                    value="<?= htmlspecialchars($row['nim'], ENT_QUOTES, 'UTF-8') ?>">

                                  <button type="submit" class="btn btn-sm btn-danger" title="Hapus data">
                                    <i class="bi bi-trash"></i>
                                  </button>
                                </form>
                              </td>

                            </tr>
                          <?php endwhile; ?>
                        <?php else: ?>


                          <tr>
                            <td colspan="8" class="text-center p-4">
                              <?= ($keyword !== '' || $filterPredikat !== '') ? 'Data mahasiswa tidak ditemukan.' : 'Belum ada data mahasiswa.' ?>
                            </td>
                          </tr>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                </div>



                <!--end::Card Body-->
                <!--begin::Card Footer-->
                <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-3">
                  <div class="d-flex flex-wrap align-items-center gap-2">
                    <form method="get" action="users.php" class="d-flex align-items-center gap-2 mb-0">
                      <input type="hidden" name="keyword"
                        value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>">
                      <input type="hidden" name="predikat"
                        value="<?= htmlspecialchars($filterPredikat, ENT_QUOTES, 'UTF-8') ?>">
                      <input type="hidden" name="urut" value="<?= htmlspecialchars($urutNilai, ENT_QUOTES, 'UTF-8') ?>">
                      <label for="limit-data" class="fs-7 text-body-secondary mb-0">Tampilkan</label>
                      <select name="limit" id="limit-data" class="form-select form-select-sm w-auto"
                        onchange="this.form.submit()" aria-label="Jumlah data per halaman">
                        <?php foreach ([5, 10, 15] as $opsiLimit): ?>
                          <option value="<?= $opsiLimit ?>" <?= $perHalaman === $opsiLimit ? 'selected' : '' ?>>
                            <?= $opsiLimit ?></option>
                        <?php endforeach; ?>
                      </select>
                      <span class="fs-7 text-body-secondary">data per halaman</span>
                    </form>
                    <span class="fs-7 text-body-secondary">Menampilkan <?= $awalData ?>–<?= $akhirData ?> dari
                      <?= $totalData ?> data</span>
                  </div>
                  <nav aria-label="Navigasi halaman daftar nilai">
                    <ul class="pagination pagination-sm mb-0">
                      <?php
                      $tautanHalaman = function (int $nomor) use ($keyword, $filterPredikat, $urutNilai, $perHalaman): string {
                        return htmlspecialchars(urlHalaman($nomor, $keyword, $filterPredikat, $urutNilai, $perHalaman), ENT_QUOTES, 'UTF-8');
                      };
                      ?>
                      <li class="page-item <?= $halaman <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $tautanHalaman(1) ?>" <?= $halaman <= 1 ? 'tabindex="-1" aria-disabled="true"' : '' ?>>First Page</a>
                      </li>
                      <li class="page-item <?= $halaman <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $tautanHalaman(max(1, $halaman - 1)) ?>" <?= $halaman <= 1 ? 'tabindex="-1" aria-disabled="true"' : '' ?>>Previous</a>
                      </li>
                      <?php for ($nomor = max(1, $halaman - 2); $nomor <= min($totalHalaman, $halaman + 2); $nomor++): ?>
                        <li class="page-item <?= $nomor === $halaman ? 'active' : '' ?>">
                          <a class="page-link" href="<?= $tautanHalaman($nomor) ?>" <?= $nomor === $halaman ? 'aria-current="page"' : '' ?>><?= $nomor ?></a>
                        </li>
                      <?php endfor; ?>
                      <li class="page-item <?= $halaman >= $totalHalaman ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $tautanHalaman(min($totalHalaman, $halaman + 1)) ?>" <?= $halaman >= $totalHalaman ? 'tabindex="-1" aria-disabled="true"' : '' ?>>Next</a>
                      </li>
                      <li class="page-item <?= $halaman >= $totalHalaman ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $tautanHalaman($totalHalaman) ?>" <?= $halaman >= $totalHalaman ? 'tabindex="-1" aria-disabled="true"' : '' ?>>Last Page</a>
                      </li>
                    </ul>
                  </nav>
                </div>
                <!--end::Card Footer-->
              </div>
              <!--end::Card-->
            </div>
            <!-- /.col -->
          </div>
          <!--end::Row-->


        </div>
        <!--end::Container-->
      </div>
      <!--end::App Content-->
    </main>
    <!--end::App Main-->
    <!--begin::Footer-->
    <footer class="app-footer">
      <!--begin::To the end-->
      <div class="float-end d-none d-sm-inline">Sistem Informasi Nilai Mahasiswa</div>
      <!--end::To the end-->
      <!--begin::Copyright-->
      <strong>
        Copyright &copy; 2014-2026&nbsp;
        <a href="https://adminlte.io" class="text-decoration-none">AdminLTE.io</a>.
      </strong>
      All rights reserved.
      <!--end::Copyright-->
    </footer>
    <!--end::Footer-->
  </div>
  <!--end::App Wrapper-->

  <!-- Notifikasi aksi CRUD (status berasal dari redirect PHP) -->
  <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100">
    <div id="toastAksiNilai" class="toast align-items-center border-0" role="status" aria-live="polite"
      aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body" id="isiToastNilai"></div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
          aria-label="Tutup"></button>
      </div>
    </div>
  </div>

  <!-- Modal konfirmasi hapus dengan gaya Bootstrap/AdminLTE -->
  <div class="modal fade" id="modalHapusNilai" tabindex="-1" aria-labelledby="judulHapusNilai" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="judulHapusNilai"><i
              class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Konfirmasi Hapus Data</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <p class="mb-2">Yakin ingin menghapus data mahasiswa berikut?</p>
          <p class="fw-semibold mb-2" id="identitasHapusNilai"></p>
          <p class="small text-danger mb-0">Data yang dihapus tidak dapat dikembalikan.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-danger" id="konfirmasiHapusNilai"><i class="bi bi-trash me-1"></i>Ya,
            Hapus</button>
        </div>
      </div>
    </div>
  </div>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      let formHapusAktif = null;
      const modalHapusEl = document.getElementById('modalHapusNilai');
      const modalHapus = bootstrap.Modal.getOrCreateInstance(modalHapusEl);
      document.querySelectorAll('.form-hapus-nilai').forEach(form => {
        form.addEventListener('submit', event => {
          event.preventDefault();
          formHapusAktif = form;
          const baris = form.closest('tr');
          const nim = form.querySelector('[name="nim"]').value;
          const nama = baris?.cells[1]?.textContent.trim() || '';
          document.getElementById('identitasHapusNilai').textContent = `${nama} (NIM: ${nim})`;
          modalHapus.show();
        });
      });
      document.getElementById('konfirmasiHapusNilai').addEventListener('click', () => {
        if (formHapusAktif) HTMLFormElement.prototype.submit.call(formHapusAktif);
      });
      const status = new URLSearchParams(location.search).get('status');
      const pesanStatus = {
        edit_sukses: ['Data mahasiswa berhasil diperbarui.', 'text-bg-success'],
        hapus_sukses: ['Data mahasiswa berhasil dihapus.', 'text-bg-success'],
        hapus_gagal: ['Data mahasiswa gagal dihapus.', 'text-bg-danger'],
        tidak_ditemukan: ['Data mahasiswa tidak ditemukan.', 'text-bg-warning']
      };
      if (status && pesanStatus[status]) {
        const [pesan, warna] = pesanStatus[status];
        const toastEl = document.getElementById('toastAksiNilai');
        toastEl.classList.add(warna);
        document.getElementById('isiToastNilai').textContent = pesan;
        bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 4000 }).show();
        const url = new URL(location.href);
        url.searchParams.delete('status');
        history.replaceState({}, '', url.pathname + url.search + url.hash);
      }
    });
  </script>

  <!-- Modal tambah nilai: tetap di dalam halaman AdminLTE 4 -->
  <div class="modal fade" id="modalTambahNilai" tabindex="-1" aria-labelledby="modalTambahNilaiLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form novalidate id="formTambahNilai" action="konversi_dbnilai_modal.php" method="post"
          enctype="multipart/form-data">
          <div class="modal-header">
            <h5 class="modal-title" id="modalTambahNilaiLabel">Tambah Nilai Mahasiswa</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
          </div>
          <div class="modal-body">
            <div id="pesanTambahNilai" class="alert d-none" role="alert"></div>
            <div class="mb-3">
              <label for="inputNim" class="form-label">NIM <span class="text-danger">*</span></label>
              <input class="form-control" id="inputNim" name="nim" type="text" inputmode="numeric" maxlength="10"
                pattern="[0-9]{1,10}" placeholder="Masukkan NIM" required>
              <div class="invalid-feedback">NIM wajib diisi dengan angka (maksimal 10 digit).</div>
            </div>
            <div class="mb-3">
              <label for="inputNama" class="form-label">Nama Mahasiswa <span class="text-danger">*</span></label>
              <input class="form-control" id="inputNama" name="nama_mhsw" type="text" maxlength="30"
                placeholder="Masukkan nama mahasiswa" required>
              <div class="invalid-feedback">Nama wajib diisi dengan huruf dan spasi (maksimal 30 karakter).</div>
            </div>
            <div class="mb-3">
              <label for="inputNilai" class="form-label">Nilai Akhir <span class="text-danger">*</span></label>
              <input class="form-control" id="inputNilai" name="nilai_akhir" type="number" min="0" max="100" step="any"
                placeholder="0 - 100" required>
              <div class="invalid-feedback">Nilai akhir wajib diisi dengan angka antara 0 sampai 100.</div>
            </div>
            <div class="mb-3">
              <label for="inputFoto" class="form-label">Foto Mahasiswa</label>

              <div class="input-group">
                <label for="inputFoto" class="btn btn-outline-secondary">
                  <i class="bi bi-image me-1"></i>
                  Pilih Foto
                </label>

                <input type="file" id="inputFoto" name="foto" accept=".jpg,.jpeg,.png,.webp" class="d-none" onchange="document.getElementById('namaFileFoto').textContent =
        this.files.length ? this.files[0].name : 'Belum ada file dipilih'">

                <span id="namaFileFoto" class="form-control text-body-secondary"
                  style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                  Belum ada file dipilih
                </span>
              </div>

              <small class="text-body-secondary">
                JPG, PNG, atau WEBP; maksimal 2 MB. Tanpa foto akan tampil avatar bawaan.
              </small>
            </div>
            <p class="small text-body-secondary mb-0">*Wajib diisi</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" id="btnSimpanNilai" class="btn btn-primary"><i class="bi bi-floppy me-1"></i>Simpan
              Data</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <script>
    // AJAX: kirim data tanpa meninggalkan halaman AdminLTE.
    document.addEventListener('DOMContentLoaded', () => {
      const form = document.getElementById('formTambahNilai');
      const pesan = document.getElementById('pesanTambahNilai');
      const tombol = document.getElementById('btnSimpanNilai');
      function validasiFormNilai(formulir) {
        let benar = true;
        for (const input of formulir.querySelectorAll('input[required]')) {
          let valid = input.checkValidity();
          if (input.name === 'nama_mhsw') valid = valid && /^[A-Za-z\s]+$/.test(input.value.trim());
          input.classList.toggle('is-invalid', !valid);
          if (!valid) {
            if (benar) input.focus();
            benar = false;
          }
        }
        return benar;
      }
      form.querySelectorAll('input[required]').forEach(input => {
        input.addEventListener('input', () => input.classList.remove('is-invalid'));
      });
      form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!validasiFormNilai(form)) return;
        const foto = form.querySelector('[name="foto"]')?.files[0];
        if (foto && (foto.size > 2 * 1024 * 1024 || !['image/jpeg', 'image/png', 'image/webp'].includes(foto.type))) {
          pesan.className = 'alert alert-danger';
          pesan.textContent = 'Foto harus JPG/PNG/WEBP dengan ukuran maksimal 2 MB.';
          return;
        }
        tombol.disabled = true;
        pesan.className = 'alert d-none';
        try {
          const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'Accept': 'application/json' }
          });
          const teks = await response.text();

          console.log("Respons PHP:", teks);

          let hasil;

          try {
            hasil = JSON.parse(teks);
          } catch (error) {
            throw new Error(
              "Server mengembalikan respons bukan JSON. " +
              "Tekan F12 dan lihat tab Console."
            );
          }
          if (!response.ok || !hasil.success) {
            throw new Error(hasil.message || 'Gagal menyimpan data.');
          }
          pesan.className = 'alert alert-success';
          pesan.textContent = hasil.message;
          form.reset();
          // Setelah berhasil, muat ulang daftar agar baris baru terlihat.
          setTimeout(() => window.location.reload(), 650);
        } catch (error) {
          pesan.className = 'alert alert-danger';
          pesan.textContent = error.message || 'Tidak dapat menghubungi server.';
        } finally {
          tombol.disabled = false;
        }
      });
    });
  </script>
  <!--begin::Script-->
  <!--begin::Third Party Plugin(OverlayScrollbars)-->
  <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"
    crossorigin="anonymous"></script>
  <!--end::Third Party Plugin(OverlayScrollbars)--><!--begin::Required Plugin(popperjs for Bootstrap 5)-->
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
    crossorigin="anonymous"></script>
  <!--end::Required Plugin(popperjs for Bootstrap 5)--><!--begin::Required Plugin(Bootstrap 5)-->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
  <!--end::Required Plugin(Bootstrap 5)--><!--begin::Required Plugin(AdminLTE)-->
  <script src="./js/adminlte.js"></script>
  <!--end::Required Plugin(AdminLTE)-->
  <!--begin::OverlayScrollbars Configure-->
  <script>
    const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
    const Default = {
      scrollbarTheme: 'os-theme-light',
      scrollbarAutoHide: 'leave',
      scrollbarClickScroll: true,
    };
    document.addEventListener('DOMContentLoaded', function () {
      const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);

      // Disable OverlayScrollbars on mobile devices to prevent touch interference
      const isMobile = window.innerWidth <= 992;

      if (
        sidebarWrapper &&
        OverlayScrollbarsGlobal?.OverlayScrollbars !== undefined &&
        !isMobile
      ) {
        OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
          scrollbars: {
            theme: Default.scrollbarTheme,
            autoHide: Default.scrollbarAutoHide,
            clickScroll: Default.scrollbarClickScroll,
          },
        });
      }
    });
  </script>
  <!--end::OverlayScrollbars Configure-->

  <!--begin::Color Mode Toggle-->
  <!-- The light/dark/auto switcher ships in adminlte.js as the ColorMode
     module (since 4.1) — no page script needed. Only the no-flash snippet
     in <head> stays inline, because it must run before first paint. -->
  <!--end::Color Mode Toggle-->

  <!-- Menutup hasil query dan koneksi database -->

  <?php
  mysqli_free_result($result);
  mysqli_stmt_close($stmt);
  mysqli_close($conn);
  ?>
  <!--end::Script-->
</body>
<!--end::Body-->

</html>