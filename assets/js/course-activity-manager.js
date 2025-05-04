import { confirm, notify, escapeHtml } from './utils.js';

/**
 * CourseActivityManager - Handles course activity operations
 */
export class CourseActivityManager {
    constructor() {
        this.bindEventListeners();
    }

    /**
     * Bind event listeners to the page elements
     */
    bindEventListeners() {
        document.addEventListener('click', (e) => {
            const target = e.target.closest('button');
            if (!target) return;

            // Handle pin/unpin buttons
            if (target.hasAttribute('data-activity-pin')) {
                this.handlePinClick(target);
            }
            // Handle unpin buttons
            else if (target.hasAttribute('data-activity-unpin')) {
                this.handleUnpinClick(target);
            }
            // Handle delete buttons
            else if (target.hasAttribute('data-activity-delete')) {
                this.handleDeleteClick(target);
            }
        });
    }

    /**
     * Handle pin button click - show modal to get pinned message
     * @param {HTMLElement} button
     */
    async handlePinClick(button) {
        const activityId = button.dataset.activityId;
        const activityName = button.dataset.activityName;

        if (!activityId || !activityName) {
            console.error('Missing activity data');
            return;
        }

        const message = await this.showPinModal(activityName);
        if (message) {
            await this.pinActivity(activityId, message);
        }
    }

    /**
     * Handle unpin button click - show confirmation
     * @param {HTMLElement} button
     */
    async handleUnpinClick(button) {
        const activityId = button.dataset.activityId;
        const activityName = button.dataset.activityName;

        if (!activityId || !activityName) {
            console.error('Missing activity data');
            return;
        }

        const confirmed = await confirm(
            `Êtes-vous sûr de vouloir dépingler "${activityName}" ?`,
            'Dépingler',
            'Annuler'
        );

        if (confirmed) {
            await this.pinActivity(activityId, null);
        }
    }

    /**
     * Handle delete button click - show confirmation
     * @param {HTMLElement} button
     */
    async handleDeleteClick(button) {
        const activityId = button.dataset.activityId;
        const activityName = button.dataset.activityName;

        if (!activityId || !activityName) {
            console.error('Missing activity data');
            return;
        }

        const confirmed = await confirm(
            `Êtes-vous sûr de vouloir supprimer "${activityName}" ?`,
            'Supprimer',
            'Annuler'
        );

        if (confirmed) {
            await this.deleteActivity(activityId);
        }
    }

    /**
     * Show modal to get pinned message
     * @param {string} activityName
     * @returns {Promise<string|null>}
     */
    showPinModal(activityName) {
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
            header.innerHTML = `<h3 class="text-lg font-medium text-gray-900">Épingler ${escapeHtml(activityName)}</h3>`;

            // Modal body
            const body = document.createElement('div');
            body.className = 'px-6 py-4';

            const inputContainer = document.createElement('div');
            inputContainer.className = 'mb-4';

            const label = document.createElement('label');
            label.className = 'block text-sm font-medium text-gray-700 mb-1';
            label.textContent = 'Message à afficher (max 32 caractères)';
            label.setAttribute('for', 'pinnedMessage');

            const input = document.createElement('input');
            input.type = 'text';
            input.id = 'pinnedMessage';
            input.className = 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#37A0C9] focus:border-transparent';
            input.maxLength = '32';
            input.value = `Épingler ${activityName}`;

            const charCount = document.createElement('div');
            charCount.className = 'text-sm text-gray-500 mt-1';
            charCount.textContent = `${input.value.length}/32 caractères`;

            // Update character count on input
            input.addEventListener('input', () => {
                charCount.textContent = `${input.value.length}/32 caractères`;
            });

            inputContainer.appendChild(label);
            inputContainer.appendChild(input);
            inputContainer.appendChild(charCount);
            body.appendChild(inputContainer);

            // Modal footer with buttons
            const footer = document.createElement('div');
            footer.className = 'px-6 py-4 border-t border-gray-200 flex justify-end space-x-2';

            const cancelBtn = document.createElement('button');
            cancelBtn.className = 'px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2';
            cancelBtn.textContent = 'Annuler';
            cancelBtn.addEventListener('click', () => {
                document.body.removeChild(modalOverlay);
                resolve(null);
            });

            const confirmBtn = document.createElement('button');
            confirmBtn.className = 'px-4 py-2 bg-[#37A0C9] text-white rounded-md hover:bg-[#2c8aa8] focus:outline-none focus:ring-2 focus:ring-[#37A0C9] focus:ring-offset-2';
            confirmBtn.textContent = 'Épingler';
            confirmBtn.addEventListener('click', () => {
                const message = input.value.trim();
                if (message.length > 0 && message.length <= 32) {
                    document.body.removeChild(modalOverlay);
                    resolve(message);
                }
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

            // Focus on input
            input.focus();
            input.select();
        });
    }

    /**
     * Pin or unpin an activity
     * @param {number} activityId
     * @param {string|null} message
     */
    async pinActivity(activityId, message) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token-switch"]')?.content;

            if (!csrfToken) {
                throw new Error('CSRF token not found');
            }

            const response = await fetch(`/api/course/activity/${activityId}/pin`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    _token: csrfToken,
                    pinnedMessage: message
                })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Erreur lors de l\'opération');
            }

            // Show success notification
            if (data.data.isPinned) {
                notify('Activité épinglée avec succès', 'success');
            } else {
                notify('Activité dépinglée avec succès', 'success');
            }

            // Refresh the page
            window.location.reload();
        } catch (error) {
            console.error('Error pinning/unpinning activity:', error);
            notify(error.message || 'Une erreur est survenue', 'error');
        }
    }

    /**
     * Delete an activity
     * @param {number} activityId
     */
    async deleteActivity(activityId) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token-delete"]')?.content;

            if (!csrfToken) {
                throw new Error('CSRF token not found');
            }

            const response = await fetch(`/api/course/activity/${activityId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    _token: csrfToken
                })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Erreur lors de la suppression');
            }

            // Show success notification
            notify('Activité supprimée avec succès', 'success');

            // Refresh the page
            window.location.reload();
        } catch (error) {
            console.error('Error deleting activity:', error);
            notify(error.message || 'Une erreur est survenue', 'error');
        }
    }
}

// Initialize the manager when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.courseActivityManager = new CourseActivityManager();
});