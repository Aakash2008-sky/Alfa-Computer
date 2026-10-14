<?php

$password = "Suthar2308";

$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password : " . $password . "<br>";
echo "Password Hash : " . $hash;

?>