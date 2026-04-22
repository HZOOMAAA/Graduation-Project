# Graduation Project - Smart Insurance Platform

## Project Structure

- `admin/`
  - Admin interfaces: dashboard, users, companies, categories, plans, applications, analytics, chatbot management
- `agent/`
  - Agent interfaces: assigned applications, application review, profile, support
- `customer/`
  - Customer interfaces: registration/login, profile, categories, plans, compare, application, payment
- `auth/`
  - Authentication pages and logic for login, registration, password recovery
- `connection/`
  - Database connection and session handling
- `includes/`
  - Shared PHP includes such as header, footer, helper functions
- `assets/css/`
  - Stylesheets and Bootstrap custom CSS
  - JavaScript files, client-side interactions, chatbot and recommendation scripts
- `assets/images/`
  - Images, logos, icons
- `uploads/documents/`
  - Uploaded customer documents for applications
- `uploads/payments/`
  - Payment receipts and transaction files
- `api/`
  - API endpoints for chatbot, recommendation engine, notifications, AJAX calls
- `connection.php`
  - Database connection and session handling

## Database Access

Your current database connection is configured in `connection.php`:

- host: `localhost`
- user: `root`
- password: `` (empty)
- database: `graduation_db`

Use `require 'connection.php';` at the top of your PHP pages to access the database via the `$connect` variable.

## Next Steps

1. Create login/register pages in the root or `customer/` folder.
2. Add role-based access control for customer, agent, and admin.
3. Build the database tables for users, companies, categories, plans, applications, documents, payments, messages.
4. Implement the customer flow, agent flow, and admin flow using the folder structure above.
5. Add AI/chatbot endpoints in `api/` and connect them with the frontend.
