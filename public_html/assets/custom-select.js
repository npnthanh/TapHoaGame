(() => {
    const instances = new WeakMap();
    let sequence = 0;
    let current = null;
    const normalize = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/đ/g, 'd');
    const icon = (className, path) => {
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', '0 0 24 24');
        svg.setAttribute('fill', 'none');
        svg.setAttribute('stroke', 'currentColor');
        svg.setAttribute('stroke-width', '1.7');
        svg.setAttribute('stroke-linecap', 'round');
        svg.setAttribute('stroke-linejoin', 'round');
        svg.setAttribute('aria-hidden', 'true');
        svg.classList.add(className);
        const shape = document.createElementNS(svg.namespaceURI, 'path');
        shape.setAttribute('d', path);
        svg.append(shape);
        return svg;
    };

    class CustomSelect {
        constructor(select) {
            instances.set(select, this);
            this.select = select;
            this.id = `custom-select-${++sequence}`;
            this.label = select.getAttribute('aria-label') || [...select.labels || []].map(label => {
                const copy = label.cloneNode(true);
                copy.querySelectorAll('select, input, textarea, button, .custom-select').forEach(el => el.remove());
                return copy.textContent.trim();
            }).join(' ').replace(/\s+/g, ' ') || 'Chọn giá trị';
            this.wrapper = document.createElement('div');
            this.wrapper.className = 'custom-select';
            this.trigger = document.createElement('button');
            this.trigger.type = 'button';
            this.trigger.className = 'custom-select-trigger';
            this.trigger.setAttribute('role', 'combobox');
            this.trigger.setAttribute('aria-label', this.label);
            this.trigger.setAttribute('aria-haspopup', 'listbox');
            this.trigger.setAttribute('aria-expanded', 'false');
            this.trigger.setAttribute('aria-controls', `${this.id}-list`);
            this.value = document.createElement('span');
            this.value.className = 'custom-select-value';
            this.trigger.append(this.value, icon('custom-select-chevron', 'm6 9 6 6 6-6'));
            this.error = document.createElement('span');
            this.error.className = 'custom-select-error';
            this.error.id = `${this.id}-error`;
            this.error.hidden = true;
            this.error.setAttribute('aria-live', 'polite');
            this.baseDescription = select.getAttribute('aria-describedby') || '';
            select.before(this.wrapper);
            this.wrapper.append(select, this.trigger, this.error);
            select.classList.add('custom-select-native');
            select.tabIndex = -1;
            select.setAttribute('aria-hidden', 'true');
            this.createMenu();
            this.bind();
            this.sync();
        }

        createMenu() {
            this.menu = document.createElement('div');
            this.menu.className = 'custom-select-menu';
            this.menu.hidden = true;
            this.searchWrap = document.createElement('div');
            this.searchWrap.className = 'custom-select-search-wrap';
            this.search = document.createElement('input');
            this.search.type = 'search';
            this.search.className = 'custom-select-search';
            this.search.placeholder = 'Tìm lựa chọn...';
            this.search.autocomplete = 'off';
            this.search.setAttribute('aria-label', `Tìm lựa chọn: ${this.label}`);
            this.search.setAttribute('aria-controls', `${this.id}-list`);
            this.searchWrap.append(this.search);
            this.list = document.createElement('div');
            this.list.className = 'custom-select-options';
            this.list.id = `${this.id}-list`;
            this.list.setAttribute('role', 'listbox');
            this.list.setAttribute('aria-label', this.label);
            this.empty = document.createElement('div');
            this.empty.className = 'custom-select-empty';
            this.empty.textContent = 'Không tìm thấy lựa chọn phù hợp.';
            this.empty.hidden = true;
            this.empty.setAttribute('role', 'status');
            this.menu.append(this.searchWrap, this.list, this.empty);
            document.body.append(this.menu);
            this.build();
        }

        build() {
            this.options = [...this.select.options];
            this.nodes = this.options.map((option, index) => {
                const node = document.createElement('div');
                node.className = 'custom-select-option';
                node.id = `${this.id}-option-${index}`;
                node.dataset.index = index;
                node.setAttribute('role', 'option');
                const label = document.createElement('span');
                label.className = 'custom-select-option-label';
                label.textContent = option.label;
                node.append(label, icon('custom-select-check', 'm5 12 4 4L19 6'));
                return node;
            });
            this.list.replaceChildren(...this.nodes);
            this.searchWrap.hidden = this.options.length <= 8;
            this.filter();
        }

        disabled(index) {
            const option = this.options[index];
            return !option || option.disabled || option.parentElement.disabled;
        }

        sync() {
            this.value.textContent = this.select.selectedOptions[0]?.label || 'Chọn một lựa chọn';
            this.trigger.disabled = this.select.matches(':disabled');
            this.trigger.setAttribute('aria-required', String(this.select.required));
            this.trigger.title = this.value.textContent;
            this.nodes.forEach((node, index) => {
                node.setAttribute('aria-selected', String(index === this.select.selectedIndex));
                node.setAttribute('aria-disabled', String(Boolean(this.disabled(index))));
            });
            const invalid = Boolean((this.validationShown && !this.select.validity.valid) || this.select.getAttribute('aria-invalid') === 'true');
            this.wrapper.classList.toggle('is-invalid', invalid);
            this.trigger.setAttribute('aria-invalid', String(invalid));
            this.error.hidden = !invalid || this.select.validity.valid;
            this.error.textContent = this.select.validity.valueMissing ? 'Vui lòng chọn một lựa chọn.' : this.select.validationMessage;
            const descriptions = [this.baseDescription, !this.error.hidden ? this.error.id : ''].filter(Boolean).join(' ');
            if (descriptions) this.trigger.setAttribute('aria-describedby', descriptions);
            else this.trigger.removeAttribute('aria-describedby');
            if (this.trigger.disabled && current === this) this.close();
        }

        filter() {
            const query = normalize(this.search.value.trim());
            this.nodes.forEach((node, index) => {
                node.hidden = this.options[index].hidden || this.options[index].parentElement.hidden || !normalize(this.options[index].label).includes(query);
            });
            this.visible = this.nodes.map((node, index) => !node.hidden && !this.disabled(index) ? index : -1).filter(index => index >= 0);
            this.empty.hidden = this.nodes.some(node => !node.hidden);
            const preferred = this.visible.includes(this.select.selectedIndex) ? this.select.selectedIndex : this.visible[0];
            this.activate(preferred ?? -1, false);
        }

        activate(index, scroll = true) {
            this.active = index;
            this.nodes.forEach((node, i) => node.classList.toggle('is-active', i === index));
            for (const control of [this.trigger, this.search]) {
                if (index >= 0 && current === this) control.setAttribute('aria-activedescendant', this.nodes[index].id);
                else control.removeAttribute('aria-activedescendant');
            }
            if (scroll && index >= 0) this.nodes[index].scrollIntoView({ block: 'nearest' });
        }

        position() {
            if (current !== this) return;
            const rect = this.trigger.getBoundingClientRect();
            const viewport = window.visualViewport;
            const top = viewport?.offsetTop || 0;
            const left = viewport?.offsetLeft || 0;
            const width = viewport?.width || window.innerWidth;
            const height = viewport?.height || window.innerHeight;
            if (!rect.height || rect.bottom < top || rect.top > top + height) { this.close(); return; }
            const below = top + height - rect.bottom - 12;
            const above = rect.top - top - 12;
            this.menu.style.width = `${Math.min(Math.max(rect.width, 220), width - 24)}px`;
            const desiredHeight = Math.min(340, this.list.scrollHeight + (this.searchWrap.hidden ? 0 : this.searchWrap.getBoundingClientRect().height) + (this.empty.hidden ? 0 : this.empty.getBoundingClientRect().height) + 12);
            const upward = below < desiredHeight && above > below;
            const available = Math.max(60, upward ? above : below);
            this.menu.style.maxHeight = `${Math.min(340, available)}px`;
            this.menu.style.left = `${Math.max(left + 12, Math.min(rect.left, left + width - this.menu.getBoundingClientRect().width - 12))}px`;
            this.menu.style.top = `${upward ? rect.top - this.menu.getBoundingClientRect().height - 6 : rect.bottom + 6}px`;
        }

        open() {
            if (this.select.matches(':disabled')) return;
            if (current && current !== this) current.close();
            current = this;
            this.search.value = '';
            this.buffer = '';
            this.menu.hidden = false;
            this.wrapper.classList.add('is-open');
            this.trigger.setAttribute('aria-expanded', 'true');
            this.build();
            this.sync();
            this.position();
            this.activate(this.active);
        }

        close(focus = false) {
            this.menu.hidden = true;
            this.wrapper.classList.remove('is-open');
            this.trigger.setAttribute('aria-expanded', 'false');
            this.trigger.removeAttribute('aria-activedescendant');
            this.search.removeAttribute('aria-activedescendant');
            clearTimeout(this.typeTimer);
            if (current === this) current = null;
            if (focus) this.trigger.focus({ preventScroll: true });
        }

        choose(index) {
            if (index < 0 || this.select.matches(':disabled') || this.disabled(index)) return;
            const changed = this.select.selectedIndex !== index;
            this.select.selectedIndex = index;
            this.sync();
            this.close(true);
            if (changed) {
                this.select.dispatchEvent(new Event('input', { bubbles: true }));
                this.select.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        keydown(event) {
            const open = current === this;
            const searching = event.target === this.search;
            if (event.key === 'Tab') {
                if (open) this.close(searching);
                return;
            }
            if (event.key === 'Escape') {
                if (open) { event.preventDefault(); event.stopPropagation(); this.close(true); }
                return;
            }
            if (event.key === 'Enter' || (!searching && event.key === ' ')) {
                event.preventDefault();
                if (open) this.choose(this.active);
                else this.open();
                return;
            }
            if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
                if (searching && ['Home', 'End'].includes(event.key)) return;
                event.preventDefault();
                if (open && event.altKey && event.key === 'ArrowUp') { this.close(true); return; }
                if (!open) this.open();
                const position = this.visible.indexOf(this.active);
                let next = position;
                if (event.key === 'Home') next = 0;
                else if (event.key === 'End') next = this.visible.length - 1;
                else if (open) next += event.key === 'ArrowDown' ? 1 : -1;
                this.activate(this.visible[Math.max(0, Math.min(next, this.visible.length - 1))] ?? -1);
                return;
            }
            if (!searching && event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
                event.preventDefault();
                if (!open) this.open();
                this.buffer = (this.buffer || '') + normalize(event.key);
                const ordered = [...this.visible.filter(i => i > this.active), ...this.visible.filter(i => i <= this.active)];
                const query = [...this.buffer].every(letter => letter === this.buffer[0]) ? this.buffer[0] : this.buffer;
                const match = ordered.find(i => normalize(this.options[i].label).startsWith(query));
                if (match !== undefined) this.activate(match);
                clearTimeout(this.typeTimer);
                this.typeTimer = setTimeout(() => { this.buffer = ''; }, 650);
            }
        }

        bind() {
            this.trigger.addEventListener('click', () => current === this ? this.close() : this.open());
            this.trigger.addEventListener('keydown', event => this.keydown(event));
            this.search.addEventListener('keydown', event => this.keydown(event));
            this.search.addEventListener('input', () => { this.filter(); this.position(); });
            this.list.addEventListener('pointerdown', event => event.preventDefault());
            this.list.addEventListener('pointermove', event => {
                const node = event.target.closest('[data-index]');
                if (node && !this.disabled(Number(node.dataset.index))) this.activate(Number(node.dataset.index), false);
            });
            this.list.addEventListener('click', event => {
                const node = event.target.closest('[data-index]');
                if (node) this.choose(Number(node.dataset.index));
            });
            this.select.addEventListener('change', () => this.sync());
            this.select.addEventListener('focus', () => this.trigger.focus());
            this.select.addEventListener('click', event => { event.preventDefault(); this.trigger.focus(); this.open(); });
            this.select.addEventListener('invalid', event => {
                event.preventDefault();
                this.validationShown = true;
                this.sync();
                if (!this.select.form || this.select.form.querySelector(':invalid') === this.select) this.trigger.focus();
            });
            this.select.form?.addEventListener('reset', () => requestAnimationFrame(() => {
                this.validationShown = false;
                if (current === this) this.close();
                this.sync();
            }));
            new MutationObserver(() => { this.build(); this.sync(); if (current === this) this.position(); })
                .observe(this.select, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled', 'required', 'selected', 'label', 'value', 'hidden', 'aria-invalid'] });
        }
    }

    function enhance(root) {
        const candidates = root.matches?.('select') ? [root] : [...root.querySelectorAll('select')];
        candidates.filter(select => !select.multiple && select.size <= 1 && !instances.has(select)).forEach(select => new CustomSelect(select));
    }

    function init() {
        enhance(document);
        new MutationObserver(records => {
            records.forEach(record => record.addedNodes.forEach(node => { if (node.nodeType === Node.ELEMENT_NODE) enhance(node); }));
            if (current && !current.select.isConnected) current.close();
        }).observe(document.body, { childList: true, subtree: true });
        document.addEventListener('pointerdown', event => {
            if (current && !current.wrapper.contains(event.target) && !current.menu.contains(event.target)) current.close();
        });
        document.addEventListener('focusin', event => {
            if (current && !current.wrapper.contains(event.target) && !current.menu.contains(event.target)) current.close();
        });
        document.addEventListener('toggle', () => current?.position(), true);
        window.addEventListener('resize', () => current?.position());
        window.addEventListener('scroll', event => { if (current && !current.menu.contains(event.target)) current.position(); }, true);
        window.visualViewport?.addEventListener('resize', () => current?.position());
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
