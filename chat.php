<?php $q = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''; header('Location: view/chat.php' . $q, true, 301); exit;
