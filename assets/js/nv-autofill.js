/**
 * NEO V-TRACK shared autosuggest / autofill (do-report style).
 *
 * Two roles, auto-wired on load:
 *  1) Vehicle register/update forms: any of #platenum/#name/#idnumber/#phone gets a
 *     suggestion dropdown; picking a match autofills the whole form.
 *  2) Search inputs marked with data-nv-suggest: picking a match fills the input and
 *     submits its form. e.g. <input name="search" data-nv-suggest="any" data-nv-submit
 *     data-nv-field="plate">
 *
 * Suggestions come from /api/vehicle_suggest_api.php?q=&by=.
 */
(function () {
  'use strict';

  var ENDPOINT = '/api/vehicle_suggest_api.php';

  function escapeHtml(s) {
    return s == null ? '' : String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function ensureStyles() {
    if (document.getElementById('nv-suggest-style')) return;
    var st = document.createElement('style');
    st.id = 'nv-suggest-style';
    st.textContent =
      '.nv-suggest-box{position:absolute;left:0;right:0;top:100%;z-index:60;background:#fff;' +
      'border:1px solid var(--border,#d9d9e3);border-top:0;border-radius:0 0 8px 8px;max-height:280px;' +
      'overflow-y:auto;box-shadow:0 8px 24px rgba(0,0,0,.12);display:none;}' +
      '.nv-suggest-item{padding:8px 12px;cursor:pointer;border-bottom:1px solid #f0f0f3;font-size:14px;}' +
      '.nv-suggest-item:last-child{border-bottom:0;}' +
      '.nv-suggest-item:hover,.nv-suggest-item.active{background:var(--surface-tint,#f5f3ff);}' +
      '.nv-suggest-item .muted{color:#777;font-size:12px;}';
    document.head.appendChild(st);
  }

  function setSelectValue(sel, value) {
    if (!sel || !value) return;
    var v = String(value).toUpperCase();
    for (var i = 0; i < sel.options.length; i++) {
      if (sel.options[i].value.toUpperCase() === v) { sel.value = sel.options[i].value; return; }
    }
  }

  // Attach a suggestion dropdown to `input`. `by` selects the search column.
  // `onPick(record)` is called when a suggestion is chosen.
  function attach(input, by, onPick) {
    ensureStyles();
    var wrap = input.parentNode;
    if (wrap && getComputedStyle(wrap).position === 'static') wrap.style.position = 'relative';

    var box = document.createElement('div');
    box.className = 'nv-suggest-box';
    (wrap || input).appendChild(box);

    var timer = null, items = [], active = -1;

    function close() { box.style.display = 'none'; active = -1; }
    function render() {
      if (!items.length) { close(); return; }
      box.innerHTML = items.map(function (m, i) {
        return '<div class="nv-suggest-item" data-i="' + i + '">' +
          '<strong>' + escapeHtml(m.plate) + '</strong> &mdash; ' + escapeHtml(m.name || '(no name)') +
          ' <span class="muted">' + escapeHtml(m.status || '') +
          (m.phone ? ' · ' + escapeHtml(m.phone) : '') + '</span></div>';
      }).join('');
      box.style.display = 'block';
    }
    function choose(i) {
      var m = items[i];
      if (!m) return;
      onPick(m);
      close();
    }

    box.addEventListener('mousedown', function (e) {
      var el = e.target.closest('.nv-suggest-item');
      if (el) { e.preventDefault(); choose(parseInt(el.dataset.i, 10)); }
    });

    input.setAttribute('autocomplete', 'off');
    input.addEventListener('input', function () {
      var q = input.value.trim();
      clearTimeout(timer);
      if (q.length < 2) { close(); return; }
      timer = setTimeout(function () {
        fetch(ENDPOINT + '?by=' + encodeURIComponent(by) + '&q=' + encodeURIComponent(q))
          .then(function (r) { return r.json(); })
          .then(function (data) { items = Array.isArray(data) ? data : []; active = -1; render(); })
          .catch(function () { close(); });
      }, 200);
    });
    input.addEventListener('keydown', function (e) {
      if (box.style.display !== 'block') return;
      if (e.key === 'ArrowDown') { active = Math.min(active + 1, items.length - 1); e.preventDefault(); }
      else if (e.key === 'ArrowUp') { active = Math.max(active - 1, 0); e.preventDefault(); }
      else if (e.key === 'Enter') { if (active >= 0) { e.preventDefault(); choose(active); } return; }
      else { return; }
      Array.prototype.forEach.call(box.children, function (c, i) { c.classList.toggle('active', i === active); });
    });
    input.addEventListener('blur', function () { setTimeout(close, 150); });
  }

  function wireVehicleForm() {
    var plate = document.getElementById('platenum');
    if (!plate || !(document.getElementById('name') || document.getElementById('phone'))) return;

    function fill(rec) {
      var f = {
        platenum: rec.plate, name: rec.name, idnumber: rec.idnumber, phone: rec.phone
      };
      Object.keys(f).forEach(function (id) {
        var el = document.getElementById(id);
        if (el && f[id] != null) el.value = f[id];
      });
      setSelectValue(document.getElementById('type'), rec.type);
      // Do not override the category (#status) — the page is scoped to its own category.
    }

    [['platenum', 'plate'], ['name', 'name'], ['idnumber', 'idnumber'], ['phone', 'phone']].forEach(function (p) {
      var el = document.getElementById(p[0]);
      if (el) attach(el, p[1], fill);
    });
  }

  function wireMarkedInputs() {
    document.querySelectorAll('input[data-nv-suggest]').forEach(function (input) {
      var by = input.getAttribute('data-nv-suggest') || 'any';
      var field = input.getAttribute('data-nv-field') || 'plate';
      var submit = input.hasAttribute('data-nv-submit');
      attach(input, by, function (rec) {
        input.value = rec[field] != null ? rec[field] : rec.plate;
        if (submit && input.form) input.form.submit();
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    wireMarkedInputs();
    wireVehicleForm();
  });
})();
