<?php
// Set error reporting to maximum
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Registration System Fix Tool</h1>";

// Check database tables
try {
    include('config/config.php');
    
    if (!isset($conn)) {
        echo "<p style='color:red'>Database connection variable (\$conn) is not set in config.php</p>";
        exit;
    }
    
    if ($conn->connect_error) {
        echo "<p style='color:red'>Database connection failed: " . $conn->connect_error . "</p>";
        exit;
    }
    
    echo "<p style='color:green'>Database connection successful!</p>";
    
    // Check if tables exist and create them if they don't
    echo "<h2>Database Tables Check</h2>";
    
    $tables = [
        'students_registerqe' => "
            CREATE TABLE `students_registerqe` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `last_name` varchar(100) NOT NULL,
              `first_name` varchar(100) NOT NULL,
              `middle_name` varchar(100) DEFAULT NULL,
              `gender` varchar(10) NOT NULL,
              `dob` date NOT NULL,
              `email` varchar(100) NOT NULL,
              `contact_number` varchar(20) NOT NULL,
              `street` varchar(255) NOT NULL,
              `student_type` varchar(20) NOT NULL,
              `previous_school` varchar(255) NOT NULL,
              `year_level` varchar(10) DEFAULT NULL,
              `previous_program` varchar(255) NOT NULL,
              `desired_program` varchar(255) NOT NULL,
              `tor` varchar(255) NOT NULL,
              `school_id` varchar(255) NOT NULL,
              `is_tech` tinyint(1) NOT NULL DEFAULT 0,
              `status` varchar(20) NOT NULL DEFAULT 'pending',
              `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ",
        'matched_courses' => "
            CREATE TABLE `matched_courses` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `subject_code` varchar(20) NOT NULL,
              `subject_description` varchar(255) NOT NULL,
              `units` decimal(3,1) NOT NULL,
              `student_id` int(11) NOT NULL,
              `matched_at` timestamp NOT NULL DEFAULT current_timestamp(),
              `original_code` varchar(20) DEFAULT NULL,
              `grade` varchar(10) DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `student_id` (`student_id`),
              CONSTRAINT `matched_courses_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students_registerqe` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ",
        'coded_courses' => "
            CREATE TABLE `coded_courses` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `subject_code` varchar(20) NOT NULL,
              `subject_description` varchar(255) NOT NULL,
              `units` decimal(3,1) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ",
        'university_grading_systems' => "
            CREATE TABLE `university_grading_systems` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `university_name` varchar(255) NOT NULL,
              `grade_value` decimal(3,2) NOT NULL,
              `min_percentage` decimal(5,2) NOT NULL,
              `max_percentage` decimal(5,2) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        "
    ];
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Table</th><th>Status</th><th>Action</th></tr>";
    
    foreach ($tables as $table => $create_sql) {
        echo "<tr>";
        echo "<td>$table</td>";
        
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "<td style='color:green'>Exists</td>";
            echo "<td>No action needed</td>";
        } else {
            echo "<td style='color:red'>Missing</td>";
            echo "<td>";
            echo "<form method='post'>";
            echo "<input type='hidden' name='create_table' value='$table'>";
            echo "<button type='submit'>Create Table</button>";
            echo "</form>";
            echo "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // Create table if requested
    if (isset($_POST['create_table']) && isset($tables[$_POST['create_table']])) {
        $table = $_POST['create_table'];
        $sql = $tables[$table];
        
        if ($conn->multi_query($sql)) {
            echo "<p style='color:green'>Table '$table' created successfully!</p>";
            echo "<p>Please refresh the page to see the updated status.</p>";
        } else {
            echo "<p style='color:red'>Error creating table '$table': " . $conn->error . "</p>";
        }
    }
    
    // Check for sample data in coded_courses
    $result = $conn->query("SELECT COUNT(*) as count FROM coded_courses");
    if ($result && $row = $result->fetch_assoc()) {
        $count = $row['count'];
        echo "<h2>Sample Data Check</h2>";
        echo "<p>Coded courses in database: $count</p>";
        
        if ($count == 0) {
            echo "<p style='color:red'>No coded courses found in the database.</p>";
            echo "<form method='post'>";
            echo "<button type='submit' name='add_sample_data'>Add Sample Courses</button>";
            echo "</form>";
        } else {
            echo "<p style='color:green'>Coded courses data exists.</p>";
        }
    }
    
    // Add sample data if requested
    if (isset($_POST['add_sample_data'])) {
        $sample_courses = [
            ['CS101', 'Introduction to Computer Science', 3.0],
            ['CS102', 'Programming Fundamentals', 3.0],
            ['CS201', 'Data Structures', 3.0],
            ['CS202', 'Algorithms', 3.0],
            ['CS301', 'Database Systems', 3.0],
            ['CS302', 'Web Development', 3.0],
            ['CS401', 'Software Engineering', 3.0],
            ['CS402', 'Operating Systems', 3.0],
            ['MATH101', 'College Algebra', 3.0],
            ['MATH102', 'Calculus', 3.0]
        ];
        
        $stmt = $conn->prepare("INSERT INTO coded_courses (subject_code, subject_description, units) VALUES (?, ?, ?)");
        $stmt->bind_param("ssd", $code, $description, $units);
        
        $success_count = 0;
        foreach ($sample_courses as $course) {
            $code = $course[0];
            $description = $course[1];
            $units = $course[2];
            
            if ($stmt->execute()) {
                $success_count++;
            }
        }
        
        echo "<p style='color:green'>Added $success_count sample courses to the database.</p>";
        echo "<p>Please refresh the page to see the updated status.</p>";
    }
    
    // Check for sample data in university_grading_systems
    $result = $conn->query("SELECT COUNT(*) as count FROM university_grading_systems");
    if ($result && $row = $result->fetch_assoc()) {
        $count = $row['count'];
        echo "<p>University grading systems in database: $count</p>";
        
        if ($count == 0) {
            echo "<p style='color:red'>No university grading systems found in the database.</p>";
            echo "<form method='post'>";
            echo "<button type='submit' name='add_sample_grading'>Add Sample Grading Systems</button>";
            echo "</form>";
        } else {
            echo "<p style='color:green'>Grading systems data exists.</p>";
        }
    }
    
    // Add sample grading systems if requested
    if (isset($_POST['add_sample_grading'])) {
        $sample_grading = [
            ['Polytechnic University of the Philippines', 1.00, 96.00, 100.00],
            ['Polytechnic University of the Philippines', 1.25, 94.00, 95.99],
            ['Polytechnic University of the Philippines', 1.50, 92.00, 93.99],
            ['Polytechnic University of the Philippines', 1.75, 89.00, 91.99],
            ['Polytechnic University of the Philippines', 2.00, 86.00, 88.99],
            ['Polytechnic University of the Philippines', 2.25, 83.00, 85.99],
            ['Polytechnic University of the Philippines', 2.50, 80.00, 82.99],
            ['Polytechnic University of the Philippines', 2.75, 77.00, 79.99],
            ['Polytechnic University of the Philippines', 3.00, 75.00, 76.99],
            ['Polytechnic University of the Philippines', 5.00, 0.00, 74.99]
        ];
        
        $stmt = $conn->prepare("INSERT INTO university_grading_systems (university_name, grade_value, min_percentage, max_percentage) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sddd", $university, $grade, $min, $max);
        
        $success_count = 0;
        foreach ($sample_grading as $grade_system) {
            $university = $grade_system[0];
            $grade = $grade_system[1];
            $min = $grade_system[2];
            $max = $grade_system[3];
            
            if ($stmt->execute()) {
                $success_count++;
            }
        }
        
        echo "<p style='color:green'>Added $success_count sample grading systems to the database.</p>";
        echo "<p>Please refresh the page to see the updated status.</p>";
    }
    
    // Check upload directories
    echo "<h2>Upload Directories Check</h2>";
    $upload_dirs = [
        'uploads/tor',
        'uploads/school_id',
        'logs'
    ];
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Directory</th><th>Status</th><th>Permissions</th><th>Action</th></tr>";
    
    foreach ($upload_dirs as $dir) {
        $full_path = __DIR__ . '/' . $dir;
        echo "<tr>";
        echo "<td>$dir</td>";
        
        if (file_exists($full_path)) {
            echo "<td style='color:green'>Exists</td>";
            
            if (is_writable($full_path)) {
                echo "<td style='color:green'>Writable</td>";
                echo "<td>No action needed</td>";
            } else {
                echo "<td style='color:red'>Not writable</td>";
                echo "<td>";
                echo "<form method='post'>";
                echo "<input type='hidden' name='fix_permissions' value='$dir'>";
                echo "<button type='submit'>Fix Permissions</button>";
                echo "</form>";
                echo "</td>";
            }
        } else {
            echo "<td style='color:red'>Missing</td>";
            echo "<td>N/A</td>";
            echo "<td>";
            echo "<form method='post'>";
            echo "<input type='hidden' name='create_dir' value='$dir'>";
            echo "<button type='submit'>Create Directory</button>";
            echo "</form>";
            echo "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // Create directory if requested
    if (isset($_POST['create_dir'])) {
        $dir = $_POST['create_dir'];
        $full_path = __DIR__ . '/' . $dir;
        
        if (mkdir($full_path, 0755, true)) {
            echo "<p style='color:green'>Directory '$dir' created successfully!</p>";
            echo "<p>Please refresh the page to see the updated status.</p>";
        } else {
            echo "<p style='color:red'>Error creating directory '$dir'.</p>";
        }
    }
    
    // Fix permissions if requested
    if (isset($_POST['fix_permissions'])) {
        $dir = $_POST['fix_permissions'];
        $full_path = __DIR__ . '/' . $dir;
        
        if (chmod($full_path, 0755)) {
            echo "<p style='color:green'>Permissions for '$dir' fixed successfully!</p>";
            echo "<p>Please refresh the page to see the updated status.</p>";
        } else {
            echo "<p style='color:red'>Error fixing permissions for '$dir'.</p>";
        }
    }
    
    // Check Google Cloud Vision API configuration
    echo "<h2>Google Cloud Vision API Configuration</h2>";
    $config_file = __DIR__ . '/config/google_cloud_config.php';
    
    if (file_exists($config_file)) {
        echo "<p style='color:green'>Google Cloud config file exists.</p>";
        
        // Check if we can include it without errors
        try {
            include_once($config_file);
            
            if (defined('GOOGLE_API_KEY') && !empty(GOOGLE_API_KEY)) {
                echo "<p style='color:green'>GOOGLE_API_KEY is defined.</p>";
            } else {
                echo "<p style='color:red'>GOOGLE_API_KEY is not defined or empty.</p>";
                echo "<form method='post'>";
                echo "<input type='text' name='api_key' placeholder='Enter your Google API Key'>";
                echo "<button type='submit' name='set_api_key'>Set API Key</button>";
                echo "</form>";
            }
            
            if (defined('GOOGLE_VISION_API_ENDPOINT') && !empty(GOOGLE_VISION_API_ENDPOINT)) {
                echo "<p style='color:green'>GOOGLE_VISION_API_ENDPOINT is defined.</p>";
            } else {
                echo "<p style='color:red'>GOOGLE_VISION_API_ENDPOINT is not defined or empty.</p>";
                echo "<form method='post'>";
                echo "<input type='text' name='api_endpoint' placeholder='Enter the API Endpoint' value='https://vision.googleapis.com/v1/images:annotate'>";
                echo "<button type='submit' name='set_api_endpoint'>Set API Endpoint</button>";
                echo "</form>";
            }
        } catch (Exception $e) {
            echo "<p style='color:red'>Error including Google Cloud config: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color:red'>Google Cloud config file does not exist.</p>";
        echo "<form method='post'>";
        echo "<button type='submit' name='create_config'>Create Config File</button>";
        echo "</form>";
    }
    
    // Create Google Cloud config file if requested
    if (isset($_POST['create_config'])) {
        $config_dir = __DIR__ . '/config';
        
        if (!file_exists($config_dir)) {
            mkdir($config_dir, 0755, true);
        }
        
        $config_content = "<?php\n// Google Cloud Vision API Configuration\ndefine('GOOGLE_API_KEY', '');\ndefine('GOOGLE_VISION_API_ENDPOINT', 'https://vision.googleapis.com/v1/images:annotate');\n?>";
        
        if (file_put_contents($config_file, $config_content)) {
            echo "<p style='color:green'>Google Cloud config file created successfully!</p>";
            echo "<p>Please refresh the page to see the updated status.</p>";
        } else {
            echo "<p style='color:red'>Error creating Google Cloud config file.</p>";
        }
    }
    
    // Set API Key if requested
    if (isset($_POST['set_api_key']) && !empty($_POST['api_key'])) {
        $api_key = $_POST['api_key'];
        
        if (file_exists($config_file)) {
            $config_content = file_get_contents($config_file);
            $config_content = preg_replace("/define\('GOOGLE_API_KEY', '.*?'\);/", "define('GOOGLE_API_KEY', '$api_key');", $config_content);
            
            if (file_put_contents($config_file, $config_content)) {
                echo "<p style='color:green'>API Key set successfully!</p>";
                echo "<p>Please refresh the page to see the updated status.</p>";
            } else {
                echo "<p style='color:red'>Error setting API Key.</p>";
            }
        }
    }
    
    // Set API Endpoint if requested
    if (isset($_POST['set_api_endpoint']) && !empty($_POST['api_endpoint'])) {
        $api_endpoint = $_POST['api_endpoint'];
        
        if (file_exists($config_file)) {
            $config_content = file_get_contents($config_file);
            $config_content = preg_replace("/define\('GOOGLE_VISION_API_ENDPOINT', '.*?'\);/", "define('GOOGLE_VISION_API_ENDPOINT', '$api_endpoint');", $config_content);
            
            if (file_put_contents($config_file, $config_content)) {
                echo "<p style='color:green'>API Endpoint set successfully!</p>";
                echo "<p>Please refresh the page to see the updated status.</p>";
            } else {
                echo "<p style='color:red'>Error setting API Endpoint.</p>";
            }
        }
    }
    
    // Check for Composer dependencies
    echo "<h2>Composer Dependencies</h2>";
    $vendor_dir = __DIR__ . '/vendor';
    $composer_json = __DIR__ . '/composer.json';
    
    if (file_exists($vendor_dir) && is_dir($vendor_dir)) {
        echo "<p style='color:green'>Vendor directory exists.</p>";
        
        if (file_exists($vendor_dir . '/autoload.php')) {
            echo "<p style='color:green'>Autoload file exists.</p>";
            
            // Check if GuzzleHttp is available
            try {
                require_once $vendor_dir . '/autoload.php';
                if (class_exists('GuzzleHttp\\Client')) {
                    echo "<p style='color:green'>GuzzleHttp\\Client class is available.</p>";
                } else {
                    echo "<p style='color:red'>GuzzleHttp\\Client class is not available.</p>";
                    echo "<p>You may need to run: <code>composer require guzzlehttp/guzzle</code></p>";
                }
            } catch (Exception $e) {
                echo "<p style='color:red'>Error loading autoload.php: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color:red'>Autoload file does not exist.</p>";
        }
    } else {
        echo "<p style='color:red'>Vendor directory does not exist.</p>";
    }
    
    if (file_exists($composer_json)) {
        echo "<p style='color:green'>composer.json file exists.</p>";
    } else {
        echo "<p style='color:red'>composer.json file does not exist.</p>";
        echo "<form method='post'>";
        echo "<button type='submit' name='create_composer_json'>Create composer.json</button>";
        echo "</form>";
    }
    
    // Create composer.json if requested
    if (isset($_POST['create_composer_json'])) {
        $composer_content = '{
    "require": {
        "guzzlehttp/guzzle": "^7.0"
    }
}';
        
        if (file_put_contents($composer_json, $composer_content)) {
            echo "<p style='color:green'>composer.json file created successfully!</p>";
            echo "<p>Please run <code>composer install</code> in your project directory to install dependencies.</p>";
        } else {
            echo "<p style='color:red'>Error creating composer.json file.</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?> 