import {escapeHtml, InfiniteScrollManager, notify, confirm} from './utils.js';
import {apiService} from './api-service.js';

// Define available roles
const AVAILABLE_ROLES = [
    { value: 'ROLE_ADMIN', label: 'Administrateur' },
    { value: 'ROLE_TEACHER', label: 'Professeur' },
    { value: 'ROLE_STUDENT', label: 'Étudiant' }
];

// Define roles incompatibilities
const INCOMPATIBLE_ROLES = {
    'ROLE_ADMIN': ['ROLE_STUDENT'],
    'ROLE_TEACHER': ['ROLE_STUDENT'],
    'ROLE_STUDENT': ['ROLE_ADMIN', 'ROLE_TEACHER']
};

function renderRole(role) {
    switch (role) {
        case 'ROLE_ADMIN': return 'Administrateur';
        case 'ROLE_TEACHER': return 'Professeur';
        case 'ROLE_STUDENT': return 'Étudiant';
        default: return null;
    }
}

function renderFullName(user) {
    const ucFirst = (s) => s.charAt(0).toUpperCase() + s.slice(1);
    return `${escapeHtml(user.lastname).toUpperCase()} ${ucFirst(escapeHtml(user.firstname))}`;
}

function renderUsers(users) {
    const frag = document.createDocumentFragment();
    users.forEach(u => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
      <td class="px-6 py-4">${escapeHtml(u.id)}</td>
      <td class="px-6 py-4">${renderFullName(u)}</td>
      <td class="px-6 py-4">${escapeHtml(u.email)}</td>
      <td class="px-6 py-4">${escapeHtml(u.roles.filter(r => r !== 'ROLE_USER').map(renderRole).join(', '))}</td>
      <td class="px-6 py-4">
        <button data-id="${u.id}" class="edit-user-btn px-2 py-1 bg-[#37A0C9] text-white rounded hover:bg-[#2c8aa8] cursor-pointer">Modifier</button>
        <button data-id="${u.id}" class="delete-user-btn px-2 py-1 bg-red-400 text-white rounded hover:bg-red-500 cursor-pointer">Supprimer</button>
      </td>`;
        frag.appendChild(tr);
    });
    document.getElementById('users-table-body').appendChild(frag);

    // Attach event listeners to the new buttons
    attachUserButtonListeners();
}

function handleUserError(message = 'Impossible de charger les utilisateurs.') {
    const errEl = document.getElementById('user-form-error');
    errEl.innerText = message;
    errEl.classList.remove('hidden');
}

function clearUserFormErrors() {
    // Hide the main error message
    document.getElementById('user-form-error').classList.add('hidden');

    // Hide all field-specific error messages
    const errorFields = ['email-error', 'firstname-error', 'lastname-error', 'password-error', 'roles-error'];
    errorFields.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.classList.add('hidden');
            element.textContent = '';
        }
    });
}

function displayValidationErrors(errors) {
    for (const [field, message] of Object.entries(errors)) {
        const errorElement = document.getElementById(`${field}-error`);
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.remove('hidden');
        } else {
            // If no specific error field exists, show in the general error area
            const generalError = document.getElementById('user-form-error');
            generalError.textContent = `${field}: ${message}`;
            generalError.classList.remove('hidden');
        }
    }
}

function checkRoleCompatibility(role, checkbox) {
    const incompatibleRoles = INCOMPATIBLE_ROLES[role.value];
    const isSelected = checkbox.checked;

    if (incompatibleRoles) {
        incompatibleRoles.forEach(incompatibleRole => {
            const incompatibleCheckbox = document.getElementById(`role-${incompatibleRole}`);
            const incompatibleLabel = document.querySelector(`label[for="role-${incompatibleRole}"]`);

            if (incompatibleCheckbox) {
                // Check if the incompatible role should be disabled
                let shouldDisable = isSelected;

                // If we're unchecking, we need to check if any other roles conflict with the incompatible role
                if (!isSelected) {
                    // Find all roles that are incompatible with the incompatible role
                    const conflictingRoles = Object.entries(INCOMPATIBLE_ROLES)
                        .filter(([_, incompatibles]) => incompatibles.includes(incompatibleRole))
                        .map(([roleValue, _]) => roleValue);

                    // Check if any of these conflicting roles are still selected
                    shouldDisable = conflictingRoles.some(conflictingRole => {
                        const conflictingCheckbox = document.getElementById(`role-${conflictingRole}`);
                        return conflictingCheckbox && conflictingCheckbox.checked;
                    });
                }

                incompatibleCheckbox.disabled = shouldDisable;
                incompatibleLabel.classList.toggle('opacity-50', shouldDisable);
            }
        });
    }
}

function populateRolesCheckboxes(selectedRoles = []) {
    const rolesContainer = document.getElementById('user-roles');
    rolesContainer.innerHTML = '';

    AVAILABLE_ROLES.forEach(role => {
        const isChecked = selectedRoles.includes(role.value);
        const div = document.createElement('div');
        div.className = 'flex items-center';
        div.innerHTML = `
            <input type="checkbox" id="role-${role.value}" name="roles[]" value="${role.value}" 
                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                   ${isChecked ? 'checked' : ''}>
            <label for="role-${role.value}" class="ml-2 block text-sm text-gray-900">
                ${role.label}
            </label>
        `;
        rolesContainer.appendChild(div);

        // Add event listener to checkbox for role incompatibilities => disable incompatible roles
        const checkbox = document.getElementById(`role-${role.value}`);
        checkbox.addEventListener('change', () => checkRoleCompatibility(role, checkbox));
    });

    // Check role compatibilities AFTER all checkboxes are created
    AVAILABLE_ROLES.forEach(role => {
        const checkbox = document.getElementById(`role-${role.value}`);
        checkRoleCompatibility(role, checkbox);
    });
}

function resetUserForm() {
    const form = document.getElementById('user-form');
    form.reset();
    document.getElementById('user-id').value = '';
    clearUserFormErrors();
    populateRolesCheckboxes();

    // Always add ROLE_USER by default (hidden from UI but included in submission)
    const hiddenRoleUser = document.createElement('input');
    hiddenRoleUser.type = 'hidden';
    hiddenRoleUser.name = 'roles[]';
    hiddenRoleUser.value = 'ROLE_USER';
    document.getElementById('user-roles').appendChild(hiddenRoleUser);
}

function openUserModal(title = 'Ajouter un utilisateur', isCreating = true) {
    document.getElementById('user-modal-title').textContent = title;
    const passwordField = document.getElementById('password-field');

    // Show password field only for new users
    if (isCreating) {
        passwordField.classList.remove('hidden');
        document.getElementById('user-password').required = true;
    } else {
        passwordField.classList.add('hidden');
        document.getElementById('user-password').required = false;
    }

    document.getElementById('user-modal').classList.remove('hidden');
}

function closeUserModal() {
    document.getElementById('user-modal').classList.add('hidden');
    resetUserForm();
}

function getSelectedRoles() {
    const checkboxes = document.querySelectorAll('#user-roles input[type="checkbox"]:checked');
    return Array.from(checkboxes).map(checkbox => checkbox.value);
}

async function fetchUserById(userId) {
    try {
        return await apiService.getUser(userId);
    } catch (error) {
        console.error('Error fetching user:', error);
        handleUserError('Impossible de récupérer les données de l\'utilisateur.');
        return null;
    }
}

async function populateUserForm(userId) {
    try {
        const user = await fetchUserById(userId);
        if (!user) return;

        document.getElementById('user-id').value = user.id;
        document.getElementById('user-email').value = user.email;
        document.getElementById('user-firstname').value = user.firstname;
        document.getElementById('user-lastname').value = user.lastname;

        // Populate roles checkboxes
        populateRolesCheckboxes(user.roles);

        openUserModal('Modifier l\'utilisateur', false);
    } catch (error) {
        console.error('Error populating user form:', error);
        handleUserError('Impossible de charger les données de l\'utilisateur.');
    }
}

async function saveUser() {
    clearUserFormErrors();

    const userId = document.getElementById('user-id').value;
    const isNewUser = !userId;

    // Get form data
    const userData = {
        email: document.getElementById('user-email').value,
        firstname: document.getElementById('user-firstname').value,
        lastname: document.getElementById('user-lastname').value,
        roles: getSelectedRoles(),
        _token: document.getElementById('user-csrf-token').value
    };

    // Add password only if provided (required for new users, optional for updates)
    const password = document.getElementById('user-password').value;
    if (password) {
        userData.password = password;
    }

    try {
        let result;
        if (isNewUser) {
            result = await apiService.createUser(userData);
        } else {
            result = await apiService.updateUser(userId, userData);
        }

        if (result.status === 'error') {
            if (result.form && result.form.errors) {
                displayValidationErrors(result.form.errors);
            } else {
                handleUserError(result.message || 'Une erreur est survenue lors de l\'enregistrement.');
            }
            return;
        }

        // Successfully saved
        closeUserModal();

        // Refresh the users list
        window.userManager.reload();

    } catch (error) {
        console.error('Error saving user:', error);
        handleUserError('Une erreur est survenue lors de l\'enregistrement.');
    }
}

async function showDeleteConfirmation(userId) {
    const confirmed = await confirm(
        'Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.',
        'Supprimer',
        'Annuler'
    );

    if (confirmed) {
        await deleteUser(userId);
    }
}

async function deleteUser(userId) {
    try {
        const success = await apiService.deleteUser(userId);

        if (!success) {
            notify('Erreur lors de la suppression de l\'utilisateur.', 'error');
            return;
        }

        // Successfully deleted, refresh the list
        window.userManager.reload();

        // Show success notification
        notify('Utilisateur supprimé avec succès.', 'success');

    } catch (error) {
        console.error('Error deleting user:', error);
        notify('Une erreur est survenue lors de la suppression de l\'utilisateur.', 'error');
    }
}

function attachUserButtonListeners() {
    // Edit user buttons
    document.querySelectorAll('.edit-user-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            const userId = e.target.dataset.id;
            populateUserForm(userId);
        });
    });

    // Delete user buttons
    document.querySelectorAll('.delete-user-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            const userId = e.target.dataset.id;
            showDeleteConfirmation(userId);
        });
    });
}

function setupUserModalEvents() {
    // Add user button
    document.getElementById('add-user-btn').addEventListener('click', () => {
        resetUserForm();
        openUserModal('Ajouter un utilisateur', true);
    });

    // Close modal buttons
    document.getElementById('close-modal-btn').addEventListener('click', closeUserModal);
    document.getElementById('cancel-btn').addEventListener('click', closeUserModal);

    // Save user button
    document.getElementById('save-user-btn').addEventListener('click', saveUser);
}

export function initUsers() {
    // Initialize the InfiniteScrollManager for users list
    window.userManager = new InfiniteScrollManager({
        panelId: 'panel-2',
        tableBodyId: 'users-table-body',
        sentinelId: 'user-scroll-sentinel',
        paginationLoaderId: 'users-pagination-loader',
        endMessageId: 'end-of-users',
        initialLoaderId: 'initial-user-loader',
        searchInputId: 'user-search',
        apiEndpoint: '/api/user',
        renderFn: renderUsers,
        errorHandler: handleUserError
    });

    // Setup all modal related events
    setupUserModalEvents();
}

window.initUsers = initUsers;