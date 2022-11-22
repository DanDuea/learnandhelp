<?php
$status = session_status();
if ($status == PHP_SESSION_NONE) {
    session_start();
}

// Block unauthorized users from accessing the page
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] != 'admin') {
        http_response_code(403);
        die('Forbidden');
    }
} else {
    http_response_code(403);
    die('Forbidden');
}
?>

<!DOCTYPE html>
<html lang="en-US">
<head>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <script src="http://code.jquery.com/jquery-1.11.3.min.js"></script>
    <link href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css"/>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script>
      $(document).ready(function () {
        $('#classes thead tr').clone(true).appendTo( '#classes thead' );
        $('#classes thead tr:eq(1) th').each(function () {
        var title = $(this).text();
        $(this).html('<input type="text" placeholder="Search ' + title + '" />');
        });

        var table = $('#classes').DataTable({
           initComplete: function () {
               // Apply the search
               this.api()
                   .columns()
                   .every(function () {
                       var that = this;

                       $('input', this.header()).on('keyup change clear', function () {
                           if (that.search() !== this.value) {
                               that.search(this.value).draw();
                           }
                       });
                   });
               },
           });

        $('a.toggle-vis').on('click', function (e) {
        e.preventDefault();

        // Get the column API object
        var column = table.column($(this).attr('data-column'));

        // Toggle the visibility
        column.visible(!column.visible());
        });
       });
    </script>
</head>
<body>
<?php include 'show-navbar.php'; ?>
<?php show_navbar(); ?>
<header class="inverse">
    <div class="container">
        <h1><span class="accent-text">Classes</span></h1>
    </div>
</header>
<div class="toggle_columns">
  Toggle column: <a class="toggle-vis" data-column="0">Class</a>
    - <a class="toggle-vis" data-column="1">Description</a>
    - <a class="toggle-vis" data-column="2">Teacher Name</a>
    - <a class="toggle-vis" data-column="3">Submit</a>
    - <a class="toggle-vis" data-column="4">Action</a>
</div>
<div style="width: 90%; margin: auto;">
    <table id="classes" class="display compact">
        <thead>
        <tr>
            <th>Class</th>
            <th>Description</th>
            <th>Teacher Name</th>
            <th>Submit</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        <?php
        // Pull Class data from the database and create a Jquery Datatable
        require 'db_configuration.php';
        $connection = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
        if ($connection === false) {
            die("Failed to connect to database: " . mysqli_connect_error());
        }
        // Get the number of teachers.
        $sql2 = "SELECT * from users WHERE Role = 'admin'";
        $teachers_result = mysqli_query($connection, $sql2);
        // Query selects all the classes, and relates them to the teachers.
        $sql = "SELECT Class_Id, Class_Name, Description, Teacher_Id, First_Name, Last_Name
                      FROM classes
                      LEFT JOIN users on Teacher_Id = User_Id";
        $result = mysqli_query($connection, $sql);
        if ($result->num_rows > 0) {
            // Create table with data from every row
            while ($row = $result->fetch_assoc()) {
                echo '<form action="update_classes.php" method="post">
                            <input type="hidden" name="rowId" value="' . $row['Class_Id'] . '">
                            <tr>
                              <td>
                                <input type="text" name="name" value="' . $row['Class_Name'] . '">
                              </td>
                              <td>
                                <textarea rows="2" cols="20" name="description">' . $row['Description'] . '</textarea>
                              </td>
                            <!-- FIXME: The teacher name should be a dropdown of the available teachers -->
                              <td>
                                <textarea readonly rows="2" cols="20" name="teacher_name">' . $row['First_Name'] . ' ' . $row['Last_Name'] . '</textarea>
                              </td>
                              <td>
                                <input type="submit" value="Update">
                              </td>
                              <td>
                                <select name="action" style="width: 100%">
                                  <option value="update">Edit</option>
                                  <option value="delete">Delete</option>
                                </select>
                              </td>
                          </tr>
                          </form>';
            }
        }
        ?>
        </tbody>
    </table>
    <h1>Add New</h1>
    <form action="update_classes.php" method="post" id="add_class">
        <label>
            <input type="text" name="name" placeholder="Class Name" required>
        </label>
        <br>
        <label>
            <textarea rows=9 cols=90 name="description" placeholder="Class Description" required></textarea>
        </label>
        <br>
        <label>
            <select name="teacher_id" id="teacher_id">
                <?PHP if ($teachers_result->num_rows > 0) {
                    while ($row = $teachers_result->fetch_assoc()) {
                        echo '<option value = ' . $row['User_Id'] . '>' . $row['First_Name'] . ' ' . $row['Last_Name'] . '</option>';
                    }
                } ?>
            </select>
        </label>
        <br>
        <input type="hidden" name="action" value="add">
        <input type="submit" value="Add" style="width: 15%">
    </form>
</body>
</html>
