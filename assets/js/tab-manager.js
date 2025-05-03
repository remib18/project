import { getQueryParam, setQueryParam } from './utils.js';

/**
 * Tab Manager - Handles tabbed interfaces
 */
class TabManager {
    /**
     * Initialize a new TabManager
     * @param {string} tablistSelector - Selector for the tablist element
     */
    constructor(tablistSelector = '[role="tablist"]') {
        this.tablists = document.querySelectorAll(tablistSelector);
        this.initialize();
    }

    /**
     * Initialize all tablists
     */
    initialize() {
        this.tablists.forEach(tablist => {
            this.initializeTablist(tablist);
        });

        // Handle tab changes from URL
        window.addEventListener('popstate', () => this.handleUrlTabChange());
        this.handleUrlTabChange();
    }

    /**
     * Initialize a single tablist
     * @param {HTMLElement} tablist - The tablist element
     */
    initializeTablist(tablist) {
        const tabs = Array.from(tablist.querySelectorAll('[role="tab"]'));

        tabs.forEach(tab => {
            tab.addEventListener('click', () => this.activateTab(tab));
            tab.addEventListener('keydown', (event) => this.handleTabKeydown(event, tabs));
        });
    }

    /**
     * Handle keyboard navigation for tabs
     * @param {KeyboardEvent} event - The keydown event
     * @param {Array<HTMLElement>} tabs - Array of tab elements
     */
    handleTabKeydown(event, tabs) {
        // Only handle arrow keys
        if (![37, 38, 39, 40].includes(event.keyCode)) return;

        event.preventDefault();

        const currentTab = event.currentTarget;
        const currentIndex = tabs.indexOf(currentTab);
        let newIndex;

        // Left/Up arrows select previous tab, Right/Down arrows select next tab
        if ([37, 38].includes(event.keyCode)) {
            newIndex = (currentIndex - 1 + tabs.length) % tabs.length;
        } else {
            newIndex = (currentIndex + 1) % tabs.length;
        }

        // Activate the new tab and focus it
        this.activateTab(tabs[newIndex]);
        tabs[newIndex].focus();
    }

    /**
     * Activate a tab
     * @param {HTMLElement} tab - The tab to activate
     */
    activateTab(tab) {
        if (!tab) return;

        const tablist = tab.closest('[role="tablist"]');
        const tabs = Array.from(tablist.querySelectorAll('[role="tab"]'));

        // Deactivate all tabs
        tabs.forEach(t => {
            t.setAttribute('aria-selected', 'false');
            t.setAttribute('tabindex', '-1');
            t.classList.remove('border-[#37A0C9]', 'text-[#37A0C9]');
            t.classList.add('border-transparent', 'text-gray-500');

            const panel = document.getElementById(t.getAttribute('aria-controls'));
            if (panel) panel.classList.add('hidden');
        });

        // Activate the selected tab
        tab.setAttribute('aria-selected', 'true');
        tab.setAttribute('tabindex', '0');
        tab.classList.add('border-[#37A0C9]', 'text-[#37A0C9]');
        tab.classList.remove('border-transparent', 'text-gray-500');

        const activePanel = document.getElementById(tab.getAttribute('aria-controls'));
        if (activePanel) activePanel.classList.remove('hidden');

        // Update URL query parameter
        setQueryParam('t', tab.id);

        // Dispatch custom event
        this.dispatchTabChangedEvent(tab);
    }

    /**
     * Dispatch a custom event when a tab is changed
     * @param {HTMLElement} tab - The activated tab
     */
    dispatchTabChangedEvent(tab) {
        const panelId = tab.getAttribute('aria-controls');
        window.dispatchEvent(new CustomEvent('tabChanged', {
            detail: { tabId: tab.id, panelId: panelId }
        }));
    }

    /**
     * Handle tab changes from URL
     */
    handleUrlTabChange() {
        const queryTab = getQueryParam('t');

        if (queryTab) {
            const tabElement = document.getElementById(queryTab);
            if (tabElement) {
                this.activateTab(tabElement);
                return;
            }
        }

        // If no valid tab in URL, activate the default tab
        this.tablists.forEach(tablist => {
            const defaultTab = tablist.querySelector('[aria-selected="true"]') ||
                tablist.querySelector('[role="tab"]');

            if (defaultTab) {
                this.activateTab(defaultTab);
            }
        });
    }
}

/**
 * Initialize tab manager
 */
export function initTabs() {
    window.tabManager = new TabManager();
}

// Expose to window for global access
window.initTabs = initTabs;