<?php
declare(strict_types=1);
$parametres = get_parametres();
$pageTitle = $pageTitle ?? 'Gestion Archive';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> - <?= e($parametres['nom_etablissement'] ?? 'Gestion Archive') ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/bootstrap.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap-icons/bootstrap-icons.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body>
<?php if (!empty($_SESSION['user_id'])): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3" id="main-navbar">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>/index.php">
      <img src="<?= BASE_URL ?>/images/<?= e($parametres['logo'] ?? 'logo.png') ?>" alt="logo" height="36">
      <span><?= e($parametres['nom_etablissement'] ?? 'Gestion Archive') ?></span>
      <span class="badge bg-secondary">v<?= e($appVersion ?? '1.0') ?></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/index.php"><i class="bi bi-speedometer2"></i> لوحة القيادة</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/modules/archive/list.php"><i class="bi bi-archive"></i> الأرشيف</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/modules/transfert/list.php"><i class="bi bi-arrow-left-right"></i> النقل</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/modules/versement/list.php"><i class="bi bi-box-arrow-in-down"></i> الإيداع</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/modules/elimination/list.php"><i class="bi bi-trash3"></i> الإتلاف</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-card-list"></i> الجداول المرجعية</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/service/list.php">الخدمات</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/employe/list.php">الموظفون</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/depot/list.php">المستودعات</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/annee/list.php">السنوات</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/classification/list.php">التصنيف</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/carac_ideologique/list.php">الخاصية الإيديولوجية</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/carac_temporelle/list.php">الخاصية الزمنية</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/carac_geographique/list.php">الخاصية الجغرافية</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/type_doc/list.php">نوع الوثيقة</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/sort_fin_doc/list.php">مصير الوثيقة</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/etat_archive/list.php">حالة الأرشيف</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/institution/list.php">المؤسسات</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-file-earmark-pdf"></i> تصدير</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/pdf/export.php?type=archive">قائمة الأرشيف</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/pdf/export.php?type=transfert">قائمة النقل</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/pdf/export.php?type=versement">قائمة الإيداع</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/pdf/export.php?type=elimination">قائمة الإتلاف</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/pdf/export.php?type=depot">قائمة المستودعات</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/pdf/export.php?type=historique">سجل العمليات</a></li>
          </ul>
        </li>
        <?php if (has_permission('user.manage')): ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-gear"></i> الإدارة</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/user/list.php">المستخدمون</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/role/list.php">الأدوار والصلاحيات</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/parametres/edit.php">المعلمات</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/version/list.php">الإصدارات</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/historique/list.php">سجل العمليات</a></li>
          </ul>
        </li>
        <?php endif; ?>
      </ul>
      <form class="d-flex me-2" id="global-search-form" role="search">
        <input type="search" id="global-search-input" class="form-control" placeholder="بحث شامل...">
      </form>
      <ul class="navbar-nav">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle"></i> <?= e($_SESSION['user_prenom'] ?? '') ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/profile/edit.php">الملف الشخصي</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/profile/password.php">تغيير كلمة المرور</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/logout.php">تسجيل الخروج</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>
<div id="global-search-results" class="container-fluid d-none"></div>
<?php endif; ?>
<main class="container-fluid py-3">
<?php foreach (flash_get() as $flash): ?>
  <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
    <?= e($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endforeach; ?>
