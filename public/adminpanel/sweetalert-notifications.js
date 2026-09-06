(function () {
    'use strict';

    if (typeof Swal === 'undefined') {
        return;
    }

    const shownIds = new Set();
    let alertQueue = Promise.resolve();

    const statusMeta = {
        success: {
            icon: '✓',
            ring: '#d1fae5',
            color: '#059669',
            label: 'موفق',
        },
        danger: {
            icon: '✕',
            ring: '#ffe4e6',
            color: '#e11d48',
            label: 'خطا',
        },
        warning: {
            icon: '!',
            ring: '#fef3c7',
            color: '#d97706',
            label: 'توجه',
        },
        info: {
            icon: 'i',
            ring: '#e0f2fe',
            color: '#0284c7',
            label: 'اطلاع',
        },
    };

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function resolveStatus(status) {
        return statusMeta[status] ? status : 'info';
    }

    function buildCard(title, body, status) {
        const meta = statusMeta[resolveStatus(status)];

        return (
            '<div class="shop-swal-card">' +
                '<div class="shop-swal-icon-wrap" style="--shop-swal-ring:' + meta.ring + ';--shop-swal-color:' + meta.color + '">' +
                    '<span class="shop-swal-icon-char">' + meta.icon + '</span>' +
                '</div>' +
                '<div class="shop-swal-badge">' + meta.label + '</div>' +
                '<h2 class="shop-swal-title">' + escapeHtml(title) + '</h2>' +
                (body ? '<p class="shop-swal-body">' + escapeHtml(body) + '</p>' : '') +
            '</div>'
        );
    }

    function enqueueAlert(options) {
        alertQueue = alertQueue.then(function () {
            return window.shopAdminAlert(options);
        });

        return alertQueue;
    }

    window.shopAdminAlert = function (options) {
        const opts = options || {};
        const status = resolveStatus(opts.status || opts.icon || 'success');
        const isSuccess = status === 'success';
        const timer = opts.timer !== undefined ? opts.timer : (isSuccess ? 3200 : undefined);

        return Swal.fire({
            html: buildCard(opts.title || 'اعلان', opts.body || opts.text || '', status),
            showConfirmButton: opts.showConfirmButton !== undefined ? opts.showConfirmButton : !isSuccess,
            confirmButtonText: opts.confirmButtonText || 'باشه',
            showCancelButton: Boolean(opts.showCancelButton),
            cancelButtonText: opts.cancelButtonText || 'انصراف',
            timer: timer,
            timerProgressBar: timer !== undefined && timer > 0,
            backdrop: 'rgba(15, 23, 42, 0.42)',
            allowOutsideClick: isSuccess,
            customClass: {
                popup: 'shop-swal-popup',
                confirmButton: 'shop-swal-btn shop-swal-btn--confirm',
                cancelButton: 'shop-swal-btn shop-swal-btn--cancel',
            },
            buttonsStyling: false,
            showClass: {
                popup: 'shop-swal-animate-in',
            },
            hideClass: {
                popup: 'shop-swal-animate-out',
            },
            didOpen: function (popup) {
                popup.setAttribute('dir', 'rtl');
            },
        });
    };

    window.shopAdminToast = function (title, status, body) {
        return enqueueAlert({
            title: title,
            body: body || '',
            status: status || 'success',
        });
    };

    window.shopAdminConfirm = function (options) {
        const opts = options || {};

        return window.shopAdminAlert({
            title: opts.title || 'آیا مطمئن هستید؟',
            body: opts.body || opts.text || '',
            status: 'warning',
            showCancelButton: true,
            confirmButtonText: opts.confirmButtonText || 'بله',
            cancelButtonText: opts.cancelButtonText || 'انصراف',
            timer: undefined,
            showConfirmButton: true,
        });
    };

    function showFromPayload(payload) {
        if (!payload) {
            return;
        }

        const id = payload.id || (payload.title + '|' + (payload.body || '') + '|' + (payload.status || 'success'));

        if (shownIds.has(id)) {
            return;
        }

        shownIds.add(id);

        enqueueAlert({
            title: payload.title || 'اعلان',
            body: payload.body || '',
            status: payload.status || 'success',
        });
    }

    function parseFilamentNotification(node) {
        if (!node || node.dataset.shopSwalShown === '1') {
            return null;
        }

        const title = node.querySelector('.fi-no-notification-title')?.textContent?.trim() || '';
        const body = node.querySelector('.fi-no-notification-body')?.textContent?.trim() || '';
        let status = 'success';

        if (node.classList.contains('fi-status-danger')) {
            status = 'danger';
        } else if (node.classList.contains('fi-status-warning')) {
            status = 'warning';
        } else if (node.classList.contains('fi-status-info')) {
            status = 'info';
        }

        node.dataset.shopSwalShown = '1';

        return {
            id: node.getAttribute('wire:key') || title + '|' + body + '|' + status,
            title: title,
            body: body,
            status: status,
        };
    }

    function scanFilamentNotifications() {
        document.querySelectorAll('.fi-no-notification').forEach(function (node) {
            const payload = parseFilamentNotification(node);

            if (payload) {
                showFromPayload(payload);
            }
        });
    }

    function bindFilamentObserver() {
        const root = document.querySelector('.fi-no');

        if (!root || root.dataset.shopSwalBound === '1') {
            scanFilamentNotifications();
            return;
        }

        root.dataset.shopSwalBound = '1';

        const observer = new MutationObserver(function () {
            scanFilamentNotifications();
        });

        observer.observe(root, {
            childList: true,
            subtree: true,
        });

        scanFilamentNotifications();
    }

    window.addEventListener('notificationSent', function (event) {
        const notification = event.detail?.notification;

        if (!notification) {
            return;
        }

        showFromPayload({
            id: notification.id,
            title: notification.title,
            body: notification.body,
            status: notification.status,
            duration: notification.duration,
        });
    });

    window.addEventListener('shop-admin-alert', function (event) {
        enqueueAlert(event.detail || {});
    });

    function boot() {
        bindFilamentObserver();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    document.addEventListener('livewire:navigated', boot);
    document.addEventListener('livewire:initialized', boot);

    if (window.Livewire && typeof window.Livewire.hook === 'function') {
        window.Livewire.hook('commit', function ({ succeed }) {
            succeed(function () {
                window.setTimeout(scanFilamentNotifications, 60);
            });
        });
    }
})();
