/**
 * Ponto Mega Downloads - Live Faceted Search, Detail Tabs, and One-Click Copy
 */
(function () {
  const getText = (key, fallback) => {
    if (window.Joomla && window.Joomla.Text && typeof window.Joomla.Text._ === 'function') {
      const val = window.Joomla.Text._(key);
      if (val && val !== key) {
        return val;
      }
    }
    return fallback;
  };

  function bindCopyButtons(root) {
    (root || document).querySelectorAll('[data-pmd-copy]').forEach((btn) => {
      if (btn.dataset.pmdCopyBound === '1') {
        return;
      }
      btn.dataset.pmdCopyBound = '1';
      btn.addEventListener('click', async () => {
        const value = btn.getAttribute('data-pmd-copy') || '';
        if (!value) {
          return;
        }
        try {
          await navigator.clipboard.writeText(value);
          const span = btn.querySelector('span');
          if (span) {
            const orig = span.textContent;
            span.textContent = getText('COM_PMDOWNLOADS_COPIED', 'Copiado!');
            setTimeout(() => {
              span.textContent = orig;
            }, 1800);
          }
        } catch (err) {
          // Fallback silent
        }
      });
    });
  }

  function initDetailTabs() {
    document.querySelectorAll('[data-pmd-tabs]').forEach((wrap) => {
      const buttons = wrap.querySelectorAll('[data-pmd-tab]');
      const panels = wrap.querySelectorAll('[data-pmd-panel]');
      buttons.forEach((btn) => {
        btn.addEventListener('click', () => {
          const target = btn.getAttribute('data-pmd-tab');
          buttons.forEach((b) => {
            const active = b.getAttribute('data-pmd-tab') === target;
            b.classList.toggle('is-active', active);
            b.setAttribute('aria-selected', active ? 'true' : 'false');
          });
          panels.forEach((p) => {
            p.classList.toggle('is-active', p.getAttribute('data-pmd-panel') === target);
          });
        });
      });
    });
  }

  function initCatalog() {
    const root = document.querySelector('[data-pmd-catalog]');
    if (!root) {
      return;
    }

    const ajaxUrl = root.getAttribute('data-ajax-url') || '';
    const form = root.querySelector('[data-pmd-filter-form]');
    const searchInput = root.querySelector('[data-pmd-search]');
    const statusInput = root.querySelector('[data-pmd-status-input]');
    const statusBtns = root.querySelectorAll('[data-pmd-status]');
    const quickCats = root.querySelectorAll('[data-pmd-quick-cat]');
    const selects = root.querySelectorAll('[data-pmd-filter]');
    const resetBtn = root.querySelector('[data-pmd-reset]');
    const resultsRegion = root.querySelector('[data-pmd-results]');
    const paginationRegion = root.querySelector('[data-pmd-pagination]');
    const summaryEl = root.querySelector('[data-pmd-summary]');

    if (!form || !ajaxUrl || !resultsRegion) {
      return;
    }

    let timer = null;
    let controller = null;

    const syncQuickUI = () => {
      const catSel = form.querySelector('#pmd-filter-catid');
      const curCat = catSel ? catSel.value : '0';
      const curStatus = statusInput ? statusInput.value : '';

      quickCats.forEach((btn) => {
        btn.classList.toggle('is-active', btn.getAttribute('data-pmd-quick-cat') === curCat);
      });
      statusBtns.forEach((btn) => {
        btn.classList.toggle('is-active', btn.getAttribute('data-pmd-status') === curStatus);
      });

      const hasFilters = Boolean(
        (searchInput && searchInput.value.trim() !== '') ||
        (curCat && curCat !== '0') ||
        curStatus !== '' ||
        (form.querySelector('#pmd-filter-version')?.value || '') !== '' ||
        (form.querySelector('#pmd-filter-type')?.value || '') !== ''
      );
      if (resetBtn) {
        resetBtn.classList.toggle('d-none', !hasFilters);
      }
    };

    const runQuery = async (pushHistory = true) => {
      syncQuickUI();

      const params = new URLSearchParams();
      const fd = new FormData(form);
      for (const [k, v] of fd.entries()) {
        const val = String(v).trim();
        if (val !== '' && !(k === 'catid' && val === '0')) {
          params.set(k, val);
        }
      }

      if (controller) {
        controller.abort();
      }
      controller = new AbortController();

      resultsRegion.classList.add('is-loading');

      try {
        const sep = ajaxUrl.includes('?') ? '&' : '?';
        const url = params.toString() ? `${ajaxUrl}${sep}${params.toString()}` : ajaxUrl;
        const resp = await fetch(url, {
          headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
          signal: controller.signal,
        });
        const json = await resp.json();
        if (json && json.success && json.data) {
          resultsRegion.innerHTML = json.data.html || '';
          if (paginationRegion) {
            paginationRegion.innerHTML = json.data.pagination || '';
          }
          if (summaryEl && json.data.summary) {
            summaryEl.textContent = json.data.summary;
          }
          bindCopyButtons(resultsRegion);
          if (pushHistory) {
            const base = form.getAttribute('action') || window.location.pathname;
            const nextUrl = params.toString() ? `${base}?${params.toString()}` : base;
            window.history.replaceState({}, '', nextUrl);
          }
        }
      } catch (err) {
        if (err.name !== 'AbortError') {
          form.submit();
        }
      } finally {
        resultsRegion.classList.remove('is-loading');
      }
    };

    form.addEventListener('submit', (e) => {
      e.preventDefault();
      runQuery(true);
    });

    if (searchInput) {
      searchInput.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => runQuery(true), 240);
      });
    }

    selects.forEach((sel) => sel.addEventListener('change', () => runQuery(true)));

    statusBtns.forEach((btn) => {
      btn.addEventListener('click', () => {
        if (statusInput) {
          statusInput.value = btn.getAttribute('data-pmd-status') || '';
        }
        runQuery(true);
      });
    });

    quickCats.forEach((btn) => {
      btn.addEventListener('click', () => {
        const catSel = form.querySelector('#pmd-filter-catid');
        if (catSel) {
          catSel.value = btn.getAttribute('data-pmd-quick-cat') || '0';
        }
        runQuery(true);
      });
    });

    if (resetBtn) {
      resetBtn.addEventListener('click', () => {
        if (searchInput) searchInput.value = '';
        if (statusInput) statusInput.value = '';
        selects.forEach((s) => {
          if (s.name === 'catid') s.value = '0';
          else if (s.name === 'sort') s.value = 'featured';
          else s.value = '';
        });
        runQuery(true);
      });
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    bindCopyButtons(document);
    initDetailTabs();
    initCatalog();
  });
})();
