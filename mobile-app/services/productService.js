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
  async getAll() {
    try {
      const response = await axios.get(API_ENDPOINTS.PRODUCTS, {
        params: {
          action: 'getAll'
        }
      });

      if (response.data.success) {
        return response.data.products;
      } else {
        throw new Error(response.data.message);
      }
    } catch (error) {
      console.error('Get all products error:', error);
      throw error;
    }
  }

  // Add new product
  async addProduct(productData) {
    try {
      const response = await axios.post(API_ENDPOINTS.PRODUCTS, {
        action: 'add',
        ...productData
      });

      if (response.data.success) {
        return response.data;
      } else {
        throw new Error(response.data.message);
      }
    } catch (error) {
      console.error('Add product error:', error);
      throw error;
    }
  }

  // Update product
  async updateProduct(productId, productData) {
    try {
      const response = await axios.post(API_ENDPOINTS.PRODUCTS, {
        action: 'update',
        id: productId,
        ...productData
      });

      if (response.data.success) {
        return response.data;
      } else {
        throw new Error(response.data.message);
      }
    } catch (error) {
      console.error('Update product error:', error);
      throw error;
    }
  }

  // Delete product
  async deleteProduct(productId) {
    try {
      const response = await axios.post(API_ENDPOINTS.PRODUCTS, {
        action: 'delete',
        id: productId
      });

      if (response.data.success) {
        return response.data;
      } else {
        throw new Error(response.data.message);
      }
    } catch (error) {
      console.error('Delete product error:', error);
      throw error;
    }
  }

  // Legacy method - keep for backward compatibility
  async getAllProducts() {
    return this.getAll();
  }
}

export default new ProductService();
