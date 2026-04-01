<?php 
   session_start();
   include "db_conn.php";
   if (isset($_SESSION['username']) && isset($_SESSION['id'])) {
       // Redirect to appropriate dashboard based on role
       $role = $_SESSION['role'];
       
       switch($role) {
           case 'admin':
               header("Location: admin/dashboard.php");
               break;
           case 'user':
               // Regular user goes to staff dashboard
               header("Location: staff/dashboard.php");
               break;
           case 'teacher':
               header("Location: teacher/dashboard.php");
               break;
           case 'student':
               header("Location: student/dashboard.php");
               break;
           default:
               // Fallback
               header("Location: admin/dashboard.php");
               break;
       }
       exit;
   } else {
       header("Location: index.php");
       exit;
   }
?>