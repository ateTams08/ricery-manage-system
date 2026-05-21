<?php
require_once '../db.php';

// INSERTION:
if (isset($_POST['insert'])) {

    if (!empty($_POST['supp_id']) &&
        !empty($_POST['emp_id']) &&
        !empty($_POST['supp_name']) &&
        !empty($_POST['supp_contactn']) &&
        !empty($_POST['supp_add'])
    ) {

        $SUPP_ID       = $_POST['supp_id'];
        $EMP_ID        = $_POST['emp_id'];
        $SUPP_NAME     = $_POST['supp_name'];
        $SUPP_CONTACTN = $_POST['supp_contactn'];
        $SUPP_ADD      = $_POST['supp_add'];

        // Check if EMP_ID exists in employee table
        $check_emp_sql = "SELECT EMP_ID FROM project.employee WHERE EMP_ID='$EMP_ID'";
        $check_emp = mysqli_query($mysqli, $check_emp_sql);

        if (!$check_emp) {
            echo "Database Check Error: " . mysqli_error($mysqli);
            exit;
        }

        if (mysqli_num_rows($check_emp) == 0) {
            echo "Error: Employee ID '{$EMP_ID}' does not exist. Cannot insert supplier.";
            mysqli_free_result($check_emp);
            exit;
        }
        mysqli_free_result($check_emp);

        // Check if SUPP_ID already exists
        $check_supp_sql = "SELECT COUNT(*) AS count FROM project.supplier WHERE SUPP_ID='$SUPP_ID'";
        $check_supp = mysqli_query($mysqli, $check_supp_sql);
        $row = mysqli_fetch_assoc($check_supp);
        $count = $row['count'];
        mysqli_free_result($check_supp);

        if ($count == 0) {
            // Insert supplier
            $sql = "INSERT INTO project.supplier (SUPP_ID, EMP_ID, SUPP_NAME, SUPP_CONTACTN, SUPP_ADD)
                    VALUES ('$SUPP_ID', '$EMP_ID', '$SUPP_NAME', '$SUPP_CONTACTN', '$SUPP_ADD')";
            if (mysqli_query($mysqli, $sql)) {
                echo "Supplier successfully added.";
            } else {
                echo "Insert Error: " . mysqli_error($mysqli);
            }
        } else {
            echo "Insert Error: Supplier ID '{$SUPP_ID}' already exists.";
        }

    } else {
        echo "Insert Error: Please fill in all required fields.";
    }
}

// DELETE:
if (isset($_POST['delete'])) {

    if (empty($_POST['supp_id'])) {
        echo "Delete Error: Supplier ID is required.";
    } else {

        $SUPP_ID = $_POST['supp_id'];

        // Check if supplier exists
        $check_sql = "SELECT SUPP_ID FROM project.supplier WHERE SUPP_ID='$SUPP_ID'";
        $check = mysqli_query($mysqli, $check_sql);

        if (mysqli_num_rows($check) == 0) {
            echo "Error: Supplier ID '{$SUPP_ID}' not found.";
            mysqli_free_result($check);
            exit;
        }
        mysqli_free_result($check);

        // Delete supplier
        $delete_sql = "DELETE FROM project.supplier WHERE SUPP_ID='$SUPP_ID'";
        if (mysqli_query($mysqli, $delete_sql)) {
            echo "Supplier ID '{$SUPP_ID}' deleted successfully.";
        } else {
            echo "Delete Error: " . mysqli_error($mysqli);
        }
    }
}

// UPDATE:
if (isset($_POST['update'])) {

    if (empty($_POST['supp_id'])) {
        echo "Update Error: Supplier ID is required.";
    } else {

        $SUPP_ID = $_POST['supp_id'];
        $EMP_ID = $_POST['emp_id'];
        $SUPP_NAME = $_POST['supp_name'];
        $SUPP_CONTACTN = $_POST['supp_contactn'];
        $SUPP_ADD = $_POST['supp_add'];

        $origin_stmt = $mysqli->prepare("SELECT * FROM project.supplier WHERE SUPP_ID=?");
        $origin_stmt->bind_param("s", $SUPP_ID);
        $origin_stmt->execute();
        $result = $origin_stmt->get_result();
        $row = $result->fetch_assoc();
        $origin_stmt->close();

        if (!$row) {
            echo "Error: Supplier ID '{$SUPP_ID}' not found.";
        } else {
            $final_EMP_ID = !empty($EMP_ID) ? $EMP_ID : $row['EMP_ID'];
            $final_SUPP_NAME = !empty($SUPP_NAME) ? $SUPP_NAME : $row['SUPP_NAME'];
            $final_CONTACT = !empty($SUPP_CONTACTN) ? $SUPP_CONTACTN : $row['SUPP_CONTACTN'];
            $final_ADD = !empty($SUPP_ADD) ? $SUPP_ADD : $row['SUPP_ADD'];

            if (!empty($final_EMP_ID)) {
                $check_emp_stmt = $mysqli->prepare("SELECT EMP_ID FROM project.employee WHERE EMP_ID=?");
                $check_emp_stmt->bind_param("s", $final_EMP_ID);
                $check_emp_stmt->execute();
                $check_result = $check_emp_stmt->get_result();
                $check_emp_stmt->close();

                if ($check_result->num_rows === 0) {
                    echo "Update Error: Employee ID '{$final_EMP_ID}' does not exist in the employee table. Foreign Key Constraint failed.";
                    return;
                }
            }

            $final_EMP_ID = empty($final_EMP_ID) ? null : $final_EMP_ID;

            $sql = "UPDATE project.supplier
                    SET EMP_ID=?, SUPP_NAME=?, SUPP_CONTACTN=?, SUPP_ADD=?
                    WHERE SUPP_ID=?";

            $stmt = $mysqli->prepare($sql);
            
            if ($final_EMP_ID === null) {
                $sql = "UPDATE project.supplier
                        SET EMP_ID=NULL, SUPP_NAME=?, SUPP_CONTACTN=?, SUPP_ADD=?
                        WHERE SUPP_ID=?";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("ssss", $final_SUPP_NAME, $final_CONTACT, $final_ADD, $SUPP_ID);
            } else {
                $stmt->bind_param("sssss", $final_EMP_ID, $final_SUPP_NAME, $final_CONTACT, $final_ADD, $SUPP_ID);
            }

            if ($stmt->execute()) {
                echo "Supplier successfully updated.";
            } else {
                echo "Update Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// DISPLAY TABLE:
$sql = "SELECT * FROM project.supplier";
$result = $mysqli->query($sql);

if (!$result) {
    die("Query failed: " . $mysqli->error);
}
?>

<!DOCTYPE html>
<html>
<body>

<h2>Supplier Table</h2>
<table border="1">
    <tr>
        <th>SUPP_ID</th>
        <th>EMP_ID</th>
        <th>SUPP_NAME</th>
        <th>SUPP_CONTACTN</th>
        <th>SUPP_ADD</th>
    </tr>

<?php while ($row = $result->fetch_assoc()) { ?>
    <tr>
        <td><?= $row["SUPP_ID"]; ?></td>
        <td><?= $row["EMP_ID"]; ?></td>
        <td><?= $row["SUPP_NAME"]; ?></td>
        <td><?= $row["SUPP_CONTACTN"]; ?></td>
        <td><?= $row["SUPP_ADD"]; ?></td>
    </tr>
<?php } ?>

</table>

<br>
<form action="/Ricery/frontend/modules/supplier.html" method="POST">
    <input type="submit" value="Return">
</form>

<?php $mysqli->close(); ?>

</body>
</html>