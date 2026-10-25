# MedCare Patient Record and Care Coordination System

A comprehensive, integrated web-based healthcare system designed for a healthcare setting in Tupi, South Cotabato. The system transforms manual healthcare workflows into a centralized digital system for patient care, medication management, and care coordination.

## System Overview

MedCare is an integrated healthcare information system that connects:
- **Patient and Clinical Care**: Patient records, consultations, diagnoses, treatments, and prescriptions
- **Appointment Scheduling**: Doctor calendar plotting patient visits by appointment date, with status tracking and double-booking prevention
- **Medication and Medical Supply Inventory**: Stock management, expiration monitoring, and inventory alerts
- **Healthcare Care Coordination**: Team communication and treatment progress tracking
- **Health Education**: Patient-accessible health information resources

## Technology Stack

- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP
- **Database**: MySQL
- **Web Server**: Apache (via XAMPP)

## Features

### Patient and Clinical Care
- Patient registration and record management
- Medical history tracking
- Consultation recording
- Diagnosis and treatment management
- Prescription creation and management
- Patient priority classification (Low, Medium, High, Critical)

### Medication and Medical Supply Inventory
- Medication and medical supply tracking
- Stock quantity monitoring
- Low-stock alerts
- Expiration date monitoring
- Expired item alerts
- Inventory transaction logging
- Stock adjustment capabilities

### Healthcare Care Coordination
- Treatment progress tracking
- Healthcare team communication
- Patient-related updates
- Care coordination messaging

### Health Education
- Health education resource library
- Disease prevention materials
- Healthy living educational content
- Patient-accessible health information

## User Roles

### Doctor
- Access and manage patient information
- Record consultations, diagnoses, and treatments
- Create and manage prescriptions
- Check medication availability
- Set patient priority levels
- Track treatment progress
- Communicate with healthcare team

### Staff
- Manage patient information
- Manage medication and medical supply inventory
- Monitor stock levels and expiration dates
- Dispense medications
- Record treatment progress
- Manage health education resources
- Communicate with healthcare team

### Patient
- View personal information
- View treatment and prescription information
- Access health education library
- Track treatment progress

## Installation

### Prerequisites
- XAMPP (or equivalent PHP/MySQL environment)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser (Chrome, Firefox, Edge, Safari)

### Setup Instructions

1. **Place the Files**
   - Copy the entire `Fanugon_System` folder to your XAMPP `htdocs` directory
   - Path: `C:\xampp\htdocs\Fanugon_System\`

2. **Database Setup**
   - Start XAMPP Control Panel
   - Start Apache and MySQL services
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Import the database schema file:
     - File: `database/schema.sql`
     - This will create the `medcare_db` database with all required tables and sample data

3. **Configure Database Connection**
   - The database configuration is in `config/database.php`
   - Default settings:
     - Host: `localhost`
     - Database: `medcare_db`
     - Username: `root`
     - Password: (empty)
   - Modify if your MySQL credentials differ

4. **Access the System**
   - Open your web browser
   - Navigate to: `http://localhost/Fanugon_System/`
   - You will be redirected to the login page

## Default Login Credentials

The system comes with sample user accounts (password: `admin123` for all):

### Admin/Staff
- Username: `admin`
- Role: Staff
- Password: `admin123`

### Doctor
- Username: `doctor1`
- Role: Doctor
- Password: `admin123`

### Staff
- Username: `staff1`
- Role: Staff
- Password: `admin123`

### Sample Patient
- Name: Pedro Reyes
- Note: Patient accounts need to be linked to user accounts for login access

## System Architecture

### Directory Structure
```
Fanugon_System/
├── assets/
│   ├── css/
│   │   └── style.css          # Main stylesheet
│   └── js/                    # JavaScript files
├── config/
│   ├── config.php              # System configuration
│   └── database.php           # Database connection
├── database/
│   └── schema.sql             # Database schema
├── includes/
│   ├── auth.php               # Authentication functions
│   ├── header.php             # Page header
│   └── footer.php             # Page footer
├── pages/
│   ├── auth/
│   │   ├── login.php          # Login page
│   │   ├── logout.php         # Logout handler
│   │   └── unauthorized.php   # Unauthorized access page
│   ├── doctor/
│   │   ├── dashboard.php      # Doctor dashboard
│   │   ├── patients.php       # Patient management
│   │   ├── new_patient.php    # Register new patient
│   │   ├── view_patient.php   # View patient details
│   │   ├── new_consultation.php # Create consultation
│   │   ├── consultation_diagnosis.php # Record diagnosis/treatment
│   │   ├── view_consultation.php # View consultation details
│   │   ├── new_prescription.php # Create prescription
│   │   ├── view_prescription.php # View prescription details
│   │   ├── prescriptions.php  # Prescriptions list
│   │   ├── set_priority.php   # Set patient priority
│   │   ├── treatment_progress_list.php # Treatment progress list
│   │   ├── treatment_progress.php # Treatment progress tracker
│   │   └── communication.php  # Healthcare communication
│   ├── staff/
│   │   ├── dashboard.php      # Staff dashboard
│   │   ├── patients.php       # Patient management
│   │   ├── new_patient.php    # Register new patient
│   │   ├── view_patient.php   # View patient details
│   │   ├── edit_patient.php    # Edit patient information
│   │   ├── inventory.php       # Inventory management
│   │   ├── add_inventory.php  # Add inventory item
│   │   ├── view_inventory.php # View inventory item
│   │   ├── edit_inventory.php # Edit inventory item
│   │   ├── add_stock.php      # Stock transactions
│   │   ├── stock_alerts.php   # Stock alerts
│   │   ├── inventory_transactions.php # Transaction history
│   │   ├── prescriptions.php  # Prescription management
│   │   ├── view_prescription.php # View prescription details
│   │   ├── dispense_prescription.php # Dispense medications
│   │   ├── treatment_progress_list.php # Treatment progress list
│   │   ├── treatment_progress.php # Treatment progress tracker
│   │   ├── communication.php  # Healthcare communication
│   │   ├── health_education.php # Health education management
│   │   ├── view_health_education.php # View health education resource
│   │   └── edit_health_education.php # Edit health education resource
│   └── patient/
│       ├── dashboard.php      # Patient dashboard
│       ├── my_info.php        # Personal information
│       ├── my_treatments.php  # Treatment information
│       ├── prescriptions.php  # Prescription information
│       ├── view_prescription.php # View prescription details
│       ├── health_education.php # Health education library
│       └── view_health_education.php # View health education resource
├── index.php                  # Entry point
├── README.md                  # This file
└── SECURITY_REVIEW.md         # Security documentation
```

## Database Schema

The system uses a normalized MySQL database with the following main tables:

- `users` - User accounts and authentication
- `patients` - Patient information
- `patient_medical_history` - Medical history records
- `consultations` - Consultation records
- `patient_priority` - Patient priority classifications
- `diagnoses` - Diagnosis records
- `treatments` - Treatment records
- `prescriptions` - Prescription records
- `prescription_items` - Individual medications in prescriptions
- `inventory` - Medication and medical supply inventory
- `inventory_transactions` - Stock movement records
- `treatment_progress` - Treatment progress tracking
- `healthcare_communication` - Healthcare team messages
- `health_education` - Health education resources

## Key Integration Points

### Prescription ↔ Inventory Integration
- When creating a prescription, the system checks medication availability
- Prevents prescription of expired medications
- Prevents dispensing when insufficient stock
- Stock is automatically deducted when medications are dispensed
- All inventory transactions are logged

### Patient Record Integration
- All patient data is connected through the centralized patient record
- Consultations link to diagnoses, treatments, and prescriptions
- Treatment progress is linked to specific treatments
- Medical history is maintained per patient

### Healthcare Team Communication
- Messages can be linked to specific patients
- Supports different message types (general, patient updates, treatment updates)
- Read/unread status tracking
- Communication restricted to authorized healthcare personnel

## Security Features

- Password hashing using bcrypt
- Session-based authentication
- Role-based access control
- Server-side authorization validation
- SQL injection prevention (PDO prepared statements)
- XSS prevention (output escaping)
- Input validation and sanitization
- Patient data privacy (patients can only access their own records)

*See SECURITY_REVIEW.md for detailed security information*

## Testing Checklist

### Basic Functionality
- [ ] Login as admin, doctor, staff, and patient
- [ ] Verify role-based access control
- [ ] Test patient registration
- [ ] Test consultation creation
- [ ] Test diagnosis and treatment recording
- [ ] Test prescription creation
- [ ] Test inventory management
- [ ] Test stock alerts
- [ ] Test prescription dispensing
- [ ] Test treatment progress tracking
- [ ] Test healthcare communication
- [ ] Test health education library

### Integration Testing
- [ ] Test prescription to inventory integration
- [ ] Verify stock deduction on dispensing
- [ ] Test expired medication prevention
- [ ] Test insufficient stock prevention
- [ ] Verify patient record connectivity
- [ ] Test treatment progress linkage

### Security Testing
- [ ] Verify patient cannot access other patients' records
- [ ] Test URL parameter manipulation
- [ ] Verify role restrictions
- [ ] Test session timeout
- [ ] Verify input validation

## Troubleshooting

### Database Connection Issues
- Ensure MySQL service is running in XAMPP
- Verify database credentials in `config/database.php`
- Check that `medcare_db` database exists
- Ensure schema has been imported

### Login Issues
- Verify user exists in database
- Check password hashing is working correctly
- Ensure session management is functioning
- Clear browser cookies and try again

### Permission Issues
- Verify role assignments in database
- Check authorization functions in `includes/auth.php`
- Ensure session variables are set correctly

### Display Issues
- Verify CSS file is loading
- Check BASE_URL configuration in `config/config.php`
- Ensure assets folder permissions are correct

## Development Notes

This system was developed as an academic Information Technology project following the Agile Software Development Model. The implementation prioritizes:

1. **Integration**: All modules work together through a centralized database
2. **Data Integrity**: Proper relationships and validation throughout
3. **Role Security**: Clear separation of user permissions
4. **Usability**: Clean, professional interface for healthcare setting
5. **Practicality**: Features directly address the stated problems

## Future Enhancements

Potential improvements for future versions:
- Two-factor authentication
- Email notifications and alerts
- Mobile-responsive design optimization
- Advanced reporting and analytics
- Integration with external health systems
- Patient-facing appointment self-booking
- Recurring appointments
- Billing and payment processing
- File upload for medical documents
- Barcode/QR code scanning for inventory
- API integration for third-party services

## Support

For issues or questions about the MedCare system, please refer to:
- This README file for setup and usage
- SECURITY_REVIEW.md for security information
- Database schema in `database/schema.sql` for data structure
- Code comments for implementation details

## License

This project is developed as an academic project for educational purposes.

---

**MedCare Patient Record and Care Coordination System**  
Version 1.0  
Developed for Tupi, South Cotabato Healthcare Setting
