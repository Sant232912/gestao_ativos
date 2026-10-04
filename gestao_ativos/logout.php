<?php

require_once 'auth.php';

fazerLogout();

header('Location: login.php');
exit;