<?php

require_once '../../bootstrap.php';

session_destroy();
redirect('Login.php');
