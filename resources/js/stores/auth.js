import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null);
  const token = ref(localStorage.getItem('auth_token') || null);
  const currentBusiness = ref(JSON.parse(localStorage.getItem('current_business')) || null);

  const isAuthenticated = computed(() => !!token.value);

  // Set axios default headers
  if (token.value) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
  }

  if (currentBusiness.value) {
    axios.defaults.headers.common['X-Business-ID'] = currentBusiness.value.id;
  }

  async function register(credentials) {
    try {
      const response = await axios.post('/api/v1/auth/register', credentials);

      token.value = response.data.data.token;
      user.value = response.data.data.user;

      localStorage.setItem('auth_token', token.value);
      axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;

      return response.data;
    } catch (error) {
      throw error.response?.data || error;
    }
  }

  async function login(credentials) {
    try {
      const response = await axios.post('/api/v1/auth/login', credentials);

      token.value = response.data.data.token;
      user.value = response.data.data.user;

      localStorage.setItem('auth_token', token.value);
      axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;

      // Set default business if user has businesses
      if (user.value.businesses && user.value.businesses.length > 0) {
        setCurrentBusiness(user.value.businesses[0]);
      }

      return response.data;
    } catch (error) {
      throw error.response?.data || error;
    }
  }

  async function logout() {
    try {
      await axios.post('/api/v1/auth/logout');
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      user.value = null;
      token.value = null;
      currentBusiness.value = null;

      localStorage.removeItem('auth_token');
      localStorage.removeItem('current_business');
      delete axios.defaults.headers.common['Authorization'];
      delete axios.defaults.headers.common['X-Business-ID'];
    }
  }

  async function fetchUser() {
    try {
      const response = await axios.get('/api/v1/auth/me');
      user.value = response.data.data;
      return response.data;
    } catch (error) {
      // Token might be invalid
      await logout();
      throw error;
    }
  }

  function setCurrentBusiness(business) {
    currentBusiness.value = business;
    localStorage.setItem('current_business', JSON.stringify(business));
    axios.defaults.headers.common['X-Business-ID'] = business.id;
  }

  return {
    user,
    token,
    currentBusiness,
    isAuthenticated,
    register,
    login,
    logout,
    fetchUser,
    setCurrentBusiness,
  };
});
