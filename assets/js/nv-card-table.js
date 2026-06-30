/*!
 * NEO V-TRACK — card-stack tables at mobile dimensions.
 *
 * Data tables render as a vertical stack of cards (one card per row, each column
 * a stacked label/value pair) whenever the root element carries `nv-cards`. This
 * script toggles that class on the root when EITHER:
 *   - it's the native WebView app  (html.nv-app, set in includes/header.php), or
 *   - the viewport is mobile-width  (max-width: 639px)
 * so the app always gets cards and the browser web gets them at phone widths,
 * while desktop keeps real tables. (Card styling: html.nv-cards rules in
 * assets/css/responsive.css.)
 *
 * The CSS shows each cell's column name via `td::before { content: attr(data-label) }`,
 * so this script also copies the matching <thead> header text onto every <td> as
 * `data-label`. DataTables rebuilds the body rows on paging/search/sort, so we
 * re-apply the labels after each `draw.dt`. Without JS the tables fall back to the
 * horizontal-scroll treatment.
 */
(function () {
  var root = document.documentElement;
  if (!root || !root.classList) return;

  var isApp = root.classList.contains('nv-app');
  var mq = window.matchMedia ? window.matchMedia('(max-width: 639px)') : null;

  function syncCards() {
    root.classList.toggle('nv-cards', !!(isApp || (mq && mq.matches)));
  }

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
    syncCards();
    labelAll();
    // Re-evaluate the card mode when the viewport crosses the breakpoint (resize / rotate).
    if (mq) {
      if (mq.addEventListener) mq.addEventListener('change', syncCards);
      else if (mq.addListener) mq.addListener(syncCards); // older WebKit
    }
    // Re-label after DataTables rebuilds the rows (paging / search / sort).
    if (window.jQuery) { window.jQuery(document).on('draw.dt', labelAll); }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
