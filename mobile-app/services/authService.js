import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { API_ENDPOINTS } from '../config/api';

class AuthService {
  // Login user
  async login(username, password) {
    try {
      const formData = new FormData();
      formData.append('action', 'login');
      formData.append('username', username);
      formData.append('password', password);

      const response = await axios.post(API_ENDPOINTS.LOGIN, formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });

      if (response.data.success) {
        // Store user data
        await AsyncStorage.setItem('user', JSON.stringify(response.data.user));
        await AsyncStorage.setItem('token', response.data.token || '');
        return { success: true, user: response.data.user };
      } else {
        return { success: false, message: response.data.message };
      }
    } catch (error) {
      console.error('Login error:', error);
      return { 
        success: false, 
        message: error.response?.data?.message || 'Network error. Please check your connection.' 
      };
    }
  }

  // Logout user
  async logout() {
    try {
      await AsyncStorage.removeItem('user');
      await AsyncStorage.removeItem('token');
      return { success: true };
    } catch (error) {
      console.error('Logout error:', error);
      return { success: false, message: 'Failed to logout' };
    }
  }

  // Get current user
  async getCurrentUser() {
    try {
      const userJson = await AsyncStorage.getItem('user');
      return userJson ? JSON.parse(userJson) : null;
    } catch (error) {
      console.error('Get user error:', error);
      return null;
    }
  }

  // Check if user is logged in
  async isAuthenticated() {
    const user = await this.getCurrentUser();
    return user !== null;
  }
}

export default new AuthService();
