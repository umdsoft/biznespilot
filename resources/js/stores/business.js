import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';

export const useBusinessStore = defineStore('business', () => {
  const businesses = ref([]);
  const loading = ref(false);

  async function fetchBusinesses() {
    loading.value = true;
    try {
      const response = await axios.get('/api/v1/businesses');
      businesses.value = response.data.data;
      return response.data;
    } catch (error) {
      throw error.response?.data || error;
    } finally {
      loading.value = false;
    }
  }

  async function createBusiness(data) {
    loading.value = true;
    try {
      const response = await axios.post('/api/v1/businesses', data);
      businesses.value.push(response.data.data);
      return response.data;
    } catch (error) {
      throw error.response?.data || error;
    } finally {
      loading.value = false;
    }
  }

  async function updateBusiness(id, data) {
    loading.value = true;
    try {
      const response = await axios.put(`/api/v1/businesses/${id}`, data);
      const index = businesses.value.findIndex(b => b.id === id);
      if (index !== -1) {
        businesses.value[index] = response.data.data;
      }
      return response.data;
    } catch (error) {
      throw error.response?.data || error;
    } finally {
      loading.value = false;
    }
  }

  async function deleteBusiness(id) {
    loading.value = true;
    try {
      await axios.delete(`/api/v1/businesses/${id}`);
      businesses.value = businesses.value.filter(b => b.id !== id);
    } catch (error) {
      throw error.response?.data || error;
    } finally {
      loading.value = false;
    }
  }

  return {
    businesses,
    loading,
    fetchBusinesses,
    createBusiness,
    updateBusiness,
    deleteBusiness,
  };
});
