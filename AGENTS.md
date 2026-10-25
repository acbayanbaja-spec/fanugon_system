# MedCare System - Development Notes

## Project Information

**Project Name:** MedCare Patient Record and Care Coordination System  
**Location:** Tupi, South Cotabato  
**Development Model:** Agile Software Development  
**Technology Stack:** HTML, CSS, JavaScript, PHP, MySQL  

## Development Commands

### Database Setup
```bash
# Import database schema
mysql -u root -p medcare_db < database/schema.sql
```

### Web Server Access
```bash
# Start XAMPP services
# Access system at: http://localhost/Fanugon_System/
```

### Testing Checklist

#### Authentication & Authorization
- Login as admin (staff role)
- Login as doctor1 (doctor role)  
- Login as staff1 (staff role)
- Verify role-based access control
- Test session timeout
- Test logout functionality

#### Patient Management
- Register new patient (doctor and staff)
- View patient details
- Edit patient information (staff only)
- Search patients
- Set patient priority (doctor only)
- Schedule appointment from patient list (doctor only)

#### Appointment Calendar (Doctor)
- Open Calendar from the doctor sidebar
- View the mini month calendar + today's agenda on the Doctor Dashboard
- Switch between Month / Week / Day views
- Navigate between periods (previous / next / Today)
- View patient appointments plotted on the appointment date
- Filter by status (All, Scheduled, Confirmed, Completed, Cancelled, No Show)
- Only the logged-in doctor's appointments are ever returned (server-side `doctor_id` filter)
- Click an appointment to open its details, then Confirm / Complete / No Show / Cancel
- Create a new appointment (date, time, duration, type, reason, notes)
- Verify double-booking is rejected (overlapping time for the same doctor)
- Verify an adjacent (back-to-back) time is allowed
- Edit / reschedule an existing appointment

#### Patient Appointment Requests
- Log in as a patient and open "My Appointments" from the sidebar
- Request an appointment with a specific doctor, date, time, type and reason
- Confirm the request appears on the selected doctor's calendar
- Cancel an own scheduled/confirmed appointment
- Verify a patient cannot cancel another patient's appointment

#### Clinical Functions
- Create consultation
- Record diagnosis
- Record treatment
- Create prescription
- View consultation history
- Check medication availability

#### Inventory Management
- Add inventory item
- View inventory details
- Edit inventory item
- Add stock (stock in)
- Remove stock (stock out)
- View stock alerts
- View inventory transactions

#### Prescription & Inventory Integration
- Create prescription with medication
- Check availability before prescribing
- Dispense prescription
- Verify stock deduction
- Test expired medication prevention
- Test insufficient stock prevention

#### Treatment Progress
- View treatment progress list
- Add progress record
- View progress history
- Link progress to treatment

#### Communication
- Send message to staff
- Send message to doctor
- Mark messages as read
- View sent/received messages
- Link messages to patients

#### Health Education
- Create health education resource
- View health education resources
- Edit health education resources
- Publish resources
- Patient access to library

#### Patient Access
- Login as patient
- View personal information
- Request and cancel appointments (My Appointments)
- View treatments
- View prescriptions
- Access health education library
- Verify data privacy (cannot access other patients)

## Known Issues & Limitations

1. **Patient Login:** Patient accounts need to be linked to user accounts for full login functionality
2. **File Uploads:** No file upload functionality for medical documents
3. **Email Notifications:** No email notification system
4. **Mobile Optimization:** Basic responsive design, could be improved
5. **Real-time Updates:** No real-time features (WebSocket, etc.)
6. **Advanced Reporting:** Limited reporting capabilities
7. **Audit Logging:** Basic transaction logging, no comprehensive audit trail

## Security Considerations

- Passwords are hashed using bcrypt
- All SQL queries use prepared statements
- Input validation and sanitization implemented
- Role-based access control enforced
- Patient data privacy protected
- Session management implemented

*See SECURITY_REVIEW.md for detailed security analysis*

## Integration Points

### Critical Integration: Prescription ↔ Inventory
- Prescription creation checks inventory availability
- Prevents prescribing expired medications
- Prevents prescribing when insufficient stock
- Stock automatically deducted on dispensing
- All transactions logged

### Patient Record Integration
- All patient data connected through centralized record
- Consultations → Diagnoses → Treatments → Prescriptions
- Treatment progress linked to treatments
- Medical history maintained per patient

### Healthcare Team Communication
- Messages linked to specific patients
- Different message types supported
- Read/unread status tracking
- Restricted to authorized personnel

## Database Schema Notes

### Key Relationships
- `patients.user_id` → `users.user_id` (patient login)
- `consultations.patient_id` → `patients.patient_id`
- `consultations.doctor_id` → `users.user_id`
- `appointments.patient_id` → `patients.patient_id`
- `appointments.doctor_id` → `users.user_id`
- `prescriptions.patient_id` → `patients.patient_id`
- `prescriptions.doctor_id` → `users.user_id`
- `prescription_items.inventory_id` → `inventory.inventory_id`
- `treatments.patient_id` → `patients.patient_id`
- `treatment_progress.treatment_id` → `treatments.treatment_id`
- `healthcare_communication.sender_id` → `users.user_id`
- `healthcare_communication.recipient_id` → `users.user_id`
- `healthcare_communication.patient_id` → `patients.patient_id`

### Data Integrity Constraints
- Foreign key constraints prevent orphaned records
- Referential integrity maintained
- Transaction rollback on errors
- No negative inventory quantities
- Expired medications marked but preserved for records
- Appointments cannot overlap for the same doctor (checked at booking time)
- Appointment status transitions are validated (e.g. `completed` cannot return to `confirmed`)

## Configuration Files

### config/config.php
- Base URL configuration
- File path definitions
- Database credentials
- Security settings
- System constants

### config/database.php
- PDO database connection
- Connection parameters
- Error handling

### includes/auth.php
- Authentication functions
- Authorization checks
- Session management
- Password hashing/verification
- User access control

## File Organization

### Frontend Assets
- `assets/css/style.css` - Main stylesheet
- `assets/js/` - JavaScript files (minimal JS used)

### Backend Logic
- `includes/auth.php` - Authentication/authorization
- Role-specific pages in `pages/doctor/`, `pages/staff/`, `pages/patient/`
- Shared components in `includes/`

### Database
- `database/schema.sql` - Complete database schema
- `database/appointments_migration.sql` - Idempotent migration adding the `appointments` table (appointment calendar)
- Includes sample data for testing

## Performance Considerations

- Indexed columns on frequently queried fields
- Prepared statements for SQL efficiency
- Pagination for large datasets (limit 50 records)
- Optimized queries with JOINs instead of multiple queries
- Minimal JavaScript for faster page loads

## Browser Compatibility

- Chrome (recommended)
- Firefox
- Edge
- Safari
- Internet Explorer 11+ (basic support)

## Deployment Notes

### Production Requirements
1. Enable HTTPS/SSL
2. Configure proper database user with limited privileges
3. Move database credentials to environment variables
4. Enable error logging
5. Disable error display in production
6. Implement backup strategy
7. Configure firewall rules
8. Set up monitoring

### Backup Strategy
- Regular database backups
- File system backups
- Transaction log backups
- Off-site backup storage

## Maintenance

### Regular Tasks
- Monitor stock levels and expiration dates
- Review and archive old records
- Update health education content
- Review user accounts and access
- System performance monitoring
- Security updates

### Updates
- Database schema updates require careful migration
- Test changes in development environment first
- Backup before major updates
- Document all changes

## Support Documentation

- README.md - System overview and setup
- SECURITY_REVIEW.md - Security analysis
- database/schema.sql - Database structure
- Code comments - Implementation details

## Contact & Support

For technical support or questions about the MedCare system, refer to the documentation files or contact the development team.

---

**Last Updated:** September 22, 2026  
**Version:** 1.0  
**Status:** Complete
