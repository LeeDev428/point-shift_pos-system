# PointShift POS System

A modern Point of Sale (POS) system built with PHP, MySQL, and Bootstrap. This system provides separate interfaces for administrators and staff members with role-based access control.

## Features

- **User Authentication**: Secure login and registration system
- **Role-Based Access**: Separate dashboards for Admin and Staff
- **Dashboard Overview**: Real-time statistics and recent activity
- **Inventory Management**: Product and stock management (Admin only)
- **Point of Sale**: Transaction processing interface
- **Sales Analysis**: Comprehensive sales reports and analytics
- **User Management**: User account management (Admin only)
- **Responsive Design**: Mobile-friendly interface

## Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Laragon, XAMPP, or WAMP (for local development)

### Setup Instructions

1. **Clone or Download** the project to your web directory:
   ```
   d:\laragon\www\point-shift_pos-system\
   ```

2. **Database Setup**:
   - Open phpMyAdmin or your MySQL client
   - Create a new database named `pointshift_pos`
   - Import the `database.sql` file to create tables and sample data

3. **Configuration**:
   - Open `config.php`
   - Update database credentials if needed:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'pointshift_pos');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     ```

4. **Access the Application**:
   - Open your browser and navigate to: `http://localhost/point-shift_pos-system`
   - Use the demo credentials to login:
     - **Username**: admin
     - **Password**: admin123

## File Structure

```
point-shift_pos-system/
├── config.php              # Database configuration and helper functions
├── index.php               # Landing page
├── login.php               # User login page
├── register.php            # User registration page
├── logout.php              # Logout functionality
├── layout.php              # Main layout template for dashboard
├── dashboard.php           # Dashboard overview page
├── inventory.php           # Inventory management (Admin only)
├── pos.php                 # Point of sale interface
├── sales_analysis.php      # Sales reports and analytics
├── user_management.php     # User management (Admin only)
├── database.sql            # Database schema and sample data
└── README.md               # This file
```

## Default Accounts

The system comes with a default admin account:
- **Username**: admin
- **Email**: admin@pointshift.com
- **Password**: admin123
- **Role**: Admin

## User Roles

### Admin
- Full access to all features
- Can manage inventory
- Can manage users
- Can view all reports
- Can access dashboard overview

### Staff
- Limited access to core features
- Can access POS system
- Can view sales analysis
- Can access dashboard overview
- Cannot manage inventory or users

## Database Schema

### Users Table
- `id` (Primary Key)
- `username` (Unique)
- `email` (Unique)
- `password` (Hashed)
- `role` (admin/staff)
- `first_name`
- `last_name`
- `status` (active/inactive)
- `created_at`
- `updated_at`

### Products Table
- `id` (Primary Key)
- `name`
- `category_id` (Foreign Key)
- `price`
- `stock_quantity`
- `low_stock_threshold`
- `barcode`
- `description`
- `status` (active/inactive)
- `created_at`
- `updated_at`

### Orders Table
- `id` (Primary Key)
- `order_number` (Unique)
- `user_id` (Foreign Key)
- `total_amount`
- `status` (pending/completed/cancelled)
- `created_at`

### Categories Table
- `id` (Primary Key)
- `name`
- `description`
- `created_at`

### Order Items Table
- `id` (Primary Key)
- `order_id` (Foreign Key)
- `product_id` (Foreign Key)
- `quantity`
- `unit_price`
- `total_price`

## Security Features

- Password hashing using PHP's `password_hash()`
- Session-based authentication
- Role-based access control
- SQL injection prevention using prepared statements
- CSRF protection (to be implemented)
- Input validation and sanitization

## Technologies Used

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework**: Bootstrap 5.3.0
- **Icons**: Font Awesome 6.4.0
- **Database Layer**: PDO (PHP Data Objects)

## Browser Support

- Chrome (Latest)
- Firefox (Latest)
- Safari (Latest)
- Edge (Latest)
- Mobile browsers

## Future Enhancements

- Complete POS functionality with barcode scanning
- Advanced inventory management features
- Detailed sales analytics with charts
- Receipt generation and printing
- Multi-location support
- API integration for external systems
- Advanced reporting features
- Mobile app integration

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source and available under the [MIT License](LICENSE).

## Support

For support and questions, please contact the development team or create an issue in the repository.

---

**PointShift POS System** - Streamlining retail operations with modern technology.
