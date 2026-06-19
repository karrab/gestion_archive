document.addEventListener('DOMContentLoaded', function () {
  // Confirmation avant suppression
  document.querySelectorAll('.btn-delete-confirm').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      const form = btn.closest('form');
      const url = btn.getAttribute('data-url');
      if (confirm('هل تريد تأكيد عملية الحذف هذه؟')) {
        if (form) form.submit();
        else if (url) window.location.href = url;
      }
    });
  });

  // Initialisation Select2 (recherche intégrée pour les menus déroulants)
  if (window.jQuery && jQuery.fn.select2) {
    jQuery('.select2').select2({ width: '100%', dir: 'rtl' });
  }

  // Recherche par colonne + tri sur les grids
  document.querySelectorAll('table[data-grid]').forEach(initGrid);

  // Recherche globale (navbar)
  const searchInput = document.getElementById('global-search-input');
  const resultsBox = document.getElementById('global-search-results');
  if (searchInput && resultsBox) {
    let timer = null;
    searchInput.addEventListener('input', function () {
      clearTimeout(timer);
      const q = searchInput.value.trim();
      if (q.length < 2) {
        resultsBox.classList.add('d-none');
        resultsBox.innerHTML = '';
        return;
      }
      timer = setTimeout(function () {
        fetch(BASE_URL + '/modules/archive/search_ajax.php?q=' + encodeURIComponent(q))
          .then(function (r) { return r.json(); })
          .then(function (data) {
            renderGlobalSearch(data);
          });
      }, 300);
    });
  }

  function renderGlobalSearch(items) {
    if (!items.length) {
      resultsBox.innerHTML = '<div class="p-3 text-muted">لا توجد نتائج</div>';
      resultsBox.classList.remove('d-none');
      return;
    }
    let html = '<table class="table table-sm table-hover mb-0"><tbody>';
    items.forEach(function (it) {
      html += '<tr><td><a href="' + BASE_URL + '/modules/archive/view.php?id=' + it.id + '">' +
        it.titre_dossier + ' - ' + it.num_boite + '</a></td></tr>';
    });
    html += '</tbody></table>';
    resultsBox.innerHTML = html;
    resultsBox.classList.remove('d-none');
  }
});

function initGrid(table) {
  const headers = table.querySelectorAll('thead th[data-col]');
  const searchRow = table.querySelector('thead tr.column-search');
  const tbody = table.querySelector('tbody');

  headers.forEach(function (th) {
    th.classList.add('sortable');
    th.addEventListener('click', function () {
      const col = th.getAttribute('data-col');
      const current = table.getAttribute('data-sort-col');
      const dir = current === col && table.getAttribute('data-sort-dir') === 'asc' ? 'desc' : 'asc';
      table.setAttribute('data-sort-col', col);
      table.setAttribute('data-sort-dir', dir);
      const url = new URL(window.location.href);
      url.searchParams.set('sort', col);
      url.searchParams.set('dir', dir);
      window.location.href = url.toString();
    });
  });

  if (searchRow) {
    searchRow.querySelectorAll('input[data-col]').forEach(function (input) {
      input.addEventListener('keyup', function (e) {
        if (e.key === 'Enter') {
          const url = new URL(window.location.href);
          url.searchParams.set('f_' + input.getAttribute('data-col'), input.value);
          window.location.href = url.toString();
        }
      });
    });
  }
}
