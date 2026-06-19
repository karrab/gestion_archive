<?php
declare(strict_types=1);
require __DIR__ . '/../../config/config.php';
require_permission('user.view');

$pdo = db();
$sort = in_array($_GET['sort'] ?? '', ['id', 'nom', 'login']) ? $_GET['sort'] : 'nom';
$dir = ($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
$filtreNom = trim($_GET['f_nom'] ?? '');
$filtreLogin = trim($_GET['f_login'] ?? '');
$perPage = $_GET['per_page'] ?? '50';

$where = [];
$params = [];
if ($filtreNom !== '') {
    $where[] = 'u.nom LIKE :nom';
    $params['nom'] = '%' . $filtreNom . '%';
}
if ($filtreLogin !== '') {
    $where[] = 'u.login LIKE :login';
    $params['login'] = '%' . $filtreLogin . '%';
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT u.id, u.nom, u.prenom, u.mail, u.login, u.actif, r.nom AS role_nom
        FROM user u INNER JOIN role r ON r.id = u.role_id
        {$whereSql} ORDER BY u.{$sort} {$dir}";
if ($perPage !== 'all') {
    $sql .= ' LIMIT ' . (int) $perPage;
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$pageTitle = 'المستخدمون';
require __DIR__ . '/../../includes/layout_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4><i class="bi bi-people"></i> المستخدمون</h4>
  <div>
    <?php if (has_permission('user.print')): ?>
      <a href="<?= BASE_URL ?>/pdf/export.php?type=user" class="btn btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
    <?php endif; ?>
    <?php if (has_permission('user.add')): ?>
      <a href="add.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> إضافة</a>
    <?php endif; ?>
  </div>
</div>

<form method="get" class="row g-2 mb-3">
  <div class="col-auto">
    <select name="per_page" class="form-select" onchange="this.form.submit()">
      <?php foreach (['10','50','100','200','500','1000','all'] as $opt): ?>
        <option value="<?= $opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= $opt === 'all' ? 'الكل' : $opt ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</form>

<div class="card">
  <div class="card-body table-responsive">
    <table class="table table-hover table-bordered" data-grid data-sort-col="<?= e($sort) ?>" data-sort-dir="<?= e(strtolower($dir)) ?>">
      <thead>
        <tr>
          <th data-col="id">#</th>
          <th data-col="nom">الاسم</th>
          <th>اللقب</th>
          <th>البريد الإلكتروني</th>
          <th data-col="login">اسم الدخول</th>
          <th>الدور</th>
          <th>نشط</th>
          <th>إجراءات</th>
        </tr>
        <tr class="column-search">
          <td></td>
          <td><input type="text" class="form-control form-control-sm" data-col="nom" value="<?= e($filtreNom) ?>" placeholder="بحث..."></td>
          <td></td><td></td>
          <td><input type="text" class="form-control form-control-sm" data-col="login" value="<?= e($filtreLogin) ?>" placeholder="بحث..."></td>
          <td></td><td></td><td></td>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?= (int) $it['id'] ?></td>
          <td><?= e($it['nom']) ?></td>
          <td><?= e($it['prenom']) ?></td>
          <td><?= e($it['mail']) ?></td>
          <td><?= e($it['login']) ?></td>
          <td><?= e($it['role_nom']) ?></td>
          <td><?= ((int) $it['actif'] === 1) ? '<span class="badge bg-success">نعم</span>' : '<span class="badge bg-secondary">لا</span>' ?></td>
          <td>
            <a href="view.php?id=<?= $it['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
            <?php if (has_permission('user.edit')): ?>
              <a href="edit.php?id=<?= $it['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <?php endif; ?>
            <?php if (has_permission('user.delete') && $it['id'] != current_user_id()): ?>
              <form method="post" action="delete.php" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $it['id'] ?>">
                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-confirm"><i class="bi bi-trash"></i></button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$items): ?>
        <tr><td colspan="8" class="text-center text-muted">لا توجد بيانات</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../../includes/layout_footer.php'; ?>
