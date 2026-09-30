/* ==========================================================================
   BEACON OSAS Portal — list page kit
   Shared controller for the module pages that render a filtered, paginated
   directory table (regulations, orientation, events, broadcasts, users,
   support, reports). Keeps search / filter / pagination behaviour identical
   across modules and binds every handler once, outside the render cycle.
   ========================================================================== */
OSAS.pagekit = (function () {
  function listPage(config) {
    var C = OSAS.components;
    var U = OSAS.util;
    var content = config.content;
    var state = {};
    Object.keys(config.defaultState || {}).forEach(function (key) {
      state[key] = config.defaultState[key];
    });
    if (state.page === undefined) { state.page = 1; }

    function sourceRows() {
      return typeof config.source === 'function' ? config.source() : OSAS.store.all(config.resource);
    }

    function filteredRows() {
      var rows = sourceRows().slice();
      if (typeof config.filter === 'function') { rows = config.filter(rows, state); }
      if (config.sort) { rows = U.sortBy(rows, config.sort.field, config.sort.dir); }
      return rows;
    }

    function filterValues(filter) {
      return typeof filter.values === 'function' ? filter.values() : (filter.values || []);
    }

    function filterBar() {
      var columns = config.filters.length <= 2 ? ' filter-bar--2' :
        (config.filters.length === 3 ? ' filter-bar--3' : '');
      return '<div class="filter-bar' + columns + '">' + config.filters.map(function (filter) {
        var value = state[filter.key] === undefined ? '' : state[filter.key];
        if (filter.type === 'search') {
          return '<div class="field"><label for="' + filter.id + '">' + filter.label + '</label>' +
            '<span class="input-group"><span class="input-group__ico">' + OSAS.icons.icon('search', 14) + '</span>' +
            '<input class="input" id="' + filter.id + '" placeholder="' + U.esc(filter.placeholder || '') +
            '" value="' + U.esc(value) + '"></span></div>';
        }
        var options = ['<option value="All">All</option>'].concat(filterValues(filter).map(function (option) {
          return '<option value="' + U.esc(option) + '"' + (option === value ? ' selected' : '') + '>' +
            U.esc(option) + '</option>';
        })).join('');
        return '<div class="field"><label for="' + filter.id + '">' + filter.label + '</label>' +
          '<select class="input" id="' + filter.id + '">' + options + '</select></div>';
      }).join('') + '</div>';
    }

    function render() {
      var rows = filteredRows();
      var page = U.paginate(rows, state.page, config.pageSize || OSAS.CONFIG.pageSize);
      var html = '';
      if (config.header !== false) {
        html += OSAS.shell.pageHead({
          title: config.title, subtitle: config.subtitle, actions: config.actions
        });
      }
      if (typeof config.stats === 'function') { html += config.stats(state, rows); }
      if (config.filters && config.filters.length) { html += filterBar(); }
      if (typeof config.beforeTable === 'function') { html += config.beforeTable(state, rows); }
      html += C.dataTable({
        columns: config.columns(state, rows),
        rows: page.items,
        toolbar: typeof config.toolbar === 'function' ? config.toolbar(state, rows, page) : (config.toolbar || ''),
        footer: config.footer === false ? '' :
          C.pager(page, config.footerLabel ? config.footerLabel(page, rows) : undefined),
        empty: config.empty
      });
      if (typeof config.afterTable === 'function') { html += config.afterTable(state, rows, page); }
      content.innerHTML = html;
      if (typeof config.afterRender === 'function') { config.afterRender(state, rows, page); }
      return page;
    }

    var api = {
      state: state,
      render: render,
      rows: filteredRows,
      reset: function (patch) {
        Object.keys(patch).forEach(function (key) { state[key] = patch[key]; });
        state.page = 1;
        render();
      }
    };

    /* ------- delegated handlers, bound once and kept across re-renders ---- */
    if (config.filters && config.filters.length) {
      content.addEventListener('input', function (event) {
        var filter = config.filters.filter(function (f) {
          return f.type === 'search' && f.id === event.target.id;
        })[0];
        if (!filter) { return; }
        state[filter.key] = event.target.value;
        state.page = 1;
        render();
        var box = document.getElementById(filter.id);
        if (box) {
          box.focus();
          box.setSelectionRange(box.value.length, box.value.length);
        }
      });
      content.addEventListener('change', function (event) {
        var filter = config.filters.filter(function (f) { return f.id === event.target.id; })[0];
        if (!filter || filter.type === 'search') { return; }
        state[filter.key] = event.target.value;
        state.page = 1;
        render();
      });
    }

    C.bindPager(content, function (page) {
      state.page = page;
      render();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    content.addEventListener('click', function (event) {
      var trigger = event.target.closest ? event.target.closest('[data-action]') : null;
      if (!trigger) { return; }
      if (typeof config.onAction === 'function') {
        config.onAction(trigger.getAttribute('data-action'), trigger.getAttribute('data-id'), api, trigger);
      }
    });

    render();
    return api;
  }

  return { listPage: listPage };
})();
