<script>
    (function () {
        var toolbar = document.querySelector('[data-grid-toolbar]');
        if (!toolbar) return;

        var filterToggle = toolbar.querySelector('[data-filter-toggle]');
        var filterPanel = document.querySelector('[data-filter-panel]');
        var columnsToggle = toolbar.querySelector('[data-columns-toggle]');
        var columnsMenu = toolbar.querySelector('[data-columns-menu]');
        var columnsList = toolbar.querySelector('[data-columns-list]');
        var columnsCount = toolbar.querySelector('[data-columns-count]');
        var columnsReset = toolbar.querySelector('[data-columns-reset]');
        var columnsClose = toolbar.querySelector('[data-columns-close]');
        var table = document.querySelector('table[data-column-storage]');
        var storageKey = table ? table.getAttribute('data-column-storage') : null;

        function setOpen(el, open) {
            if (!el) return;
            el.classList.toggle('is-open', open);
            if (open) {
                el.removeAttribute('hidden');
            } else {
                el.setAttribute('hidden', 'hidden');
            }
        }

        if (filterToggle && filterPanel) {
            filterToggle.addEventListener('click', function () {
                var open = !filterPanel.classList.contains('is-open');
                setOpen(filterPanel, open);
                filterToggle.classList.toggle('is-active', open || !!filterToggle.querySelector('.grid-filter-badge'));
                setOpen(columnsMenu, false);
            });
        }

        var filterCancel = document.querySelector('[data-filter-cancel]');
        if (filterCancel && filterPanel && filterToggle) {
            filterCancel.addEventListener('click', function () {
                setOpen(filterPanel, false);
                filterToggle.classList.toggle('is-active', !!filterToggle.querySelector('.grid-filter-badge'));
            });
        }

        function readVisible() {
            if (!storageKey) return null;
            try {
                var raw = localStorage.getItem(storageKey);
                var parsed = raw ? JSON.parse(raw) : null;
                return Array.isArray(parsed) ? parsed : null;
            } catch (e) {
                return null;
            }
        }

        function writeVisible(cols) {
            if (!storageKey) return;
            localStorage.setItem(storageKey, JSON.stringify(cols));
        }

        function allColumns() {
            if (!table) return [];
            return Array.prototype.map.call(table.querySelectorAll('thead th[data-col]'), function (th) {
                return {
                    key: th.getAttribute('data-col'),
                    label: (th.textContent || '').trim(),
                    locked: th.getAttribute('data-col-locked') === '1'
                };
            });
        }

        function applyColumns(visibleKeys) {
            if (!table) return;
            var cols = allColumns();
            var visible = visibleKeys || cols.map(function (c) { return c.key; });

            cols.forEach(function (col) {
                var show = visible.indexOf(col.key) !== -1 || col.locked;
                table.querySelectorAll('[data-col="' + col.key + '"]').forEach(function (cell) {
                    cell.style.display = show ? '' : 'none';
                });
            });

            if (columnsCount) {
                var shown = cols.filter(function (col) {
                    return visible.indexOf(col.key) !== -1 || col.locked;
                }).length;
                columnsCount.textContent = shown + ' out of ' + cols.length + ' visible';
            }
        }

        function renderColumnsMenu() {
            if (!columnsList || !table) return;
            var cols = allColumns();
            var saved = readVisible();
            var visible = saved && saved.length ? saved : cols.map(function (c) { return c.key; });

            columnsList.innerHTML = '';
            cols.forEach(function (col) {
                var label = document.createElement('label');
                var input = document.createElement('input');
                input.type = 'checkbox';
                input.value = col.key;
                input.checked = visible.indexOf(col.key) !== -1 || col.locked;
                input.disabled = col.locked;
                input.addEventListener('change', function () {
                    var next = Array.prototype.filter.call(columnsList.querySelectorAll('input'), function (el) {
                        return el.checked;
                    }).map(function (el) { return el.value; });

                    if (next.length === 0) {
                        input.checked = true;
                        return;
                    }

                    writeVisible(next);
                    applyColumns(next);
                });
                label.appendChild(input);
                label.appendChild(document.createTextNode(' ' + col.label));
                columnsList.appendChild(label);
            });

            applyColumns(visible);
        }

        if (columnsToggle && columnsMenu) {
            columnsToggle.addEventListener('click', function () {
                var open = !columnsMenu.classList.contains('is-open');
                setOpen(columnsMenu, open);
                columnsToggle.classList.toggle('is-active', open);
            });
        }

        if (columnsClose && columnsMenu) {
            columnsClose.addEventListener('click', function () {
                setOpen(columnsMenu, false);
                if (columnsToggle) columnsToggle.classList.remove('is-active');
            });
        }

        if (columnsReset) {
            columnsReset.addEventListener('click', function () {
                if (storageKey) localStorage.removeItem(storageKey);
                renderColumnsMenu();
            });
        }

        document.addEventListener('click', function (event) {
            if (!columnsMenu || !columnsMenu.classList.contains('is-open')) return;
            if (toolbar.contains(event.target)) return;
            setOpen(columnsMenu, false);
            if (columnsToggle) columnsToggle.classList.remove('is-active');
        });

        renderColumnsMenu();
    })();
</script>
