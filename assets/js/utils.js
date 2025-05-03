/**
 * Enhanced fetch API with better error handling
 * @param {string} url - The URL to fetch
 * @param {object} options - Fetch options
 * @returns {Promise<object>} - The parsed JSON response
 */
export async function fetchWithErrorHandling(url, options = {}) {
    try {
        const response = await fetch(url, options);

        // Parse JSON response
        const data = await response.json().catch(() => ({}));

        // Handle non-2xx responses
        if (!response.ok) {
            const error = new Error(data.message || `Request failed with status ${response.status}`);
            error.status = response.status;
            error.data = data;
            console.error(error);
            notify(
                'An error occurred while processing your request. Please try again later.',
                'error'
            )
        }

        return data;
    } catch (error) {
        // Handle network errors
        notify(
            'An error occurred while processing your request. Please try again later.',
            'error'
        )
        console.error('Fetch error:', error);
    }
}

/**
 * Create a debounced function
 * @param {Function} fn - The function to debounce
 * @param {number} wait - The debounce delay in ms
 * @returns {Function} - The debounced function
 */
export function debounce(fn, wait = 300) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            fn(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Safely escape HTML to prevent XSS
 * @param {string|null|undefined} unsafe - The unsafe string
 * @returns {string} - The escaped string
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
 * Get query string parameter value
 * @param {string} name - The parameter name
 * @returns {string|null} - The parameter value
 */
export function getQueryParam(name) {
    try {
        const url = new URL(window.location);
        return url.searchParams.get(name);
    } catch (error) {
        console.error('Error getting query parameter:', error);
        return null;
    }
}

/**
 * Update URL query string without reloading
 * @param {string} param - The parameter name
 * @param {string} value - The parameter value
 */
export function setQueryParam(param, value) {
    try {
        const url = new URL(window.location);
        url.searchParams.set(param, value);
        history.pushState(null, '', url);
    } catch (error) {
        console.error('Error setting query parameter:', error);
    }
}

/**
 * Shows a confirmation dialog with custom message
 * @param {string} message - Message to display
 * @param {string} confirmText - Text for confirm button
 * @param {string} cancelText - Text for cancel button
 * @returns {Promise<boolean>} - Resolves to true if confirmed, false if cancelled
 */
export function confirm(message, confirmText = 'Confirm', cancelText = 'Cancel') {
    return new Promise((resolve) => {
        try {
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
        } catch (error) {
            console.error('Error showing confirmation dialog:', error);
            resolve(false);
        }
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
    try {
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
                if (container.children.length === 0 && container.parentNode) {
                    container.parentNode.removeChild(container);
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
        return { close, element: notification };
    } catch (error) {
        console.error('Error showing notification:', error);
        return { close: () => {}, element: null };
    }
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
        this.panel              = document.getElementById(panelId);
        this.tableBody          = document.getElementById(tableBodyId);
        this.sentinel           = document.getElementById(sentinelId);
        this.paginationLoader   = document.getElementById(paginationLoaderId);
        this.endMessage         = document.getElementById(endMessageId);
        this.initialLoader      = document.getElementById(initialLoaderId);
        this.searchInput        = document.getElementById(searchInputId);
        this.apiEndpoint        = apiEndpoint;
        this.renderFn           = renderFn;
        this.errorHandler       = errorHandler;

        // State
        this.active = false;
        this.eventsBound = false;
        this.observer = null;
        this.state = {
            page: 1,
            limit: 20,
            hasMore: true,
            loading: false,
            searchTerm: this.searchInput ? this.searchInput.value : ''
        };

        // Initialize event listeners
        this._bindGlobalEvents();
    }

    /**
     * Bind global event listeners
     * @private
     */
    _bindGlobalEvents() {
        window.addEventListener('tabChanged', event => this._onTabChanged(event.detail));

        // Check if current tab is active on load
        const currentTab = document.querySelector(`[role="tab"][aria-controls="${this.panelId}"]`);
        if (currentTab && currentTab.getAttribute('aria-selected') === 'true') {
            this.activate();
        }
    }

    /**
     * Handle tab change events
     * @param {object} detail - Event detail with panelId
     * @private
     */
    _onTabChanged({ panelId }) {
        if (!this.panel) {
            this.panel = document.getElementById(this.panelId);
        }

        if (panelId === this.panelId || (this.panel && panelId === this.panel.id)) {
            this.activate();
        } else if (this.active) {
            this.deactivate();
        }
    }

    /**
     * Activate the manager
     */
    activate() {
        if (this.active) return;
        this.active = true;
        this._bindEvents();
        this._fetchPage();
    }

    /**
     * Deactivate the manager
     */
    deactivate() {
        this.active = false;

        if (this.observer) {
            this.observer.disconnect();
        }
    }

    /**
     * Reset and reload data
     */
    reload() {
        this._reset();
        this._fetchPage();
    }

    /**
     * Bind component-specific event listeners
     * @private
     */
    _bindEvents() {
        if (this.eventsBound) return;
        this.eventsBound = true;

        // Bind search input events if available
        if (this.searchInput) {
            const debouncedSearch = debounce(e => {
                this.state.searchTerm = e.target.value;
                this._reset();
                this._fetchPage();
            }, 300);

            this.searchInput.addEventListener('input', debouncedSearch);
        }

        // Set up intersection observer
        this._setupObserver();
    }

    /**
     * Set up intersection observer for infinite scrolling
     * @private
     */
    _setupObserver() {
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

        if (this.sentinel) {
            this.observer.observe(this.sentinel);
        }
    }

    /**
     * Fetch a page of data
     * @private
     */
    async _fetchPage() {
        if (!this.active || this.state.loading || !this.state.hasMore) return;

        this.state.loading = true;

        if (this.state.page > 1 && this.paginationLoader) {
            this.paginationLoader.classList.remove('hidden');
        }

        try {
            const offset = (this.state.page - 1) * this.state.limit;
            const url = `${this.apiEndpoint}?limit=${this.state.limit}&offset=${offset}&search=${encodeURIComponent(this.state.searchTerm)}`;

            const response = await fetch(url, { credentials: 'same-origin' });

            if (!response.ok) {
                throw new Error(`HTTP error: ${response.status}`);
            }

            // Handle different response formats
            const result = await response.json();
            const items = Array.isArray(result) ? result :
                (result.data && Array.isArray(result.data) ? result.data : []);

            if (items.length > 0) {
                this.renderFn(items);
            }

            this.state.hasMore = items.length === this.state.limit;
            this.state.page++;

            if (!this.state.hasMore && this.endMessage) {
                this.endMessage.classList.remove('hidden');

                if (this.observer) {
                    this.observer.disconnect();
                }
            }
        } catch (error) {
            console.error('Error fetching data:', error);
            if (this.errorHandler) {
                this.errorHandler(error);
            }
        } finally {
            this.state.loading = false;

            if (this.initialLoader && this.initialLoader.parentNode) {
                this.initialLoader.remove();
                this.initialLoader = null;
            }

            if (this.paginationLoader) {
                this.paginationLoader.classList.add('hidden');
            }

            this._autoLoadNextIfNeeded();
        }
    }

    /**
     * Automatically load next page if there's not enough content
     * @private
     */
    _autoLoadNextIfNeeded() {
        if (!this.active || this.state.loading || !this.state.hasMore || !this.sentinel) return;

        const rect = this.sentinel.getBoundingClientRect();
        if (rect.top <= window.innerHeight && rect.bottom >= 0) {
            this._fetchPage();
        }
    }

    /**
     * Reset the state
     * @private
     */
    _reset() {
        this.state = {
            page: 1,
            limit: this.state.limit,
            hasMore: true,
            loading: false,
            searchTerm: this.searchInput ? this.searchInput.value : this.state.searchTerm
        };

        if (this.tableBody) {
            this.tableBody.innerHTML = '';
        }

        if (this.endMessage) {
            this.endMessage.classList.add('hidden');
        }

        if (this.active) {
            this._setupObserver();
        }
    }
}