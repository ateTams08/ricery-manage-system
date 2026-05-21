<?php
require_once '../db.php';

// INSERT:
if (isset($_POST['insert'])) {

    if (!empty($_POST['cust_id']) &&
        !empty($_POST['emp_id']) &&
        !empty($_POST['cust_fname']) &&
        !empty($_POST['cust_lname']) &&
        !empty($_POST['cust_contactn'])
    ) {

        $CUST_ID = $_POST['cust_id'];
        $EMP_ID = $_POST['emp_id'];
        $FNAME = $_POST['cust_fname'];
        $LNAME = $_POST['cust_lname'];
        $CONTACT = $_POST['cust_contactn'];
        
        // Check if EMP_ID exists in employee table
        $check_emp_sql = "SELECT EMP_ID FROM project.employee WHERE EMP_ID='$EMP_ID'";
        $check_emp = mysqli_query($mysqli, $check_emp_sql);

        if (mysqli_num_rows($check_emp) == 0) {
            echo "Insert Error: Employee ID '$EMP_ID' does not exist.";
            mysqli_free_result($check_emp);
            exit;
        }
        mysqli_free_result($check_emp);

        // Check if CUST_ID exists
        $check_sql = "SELECT COUNT(*) AS count FROM project.customer WHERE CUST_ID='$CUST_ID'";
        $check_result = mysqli_query($mysqli, $check_sql);

        if (!$check_result) {
            echo "Database Check Error: " . mysqli_error($mysqli);
            exit;
        }

        $row = mysqli_fetch_assoc($check_result);
        $count = $row['count'];
        mysqli_free_result($check_result);

        if ($count == 0) {
            // Insert
            $sql = "
                INSERT INTO project.customer (CUST_ID, EMP_ID, CUST_FNAME, CUST_LNAME, CUST_CONTACTN)
                VALUES ('$CUST_ID', '$EMP_ID', '$FNAME', '$LNAME', '$CONTACT')
            ";

            if (mysqli_query($mysqli, $sql)) {
                echo "Customer added successfully.";
            } else {
                echo "Insert Error: " . mysqli_error($mysqli);
            }
        } else {
            echo "Insert Error: CUSTOMER ID '$CUST_ID' already exists.";
        }

    } else {
        echo "Insert Error: Missing required fields.";
    }
}


// DELETE:
if (isset($_POST['delete'])) {

    if (!empty($_POST['cust_id'])) {
        $CUST_ID = mysqli_real_escape_string($mysqli, $_POST['cust_id']);

        $check_sql = "SELECT COUNT(*) AS count FROM project.customer WHERE CUST_ID='$CUST_ID'";
        $check_result = mysqli_query($mysqli, $check_sql);

        if (!$check_result) {
            echo "Database Check Error: " . mysqli_error($mysqli);
            exit;
        }

        $row = mysqli_fetch_assoc($check_result);
        $count = $row['count'];
        mysqli_free_result($check_result);

        if ($count > 0) {
           
            $check_purchase_sql = "SELECT COUNT(*) AS purchase_count FROM project.purchase WHERE CUST_ID='$CUST_ID'";
            $check_purchase_result = mysqli_query($mysqli, $check_purchase_sql);
           
            if (!$check_purchase_result) {
                echo "Purchase Record Check Error: " . mysqli_error($mysqli);
                exit;
            }

            $purchase_row = mysqli_fetch_assoc($check_purchase_result);
            $purchase_count = $purchase_row['purchase_count'];
            mysqli_free_result($check_purchase_result);

           
            if ($purchase_count > 0) {
                echo "**WARNING!** Customer ID **{$CUST_ID}** cannot be deleted because they have **{$purchase_count}** existing purchase records. Please ensure all associated purchase history is cleared or reassigned before deletion.";
            } else {
                $delete_sql = "DELETE FROM project.customer WHERE CUST_ID='$CUST_ID'";

                if (mysqli_query($mysqli, $delete_sql)) {
                    echo "Customer with ID **{$CUST_ID}** deleted successfully.";
                } else {
                    echo "Delete Error: " . mysqli_error($mysqli);
                }
            }
        } else {
            echo "Error: Customer ID '{$CUST_ID}' not found.";
        }
    } else {
        echo "Delete Error: Customer ID required.";
    }
}



// UPDATE:
if (isset($_POST['update'])) {

    if (!empty($_POST['cust_id'])) {

        $CUST_ID = $_POST['cust_id'];

        // Fetch original row
        $origin = "SELECT * FROM project.customer WHERE CUST_ID='$CUST_ID'";
        $result = $mysqli->query($origin);
        $row = $result->fetch_assoc();

        if (!$row) {
            echo "Error: Customer ID '$CUST_ID' not found.";
        } else {

            // FIELD COMPARISONS (only update if filled)
            $final_EMP_ID = !empty($_POST['emp_id']) ? $_POST['emp_id'] : $row['EMP_ID'];
            $final_FNAME  = !empty($_POST['cust_fname']) ? $_POST['cust_fname'] : $row['CUST_FNAME'];
            $final_LNAME  = !empty($_POST['cust_lname']) ? $_POST['cust_lname'] : $row['CUST_LNAME'];
            $final_CONTACT = !empty($_POST['cust_contactn']) ? $_POST['cust_contactn'] : $row['CUST_CONTACTN'];

            // Update query
            $sql = "
                UPDATE project.customer
                SET
                    EMP_ID='$final_EMP_ID',
                    CUST_FNAME='$final_FNAME',
                    CUST_LNAME='$final_LNAME',
                    CUST_CONTACTN='$final_CONTACT'
                WHERE CUST_ID='$CUST_ID'
            ";

            if (mysqli_query($mysqli, $sql)) {
                echo "Customer updated successfully.";
            } else {
                echo "Update Error: " . mysqli_error($mysqli);
            }
        }

    } else {
        echo "Update Error: Customer ID required.";
    }
}



// DISPLAY TABLE:
$sql = "SELECT * FROM project.customer";
$result = $mysqli->query($sql);

if (!$result) {
    die("Query failed: " . $mysqli->error);
}
?>

<!DOCTYPE html>
<html>
<head>
</head>
<body>
    <!--DISPLAY THE TABLE'S CONTENT-->
    <h2>Customer Records</h2>
    <table border="1">
        <tr>
            <th>CUST_ID</th>
            <th>EMP_ID</th>
            <th>CUST_FNAME</th>
            <th>CUST_LNAME</th>
            <th>CUST_CONTACTN</th>
        </tr>

        <?php
        while ($row = $result->fetch_assoc()) {
        ?>
        <tr>
            <td><?php echo $row['CUST_ID']; ?></td>
            <td><?php echo $row['EMP_ID']; ?></td>
            <td><?php echo $row['CUST_FNAME']; ?></td>
            <td><?php echo $row['CUST_LNAME']; ?></td>
            <td><?php echo $row['CUST_CONTACTN']; ?></td>
        </tr>

        <?php
        }
        $mysqli -> close();
        ?>

    </table><br>

    <form action="/Ricery/frontend/modules/customer.html" method="POST">
        <input type="submit" value="Return">
    </form>
</body>
</html>
