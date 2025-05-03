import { notify, escapeHtml, InfiniteScrollManager, confirm } from "./utils.js";
import { apiService } from "./api-service.js";

/**
 * Group Members Manager - Handles loading and managing group members
 */
class GroupMembersManager {
    /**
     * Initialize the group members manager
     */
    constructor() {
        // DOM elements
        this.modal = document.getElementById('group-members-modal');
        this.groupNameElement = document.getElementById('group-members-group-name');
        this.closeButtons = [
            document.getElementById('group-members-close-btn'),
            document.getElementById('group-members-close-btn-footer')
        ];
        this.addTeacherBtn = document.getElementById('add-teacher-btn');
        this.addStudentBtn = document.getElementById('add-student-btn');
        this.membersTableBody = document.getElementById('members-table-body');

        // State
        this.currentGroupId = null;
        /** @type {InfiniteScrollManager} */
        this.membersManager = null;

        // Initialize event listeners
        this.initEvents();
    }

    /**
     * Initialize all event listeners
     */
    initEvents() {
        // Close events
        this.closeButtons.forEach(btn => {
            btn.addEventListener('click', () => this.closeModal());
        });

        // Add user events
        this.addTeacherBtn.addEventListener('click', () => this.openAddUserModal('teacher'));
        this.addStudentBtn.addEventListener('click', () => this.openAddUserModal('student'));
    }

    /**
     * Close the modal
     */
    closeModal() {
        this.modal.classList.add('hidden');

        // Fully deactivate the manager
        if (this.membersManager) {
            this.membersManager.deactivate();
        }

        // Clear the table when closing the modal
        if (this.membersTableBody) {
            this.membersTableBody.innerHTML = '';
        }
    }

    /**
     * Open the modal to add a user to the group
     * @param {string} userType - Either 'teacher' or 'student'
     */
    async openAddUserModal(userType) {
        try {
            const users = await this.fetchEligibleUsers(userType);

            if (!users.length) {
                notify(`No ${userType === 'teacher' ? 'professors' : 'students'} available`, 'info');
                return;
            }

            const modalElement = this.createUserSelectionModal(userType, users);
            document.body.appendChild(modalElement);
            modalElement.classList.remove('hidden');
        } catch (error) {
            console.error('Error fetching eligible users:', error);
            notify(error.message, 'error');
        }
    }

    /**
     * Fetch eligible users by role
     * @param {string} userType - Either 'teacher' or 'student'
     * @returns {Promise<Array>} - Array of user objects
     */
    async fetchEligibleUsers(userType) {
        try {
            return await apiService.getEligibleGroupUsers(this.currentGroupId, userType);
        } catch (error) {
            console.error('Error fetching eligible users:', error);
            throw error;
        }
    }

    /**
     * Create a modal for selecting users to add to the group
     * @param {string} userType - Either 'teacher' or 'student'
     * @param {Array} users - Array of user objects
     * @returns {HTMLElement} - The modal element
     */
    createUserSelectionModal(userType, users) {
        const typeLabel = userType === 'teacher' ? 'professor' : 'student';
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-gray-500/75 flex items-center justify-center z-50';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');

        modal.innerHTML = `
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900">Add a ${typeLabel}</h3>
                    <button class="close-btn text-gray-400 hover:text-gray-500" aria-label="Close">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="px-6 py-4">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select a ${typeLabel}</label>
                        <select class="user-select w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select...</option>
                            ${users.map(user => `<option value="${escapeHtml(user.id)}">${escapeHtml(user.fullName)} (${escapeHtml(user.email)})</option>`).join('')}
                        </select>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-2">
                    <button class="cancel-btn px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Cancel
                    </button>
                    <button class="add-btn px-4 py-2 bg-[#37A0C9] text-white rounded-md hover:bg-[#2c8aa8] focus:outline-none focus:ring-2 focus:ring-[#37A0C9] focus:ring-offset-2">
                        Add
                    </button>
                </div>
            </div>
        `;

        // DOM elements from the template
        const closeBtn = modal.querySelector('.close-btn');
        const cancelBtn = modal.querySelector('.cancel-btn');
        const addBtn = modal.querySelector('.add-btn');
        const userSelect = modal.querySelector('.user-select');

        // Add event handlers
        const closeModalHandler = () => {
            document.body.removeChild(modal);
        };

        closeBtn.addEventListener('click', closeModalHandler);
        cancelBtn.addEventListener('click', closeModalHandler);

        addBtn.addEventListener('click', async () => {
            const userId = userSelect.value;
            if (!userId) {
                notify(`Please select a ${typeLabel}`, 'warning');
                return;
            }

            try {
                await this.addUserToGroup(userId);
                notify(`${typeLabel.charAt(0).toUpperCase() + typeLabel.slice(1)} added successfully`, 'success');
                this.membersManager.reload();
                closeModalHandler();
            } catch (error) {
                notify(error.message, 'error');
            }
        });

        return modal;
    }

    /**
     * Add a user to the group
     * @param {number|string} userId - The user ID to add
     * @returns {Promise<object>} - The API response
     */
    async addUserToGroup(userId) {
        return await apiService.addUserToGroup(this.currentGroupId, userId);
    }

    /**
     * Open the members modal for a specific group
     * @param {number} groupId - The group ID
     * @param {string} groupName - The group name
     */
    open(groupId, groupName) {
        // Set state
        this.currentGroupId = groupId;
        this.groupNameElement.textContent = groupName || '';

        // Clear the table first
        if (this.membersTableBody) {
            this.membersTableBody.innerHTML = '';
        }

        // Show the modal
        this.modal.classList.remove('hidden');

        setTimeout(() => {
            if (this.membersManager) {
                // Update the manager's endpoint
                this.membersManager.updateEndpoint(`/api/course/group/${groupId}/members`);

                // Activate if not active and load data
                if (!this.membersManager.active) {
                    this.membersManager.activate();
                } else {
                    this.membersManager.reload();
                }
            } else {
                // Create a new manager if none exists
                this.initMembersManager();
            }
        }, 50);
    }

    /**
     * Initialize or reload the members infinite scroll manager
     */
    initMembersManager() {
        // Always destroy the old manager to ensure a clean state
        if (this.membersManager) {
            // Clean up resources
            if (this.membersManager.observer) {
                this.membersManager.observer.disconnect();
            }
            this.membersManager = null;
        }

        // Double-check that the table is empty
        if (this.membersTableBody) {
            this.membersTableBody.innerHTML = '';
        }

        // Set up new infinite scroll manager for members
        this.membersManager = new InfiniteScrollManager({
            panelId: 'members-panel',
            tableBodyId: 'members-table-body',
            sentinelId: 'members-scroll-sentinel',
            paginationLoaderId: 'members-pagination-loader',
            endMessageId: 'end-of-members',
            initialLoaderId: 'members-loader',
            searchInputId: null, // No search input in this panel
            apiEndpoint: `/api/course/group/${this.currentGroupId}/members`,
            renderFn: (members) => this.renderMembers(members),
            errorHandler: (err) => {
                console.error('Error loading members:', err);
                notify('Error loading members', 'error');
            }
        });

        this.membersManager.activate();
    }

    /**
     * Render members in the table
     * @param {Array} members - Array of member objects
     */
    renderMembers(members) {
        const fragment = document.createDocumentFragment();

        members.forEach(member => {
            const row = document.createElement('tr');
            row.className = 'border-b border-gray-200 hover:bg-gray-50';

            // Add a class for teachers to style them differently if needed
            if (member.roles.includes('ROLE_TEACHER')) {
                row.classList.add('member-teacher');
            } else if (member.roles.includes('ROLE_STUDENT')) {
                row.classList.add('member-student');
            }

            // Format roles for display
            const roleLabels = {
                'ROLE_TEACHER': 'Professor',
                'ROLE_STUDENT': 'Student',
                'ROLE_ADMIN': 'Administrator',
                'ROLE_USER': 'User'
            };

            const displayRoles = member.roles
                .filter(role => role !== 'ROLE_USER') // Filter out the base role
                .map(role => roleLabels[role] || role)
                .join(', ');

            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${member.id}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${escapeHtml(member.fullName)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${escapeHtml(member.email)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${escapeHtml(displayRoles)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button 
                        data-user-id="${member.id}" 
                        class="remove-user-btn text-red-600 hover:text-red-900 focus:outline-none focus:underline"
                    >
                        Remove
                    </button>
                </td>
            `;

            // Add event listener for remove button
            const removeBtn = row.querySelector('.remove-user-btn');
            removeBtn.addEventListener('click', () => this.handleRemoveUser(member.id, member.fullName));

            fragment.appendChild(row);
        });

        this.membersTableBody.appendChild(fragment);
    }

    /**
     * Handle removing a user from the group
     * @param {number|string} userId - The user ID
     * @param {string} userName - The user's name
     */
    async handleRemoveUser(userId, userName) {
        try {
            const confirmed = await confirm(
                `Are you sure you want to remove ${escapeHtml(userName)} from the group? This action cannot be undone.`,
            );

            if (!confirmed) return;

            await this.removeUserFromGroup(userId);
            this.membersManager.reload();
            notify('Member removed successfully', 'success');
        } catch (error) {
            console.error('Error removing member:', error);
            notify(error.message, 'error');
        }
    }

    /**
     * Remove a user from the group
     * @param {number|string} userId - The user ID to remove
     * @returns {Promise<object>} - The API response
     */
    async removeUserFromGroup(userId) {
        return await apiService.removeUserFromGroup(this.currentGroupId, userId);
    }
}

/**
 * Initialize the group members manager
 */
export function initGroupMembersManager() {
    window.groupMembersManagerInstance = new GroupMembersManager();
}

// Expose to window for global access
window.initGroupMembersManager = initGroupMembersManager;