import { getQueryParam, setQueryParam } from './utils.js';

/**
 * Initialize tabbed interfaces
 * Finds all elements with role="tablist" and toggles panels
 * Loads the initial tab from the URL query string or defaults to the first tab
 */
function initTabs() {
    document.querySelectorAll('[role="tablist"]').forEach(tablist => {
        const tabs = Array.from(tablist.querySelectorAll('[role="tab"]'));

        function activateTab(tab) {
            tabs.forEach(t => {
                t.setAttribute('aria-selected', 'false');
                t.setAttribute('tabindex', '-1');
                t.classList.remove('border-[#37A0C9]', 'text-[#37A0C9]');
                t.classList.add('border-transparent', 'text-gray-500');
                const panel = document.getElementById(t.getAttribute('aria-controls'));
                if (panel) panel.classList.add('hidden');
            });
            tab.setAttribute('aria-selected', 'true');
            tab.setAttribute('tabindex', '0');
            tab.classList.add('border-[#37A0C9]', 'text-[#37A0C9]');
            tab.classList.remove('border-transparent', 'text-gray-500');
            const activePanel = document.getElementById(tab.getAttribute('aria-controls'));
            if (activePanel) activePanel.classList.remove('hidden');
            setQueryParam('t', tab.id);

            // Pass the actual panelId string, not the DOM element
            const panelId = tab.getAttribute('aria-controls');
            window.dispatchEvent(new CustomEvent('tabChanged', {
                detail: { tabId: tab.id, panelId: panelId }
            }));
        }

        tabs.forEach(tab => {
            tab.addEventListener('click', () => activateTab(tab));
        });

        // Activate tab based on query param ?t=tab-2
        const queryTab = getQueryParam('t');
        const initialTab = tabs.find(t => t.id === queryTab);
        if (initialTab) {
            activateTab(initialTab);
        } else {
            const defaultTab = tabs.find(t => t.getAttribute('aria-selected') === 'true');
            if (defaultTab) activateTab(defaultTab);
            else activateTab(tabs[0]);
        }
    });
}

window.initTabs = initTabs;