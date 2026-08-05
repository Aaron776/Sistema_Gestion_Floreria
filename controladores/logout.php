<?php
require_once '../conexion/session.php';
session_destroy();
header("Location: ../index.php");
?>