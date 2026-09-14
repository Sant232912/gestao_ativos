<?php

require_once 'auth.php';

if (usuarioAutenticado()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}

exit;