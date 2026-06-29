/*!
 * NEO V-TRACK — app-only card-stack tables.
 *
 * In the native WebView app (root element carries `nv-app`, set in
 * includes/header.php) every data table is rendered as a vertical stack of cards
 * — one card per row — with each column shown as a stacked label/value pair
 * (see the html.nv-app rules in assets/css/responsive.css).
 *
 * The CSS shows each cell's column name via `td::before { content: attr(data-label) }`,
 * so this script copies the matching <thead> header text onto every <td> as
 * `data-label`. DataTables rebuilds the body rows on paging/search/sort, so we
 * re-apply the labels after each `draw.dt`. Runs only in the app; on the browser
 * web it is a no-op (the class is absent).
 */
(function () {
  var root = document.documentElement;
  if (!root || !root.classList || !root.classList.contains('nv-app')) return; // app only

  function labelTable(table) {
    var thead = table.tHead;
    if (!thead || !thead.rows.length) return;
    var head = thead.rows[thead.rows.length - 1]; // bottom header row = the column labels
    var labels = [];
    for (var i = 0; i < head.cells.length; i++) {
      labels[i] = (head.cells[i].textContent || '').replace(/\s+/g, ' ').trim();
    }
    for (var b = 0; b < table.tBodies.length; b++) {
      var rows = table.tBodies[b].rows;
      for (var r = 0; r < rows.length; r++) {
        var cells = rows[r].cells;
        // Full-width rows (e.g. the DataTables "no records" row) carry no label.
        if (cells.length === 1 && cells[0].hasAttribute('colspan')) continue;
        for (var c = 0; c < cells.length; c++) {
          var label = labels[c];
          if (label) cells[c].setAttribute('data-label', label);
          else cells[c].removeAttribute('data-label'); // checkbox / action columns
        }
      }
    }
  }

  function labelAll() {
    var tables = document.querySelectorAll('table.table, table.assignment-table');
    for (var i = 0; i < tables.length; i++) labelTable(tables[i]);
  }

  function run() {
    labelAll();
    // Re-label after DataTables rebuilds the rows (paging / search / sort).
    if (window.jQuery) { window.jQuery(document).on('draw.dt', labelAll); }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
