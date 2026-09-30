/* ==========================================================================
   BEACON OSAS Portal — UI components
   Toasts, modals, cards, tables, pagers, rich-text editor and SVG chart.
   ========================================================================== */
OSAS.components = (function () {
  var STATUS_CLASS = {
    published: 'published', sent: 'sent', active: 'active', resolved: 'resolved',
    completed: 'completed', verified: 'verified', draft: 'draft', archived: 'archived',
    closed: 'closed', inactive: 'inactive', scheduled: 'scheduled', inprogress: 'progress',
    processing: 'processing', pending: 'pending', pendingreview: 'pending', open: 'open',
    high: 'high', medium: 'medium', low: 'low', urgent: 'urgent', critical: 'critical',
    warning: 'warning', info: 'info', upcoming: 'scheduled', cancelled: 'archived'
  };

  function statusKey(value) {
    return String(value || '').toLowerCase().replace(/[^a-z]/g, '');
  }

  function badge(value, label) {
    var key = STATUS_CLASS[statusKey(value)] || 'info';
    return '<span class="badge badge--' + key + '">' + OSAS.util.esc(label || value) + '</span>';
  }

  function priority(value) {
    var key = String(value || '').toLowerCase();
    return '<span class="priority"><span class="dot dot--' + key + '"></span>' + OSAS.util.esc(value) + '</span>';
  }

  function avatar(first, last, tone) {
    return '<span class="avatar' + (tone ? ' avatar--' + tone : '') + '">' +
      OSAS.util.esc(OSAS.fmt.initials(first, last)) + '</span>';
  }

  function avatarOf(name, tone, size) {
    return '<span class="avatar' + (tone ? ' avatar--' + tone : '') + (size === 'lg' ? ' avatar--lg' : '') + '">' +
      OSAS.util.esc(OSAS.fmt.initialsOf(name)) + '</span>';
  }

  function alert(tone, title, text, iconName) {
    var icon = iconName || (tone === 'success' ? 'checkCircle' : tone === 'danger' ? 'alert' :
      tone === 'warning' ? 'alert' : 'info');
    return '<div class="alert alert--' + tone + '">' +
      '<span class="alert__ico">' + OSAS.icons.icon(icon, 18) + '</span>' +
      '<div>' + (title ? '<div class="alert__title">' + title + '</div>' : '') +
      (text ? '<div class="alert__text">' + text + '</div>' : '') + '</div></div>';
  }

  function statCard(label, value, sub, options) {
    var opts = options || {};
    return '<div class="stat"' + (opts.id ? ' id="' + opts.id + '"' : '') + '>' +
      '<div class="stat__label">' + label + '</div>' +
      '<div class="stat__value">' + value + (opts.unit ? ' <small>' + opts.unit + '</small>' : '') + '</div>' +
      '<div class="stat__sub">' + (sub || '') + '</div></div>';
  }

  function delta(value, label) {
    var numeric = Number(value) || 0;
    var cls = numeric > 0 ? 'delta--up' : numeric < 0 ? 'delta--down' : 'delta--flat';
    var arrow = numeric > 0 ? 'arrowUp' : numeric < 0 ? 'arrowDown' : 'dot';
    return '<span class="delta ' + cls + '">' + OSAS.icons.icon(arrow, 11) + ' ' +
      (numeric > 0 ? '+' : '') + numeric.toFixed(1) + '% ' + (label || '') + '</span>';
  }

  function progressBar(value, tone) {
    var width = OSAS.util.clamp(Number(value) || 0, 0, 100);
    return '<div class="meter-row"><div class="bar"><div class="bar__fill' +
      (tone ? ' bar__fill--' + tone : '') + '" style="width:' + width + '%"></div></div>' +
      '<span class="meter-val">' + width + '%</span></div>';
  }

  function statStrip(items) {
    return '<div class="stat-strip">' + items.map(function (item) {
      return '<div><div class="stat-strip__label">' + item.label + '</div>' +
        '<div class="stat-strip__value">' + item.value + '</div></div>';
    }).join('') + '</div>';
  }

  function emptyState(message, actionHtml) {
    return '<div class="empty-state">' + OSAS.icons.icon('search', 22) +
      '<div class="u-mt-8">' + (message || 'No records found.') + '</div>' +
      (actionHtml ? '<div class="u-mt-14">' + actionHtml + '</div>' : '') + '</div>';
  }

  function logItem(entry) {
    var tone = entry.tone || 'brand';
    var toneClass = tone === 'brand' ? '' : ' log-item__ico--' + tone;
    return '<div class="log-item">' +
      '<span class="log-item__ico' + toneClass + '">' + OSAS.icons.icon(entry.icon || 'info', 13) + '</span>' +
      '<div class="log-item__body">' +
      '<div class="log-item__title">' + OSAS.util.esc(entry.title) + '</div>' +
      '<div class="log-item__text">' + OSAS.util.esc(entry.text) + '</div>' +
      '<div class="log-item__time">' + OSAS.icons.icon('clock', 11) + ' ' +
      OSAS.util.esc(entry.ago || OSAS.fmt.hoursAgo(entry.at)) + '</div></div></div>';
  }
  /* ------------------------------------------------------------- toasts */
  function toastRoot() {
    var root = document.getElementById('toast-root');
    if (!root) {
      root = document.createElement('div');
      root.id = 'toast-root';
      root.className = 'toast-root';
      document.body.appendChild(root);
    }
    return root;
  }

  function toast(type, title, text) {
    var root = toastRoot();
    var node = document.createElement('div');
    node.className = 'toast' + (type && type !== 'success' ? ' toast--' + type : '');
    node.innerHTML = '<div class="toast__title">' +
      OSAS.icons.icon(type === 'error' ? 'alert' : type === 'info' ? 'info' : 'checkCircle', 14) +
      OSAS.util.esc(title) + '</div>' +
      (text ? '<div class="toast__text">' + OSAS.util.esc(text) + '</div>' : '');
    root.appendChild(node);
    setTimeout(function () {
      node.style.transition = 'opacity .18s ease, transform .18s ease';
      node.style.opacity = '0';
      node.style.transform = 'translateY(8px)';
      setTimeout(function () { if (node.parentNode) { node.parentNode.removeChild(node); } }, 200);
    }, 4200);
  }

  function flashFromQuery() {
    var params = new URLSearchParams(window.location.search);
    var message = params.get('flash');
    if (message) { toast('success', decodeURIComponent(message)); }
  }

  /* -------------------------------------------------------------- modals */
  function modalRoot() {
    var root = document.getElementById('modal-root');
    if (!root) {
      root = document.createElement('div');
      root.id = 'modal-root';
      root.className = 'modal-root';
      document.body.appendChild(root);
    }
    return root;
  }

  function modal(options) {
    var root = modalRoot();
    var backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop';
    backdrop.innerHTML = '<div class="modal modal--' + (options.size || 'md') + '" role="dialog" aria-modal="true">' +
      '<div class="modal__head">' +
      '<div><h3 class="modal__title">' + (options.title || '') + '</h3>' +
      (options.subtitle ? '<div class="modal__sub">' + options.subtitle + '</div>' : '') + '</div>' +
      '<button type="button" class="modal__close" data-close aria-label="Close">' + OSAS.icons.icon('close', 14) + '</button>' +
      '</div>' +
      '<div class="modal__body">' + (options.body || '') + '</div>' +
      (options.footer === false ? '' : '<div class="modal__foot' + (options.footerClass || '') + '">' +
        (options.footer || '') + '</div>') +
      '</div>';
    root.appendChild(backdrop);
    document.body.style.overflow = 'hidden';

    function close() {
      if (backdrop.parentNode) { backdrop.parentNode.removeChild(backdrop); }
      if (!root.children.length) { document.body.style.overflow = ''; }
    }

    backdrop.addEventListener('click', function (event) {
      if (event.target === backdrop && options.dismissible !== false) { close(); return; }
      var closeBtn = event.target.closest ? event.target.closest('[data-close]') : null;
      if (closeBtn) { close(); }
    });
    backdrop.addEventListener('keydown', function onKey(event) {
      if (event.key === 'Escape' && options.dismissible !== false) { close(); }
    });
    if (typeof options.onMount === 'function') { options.onMount(backdrop, close); }
    return { element: backdrop, close: close };
  }

  function confirm(options) {
    return modal({
      size: options.size || 'sm',
      title: options.title || 'Please confirm',
      subtitle: options.subtitle || '',
      body: (options.alertTone ? alert(options.alertTone, options.alertTitle, options.alertText) : '') +
        '<p class="help-text">' + (options.message || 'Are you sure you want to continue?') + '</p>',
      footer: '<button type="button" class="btn btn--ghost" data-close>' + (options.cancelLabel || 'Cancel') + '</button>' +
        '<button type="button" class="btn ' + (options.confirmClass || 'btn--primary') + '" data-confirm>' +
        (options.confirmLabel || 'Confirm') + '</button>',
      onMount: function (el, close) {
        el.querySelector('[data-confirm]').addEventListener('click', function () {
          close();
          if (typeof options.onConfirm === 'function') { options.onConfirm(); }
        });
      }
    });
  }

  /* -------------------------------------------------------------- tables
     columns: [{ key, label, className, align, render(row) }]
     ---------------------------------------------------------------------- */
  function esc(value) { return OSAS.util.esc(value); }

  function dataTable(options) {
    var columns = options.columns || [];
    var rows = options.rows || [];
    var head = columns.map(function (col) {
      return '<th' + (col.align === 'right' ? ' class="u-right"' : '') + '>' + esc(col.label) + '</th>';
    }).join('');

    if (!rows.length) {
      return '<div class="table-wrap">' + (options.toolbar || '') +
        '<div class="table-scroll"><table class="data"><thead><tr>' + head +
        '</tr></thead></table></div>' +
        emptyState(options.empty || 'No records match the current filters.') + '</div>';
    }

    var body = rows.map(function (row) {
      var cells = columns.map(function (col) {
        var content = typeof col.render === 'function' ? col.render(row) : esc(row[col.key]);
        return '<td' + (col.align === 'right' ? ' class="u-right"' : '') + '>' + content + '</td>';
      }).join('');
      return '<tr data-id="' + esc(row.id) + '">' + cells + '</tr>';
    }).join('');

    return '<div class="table-wrap">' + (options.toolbar || '') +
      '<div class="table-scroll"><table class="data"><thead><tr>' + head + '</tr></thead>' +
      '<tbody>' + body + '</tbody></table></div>' +
      (options.footer || '') + '</div>';
  }

  /* ----------------------------------------------------------- pagination */
  function pager(info, label) {
    var buttons = '<button type="button" class="pager__btn pager__nav" data-page="' + (info.page - 1) + '"' +
      (info.page <= 1 ? ' disabled' : '') + ' aria-label="Previous page">' + OSAS.icons.icon('chevronLeft', 12) + '</button>';
    for (var i = 1; i <= info.pages; i++) {
      buttons += '<button type="button" class="pager__btn' + (i === info.page ? ' pager__btn--on' : '') +
        '" data-page="' + i + '">' + i + '</button>';
    }
    buttons += '<button type="button" class="pager__btn pager__nav" data-page="' + (info.page + 1) + '"' +
      (info.page >= info.pages ? ' disabled' : '') + ' aria-label="Next page">' + OSAS.icons.icon('chevronRight', 12) + '</button>';

    return '<div class="table-foot"><div>' + (label ||
      'Showing ' + info.from + '–' + info.to + ' of ' + OSAS.fmt.number(info.total) + ' records') +
      '</div><div class="pager">' + buttons + '</div></div>';
  }

  function bindPager(container, handler) {
    if (!container) { return; }
    container.addEventListener('click', function (event) {
      var button = event.target.closest ? event.target.closest('[data-page]') : null;
      if (!button || button.disabled) { return; }
      var page = parseInt(button.getAttribute('data-page'), 10);
      if (!isNaN(page)) { handler(page); }
    });
  }

  /* ------------------------------------------------------------ line chart
     Renders the module-1 "Weekly Portal Traffic & Module Engagement" chart
     from OSAS.store.traffic() with a hover tooltip.
     ---------------------------------------------------------------------- */
  function chart(hostId) {
    var host = document.getElementById(hostId);
    if (!host) { return; }
    var data = OSAS.store.traffic();
    var W = 720, H = 272, PAD = { top: 16, right: 26, bottom: 30, left: 40 };
    var innerW = W - PAD.left - PAD.right;
    var innerH = H - PAD.top - PAD.bottom;
    var max = data.axisMax || 600;
    var step = data.axisStep || 100;
    var labels = data.labels;
    var count = labels.length;

    function x(i) { return PAD.left + (innerW / (count - 1)) * i; }
    function y(v) { return PAD.top + innerH - (innerH * (v / max)); }

    function path(values) {
      return values.map(function (v, i) {
        return (i ? 'L' : 'M') + x(i).toFixed(1) + ' ' + y(v).toFixed(1);
      }).join(' ');
    }
    function line(values, dashed) {
      return '<path d="' + path(values) + '" fill="none" stroke="' + (dashed ? '#1a67d8' : '#7a1420') +
        '" stroke-width="2.2" stroke-linejoin="round" stroke-linecap="round"' +
        (dashed ? ' stroke-dasharray="6 5"' : '') + '/>';
    }
    function area(values) {
      return '<path d="' + path(values) + ' L' + x(count - 1).toFixed(1) + ' ' + (PAD.top + innerH) +
        ' L' + x(0).toFixed(1) + ' ' + (PAD.top + innerH) + ' Z" fill="rgba(122,20,32,.07)" stroke="none"/>';
    }
    function points(values, color) {
      return values.map(function (v, i) {
        return '<circle cx="' + x(i).toFixed(1) + '" cy="' + y(v).toFixed(1) +
          '" r="3.2" fill="#fff" stroke="' + color + '" stroke-width="2"/>';
      }).join('');
    }

    var grid = '';
    for (var value = 0; value <= max; value += step) {
      grid += '<line class="grid-line" x1="' + PAD.left + '" x2="' + (W - PAD.right) + '" y1="' +
        y(value).toFixed(1) + '" y2="' + y(value).toFixed(1) + '"/>' +
        '<text x="' + (PAD.left - 10) + '" y="' + (y(value) + 3.5).toFixed(1) + '" text-anchor="end">' +
        OSAS.fmt.number(value) + '</text>';
    }
    var axis = labels.map(function (label, i) {
      return '<text x="' + x(i).toFixed(1) + '" y="' + (H - 10) + '" text-anchor="middle">' + esc(label) + '</text>';
    }).join('');
    var slot = innerW / (count - 1);
    var hotspots = labels.map(function (label, i) {
      return '<rect x="' + (x(i) - slot / 2).toFixed(1) + '" y="' + PAD.top + '" width="' + slot.toFixed(1) +
        '" height="' + innerH + '" fill="transparent" data-day="' + i + '" style="cursor:crosshair"/>';
    }).join('');

    host.innerHTML = '<svg class="chart-svg" viewBox="0 0 ' + W + ' ' + H + '" preserveAspectRatio="none" role="img" ' +
      'aria-label="Weekly portal traffic and module engagement">' + grid + axis +
      area(data.current.logins) + area(data.current.completions) +
      line(data.previous.logins, true) + line(data.previous.completions, true) +
      line(data.current.logins, false) + line(data.current.completions, false) +
      points(data.current.logins, '#7a1420') + points(data.current.completions, '#1a67d8') +
      hotspots + '</svg><div class="chart-tip" id="' + hostId + '-tip"></div>';

    var tip = document.getElementById(hostId + '-tip');
    OSAS.util.qsa('rect[data-day]', host).forEach(function (rect) {
      rect.addEventListener('mouseenter', function () {
        var i = parseInt(rect.getAttribute('data-day'), 10);
        tip.innerHTML = '<div class="chart-tip__date">' + esc(labels[i]) + ' — ' + esc(data.current.label) + '</div>' +
          '<div class="chart-tip__row"><span class="chart-tip__key">Admin logins</span><b>' +
          OSAS.fmt.number(data.current.logins[i]) + '</b></div>' +
          '<div class="chart-tip__row"><span class="chart-tip__key">Module completions</span><b>' +
          OSAS.fmt.number(data.current.completions[i]) + '</b></div>' +
          '<div class="chart-tip__row"><span class="chart-tip__key">' + esc(data.previous.label) + ' logins</span><b>' +
          OSAS.fmt.number(data.previous.logins[i]) + '</b></div>';
        var box = host.getBoundingClientRect();
        tip.style.left = ((x(i) / W) * box.width) + 'px';
        tip.style.top = ((y(data.current.logins[i]) / H) * box.height) + 'px';
        tip.classList.add('chart-tip--on');
      });
      rect.addEventListener('mouseleave', function () { tip.classList.remove('chart-tip--on'); });
    });
  }

  function legend(items) {
    return '<div class="chart-legend">' + items.map(function (item) {
      return '<span class="legend-item"><span class="legend-swatch' +
        (item.dashed ? ' legend-swatch--dash' : '') + '"></span>' + esc(item.label) + '</span>';
    }).join('') + '</div>';
  }

  return {
    STATUS_CLASS: STATUS_CLASS, badge: badge, priority: priority, avatar: avatar, avatarOf: avatarOf,
    alert: alert, statCard: statCard, delta: delta, progressBar: progressBar, statStrip: statStrip,
    emptyState: emptyState, logItem: logItem, toast: toast, toastRoot: toastRoot,
    flashFromQuery: flashFromQuery, modal: modal, confirm: confirm,
    dataTable: dataTable, pager: pager, bindPager: bindPager,
    richText: richText, initRichText: initRichText, richTextValue: richTextValue,
    chart: chart, legend: legend
  };
})();
  /* --------------------------------------------------- rich text editor */
  function richText(options) {
    var id = options.id || OSAS.util.uid('rt');
    var tools = [
      { cmd: 'bold', label: 'B', title: 'Bold' },
      { cmd: 'italic', label: 'I', title: 'Italic' },
      { cmd: 'underline', label: 'U', title: 'Underline' },
      { sep: true },
      { block: 'H1', label: 'H1', title: 'Heading 1' },
      { block: 'H2', label: 'H2', title: 'Heading 2' },
      { sep: true },
      { cmd: 'insertUnorderedList', label: '• List', title: 'Bulleted list' },
      { cmd: 'insertOrderedList', label: '1. List', title: 'Numbered list' },
      { sep: true },
      { cmd: 'createLink', label: 'Link', title: 'Insert link' },
      { cmd: 'insertImage', label: 'Image', title: 'Insert image' }
    ];
    var bar = tools.map(function (tool) {
      if (tool.sep) { return '<span class="rt__sep"></span>'; }
      if (tool.block) {
        return '<button type="button" class="rt__btn" data-block="' + tool.block +
          '" title="' + tool.title + '">' + tool.label + '</button>';
      }
      return '<button type="button" class="rt__btn" data-cmd="' + tool.cmd +
        '" title="' + tool.title + '">' + tool.label + '</button>';
    }).join('');

    var html = options.value || '';
    return '<div class="rt">' +
      '<div class="rt__bar">' + bar + '</div>' +
      '<div class="rt__area" id="' + id + '" contenteditable="true" data-empty="' +
      (!OSAS.util.stripTags(html)) + '" data-placeholder="' +
      esc(options.placeholder || 'Write the content here…') + '">' + html + '</div></div>';
  }

  function initRichText(scope) {
    OSAS.util.qsa('.rt', scope || document).forEach(function (widget) {
      if (widget.getAttribute('data-bound') === '1') { return; }
      widget.setAttribute('data-bound', '1');
      var area = widget.querySelector('.rt__area');
      function syncEmpty() {
        area.setAttribute('data-empty', String(!OSAS.util.stripTags(area.innerHTML)));
      }
      area.addEventListener('input', syncEmpty);
      area.addEventListener('blur', syncEmpty);
      widget.addEventListener('click', function (event) {
        var button = event.target.closest ? event.target.closest('button') : null;
        if (!button) { return; }
        event.preventDefault();
        area.focus();
        if (button.hasAttribute('data-block')) {
          document.execCommand('formatBlock', false, button.getAttribute('data-block'));
        } else {
          document.execCommand(button.getAttribute('data-cmd'), false, null);
        }
        syncEmpty();
      });
    });
  }

  function richTextValue(id) {
    var area = document.getElementById(id);
    return area ? area.innerHTML : '';
  }
/* @@COMP-END@@ */