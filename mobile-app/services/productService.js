import axios from 'axios';
import { API_ENDPOINTS } from '../config/api';

class ProductService {
  // Get product by barcode
  async getProductByBarcode(barcode) {
    try {
      const response = await axios.get(API_ENDPOINTS.PRODUCT_BY_BARCODE, {
        params: {
          action: 'getByBarcode',
          barcode: barcode
        }
      });

      if (response.data.success) {
        return { success: true, product: response.data.product };
      } else {
        return { success: false, message: response.data.message };
      }
    } catch (error) {
      console.error('Get product error:', error);
      return { 
        success: false, 
        message: error.response?.data?.message || 'Network error. Please check your connection.' 
      };
    }
  }

  // Search products
  async searchProducts(query) {
    try {
      const response = await axios.get(API_ENDPOINTS.PRODUCTS, {
        params: {
          action: 'search',
          query: query
        }
      });

      if (response.data.success) {
        return { success: true, products: response.data.products };
      } else {
        return { success: false, message: response.data.message };
      }
    } catch (error) {
      console.error('Search products error:', error);
      return { 
        success: false, 
        message: error.response?.data?.message || 'Network error. Please check your connection.' 
      };
    }
  }

  // Get all products
  async getAllProducts() {
    try {
      const response = await axios.get(API_ENDPOINTS.PRODUCTS, {
        params: {
          action: 'getAll'
        }
      });

      if (response.data.success) {
        return { success: true, products: response.data.products };
      } else {
        return { success: false, message: response.data.message };
      }
    } catch (error) {
      console.error('Get all products error:', error);
      return { 
        success: false, 
        message: error.response?.data?.message || 'Network error. Please check your connection.' 
      };
    }
  }
}

export default new ProductService();
