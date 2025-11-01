# q-lms - Quick Learning Management System

q-lms is a lightweight Learning Management System that runs on a classic **LAMP** stack.  
The application is written in PHP, stores data in MySQL, and is designed so it can be
deployed to traditional shared web hosting without the need for Node.js or any server
processes beyond Apache + PHP.

## Features

- **Secure Authentication** – Role-based logins for parents/guardians and children.
- **Course Management** – Create courses, enrol children, and track completion.
- **Progress Tracking** – Token rewards and simple quiz support.
- **SCORM Support** – Upload SCORM packages for self-paced learning.
- **Open Source** – Free to use and customise for personal or business needs.

## Requirements

- PHP 7.4+ with the `mysqli` extension enabled
- MySQL 5.7+ (or MariaDB equivalent)
- Apache or another web server capable of running PHP
- The ability to upload files and create databases (typical on shared hosting)

## Installation

1. **Upload the application files**
   - Copy the contents of the `lms` directory (and the root `index.php` file) to your web root.  
     On shared hosting this is typically `public_html/` or `www/`.

2. **Create the database schema**
   - Create a new MySQL database from your hosting control panel.
   - Import the SQL definition found in [`lms_schema.sql`](./lms_schema.sql) using phpMyAdmin or the MySQL command line.

3. **Configure the database connection**
   - Edit [`lms/config.php`](./lms/config.php) with your database hostname, username, password, and database name.

4. **Visit the site**
   - Navigate to your domain in a browser.  You should see the LMS welcome page.

## Local Development

You can also run the project locally using any PHP-enabled web server:

```bash
php -S localhost:8000 -t lms
```

Then open <http://localhost:8000> in your browser.  The included root `index.php` will redirect requests to the `lms/` directory.

## Licensing

- **Community Edition**: Free for personal and business use under the open-source license (see [LICENSE](LICENSE) for details).
- **Commercial Licenses**:
  - **Business License**: One-time permanent license for commercial use.
  - **Enterprise License**: Own the source code and create marketable forks.

Visit [q-lms.com](http://q-lms.com) for commercial licensing details.

## Contributing

We welcome contributions to q-lms! To get started:

1. Fork the repository.
2. Create a new branch (`git checkout -b feature/your-feature`).
3. Commit your changes (`git commit -m 'Add your feature'`).
4. Push to the branch (`git push origin feature/your-feature`).
5. Open a Pull Request.

Please read our [Contributing Guidelines](CONTRIBUTING.md) and [Code of Conduct](CODE_OF_CONDUCT.md) before submitting.

## Community & Support

Join the q-lms community at [q-lms.org](http://q-lms.org) to collaborate, share ideas, and get support.  
For issues, feature requests, or questions, please open an issue on this repository or contact us via [q-lms.com](http://q-lms.com).
