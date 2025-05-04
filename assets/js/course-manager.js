import { escapeHtml, InfiniteScrollManager, confirm, notify } from './utils.js';
import { apiService } from './api-service.js';

/**
 * Render course groups in the table
 * @param {Array} groups - Array of course group objects
 * @param {string} courseSlug - Course slug for identification
 * @returns {DocumentFragment} - Document fragment with group rows
 */
function renderCourseGroups(groups, courseSlug) {
    const frag = document.createDocumentFragment();
    groups.forEach(g => {
        const tr = document.createElement('tr');
        tr.classList.add('group-row');
        tr.dataset.courseSlug = courseSlug;
        tr.dataset.groupId = g.id;

        // Handle schedule data from the DTO
        const scheduledCourse = g.scheduledCourse || {};
        let time = '';

        // Extract schedule info from different possible formats
        if (g.schedule) {
            // Format from direct group data
            time = g.schedule.startTime && g.schedule.endTime
                ? `${g.schedule.startTime} - ${g.schedule.endTime}`
                : (g.schedule.start_time && g.schedule.end_time ? `${g.schedule.start_time} - ${g.schedule.end_time}` : '');
        } else if (scheduledCourse.startTime && scheduledCourse.endTime) {
            // Format from scheduledCourse DTO
            time = `${scheduledCourse.startTime} - ${scheduledCourse.endTime}`;
        }

        tr.innerHTML = `
        <td class="px-6 py-3"></td>
        <td class="px-6 py-3" colspan="2">→ ${escapeHtml(g.name)} (<em>Salle</em>: ${escapeHtml(g.room)})</td>
        <td class="px-6 py-3">${g.schedule.day} ${time}</td>
        <td class="px-6 py-3 flex gap-2">
            <button data-group-id="${g.id}" data-group-name="${escapeHtml(g.name)}" class="manage-members-of-group-btn px-2 py-1 bg-[#37A0C9] rounded text-white hover:bg-[#2c8aa8] cursor-pointer">Membres</button>
            <button data-group-id="${g.id}" class="edit-group-btn px-2 py-1 bg-[#37A0C9] rounded text-white hover:bg-[#2c8aa8] cursor-pointer">Modifier</button>
            <button data-group-id="${g.id}" class="delete-group-btn px-2 py-1 bg-red-400 rounded text-white hover:bg-red-500 cursor-pointer">Supprimer</button>
        </td>
        `;
        frag.appendChild(tr);
    });

    return frag;
}

/**
 * Render courses in the table
 * @param {Array} courses - Array of course objects
 */
function renderCourses(courses) {
    const frag = document.createDocumentFragment();
    courses.forEach(c => {
        const tr = document.createElement('tr');
        tr.classList.add('course-row');
        tr.dataset.courseId = c.id;

        // Extract slug from the target property or direct slug property
        tr.dataset.courseSlug = c.slug || c.target?.substring(c.target.lastIndexOf('/') + 1);

        tr.innerHTML = `
        <td class="px-6 py-4">${escapeHtml(c.id)}</td>
        <td class="px-6 py-4">${escapeHtml(c.name)}</td>
        <td class="px-6 py-4">${escapeHtml(c.description)}</td>
        <td class="px-6 py-4"></td>
        <td class="px-6 py-4">
            <button data-course-id="${c.id}" data-course-slug="${tr.dataset.courseSlug}" class="edit-course-btn px-2 py-1 bg-[#37A0C9] rounded text-white hover:bg-[#2c8aa8] cursor-pointer">Modifier</button>
            <button data-course-id="${c.id}" data-course-slug="${tr.dataset.courseSlug}" class="delete-course-btn px-2 py-1 bg-red-400 rounded text-white hover:bg-red-500 cursor-pointer">Supprimer</button>
            <button data-course-id="${c.id}" data-course-slug="${tr.dataset.courseSlug}" class="add-group-to-course-btn px-2 py-1 bg-[#37A0C9] rounded text-white hover:bg-[#2c8aa8] cursor-pointer">+ Groupe</button>
        </td>
        `;
        frag.appendChild(tr);

        // If course has groups, render them below the course row
        if (c.groups && c.groups.length > 0) {
            frag.appendChild(renderCourseGroups(c.groups, tr.dataset.courseSlug));
        }
    });

    document.getElementById('course-table-body').appendChild(frag);
}

/**
 * Handle course-related errors
 * @param {Error} err - Error object
 */
function handleCourseError(err) {
    console.error('Course error:', err);
    notify('Une erreur est survenue lors du chargement des cours.', 'error');
}

/**
 * Initialize course manager
 */
export function initCourses() {
    // Initialize course components
    initCourseModal();
    initGroupModal();
    initGroupUserModal();

    // Set up event delegation for course and group actions
    document.addEventListener('click', handleCourseActions);

    // Initialize infinite scroll for courses
    window.courseManager = new InfiniteScrollManager({
        panelId: 'panel-1',
        tableBodyId: 'course-table-body',
        sentinelId: 'course-scroll-sentinel',
        paginationLoaderId: 'pagination-loader',
        endMessageId: 'end-of-course',
        initialLoaderId: 'initial-course-loader',
        searchInputId: 'course-search',
        apiEndpoint: '/api/course',
        renderFn: renderCourses,
        errorHandler: handleCourseError
    });

    // Make sure the day select is updated when the group modal opens
    updateDaySelectOptions();
}

/**
 * Initialize course modal
 */
function initCourseModal() {
    const modal = document.getElementById('course-modal');
    const form = document.getElementById('course-form');
    const closeBtn = document.getElementById('course-close-btn');
    const cancelBtn = document.getElementById('course-cancel-btn');
    const saveBtn = document.getElementById('course-save-btn');
    const addCourseBtn = document.getElementById('add-course-btn');

    // Open modal for adding a new course
    addCourseBtn.addEventListener('click', () => {
        document.getElementById('course-modal-title').textContent = 'Ajouter une UE';
        form.reset();
        document.getElementById('course-id').value = '';
        document.getElementById('course-error').classList.add('hidden');
        modal.classList.remove('hidden');
    });

    // Close modal functionality
    [closeBtn, cancelBtn].forEach(btn => {
        btn.addEventListener('click', () => {
            modal.classList.add('hidden');
        });
    });

    // Save course
    saveBtn.addEventListener('click', async () => {
        const courseId = document.getElementById('course-id').value;
        const courseSlug = courseId ? document.querySelector(`.course-row[data-course-id="${courseId}"]`)?.dataset.courseSlug : '';
        const name = document.getElementById('course-name').value;
        const description = document.getElementById('course-description').value;
        const imageFileInput = document.getElementById('course-image-file');
        const imageFile = imageFileInput?.files?.[0] || null;

        if (!name || !description) {
            document.getElementById('course-error').textContent = 'Veuillez remplir tous les champs obligatoires.';
            document.getElementById('course-error').classList.remove('hidden');
            return;
        }

        try {
            const courseData = {
                name,
                description
            };

            if (courseId && courseSlug) {
                // Edit existing course
                await apiService.updateCourse(courseSlug, courseData, imageFile);
            } else {
                // Create new course
                await apiService.createCourse(courseData, imageFile);
            }

            // Hide modal and reload courses
            modal.classList.add('hidden');
            window.courseManager.reload();

            // Show success notification
            notify(
                courseId ? 'Cours mis à jour avec succès' : 'Cours créé avec succès',
                'success'
            );
        } catch (error) {
            document.getElementById('course-error').textContent = error.message;
            document.getElementById('course-error').classList.remove('hidden');
        }
    });
}

/**
 * Update the day select options in the group modal
 */
function updateDaySelectOptions() {
    // Convert day input to select with French day names
    const dayInput = document.getElementById('group-day');
    if (dayInput && dayInput.tagName === 'INPUT') {
        const daySelect = document.createElement('select');
        daySelect.id = 'group-day';
        daySelect.name = 'dayOfWeek';
        daySelect.classList.add('w-full', 'px-3', 'py-2', 'border', 'border-gray-300', 'rounded-md', 'focus:outline-none', 'focus:ring-2', 'focus:ring-blue-500', 'focus:border-blue-500');
        daySelect.setAttribute('required', 'required');

        const dayNames = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        dayNames.forEach((name, index) => {
            const option = document.createElement('option');
            option.value = index;
            option.textContent = name;
            daySelect.appendChild(option);
        });

        dayInput.parentNode.replaceChild(daySelect, dayInput);
    }
}

/**
 * Initialize group modal
 */
function initGroupModal() {
    const modal = document.getElementById('group-modal');
    const form = document.getElementById('group-form');
    const closeBtn = document.getElementById('group-close-btn');
    const cancelBtn = document.getElementById('group-cancel-btn');
    const saveBtn = document.getElementById('group-save-btn');
    const addGroupBtn = document.getElementById('add-course-group-btn');

    // Create course selector if it doesn't exist
    let courseSelectorContainer = document.getElementById('group-course-container');
    if (!courseSelectorContainer) {
        courseSelectorContainer = document.createElement('div');
        courseSelectorContainer.id = 'group-course-container';
        courseSelectorContainer.className = 'mb-4';
        courseSelectorContainer.innerHTML = `
            <label for="group-course-select" class="block text-sm font-medium text-gray-700 mb-1">Cours <span class="text-red-500">*</span></label>
            <select id="group-course-select" name="courseUnitId" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="">Sélectionnez un cours</option>
            </select>
        `;

        // Insert after the first hidden input
        const hiddenInputs = form.querySelectorAll('input[type="hidden"]');
        if (hiddenInputs.length > 0) {
            hiddenInputs[hiddenInputs.length - 1].parentNode.insertBefore(courseSelectorContainer, hiddenInputs[hiddenInputs.length - 1].nextSibling);
        } else {
            form.insertBefore(courseSelectorContainer, form.firstChild);
        }
    }

    // Open modal for adding a new group (without course context)
    addGroupBtn.addEventListener('click', async () => {
        document.getElementById('group-modal-title').textContent = 'Ajouter un groupe';
        form.reset();
        document.getElementById('group-id').value = '';
        document.getElementById('group-course-id').value = '';
        document.getElementById('group-error').classList.add('hidden');

        // Show the course selector and load courses
        document.getElementById('group-course-container').classList.remove('hidden');
        await loadCoursesForGroupModal();

        modal.classList.remove('hidden');
    });

    // Close modal functionality
    [closeBtn, cancelBtn].forEach(btn => {
        btn.addEventListener('click', () => {
            modal.classList.add('hidden');
        });
    });

    // Save group
    saveBtn.addEventListener('click', async () => {
        const groupId = document.getElementById('group-id').value;
        let courseUnitId = document.getElementById('group-course-id').value;

        // If no course ID is set directly, get it from the select
        if (!courseUnitId) {
            courseUnitId = document.getElementById('group-course-select').value;
        }

        const name = document.getElementById('group-name').value;
        const room = document.getElementById('group-room').value;
        const dayOfWeek = document.getElementById('group-day').value;
        const startTime = document.getElementById('group-start').value;
        const endTime = document.getElementById('group-end').value;

        if (!name || !room || dayOfWeek === '' || !startTime || !endTime || !courseUnitId) {
            document.getElementById('group-error').textContent = 'Veuillez remplir tous les champs obligatoires.';
            document.getElementById('group-error').classList.remove('hidden');
            return;
        }

        try {
            const groupData = {
                name,
                room,
                dayOfWeek,
                startTime,
                endTime,
                courseUnitId
            };

            if (groupId) {
                // Edit existing group
                await apiService.updateCourseGroup(groupId, groupData);
            } else {
                // Create new group
                await apiService.createCourseGroup(groupData);
            }

            // Hide modal and reload courses
            modal.classList.add('hidden');
            window.courseManager.reload();

            // Show success notification
            notify(
                groupId ? 'Groupe mis à jour avec succès' : 'Groupe créé avec succès',
                'success'
            );
        } catch (error) {
            document.getElementById('group-error').textContent = error.message;
            document.getElementById('group-error').classList.remove('hidden');
        }
    });
}

/**
 * Load courses for the group modal
 * @returns {Promise<void>}
 */
async function loadCoursesForGroupModal() {
    try {
        const courses = await apiService.fetchCourses(100);
        const select = document.getElementById('group-course-select');

        // Clear existing options except the first one
        while (select.options.length > 1) {
            select.remove(1);
        }

        // Add course options
        courses.forEach(course => {
            const option = document.createElement('option');
            option.value = course.id;
            option.textContent = escapeHtml(course.name);
            select.appendChild(option);
        });
    } catch (error) {
        console.error('Error loading courses:', error);
        notify('Erreur lors du chargement des cours', 'error');
    }
}

/**
 * Initialize modal for adding users to a group
 */
function initGroupUserModal() {
    // Create the modal if it doesn't exist
    if (!document.getElementById('group-user-modal')) {
        const modal = document.createElement('div');
        modal.id = 'group-user-modal';
        modal.className = 'fixed inset-0 bg-gray-500/75 flex items-center justify-center hidden z-50';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-labelledby', 'group-user-modal-title');
        modal.setAttribute('aria-modal', 'true');

        modal.innerHTML = `
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 overflow-hidden" role="document">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 id="group-user-modal-title" class="text-lg font-medium text-gray-900">Ajouter un utilisateur au groupe</h3>
                <button id="group-user-close-btn" class="text-gray-400 hover:text-gray-500" aria-label="Fermer">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="px-6 py-4">
                <form id="group-user-form">
                    <input type="hidden" id="group-user-group-id" name="groupId">
                    <div id="group-user-error" class="mb-4 text-red-500 hidden"></div>
                    
                    <div class="mb-4">
                        <label for="group-user-select" class="block text-sm font-medium text-gray-700 mb-1">Utilisateur <span class="text-red-500">*</span></label>
                        <select id="group-user-select" name="userId" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Sélectionnez un utilisateur</option>
                            <!-- Options will be loaded dynamically -->
                        </select>
                    </div>
                </form>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-2">
                <button id="group-user-cancel-btn" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Annuler
                </button>
                <button id="group-user-save-btn" class="px-4 py-2 bg-[#37A0C9] text-white rounded-md hover:bg-[#2c8aa8] focus:outline-none focus:ring-2 focus:ring-[#37A0C9] focus:ring-offset-2">
                    Ajouter
                </button>
            </div>
        </div>
        `;

        document.body.appendChild(modal);

        // Add event listeners to buttons
        const closeBtn = document.getElementById('group-user-close-btn');
        const cancelBtn = document.getElementById('group-user-cancel-btn');
        const saveBtn = document.getElementById('group-user-save-btn');

        // Close modal functionality
        [closeBtn, cancelBtn].forEach(btn => {
            btn.addEventListener('click', () => {
                modal.classList.add('hidden');
            });
        });

        // Save user to group
        saveBtn.addEventListener('click', async () => {
            const groupId = document.getElementById('group-user-group-id').value;
            const userId = document.getElementById('group-user-select').value;

            if (!userId) {
                document.getElementById('group-user-error').textContent = 'Veuillez sélectionner un utilisateur.';
                document.getElementById('group-user-error').classList.remove('hidden');
                return;
            }

            try {
                await apiService.addUserToGroup(groupId, userId);

                // Hide modal and reload courses
                modal.classList.add('hidden');
                window.courseManager.reload();

                // Show success notification
                notify('Utilisateur ajouté au groupe avec succès', 'success');
            } catch (error) {
                document.getElementById('group-user-error').textContent = error.message;
                document.getElementById('group-user-error').classList.remove('hidden');
            }
        });
    }
}

/**
 * Handle course-related actions through event delegation
 * @param {Event} event - Click event
 */
async function handleCourseActions(event) {
    const target = event.target;

    // Edit course button
    if (target.matches('.edit-course-btn')) {
        event.preventDefault();
        const courseSlug = target.dataset.courseSlug;
        editCourse(courseSlug);
    }

    // Delete course button
    else if (target.matches('.delete-course-btn')) {
        event.preventDefault();
        const courseSlug = target.dataset.courseSlug;
        deleteCourse(courseSlug);
    }

    // Add group to course button
    else if (target.matches('.add-group-to-course-btn')) {
        event.preventDefault();
        const courseId = target.dataset.courseId;
        const modal = document.getElementById('group-modal');

        document.getElementById('group-modal-title').textContent = 'Ajouter un groupe';
        document.getElementById('group-form').reset();
        document.getElementById('group-id').value = '';
        document.getElementById('group-course-id').value = courseId;
        document.getElementById('group-error').classList.add('hidden');

        // Hide the course selector since we already have a course
        document.getElementById('group-course-container').classList.add('hidden');

        modal.classList.remove('hidden');
    }

    // Edit group button
    else if (target.matches('.edit-group-btn')) {
        event.preventDefault();
        const groupId = target.dataset.groupId;
        editGroup(groupId);
    }

    // Delete group button
    else if (target.matches('.delete-group-btn')) {
        event.preventDefault();
        const groupId = target.dataset.groupId;
        deleteGroup(groupId);
    }

    // Manage group members button
    else if (target.matches('.manage-members-of-group-btn')) {
        event.preventDefault();
        const groupId = target.dataset.groupId;
        const groupName = target.dataset.groupName;

        // Access the global group members manager instance
        if (window.groupMembersManagerInstance) {
            window.groupMembersManagerInstance.open(groupId, groupName);
        }
    }
}

/**
 * Edit course functionality
 * @param {string} courseSlug - Course slug to edit
 */
async function editCourse(courseSlug) {
    try {
        // Fetch course data using the API service
        const course = await apiService.getCourse(courseSlug);

        // Check if we got valid data
        if (!course || !course.id) {
            throw new Error('Impossible de charger les données du cours');
        }

        // Populate form
        const form = document.getElementById('course-form');
        document.getElementById('course-id').value = course.id;
        document.getElementById('course-name').value = course.name;
        document.getElementById('course-description').value = course.description;

        // Update modal title
        document.getElementById('course-modal-title').textContent = 'Modifier une UE';

        // Clear any previous errors
        document.getElementById('course-error').classList.add('hidden');

        // Clear the file input
        /** @type {HTMLInputElement} */
        const fileInput = document.getElementById('course-image-file');
        if (fileInput) {
            fileInput.value = '';
        }

        // Show modal
        document.getElementById('course-modal').classList.remove('hidden');
    } catch (error) {
        notify(error.message, 'error');
    }
}

/**
 * Delete course functionality
 * @param {string} courseSlug - Course slug to delete
 */
async function deleteCourse(courseSlug) {
    try {
        const confirmed = await confirm(
            'Êtes-vous sûr de vouloir supprimer ce cours ? Cette action est irréversible.',
            'Supprimer',
            'Annuler'
        );

        if (!confirmed) return;

        await apiService.deleteCourse(courseSlug);

        // Reload courses
        window.courseManager.reload();

        // Show success notification
        notify('Cours supprimé avec succès', 'success');
    } catch (error) {
        notify(error.message, 'error');
    }
}

/**
 * Edit group functionality
 * @param {string} groupId - Group ID to edit
 */
async function editGroup(groupId) {
    try {
        // Fetch group data using the API service
        const group = await apiService.getCourseGroup(groupId);

        // Check if we got valid data
        if (!group || !group.id) {
            throw new Error('Impossible de charger les données du groupe');
        }

        // Populate form
        const form = document.getElementById('group-form');
        document.getElementById('group-id').value = group.id;

        // Handle course unit data from different possible formats
        const courseUnit = group.courseUnit || (group.scheduledCourse ? { id: group.scheduledCourse.id } : null);
        if (courseUnit && courseUnit.id) {
            document.getElementById('group-course-id').value = courseUnit.id;
        }

        document.getElementById('group-name').value = group.name;
        document.getElementById('group-room').value = group.room;

        // Extract schedule data from different possible formats
        const schedule = group.schedule || {};
        const dayOfWeek = schedule.dayOfWeek !== undefined ? schedule.dayOfWeek :
            (group.scheduledCourse && group.scheduledCourse.schedule ?
                group.scheduledCourse.schedule.dayOfWeek : 0);

        // Select the correct day
        if (dayOfWeek !== undefined) {
            const daySelect = document.getElementById('group-day');
            for (let i = 0; i < daySelect.options.length; i++) {
                if (parseInt(daySelect.options[i].value) === parseInt(dayOfWeek)) {
                    daySelect.selectedIndex = i;
                    break;
                }
            }
        }

        // Format times properly
        const startTime = schedule.startTime || group.scheduledCourse?.startTime || '';
        const endTime = schedule.endTime || group.scheduledCourse?.endTime || '';

        document.getElementById('group-start').value = startTime;
        document.getElementById('group-end').value = endTime;

        // Update modal title
        document.getElementById('group-modal-title').textContent = 'Modifier un groupe';

        // Hide the course selector since we already have a course
        document.getElementById('group-course-container').classList.add('hidden');

        // Clear any previous errors
        document.getElementById('group-error').classList.add('hidden');

        // Show modal
        document.getElementById('group-modal').classList.remove('hidden');
    } catch (error) {
        notify(error.message, 'error');
    }
}

/**
 * Delete group functionality
 * @param {string} groupId - Group ID to delete
 */
async function deleteGroup(groupId) {
    try {
        const confirmed = await confirm(
            'Êtes-vous sûr de vouloir supprimer ce groupe ? Cette action est irréversible.',
            'Supprimer',
            'Annuler'
        );

        if (!confirmed) return;

        await apiService.deleteCourseGroup(groupId);

        // Reload courses
        window.courseManager.reload();

        // Show success notification
        notify('Groupe supprimé avec succès', 'success');
    } catch (error) {
        notify(error.message, 'error');
    }
}

// Make the function available globally
window.initCourses = initCourses;