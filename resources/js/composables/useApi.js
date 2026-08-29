import axios from 'axios';

const api = axios.create({
    headers: {
        Accept: 'application/json',
    },
});

export const useApi = () => {
    return {
        async listPosts(page = 1, perPage = 10) {
            const { data } = await api.get('/api/posts', {
                params: { page, per_page: perPage },
            });
            return data;
        },

        async getPost(slug) {
            const { data } = await api.get(`/api/posts/${encodeURIComponent(slug)}`);
            return data;
        },

        async listCategories() {
            const { data } = await api.get('/api/categories');
            return data;
        },

        async listPostsByCategory(slug, page = 1, perPage = 10) {
            const { data } = await api.get(
                `/api/categories/${encodeURIComponent(slug)}/posts`,
                { params: { page, per_page: perPage } }
            );
            return data;
        },

        async searchPosts(query, page = 1, perPage = 10) {
            const { data } = await api.get('/api/search', {
                params: { q: query, page, per_page: perPage },
            });
            return data;
        },

        async getSidebar() {
            const { data } = await api.get('/api/sidebar');
            return data;
        },

        async getPage(slug) {
            const { data } = await api.get(`/api/pages/${encodeURIComponent(slug)}`);
            return data;
        },

        async getPages(parent = null) {
            const params = parent ? { parent } : {};
            const { data } = await api.get('/api/pages', { params });
            return data;
        },

        async getSiteSettings() {
            const { data } = await api.get('/api/site-settings');
            return data;
        },

        async getAuthor(username) {
            const { data } = await api.get(`/api/authors/${encodeURIComponent(username)}`);
            return data;
        },

        async getAuthorPosts(username, page = 1, perPage = 10) {
            const { data } = await api.get(
                `/api/authors/${encodeURIComponent(username)}/posts`,
                { params: { page, per_page: perPage } }
            );
            return data;
        },

        async getComments(slug) {
            const { data } = await api.get(`/api/posts/${encodeURIComponent(slug)}/comments`);
            return data;
        },

        async postComment(slug, payload) {
            const { data } = await api.post(
                `/api/posts/${encodeURIComponent(slug)}/comments`,
                payload
            );
            return data;
        },

        async subscribe(email) {
            const { data } = await api.post('/api/subscribe', { email });
            return data;
        },

        async getAdminStats() {
            const { data } = await api.get('/api/admin/stats');
            return data;
        },
    };
};

export default useApi;
