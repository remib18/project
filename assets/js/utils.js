/**
 * Escape HTML to prevent XSS
 * @param {string|null|undefined} unsafe
 * @returns {string}
 */
export function escapeHtml(unsafe) {
    if (unsafe == null) return '';
    return String(unsafe)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/**
 * Debounce a function
 * @param {Function} fn
 * @param {number} wait
 * @returns {Function}
 */
export function debounce(fn, wait) {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn(...args), wait);
    };
}

/**
 * Get query string param
 * @param {string} name
 * @returns {string|null}
 */
export function getQueryParam(name) {
    const url = new URL(window.location);
    return url.searchParams.get(name);
}

/**
 * Update URL query string without reloading
 * @param {string} param
 * @param {string} value
 */
export function setQueryParam(param, value) {
    const url = new URL(window.location);
    url.searchParams.set(param, value);
    history.pushState(null, '', url);
}

/**
 * Shows a confirmation dialog with custom message
 * @param {string} message - Message to display
 * @param {string} confirmText - Text for confirm button
 * @param {string} cancelText - Text for cancel button
 * @returns {Promise<boolean>} - Resolves to true if confirmed, false if cancelled
 */
export function confirm(message, confirmText = 'Confirmer', cancelText = 'Annuler') {
    return new Promise((resolve) => {
        // Create modal elements
        const modalOverlay = document.createElement('div');
        modalOverlay.className = 'fixed inset-0 bg-gray-500/75 flex items-center justify-center z-50';
        modalOverlay.setAttribute('role', 'dialog');
        modalOverlay.setAttribute('aria-modal', 'true');

        const modalContent = document.createElement('div');
        modalContent.className = 'bg-white rounded-lg shadow-xl max-w-md w-full mx-4 overflow-hidden';

        // Modal header
        const header = document.createElement('div');
        header.className = 'px-6 py-4 border-b border-gray-200';
        header.innerHTML = `<h3 class="text-lg font-medium text-gray-900">Confirmation</h3>`;

        // Modal body
        const body = document.createElement('div');
        body.className = 'px-6 py-4';
        body.innerHTML = `<p>${escapeHtml(message)}</p>`;

        // Modal footer with buttons
        const footer = document.createElement('div');
        footer.className = 'px-6 py-4 border-t border-gray-200 flex justify-end space-x-2';

        const cancelBtn = document.createElement('button');
        cancelBtn.className = 'px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2';
        cancelBtn.textContent = cancelText;
        cancelBtn.addEventListener('click', () => {
            document.body.removeChild(modalOverlay);
            resolve(false);
        });

        const confirmBtn = document.createElement('button');
        confirmBtn.className = 'px-4 py-2 bg-[#37A0C9] text-white rounded-md hover:bg-[#2c8aa8] focus:outline-none focus:ring-2 focus:ring-[#37A0C9] focus:ring-offset-2';
        confirmBtn.textContent = confirmText;
        confirmBtn.addEventListener('click', () => {
            document.body.removeChild(modalOverlay);
            resolve(true);
        });

        footer.appendChild(cancelBtn);
        footer.appendChild(confirmBtn);

        // Assemble modal
        modalContent.appendChild(header);
        modalContent.appendChild(body);
        modalContent.appendChild(footer);
        modalOverlay.appendChild(modalContent);

        // Add to DOM
        document.body.appendChild(modalOverlay);

        // Focus on cancel button by default for better keyboard navigation
        cancelBtn.focus();
    });
}

/**
 * Shows a notification/alert message
 * @param {string} message - Message to display
 * @param {'success'|'error'|'info'|'warning'} type - Type of notification
 * @param {number} duration - Duration in milliseconds before auto-hiding (0 for persistent)
 * @returns {Object} - Control object with a close() method
 */
export function notify(message, type = 'info', duration = 3000) {
    // Map types to colors and icons
    const typeConfig = {
        success: {
            bgColor: 'bg-green-100 border-green-400 text-green-700',
            icon: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>'
        },
        error: {
            bgColor: 'bg-red-100 border-red-400 text-red-700',
            icon: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>'
        },
        warning: {
            bgColor: 'bg-yellow-100 border-yellow-400 text-yellow-700',
            icon: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>'
        },
        info: {
            bgColor: 'bg-blue-100 border-blue-400 text-blue-700',
            icon: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>'
        }
    };

    // Create notifications container if it doesn't exist
    let container = document.getElementById('notifications-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notifications-container';
        container.className = 'fixed top-4 right-4 z-50 flex flex-col gap-2 max-w-md';
        document.body.appendChild(container);
    }

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `border px-4 py-3 rounded relative transition-all duration-300 ease-in-out flex items-center ${typeConfig[type].bgColor}`;
    notification.style.opacity = '0';
    notification.style.transform = 'translateX(20px)';

    const iconSpan = document.createElement('span');
    iconSpan.className = 'mr-2 flex-shrink-0';
    iconSpan.innerHTML = typeConfig[type].icon;

    const messageSpan = document.createElement('span');
    messageSpan.className = 'flex-grow';
    messageSpan.textContent = message;

    const closeButton = document.createElement('button');
    closeButton.className = 'ml-4 flex-shrink-0';
    closeButton.innerHTML = '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>';

    notification.appendChild(iconSpan);
    notification.appendChild(messageSpan);
    notification.appendChild(closeButton);

    container.appendChild(notification);

    // Animate in
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    }, 10);

    // Set up timers and handlers
    let timeoutId;

    const close = () => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(20px)';

        clearTimeout(timeoutId);

        // Remove from DOM after animation
        setTimeout(() => {
            if (container.contains(notification)) {
                container.removeChild(notification);
            }

            // Remove container if empty
            if (container.children.length === 0) {
                document.body.removeChild(container);
            }
        }, 300);
    };

    // Set auto-close timeout if duration > 0
    if (duration > 0) {
        timeoutId = setTimeout(close, duration);
    }

    // Add event listeners
    closeButton.addEventListener('click', close);

    // Pause timer on hover, resume on leave
    notification.addEventListener('mouseenter', () => {
        clearTimeout(timeoutId);
    });

    notification.addEventListener('mouseleave', () => {
        if (duration > 0) {
            timeoutId = setTimeout(close, duration);
        }
    });

    // Return an object with control methods
    return {
        close,
        element: notification
    };
}

/**
 * An infinite scroll manager
 * @class InfiniteScrollManager
 */
export class InfiniteScrollManager {
    /**
     * @param {object} opts
     * @param {string} opts.panelId            – the ID of the <div role="tabpanel">
     * @param {string} opts.tableBodyId        – the ID of the <tbody>
     * @param {string} opts.sentinelId         – the ID of your empty sentinel <div>
     * @param {string} opts.paginationLoaderId – the "Loading more…" wrapper
     * @param {string} opts.endMessageId       – the "All items loaded" wrapper
     * @param {string} opts.initialLoaderId    – the initial `<tr id="…-loader">`
     * @param {string} opts.searchInputId      – the search `<input>`
     * @param {string} opts.apiEndpoint        – e.g. '/api/course'
     * @param {(data:Array)=>void} opts.renderFn   – your DOM-append logic
     * @param {(err:Error)=>void} opts.errorHandler – show error in UI
     */
    constructor({
                    panelId, tableBodyId, sentinelId,
                    paginationLoaderId, endMessageId, initialLoaderId,
                    searchInputId, apiEndpoint, renderFn, errorHandler
                }) {
        this.panelId            = panelId;
        this.tableBody          = document.getElementById(tableBodyId);
        this.sentinel           = document.getElementById(sentinelId);
        this.paginationLoader   = document.getElementById(paginationLoaderId);
        this.endMessage         = document.getElementById(endMessageId);
        this.initialLoader      = document.getElementById(initialLoaderId);
        this.searchInput        = document.getElementById(searchInputId);
        this.apiEndpoint        = apiEndpoint;
        this.renderFn           = renderFn;
        this.errorHandler       = errorHandler;

        this.active   = false;
        this.eventsBound = false;
        this.observer = null;
        this.state    = { page: 1, limit: 20, hasMore: true, loading: false, searchTerm: '' };

        window.addEventListener('tabChanged', e => this.onTabChanged(e.detail));
        // immediately handle whichever tab is open on load:
        const cur = document.querySelector('[role="tab"][aria-selected="true"]');
        if (cur) this.onTabChanged({ panelId: cur.getAttribute('aria-controls') });
    }

    onTabChanged({ panelId }) {
        if (panelId === this.panelId) {
            this.activate();
        } else if (this.active) {
            this.deactivate();
        }
    }

    activate() {
        if (this.active) return;
        this.active = true;
        this._bindEvents();
        this._fetchPage();
    }

    deactivate() {
        this.active = false;
        // When deactivating, disconnect the observer but keep the state
        if (this.observer) {
            this.observer.disconnect();
        }
    }

    reload() {
        this._reset()
        this._fetchPage();
    }

    _bindEvents() {
        if (this.eventsBound) return;
        this.eventsBound = true;

        const debouncedSearch = debounce((e) => {
            this.state.searchTerm = e.target.value;
            this._reset();
            this._fetchPage();
        }, 300);

        // debounced search
        this.searchInput.addEventListener('input', debouncedSearch);

        this._setupObserver();
    }

    _setupObserver() {
        // Always create a new observer to ensure it's active
        if (this.observer) {
            this.observer.disconnect();
        }

        this.observer = new IntersectionObserver(entries => {
            if (
                !this.active ||
                !entries[0].isIntersecting ||
                this.state.loading ||
                !this.state.hasMore
            ) return;
            this._fetchPage();
        }, { threshold: 0.5 });

        this.observer.observe(this.sentinel);
    }

    async _fetchPage() {
        if (!this.active || this.state.loading || !this.state.hasMore) return;
        this.state.loading = true;
        if (this.state.page > 1) this.paginationLoader.classList.remove('hidden');

        try {
            const off = (this.state.page - 1) * this.state.limit;
            const url = `${this.apiEndpoint}?limit=${this.state.limit}&offset=${off}&search=${encodeURIComponent(this.state.searchTerm)}`;
            const res = await fetch(url, { credentials: 'same-origin' });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const items = await res.json();

            this.renderFn(items);
            this.state.hasMore = items.length === this.state.limit;
            this.state.page++;

            if (!this.state.hasMore) {
                this.endMessage.classList.remove('hidden');
                if (this.observer) {
                    this.observer.disconnect();
                }
            }
        } catch (err) {
            console.error(err);
            this.errorHandler(err);
        } finally {
            this.state.loading = false;
            if (this.initialLoader) {
                this.initialLoader.remove();
                this.initialLoader = null;
            }
            this.paginationLoader.classList.add('hidden');
            this._autoLoadNextIfNeeded();
        }
    }

    _autoLoadNextIfNeeded() {
        if (!this.active || this.state.loading || !this.state.hasMore) return;
        const rect = this.sentinel.getBoundingClientRect();
        if (rect.top <= window.innerHeight) {
            this._fetchPage();
        }
    }

    _reset() {
        this.state = { page: 1, limit: this.state.limit, hasMore: true, loading: false, searchTerm: this.state.searchTerm };
        this.tableBody.innerHTML = '';
        this.endMessage.classList.add('hidden');

        // Re-setup the observer when resetting
        if (this.active) {
            this._setupObserver();
        }
    }
}