// API Configuration
// Update this with your Laragon server URL
// For local development with Expo, use your computer's IP address
// Example: http://192.168.1.100/point-shift_pos-system

export const API_BASE_URL = 'http://192.168.0.36/point-shift_pos-system/api';

// Alternative: Use your local hostname
// export const API_BASE_URL = 'http://localhost/point-shift_pos-system/api';

export const API_ENDPOINTS = {
  LOGIN: `${API_BASE_URL}/auth.php`,
  PRODUCTS: `${API_BASE_URL}/products.php`,
  PRODUCT_BY_BARCODE: `${API_BASE_URL}/products.php?action=getByBarcode`,
  CATEGORIES: `${API_BASE_URL}/categories.php`,
};

// ⚠️ IMPORTANT: 
// 1. Replace 192.168.0.36 with your actual computer's IP address
// 2. To find your IP: Open Command Prompt and type "ipconfig"
// 3. Look for "IPv4 Address" under your WiFi/Ethernet adapter
// 4. Make sure your phone and computer are on the same WiFi network
