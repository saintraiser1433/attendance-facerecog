<?php
include "db_conn.php";

// Check if the fingerprint_templates table exists and its structure
$sql = "DESCRIBE fingerprint_templates";
$result = mysqli_query($conn, $sql);

if ($result) {
    echo "<h2>fingerprint_templates table structure:</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Error: " . mysqli_error($conn) . "</p>";
}

// Check if there are any fingerprint templates in the table
$sql = "SELECT COUNT(*) as count FROM fingerprint_templates";
$result = mysqli_query($conn, $sql);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo "<h3>Number of fingerprint templates: " . $row['count'] . "</h3>";
} else {
    echo "<p>Error: " . mysqli_error($conn) . "</p>";
}

mysqli_close($conn);
?>