// quick_token.php
<?php
header('Content-Type: application/json');
require_once '../utils/JWTHandler.php';

$jwt = new JWTHandler();
$token = $jwt->generateToken('1'); // Using default user ID 1

?>
<script>
    localStorage.setItem('token', '<?php echo $token; ?>');
    document.write('Token saved to localStorage: ' + localStorage.getItem('token'));
</script>