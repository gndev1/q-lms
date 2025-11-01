<?php
// Redirect root requests to the application directory.  This keeps the project
// compatible with shared hosting environments where the repository may live
// one level above the public web root.  If you upload the `lms/` directory to
// your document root, also upload this file so visitors hitting the domain
// automatically reach the LMS landing page.
header('Location: lms/index.php');
exit;
