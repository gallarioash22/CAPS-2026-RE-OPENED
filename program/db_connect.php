    <?php
    // db_connect.php
    $DB_HOST = 'sql204.infinityfree.com';  // Ensure this is correct
    $DB_USER = 'if0_42695431';        // Your MySQL username
    $DB_PASS = 'AFXlCWDs49';            // Your MySQL password (empty by default for XAMPP)
    $DB_NAME = 'if0_42695431_db_library_gmc'; // Your database name

    // Create connection
    $conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

    // Check connection
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // Ensure UTF-8 charset
    mysqli_set_charset($conn, 'utf8mb4');
    ?>
