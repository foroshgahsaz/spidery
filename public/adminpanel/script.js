function toggleSidebar() {
    const sidebar = document.getElementById('sidebarPrimary');
    const mainContent = document.getElementById('mainContent');
    const toggleIcon = document.getElementById('toggleIcon');

    sidebar.classList.toggle('closed');
    mainContent.classList.toggle('expanded');

    if (sidebar.classList.contains('closed')) {
        toggleIcon.classList.remove('fa-chevron-left');
        toggleIcon.classList.add('fa-chevron-right');
    } else {
        toggleIcon.classList.remove('fa-chevron-right');
        toggleIcon.classList.add('fa-chevron-left');
    }
}

function initAdminSidebar() {
    const sidebarIcons = document.querySelectorAll('.sidebar-icon-item[data-panel]');
    const menuContents = document.querySelectorAll('.menu-content');
    const panelTitles = window.__adminPanelTitles || {};

    function expandPanelSubmenus(panel) {
        if (!panel) return;
        panel.querySelectorAll('.submenu').forEach(sub => sub.classList.add('expanded'));
        panel.querySelectorAll('.menu-item[data-submenu]').forEach(item => {
            item.classList.add('active');
            item.setAttribute('aria-expanded', 'true');
            const arrow = item.querySelector('.menu-arrow');
            if (arrow) arrow.style.transform = 'rotate(180deg)';
        });
    }

    function switchPanel(panelId) {
        menuContents.forEach(content => content.classList.remove('active'));
        const current = document.getElementById(panelId + 'Content');
        if (current) {
            current.classList.add('active');
            expandPanelSubmenus(current);
        }

        const titleEl = document.getElementById('sidebarPanelTitle');
        if (titleEl && panelTitles[panelId]) {
            titleEl.textContent = panelTitles[panelId];
        }
    }

    sidebarIcons.forEach(icon => {
        icon.addEventListener('click', function () {
            document.querySelectorAll('.sidebar-icon-item').forEach(item => {
                if (!item.classList.contains('sidebar-icon-link')) {
                item.classList.remove('active');
                }
            });
            this.classList.add('active');
            switchPanel(this.getAttribute('data-panel'));
        });
    });
    
    document.querySelectorAll('.menu-item[data-submenu]').forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            const submenuId = this.getAttribute('data-submenu');
            const submenu = document.getElementById(submenuId);
            if (!submenu) return;

            const isExpanded = submenu.classList.contains('expanded');
            submenu.classList.toggle('expanded', !isExpanded);
            this.classList.toggle('active', !isExpanded);
            this.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
            const arrow = this.querySelector('.menu-arrow');
            if (arrow) arrow.style.transform = isExpanded ? 'rotate(0deg)' : 'rotate(180deg)';
        });
    });
    
    document.querySelectorAll('.submenu').forEach(submenu => {
        const activeItem = submenu.querySelector('.submenu-item.active');
        if (!activeItem) return;

        submenu.classList.add('expanded');
        const menuItem = submenu.previousElementSibling;
        if (menuItem?.classList.contains('menu-item')) {
            menuItem.classList.add('active');
            menuItem.setAttribute('aria-expanded', 'true');
            const arrow = menuItem.querySelector('.menu-arrow');
            if (arrow) arrow.style.transform = 'rotate(180deg)';
        }
    });

    const activePanel = document.querySelector('.menu-content.active');
    if (activePanel) {
        expandPanelSubmenus(activePanel);
    }
}

function initModalFix() {
    let hadOpenModal = false;

    const observer = new MutationObserver(() => {
        const hasOpenModal = !!document.querySelector('.fi-modal-open');
        document.body.classList.toggle('fi-has-open-modal', hasOpenModal);

        if (hasOpenModal && !hadOpenModal) {
            document.dispatchEvent(new CustomEvent('ax-modal-opened'));
            window.dispatchEvent(new CustomEvent('ax-modal-opened'));
        }

        hadOpenModal = hasOpenModal;
    });

    observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] });
}

function initPriceRangeSliders() {
    const formatPrice = (value) => new Intl.NumberFormat('fa-IR').format(value) + ' تومان';

    const bindWrap = (wrap) => {
        if (wrap.dataset.bound === '1') return;
        wrap.dataset.bound = '1';

        const max = parseInt(wrap.dataset.max || '10000000', 10);
        const minRange = wrap.querySelector('.price-range-min');
        const maxRange = wrap.querySelector('.price-range-max');
        const fill = wrap.querySelector('.price-range-fill');
        const minLabel = wrap.querySelector('.price-range-min-label');
        const maxLabel = wrap.querySelector('.price-range-max-label');
        const form = wrap.closest('form');

        if (!minRange || !maxRange || !form) return;

        const minInput = form.querySelector('.price-range-min-input');
        const maxInput = form.querySelector('.price-range-max-input');

        const syncFill = () => {
            let minVal = parseInt(minRange.value, 10);
            let maxVal = parseInt(maxRange.value, 10);

            if (minVal > maxVal) {
                [minVal, maxVal] = [maxVal, minVal];
                minRange.value = minVal;
                maxRange.value = maxVal;
            }

            const left = (minVal / max) * 100;
            const right = 100 - (maxVal / max) * 100;

            if (fill) {
                fill.style.right = `${right}%`;
                fill.style.left = `${left}%`;
            }

            if (minLabel) minLabel.textContent = formatPrice(minVal);
            if (maxLabel) maxLabel.textContent = formatPrice(maxVal);

            if (minInput) {
                minInput.value = minVal > 0 ? minVal : '';
                minInput.dispatchEvent(new Event('input', { bubbles: true }));
            }

            if (maxInput) {
                maxInput.value = maxVal < max ? maxVal : '';
                maxInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        };

        const syncFromInputs = () => {
            const minVal = parseInt(minInput?.value || '0', 10) || 0;
            const maxVal = parseInt(maxInput?.value || String(max), 10) || max;
            minRange.value = Math.min(minVal, max);
            maxRange.value = Math.min(Math.max(maxVal, minVal), max);
            syncFill();
        };

        minRange.addEventListener('input', syncFill);
        maxRange.addEventListener('input', syncFill);
        minInput?.addEventListener('change', syncFromInputs);
        maxInput?.addEventListener('change', syncFromInputs);

        if (minInput?.value || maxInput?.value) {
            syncFromInputs();
        } else {
            syncFill();
        }
    };

    document.querySelectorAll('[data-price-range-wrap]').forEach(bindWrap);

    const observer = new MutationObserver(() => {
        document.querySelectorAll('[data-price-range-wrap]').forEach(bindWrap);
    });

    observer.observe(document.body, { childList: true, subtree: true });
}

document.addEventListener('DOMContentLoaded', () => {
    initAdminSidebar();
    initModalFix();
    initPriceRangeSliders();
});
