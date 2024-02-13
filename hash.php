<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Untitled Document</title>
</head>

<body>
    
    <?php include ("passwordHash.php");$_POST['password'] = 12345 ;
    $hashed_password =  password_hash($_POST['password'],PASSWORD_DEFAULT);
    var_dump($hashed_password);
    echo phpversion();
    echo check();
    ?>
</body>
</html>