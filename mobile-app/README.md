# PointShift Mobile Scanner App

A React Native mobile application built with Expo for scanning product barcodes in the PointShift POS System.

## 📱 Features

- **User Authentication** - Secure login with existing POS system credentials
- **Barcode Scanner** - Scan product barcodes using device camera
- **Product Details** - View complete product information after scanning
- **Real-time Stock Status** - See current stock levels and low stock alerts
- **Cross-platform** - Works on both iOS and Android

## 🚀 Getting Started

### Prerequisites

- Node.js (v14 or higher)
- npm or yarn
- Expo Go app installed on your phone ([iOS](https://apps.apple.com/app/expo-go/id982107779) | [Android](https://play.google.com/store/apps/details?id=host.exp.exponent))
- Laragon running with PointShift POS System

### Installation

1. **Navigate to the mobile app directory:**
   ```bash
   cd mobile-app
   ```

2. **Install dependencies:**
   ```bash
   npm install
   ```

3. **Configure API endpoint:**
   
   Open `config/api.js` and update the `API_BASE_URL` with your computer's IP address:
   
   ```javascript
   export const API_BASE_URL = 'http://YOUR_IP_ADDRESS/point-shift_pos-system/api';
   ```
   
   **To find your IP address:**
   - Open Command Prompt (Windows)
   - Type: `ipconfig`
   - Look for "IPv4 Address" (e.g., 192.168.1.100)

4. **Start the development server:**
   ```bash
   npm start
   ```
   or
   ```bash
   npx expo start
   ```

5. **Open in Expo Go:**
   - Scan the QR code with Expo Go app
   - Make sure your phone and computer are on the same WiFi network

## 📖 Usage

### Login
1. Open the app
2. Enter your PointShift POS username and password
3. Tap "Login"

### Scanning Products
1. After login, the camera will open automatically
2. Point your camera at a product barcode
3. The app will vibrate and display product details
4. Tap "Scan Another Product" to scan again

### Supported Barcode Types
- QR Code
- EAN-13
- EAN-8
- Code 128
- Code 39
- UPC-A
- UPC-E

## 🔧 Configuration

### API Endpoints

The app connects to these PHP endpoints:

- `POST /api/auth.php?action=login` - User authentication
- `GET /api/products.php?action=getByBarcode&barcode={code}` - Get product by barcode
- `GET /api/products.php?action=search&query={text}` - Search products
- `GET /api/products.php?action=getAll` - Get all products

### Network Configuration

**Important:** Your phone must be on the same WiFi network as your computer running Laragon.

If you have connection issues:
1. Check your computer's firewall settings
2. Make sure Laragon's Apache is running
3. Verify the IP address in `config/api.js` is correct
4. Try accessing `http://YOUR_IP/point-shift_pos-system/api/products.php` in your phone's browser

## 📁 Project Structure

```
mobile-app/
├── App.js                 # Main app entry point
├── app.json              # Expo configuration
├── package.json          # Dependencies
├── babel.config.js       # Babel configuration
├── config/
│   └── api.js           # API configuration
├── screens/
│   ├── LoginScreen.js   # Login screen
│   └── ScannerScreen.js # Barcode scanner screen
├── services/
│   ├── authService.js   # Authentication service
│   └── productService.js # Product API service
└── components/          # Reusable components (future)
```

## 🛠️ Technologies Used

- **React Native** - Mobile framework
- **Expo** - Development platform
- **Expo Camera** - Camera and barcode scanning
- **React Navigation** - Screen navigation
- **Axios** - HTTP requests
- **AsyncStorage** - Local data storage

## 🔐 Security Notes

- User credentials are stored locally using AsyncStorage
- API requests use the same authentication as the web POS system
- Token-based authentication is implemented
- Always use HTTPS in production

## 📱 Building for Production

### Android APK
```bash
expo build:android
```

### iOS IPA
```bash
expo build:ios
```

For more information, see [Expo Build Documentation](https://docs.expo.dev/build/introduction/)

## 🐛 Troubleshooting

### Camera not working
- Make sure you granted camera permissions
- Restart the app
- Check if your device has a working camera

### Cannot connect to API
- Verify your computer's IP address
- Check if Laragon is running
- Ensure both devices are on the same WiFi
- Test the API endpoint in a browser: `http://YOUR_IP/point-shift_pos-system/api/auth.php`

### Barcode not scanning
- Ensure barcode is clear and well-lit
- Try different angles
- Make sure the barcode is within the frame
- Check if the barcode type is supported

## 📞 Support

For issues or questions, contact your system administrator.

## 📄 License

This project is part of the PointShift POS System.

---

**Version:** 1.0.0  
**Last Updated:** October 2025
