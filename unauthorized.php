<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if(!isset($_SESSION['SESS_MEMBER_ID'])){
    header('location:index.php');
}
include 'partials/main.php';

?>

<head>
    <?php
    $title = "Upload";
    include 'partials/title-meta.php';
    ?>
    <?php include 'partials/head-css.php'; ?>
</head>

<body>
<!-- Begin page -->
<div class="flex wrapper">
    <?php include 'partials/menu.php'; ?>

    <!-- ============================================================== -->
    <!-- Start Page Content here -->
    <!-- ============================================================== -->
    <div class="page-content">
        <?php include 'partials/topbar.php'; ?>

        <main class="flex-grow p-6">
            <?php
            $subtitle = "Home";
            $pagetitle = "Upload";
            include 'partials/page-title.php';
            ?>

            <!-- Main Content -->
            <div id="loadContent">
                <div class="flex animate-pulse">
                    <div class="flex-shrink-0">
                        <span class="w-12 h-12 block bg-gray-200 rounded-full dark:bg-gray-700"></span>
                    </div>

                    <div class="ms-4 mt-2 w-full">
                        <h3 class="h-4 bg-gray-200 rounded-md dark:bg-gray-700" style="width: 40%;"></h3>
                        <ul class="mt-5 space-y-3">
                            <li class="w-full h-4 bg-gray-200 rounded-md dark:bg-gray-700"></li>
                            <li class="w-full h-4 bg-gray-200 rounded-md dark:bg-gray-700"></li>
                            <li class="w-full h-4 bg-gray-200 rounded-md dark:bg-gray-700"></li>
                            <li class="w-full h-4 bg-gray-200 rounded-md dark:bg-gray-700"></li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>

        <?php include 'partials/footer.php'; ?>

    </div>
</div>

<?php include 'partials/customizer.php'; ?>
<?php include 'partials/footer-scripts.php'; ?>
<script>
    $(document).ready(function() {

        $('#loadContent').load('view/view_Upload.php');
        $("#search").focus();
        $("#search").select();
        $("#search").autocomplete({
            source: 'libs/searchStaff.php',
            type: 'POST',
            delay: 10,
            autoFocus: false,
            minLength: 3,
            select: function (event, ui) {
                event.preventDefault();
                $("#search").val(ui.item.value);
                $('#searchform').ajaxSubmit({
                    url: 'view/view_Employees.php', // URL for form submission
                    type: 'POST', // Method for form submission
                    success: function(response) {
                        $('#loadContent').html(response);
                    },
                    error: function(xhr, status, error) {
                        // Handle the error response here
                        console.log(error);
                    }
                });

            }
        });


    })
</script>

</body>

</html>
