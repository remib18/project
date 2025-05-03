import { fetchWithErrorHandling } from './utils.js';

/**
 * API Service - Centralizes all API calls
 */
class ApiService {
    /**
     * Get CSRF token from meta tag
     * @param {string} tokenName - Meta tag name attribute for the CSRF token
     * @returns {string|null} - The CSRF token
     */
    getCsrfToken(tokenName) {
        const metaTag = document.querySelector(`meta[name="${tokenName}"]`);
        if (!metaTag) {
            console.error(`CSRF token "${tokenName}" not found`);
            return null;
        }
        return metaTag.content;
    }

    // ======== COURSE API METHODS ========

    /**
     * Fetch courses with pagination and search
     * @param {number} limit - Number of items per page
     * @param {number} offset - Offset for pagination
     * @param {string} searchTerm - Search term
     * @returns {Promise<Array>} - Array of course objects
     */
    async fetchCourses(limit = 20, offset = 0, searchTerm = '') {
        try {
            const url = `/api/course?limit=${limit}&offset=${offset}&search=${encodeURIComponent(searchTerm)}`;
            console.log('Fetching courses from:', url);

            // Add a timeout to prevent hanging requests
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout

            const response = await fetch(url, {
                signal: controller.signal
            }).finally(() => clearTimeout(timeoutId));

            if (!response.ok) {
                console.error('Course fetch failed with status:', response.status);

                // Try to get more detailed error info
                let errorMsg = `HTTP error: ${response.status}`;
                try {
                    const errorData = await response.json();
                    if (errorData && errorData.message) {
                        errorMsg = errorData.message;
                        console.error('Server error message:', errorData.message);
                    }
                } catch (e) {
                    // If we can't parse the error response, just use the status code
                    console.error('Could not parse error response');
                }

                // For 500 errors, return an empty array instead of throwing to prevent breaking the UI
                if (response.status === 500) {
                    console.warn('Returning empty array for 500 error to prevent UI breaking');
                    return [];
                }

                throw new Error(errorMsg);
            }

            const data = await response.json();
            console.log('Courses response data format:', typeof data, Array.isArray(data) ? 'Array' : 'Not Array');

            // Ensure we always return an array, even if data format changes
            if (Array.isArray(data)) {
                return data;
            } else if (data && data.status === 'success' && Array.isArray(data.data)) {
                return data.data;
            } else if (data && typeof data === 'object') {
                // Last resort - if we got an object that's not in the expected format
                console.warn('Unexpected data format, attempting to extract course data');
                return Object.values(data).filter(item =>
                    item && typeof item === 'object' && item.name && item.description
                );
            }

            console.warn('Could not parse course data, returning empty array');
            return [];
        } catch (error) {
            if (error.name === 'AbortError') {
                console.error('Request timed out');
                return [];
            }
            console.error('Error fetching courses:', error);
            // Return empty array to prevent UI breaking
            return [];
        }
    }

    /**
     * Get a course by slug
     * @param {string} slug - Course slug
     * @returns {Promise<object>} - Course object
     */
    async getCourse(slug) {
        const response = await fetch(`/api/course/${slug}`);

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || `Failed to get course: ${response.status}`);
        }

        const data = await response.json();
        return data.status === 'success' ? data.data : data;
    }

    /**
     * Create a new course
     * @param {object} courseData - Course data
     * @returns {Promise<object>} - Created course
     */
    async createCourse(courseData) {
        try {
            // Try different CSRF token sources
            let token = this.getCsrfToken('csrf-token-course_creation');

            // Fallback to generic token
            if (!token) {
                token = document.querySelector('input[name="_token"]')?.value;
            }

            if (!token) {
                // Last resort - check if there's any CSRF token in the document
                const anyToken = document.querySelector('meta[name^="csrf-token"]');
                if (anyToken) {
                    token = anyToken.content;
                    console.log('Using fallback CSRF token:', anyToken.name);
                }
            }

            if (!token) {
                throw new Error('CSRF token not found');
            }

            // Log details for debugging
            console.log('Creating course with data:', courseData);
            console.log('Using CSRF token from:', token ? 'Found token' : 'No token');

            // Use the correct endpoint - the route is simply '/api/course' (POST)
            // NOT '/api/course/new'
            const endpoint = '/api/course';

            console.log('Posting to:', endpoint);
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    ...courseData,
                    _token: token
                })
            });

            if (!response.ok) {
                let errorMsg = `Failed to create course: ${response.status}`;
                try {
                    const errorData = await response.json();
                    if (errorData && errorData.message) {
                        errorMsg = errorData.message;
                    } else if (errorData && errorData.errors) {
                        errorMsg = Object.values(errorData.errors).join(', ');
                    }
                } catch (e) {
                    console.error('Could not parse error response:', e);
                }
                throw new Error(errorMsg);
            }

            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error creating course:', error);
            throw error;
        }
    }

    /**
     * Update a course
     * @param {string} slug - Course slug
     * @param {object} courseData - Updated course data
     * @returns {Promise<object>} - Updated course
     */
    async updateCourse(slug, courseData) {
        const csrfToken = this.getCsrfToken('csrf-token-course_edition');

        // If token is missing, try to use the token from the form
        const tokenFromForm = document.querySelector('input[name="_token"]')?.value;
        const token = csrfToken || tokenFromForm;

        if (!token) {
            throw new Error('CSRF token not found');
        }

        const response = await fetch(`/api/course/${slug}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                ...courseData,
                _token: token
            })
        });

        const data = await response.json();

        if (!response.ok) {
            if (data.errors) {
                let errorMessage = '';
                for (const field in data.errors) {
                    if (data.errors[field]) {
                        errorMessage += `${data.errors[field]}\n`;
                    }
                }
                throw new Error(errorMessage || data.message || 'Une erreur est survenue');
            }
            throw new Error(data.message || 'Une erreur est survenue');
        }

        return data;
    }

    /**
     * Delete a course
     * @param {string} slug - Course slug
     * @returns {Promise<object>} - Response data
     */
    async deleteCourse(slug) {
        try {
            // Try multiple possible token sources
            let token = null;

            // Try specific delete token
            token = this.getCsrfToken('csrf-token-course_deletion');
            if (!token) {
                // Try user deletion token (might be used as a generic token)
                token = this.getCsrfToken('csrf-token-user_deletion');
            }
            if (!token) {
                // Try any form token
                token = document.querySelector('input[name="_token"]')?.value;
            }
            if (!token) {
                // Last resort - check if there's any CSRF token in the document
                const anyToken = document.querySelector('meta[name^="csrf-token"]');
                if (anyToken) {
                    token = anyToken.content;
                    console.log('Using fallback CSRF token from:', anyToken.name);
                }
            }

            if (!token) {
                throw new Error('CSRF token not found');
            }

            console.log('Deleting course with slug:', slug);
            console.log('Using CSRF token:', token ? 'Token found' : 'No token');

            const response = await fetch(`/api/course/${slug}/delete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    _token: token
                })
            });

            // Check if we got redirected (common for delete operations)
            if (response.redirected) {
                console.log('Delete request was redirected - likely successful');
                return { status: 'success' };
            }

            // Normal response handling
            let data;
            try {
                data = await response.json();
            } catch (e) {
                // If we can't parse JSON, check if the response is OK anyway
                if (response.ok) {
                    return { status: 'success' };
                }
                throw new Error('Failed to parse server response');
            }

            if (!response.ok) {
                throw new Error(data.message || 'Erreur lors de la suppression du cours');
            }

            return data;
        } catch (error) {
            console.error('Error deleting course:', error);
            throw error;
        }
    }

    // ======== COURSE GROUP API METHODS ========

    /**
     * Get a course group by ID
     * @param {number} groupId - Group ID
     * @returns {Promise<object>} - Group object
     */
    async getCourseGroup(groupId) {
        try {
            const url = `/api/course/group/${groupId}`;
            console.log('Fetching course group from:', url);

            const response = await fetch(url);

            if (!response.ok) {
                let errorMsg = 'Erreur lors du chargement du groupe';
                try {
                    const errorData = await response.json();
                    if (errorData && errorData.message) {
                        errorMsg = errorData.message;
                    }
                } catch (e) {
                    console.error('Could not parse error response:', e);
                }
                throw new Error(errorMsg);
            }

            const data = await response.json();
            console.log('Course group data:', data);

            // Check if we got a success status but empty or missing data
            if (data.status === 'success' && !data.data) {
                // Use a fallback approach - fetch the group members to get group info
                console.log('No data found in success response, attempting to fetch group details differently');
                try {
                    const membersUrl = `/api/course/group/${groupId}/members`;
                    const membersResponse = await fetch(membersUrl);
                    if (membersResponse.ok) {
                        const membersData = await membersResponse.json();
                        if (membersData && (membersData.group || membersData.data)) {
                            return membersData.group || membersData.data;
                        }
                    }
                } catch (e) {
                    console.error('Failed fallback attempt:', e);
                }

                throw new Error('Impossible de charger les données du groupe: Empty data');
            }

            return data.status === 'success' ? data.data : data;
        } catch (error) {
            console.error('Error fetching course group:', error);
            throw error;
        }
    }

    /**
     * Create a new course group
     * @param {object} groupData - Group data
     * @returns {Promise<object>} - Created group
     */
    async createCourseGroup(groupData) {
        const csrfToken = this.getCsrfToken('csrf-token-course_group_create');

        // If token is missing, try to use the token from the form
        const tokenFromForm = document.querySelector('input[name="_token"]')?.value;
        const token = csrfToken || tokenFromForm;

        if (!token) {
            throw new Error('CSRF token not found');
        }

        const response = await fetch('/api/course/group/new', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                ...groupData,
                _token: token
            })
        });

        const data = await response.json();

        if (!response.ok) {
            if (data.errors) {
                let errorMessage = '';
                for (const field in data.errors) {
                    if (data.errors[field]) {
                        errorMessage += `${data.errors[field]}\n`;
                    }
                }
                throw new Error(errorMessage || data.message || 'Une erreur est survenue');
            }
            throw new Error(data.message || 'Une erreur est survenue');
        }

        return data;
    }

    /**
     * Update a course group
     * @param {number} groupId - Group ID
     * @param {object} groupData - Updated group data
     * @returns {Promise<object>} - Updated group
     */
    async updateCourseGroup(groupId, groupData) {
        const csrfToken = this.getCsrfToken('csrf-token-course_group_create');

        // If token is missing, try to use the token from the form
        const tokenFromForm = document.querySelector('input[name="_token"]')?.value;
        const token = csrfToken || tokenFromForm;

        if (!token) {
            throw new Error('CSRF token not found');
        }

        const response = await fetch(`/api/course/group/${groupId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                ...groupData,
                _token: token
            })
        });

        const data = await response.json();

        if (!response.ok) {
            if (data.errors) {
                let errorMessage = '';
                for (const field in data.errors) {
                    if (data.errors[field]) {
                        errorMessage += `${data.errors[field]}\n`;
                    }
                }
                throw new Error(errorMessage || data.message || 'Une erreur est survenue');
            }
            throw new Error(data.message || 'Une erreur est survenue');
        }

        return data;
    }

    /**
     * Delete a course group
     * @param {number} groupId - Group ID
     * @returns {Promise<object>} - Response data
     */
    async deleteCourseGroup(groupId) {
        const csrfToken = this.getCsrfToken('csrf-token-course_group_delete');

        // If token is missing, try to use the token from the form
        const tokenFromForm = document.querySelector('input[name="_token"]')?.value;
        const token = csrfToken || tokenFromForm;

        if (!token) {
            throw new Error('CSRF token not found');
        }

        const response = await fetch(`/api/course/group/${groupId}/delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                _token: token
            })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Erreur lors de la suppression du groupe');
        }

        return data;
    }

    /**
     * Get group members
     * @param {number} groupId - Group ID
     * @param {number} limit - Number of items per page
     * @param {number} offset - Offset for pagination
     * @returns {Promise<object>} - Group members with pagination info
     */
    async getGroupMembers(groupId, limit = 20, offset = 0) {
        const url = `/api/course/group/${groupId}/members?limit=${limit}&offset=${offset}`;
        const response = await fetch(url);

        if (!response.ok) {
            throw new Error(`Failed to get group members: ${response.status}`);
        }

        const data = await response.json();
        return {
            members: data.data || [],
            total: data.total || 0,
            hasMore: data.hasMore || false
        };
    }

    /**
     * Get eligible users to add to a group
     * @param {number} groupId - Group ID
     * @param {string} role - Role filter ('teacher' or 'student')
     * @returns {Promise<Array>} - Array of eligible user objects
     */
    async getEligibleGroupUsers(groupId, role) {
        try {
            const url = `/api/course/group/${groupId}/eligible-users/${role}`;
            console.log('Fetching eligible users from:', url);

            // Add a timeout to prevent hanging requests
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout

            const response = await fetch(url, {
                signal: controller.signal
            }).finally(() => clearTimeout(timeoutId));

            if (!response.ok) {
                // Try to get more specific error information
                let errorMsg = `Error loading ${role === 'teacher' ? 'professors' : 'students'}`;
                try {
                    const errorData = await response.json();
                    if (errorData && errorData.message) {
                        errorMsg = errorData.message;
                        console.error('Server error message:', errorData.message);
                    }
                } catch (e) {
                    console.error('Could not parse error response:', e);
                }

                // For backend method name mismatch, we can try a fallback approach
                if (response.status === 500 &&
                    (errorMsg.includes('findUsersWithRoleNotInGroup') ||
                        errorMsg.includes('method name must start with'))) {

                    console.warn('Method name mismatch detected, using fallback approach');
                    // Fallback to fetching all users and filtering client-side
                    const allUsersUrl = `/api/user?limit=100`;
                    const allUsersResponse = await fetch(allUsersUrl);

                    if (allUsersResponse.ok) {
                        const allUsers = await allUsersResponse.json();

                        // Get current group members to exclude them
                        const membersUrl = `/api/course/group/${groupId}/members`;
                        const membersResponse = await fetch(membersUrl);
                        let currentMembers = [];

                        if (membersResponse.ok) {
                            const membersData = await membersResponse.json();
                            currentMembers = membersData.data || [];
                        }

                        // Extract member IDs for faster lookup
                        const memberIds = new Set(currentMembers.map(member => member.id));

                        // Filter users by role and not in group
                        const roleValue = role === 'teacher' ? 'ROLE_TEACHER' : 'ROLE_STUDENT';
                        const eligibleUsers = Array.isArray(allUsers) ?
                            allUsers.filter(user =>
                                user.roles.includes(roleValue) &&
                                !memberIds.has(user.id)
                            ) : [];

                        console.log(`Found ${eligibleUsers.length} eligible ${role}s using fallback method`);
                        return eligibleUsers;
                    }
                }

                throw new Error(errorMsg);
            }

            const data = await response.json();
            console.log('Eligible users data:', data);

            // Check all possible data formats
            if (data.status === 'success' && Array.isArray(data.data)) {
                return data.data;
            } else if (Array.isArray(data)) {
                return data;
            } else if (data && typeof data === 'object' && data.status === 'success') {
                // If we have a success status but no data property
                return [];
            }

            // Last resort - try to extract users from the response
            if (data && typeof data === 'object') {
                return Object.values(data).filter(item =>
                    item && typeof item === 'object' && item.email && item.roles
                );
            }

            return [];
        } catch (error) {
            if (error.name === 'AbortError') {
                console.error('Request timed out');
                return [];
            }
            console.error(`Error fetching eligible ${role}s:`, error);
            // Return empty array to prevent UI breaking
            return [];
        }
    }

    /**
     * Add a user to a group
     * @param {number} groupId - Group ID
     * @param {number} userId - User ID
     * @returns {Promise<object>} - Response data
     */
    async addUserToGroup(groupId, userId) {
        const csrfToken = document.querySelector('meta[name="csrf-token-group-members"]')?.content;

        // If token is missing, try to use the token from the form
        const tokenFromForm = document.querySelector('input[name="_token"]')?.value;
        const token = csrfToken || tokenFromForm;

        if (!token) {
            throw new Error('CSRF token not found');
        }

        const response = await fetch(`/api/course/group/${groupId}/add`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                userId,
                _token: token
            })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Error adding user to group');
        }

        return data;
    }

    /**
     * Remove a user from a group
     * @param {number} groupId - Group ID
     * @param {number} userId - User ID
     * @returns {Promise<object>} - Response data
     */
    async removeUserFromGroup(groupId, userId) {
        const csrfToken = document.querySelector('meta[name="csrf-token-group-members"]')?.content;

        // If token is missing, try to use the token from the form
        const tokenFromForm = document.querySelector('input[name="_token"]')?.value;
        const token = csrfToken || tokenFromForm;

        if (!token) {
            throw new Error('CSRF token not found');
        }

        const response = await fetch(`/api/course/group/${groupId}/remove`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                userId,
                _token: token
            })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Error removing user from group');
        }

        return data;
    }

    // ======== USER API METHODS ========

    /**
     * Fetch users with pagination and search
     * @param {number} limit - Number of items per page
     * @param {number} offset - Offset for pagination
     * @param {string} searchTerm - Search term
     * @returns {Promise<Array>} - Array of user objects
     */
    async fetchUsers(limit = 20, offset = 0, searchTerm = '') {
        const url = `/api/user?limit=${limit}&offset=${offset}&search=${encodeURIComponent(searchTerm)}`;
        try {
            console.log('Fetching users from:', url);
            const response = await fetch(url);

            if (!response.ok) {
                // Get more detailed error information if possible
                let errorMsg = `HTTP error: ${response.status}`;
                try {
                    const errorData = await response.json();
                    if (errorData && errorData.message) {
                        errorMsg = errorData.message;
                    }
                } catch (e) {
                    // If we can't parse the error response, just use the status code
                    console.error('Could not parse error response:', e);
                }
                throw new Error(errorMsg);
            }

            const data = await response.json();
            console.log('Users data received:', data);

            // Ensure we always return an array, even if data format changes
            return Array.isArray(data) ? data : (data.data || []);
        } catch (error) {
            console.error('Error fetching users:', error);
            // Return an empty array instead of throwing to prevent infinite error loop
            return [];
        }
    }

    /**
     * Get a user by ID
     * @param {number} userId - User ID
     * @returns {Promise<object>} - User object
     */
    async getUser(userId) {
        const response = await fetch(`/api/user/${userId}`);

        if (!response.ok) {
            throw new Error('Failed to fetch user data');
        }

        const data = await response.json();
        return data.user;
    }

    /**
     * Create a new user
     * @param {object} userData - User data
     * @returns {Promise<object>} - Response data
     */
    async createUser(userData) {
        const response = await fetch('/api/user/new', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(userData)
        });

        return await response.json();
    }

    /**
     * Update a user
     * @param {number} userId - User ID
     * @param {object} userData - Updated user data
     * @returns {Promise<object>} - Response data
     */
    async updateUser(userId, userData) {
        const response = await fetch(`/api/user/${userId}/edit`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(userData)
        });

        return await response.json();
    }

    /**
     * Delete a user
     * @param {number} userId - User ID
     * @returns {Promise<boolean>} - Success status
     */
    async deleteUser(userId) {
        const csrfToken = this.getCsrfToken('csrf-token-user_deletion');

        // If token is missing, try to use the token from the form
        const tokenFromForm = document.querySelector('input[name="_token"]')?.value;
        const token = csrfToken || tokenFromForm;

        if (!token) {
            throw new Error('CSRF token not found');
        }

        const response = await fetch(`/api/user/${userId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                _token: token
            })
        });

        const data = await response.json();
        return data.status === 'success';
    }
}

// Export a singleton instance
export const apiService = new ApiService();