import axios from 'axios';
import { API_ENDPOINTS } from '../config/api';

const categoryService = {
  // Get all categories (Read-only)
  getAll: async () => {
    try {
      const response = await axios.get(API_ENDPOINTS.CATEGORIES, {
        params: { action: 'getAll' }
      });
      return response.data;
    } catch (error) {
      console.error('Error fetching categories:', error);
      throw error;
    }
  }
};

export default categoryService;
