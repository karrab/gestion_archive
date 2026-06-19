</main>
<footer class="text-center text-muted py-3 small">
  &copy; <?= date('Y') ?> <?= e($parametres['nom_etablissement'] ?? '') ?>
</footer>
<script>window.BASE_URL = '<?= BASE_URL ?>';</script>
<script src="<?= BASE_URL ?>/assets/js/jquery.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/select2.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
