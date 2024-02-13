 <?php
    if (isset($_POST['namee'])& trim($_POST['namee']) != '') {
        $originalName = $_POST['namee'];

        $originalName = strtolower($originalName);
        $originalName = explode(" ", $originalName);
        $originalName = $originalName[0] . '.' . $originalName[1] . '@oouth.com';
        echo $originalName;
    }

    ?>