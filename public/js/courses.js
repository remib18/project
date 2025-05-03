// State global pour pagination et filtres
const courseState = {
    page: 1,
    limit: 20,
    hasMore: true,
    loading: false,
    searchTerm: '',
    courses: []
};

// Sélecteurs
const tableBody = document.getElementById('course-table-body');
const paginationLoader = document.getElementById('pagination-loader');
const endOfCourseMessage = document.getElementById('end-of-course');
const searchInput = document.getElementById('course-search');
const addCourseBtn = document.getElementById('add-course-btn');

// Modals
const courseModal = document.getElementById('course-modal');
const courseForm = document.getElementById('course-form');
const courseError = document.getElementById('course-error');
const courseSaveBtn = document.getElementById('course-save-btn');
const courseCancelBtn = document.getElementById('course-cancel-btn');
const courseCloseBtn = document.getElementById('course-close-btn');

const groupModal = document.getElementById('group-modal');
const groupForm = document.getElementById('group-form');
const groupError = document.getElementById('group-error');
const groupSaveBtn = document.getElementById('group-save-btn');
const groupCancelBtn = document.getElementById('group-cancel-btn');
const groupCloseBtn = document.getElementById('group-close-btn');

function bindEvents() {
    searchInput.addEventListener('input', debounce(onSearch, 300));
    addCourseBtn.addEventListener('click', () => openCourseModal());
    courseCancelBtn.onclick = courseCloseBtn.onclick = () => closeCourseModal();
    courseSaveBtn.onclick = saveCourse;
    groupCancelBtn.onclick = groupCloseBtn.onclick = () => closeGroupModal();
    groupSaveBtn.onclick = saveGroup;

    // Infinite loading via IntersectionObserver
    const observer = new IntersectionObserver(entries => {
        if (entries[0].isIntersecting && courseState.hasMore && !courseState.loading) {
            fetchCourses();
        }
    }, { threshold: 0.5 });
    observer.observe(endOfCourseMessage);
}

async function fetchCourses(reset = false) {
    if (courseState.loading || !courseState.hasMore && !reset) return;
    courseState.loading = true;
    if (courseState.page === 1) {
        // loader initial déjà dans DOM
    } else {
        paginationLoader.classList.remove('hidden');
    }
    try {
        const offset = (courseState.page - 1) * courseState.limit;
        const url = `/api/course?limit=${courseState.limit}&offset=${offset}&search=${encodeURIComponent(courseState.searchTerm)}`;
        const res = await fetch(url);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();

        if (reset) {
            courseState.courses = data;
            courseState.page = 1;
        } else {
            courseState.courses = [...courseState.courses, ...data];
        }

        courseState.hasMore = data.length === courseState.limit;
        renderCourses();
        if (courseState.hasMore) courseState.page++;
        else endOfCourseMessage.classList.remove('hidden');

    } catch (err) {
        console.error('Erreur fetchCourses:', err);
        displayCourseError('Impossible de charger les cours');
    } finally {
        courseState.loading = false;
        paginationLoader.classList.add('hidden');
    }
}

function renderCourses() {
    if (courseState.courses.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">Aucun cours trouvé</td></tr>`;
        return;
    }
    tableBody.innerHTML = courseState.courses.map(unit => {
        const courseRow = `
      <tr id="course-row-${unit.id}" class="bg-white">
        <td class="px-6 py-4">${unit.id}</td>
        <td class="px-6 py-4 font-medium">${escapeHtml(unit.name)}</td>
        <td class="px-6 py-4">${escapeHtml(unit.description)}</td>
        <td class="px-6 py-4">-</td>
        <td class="px-6 py-4 flex gap-2">
          <button data-slug="${unit.slug}" class="edit-course-btn">Modifier</button>
          <button data-slug="${unit.slug}" class="delete-course-btn">Supprimer</button>
          <button data-slug="${unit.slug}" class="add-group-btn">+ Groupe</button>
        </td>
      </tr>
    `;
        const groupRows = unit.groups.map(group => `
      <tr class="bg-gray-50" id="group-row-${group.id}">
        <td></td>
        <td colspan="2">→ ${escapeHtml(group.name)} (<em>Salle</em>: ${escapeHtml(group.room)})</td>
        <td>${group.schedule.startTime} - ${group.schedule.endTime}</td>
        <td class="flex gap-2">
          <button data-id="${group.id}" class="edit-group-btn">✎</button>
          <button data-id="${group.id}" class="delete-group-btn">🗑</button>
        </td>
      </tr>
    `).join('');
        return courseRow + groupRows;
    }).join('');

    // Rebind buttons
    document.querySelectorAll('.edit-course-btn').forEach(btn => btn.onclick = () => openCourseModal(btn.dataset.slug));
    document.querySelectorAll('.delete-course-btn').forEach(btn => btn.onclick = () => deleteCourse(btn.dataset.slug));
    document.querySelectorAll('.add-group-btn').forEach(btn => btn.onclick = () => openGroupModalForCourse(btn.dataset.slug));
    document.querySelectorAll('.edit-group-btn').forEach(btn => btn.onclick = () => openGroupModal(btn.dataset.id));
    document.querySelectorAll('.delete-group-btn').forEach(btn => btn.onclick = () => deleteGroup(btn.dataset.id));
}

// Modals handlers
function openCourseModal(slug) { /* similaire à avant, fetch + remplissage si slug */ }
function closeCourseModal() { courseModal.classList.add('hidden'); }

async function saveCourse() { /* POST /api/course ou /api/course/{slug} */ }
async function deleteCourse(slug) { /* confirm + POST /api/course/{slug}/delete */ }

function openGroupModalForCourse(courseSlug) {
    groupForm.reset();
    document.getElementById('group-course-id').value = courseSlug;
    openGroupModal();
}
function openGroupModal(id) { /* si id, fetch details, sinon nouveau */ }
function closeGroupModal() { groupModal.classList.add('hidden'); }

async function saveGroup() { /* POST /api/course/group ou /api/course/group/{id} */ }
async function deleteGroup(id) { /* confirm + POST /api/course/group/{id}/delete */ }

// Utilitaires
function escapeHtml(s) { /* identique à users */ }
function displayCourseError(msg) { /* affiche dans tableBody */ }
function debounce(fn, wait) { /* identique */ }

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    fetchCourses();
    bindEvents();
});