import { escapeHtml, InfiniteScrollManager } from './utils.js';

function renderCourseGroups(groups) {
    const frag = document.createDocumentFragment();
    groups.forEach(g => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
        <td></td>
        <td colspan="2">→ ${escapeHtml(g.name)} (<em>Salle</em>: ${escapeHtml(g.room)})</td>
        <td>${g.schedule.start_time} - ${g.schedule.end_time}</td>
        <td class="flex gap-2">
            <button data-id="${g.id}" class="px-2 py-1 bg-[#37A0C9] rounded text-white hover:bg-[#2c8aa8] cursor-pointer">+ Utilisateur</button>
            <button data-id="${g.id}" class="px-2 py-1 bg-[#37A0C9] rounded text-white hover:bg-[#2c8aa8] cursor-pointer">Modifier</button>
            <button data-id="${g.id}" class="px-2 py-1 bg-red-400 rounded text-white hover:bg-red-500 cursor-pointer">Supprimer</button>
        </td>
        `;
        frag.appendChild(tr);
    });

    return frag;
}

function renderCourses(courses) {
    const frag = document.createDocumentFragment();
    courses.forEach(c => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
        <td class="px-6 py-4">${escapeHtml(c.id)}</td>
        <td class="px-6 py-4">${escapeHtml(c.name)}</td>
        <td class="px-6 py-4">${escapeHtml(c.description)}</td>
        <td class="px-6 py-4"></td>
        <td class="px-6 py-4">
            <button data-slug="${c.slug}" class="px-2 py-1 bg-[#37A0C9] rounded text-white hover:bg-[#2c8aa8] cursor-pointer">Modifier</button>
            <button data-slug="${c.slug}" class="px-2 py-1 bg-red-400 rounded text-white hover:bg-red-500 cursor-pointer">Supprimer</button>
        </td>
        `;
        frag.appendChild(tr);
        frag.appendChild(renderCourseGroups(c.groups));
    });
    document.getElementById('course-table-body').appendChild(frag);
}

function handleCourseError() {
    const errEl = document.getElementById('course-error');
    errEl.innerText = 'Impossible de charger les cours.';
    errEl.classList.remove('hidden');
}

export function initCourses() {
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
}
window.initCourses = initCourses;