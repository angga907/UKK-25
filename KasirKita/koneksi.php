<?php
$host = "localhost";   
$user = "root";        
$pass = "";            
$db   = "restoran";   

// Koneksi ke MySQL
$koneksi = mysqli_connect($host, $user, $pass, $db);

// Cek koneksi
if(!$koneksi){
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>
