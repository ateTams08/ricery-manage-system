<?php
require_once '../db.php';

//INSERTION:
if (isset($_POST['insert'])) {

    if (!empty($_POST['emp_id']) &&
        !empty($_POST['emp_fname']) &&
        !empty($_POST['emp_lname']) &&
        !empty($_POST['emp_gender']) &&
        !empty($_POST['emp_contactn']) &&
        !empty($_POST['emp_email']) &&
        !empty($_POST['emp_bankacc'])
    ) {

        $EMP_ID = $_POST['emp_id'];
        $EMP_FNAME = $_POST['emp_fname'];
        $EMP_LNAME = $_POST['emp_lname'];
        $EMP_GENDER = $_POST['emp_gender'];
        $EMP_CONTACTN = $_POST['emp_contactn'];
        $EMP_EMAIL = $_POST['emp_email'];
        $EMP_BANKACC = $_POST['emp_bankacc'];

        // Check if PK exists
        $check_sql = "SELECT COUNT(*) AS count FROM project.employee WHERE EMP_ID='$EMP_ID'";
        $check = mysqli_query($mysqli, $check_sql);

        if (!$check) {
            echo "Database Check Error: " . mysqli_error($mysqli);
            exit;
        }

        $row = mysqli_fetch_assoc($check);
        $count = $row['count'];
        mysqli_free_result($check);

        if ($count == 0) {
            // Insert
            $sql = "
            INSERT INTO project.employee
            (EMP_ID, EMP_FNAME, EMP_LNAME, EMP_GENDER, EMP_CONTACTN, EMP_EMAIL, EMP_BANKACC)
            VALUES('$EMP_ID', '$EMP_FNAME', '$EMP_LNAME', '$EMP_GENDER', '$EMP_CONTACTN', '$EMP_EMAIL', '$EMP_BANKACC')";

            if (mysqli_query($mysqli, $sql)) {
                echo "Employee successfully added.";
            } else {
                echo "Insert Error: " . mysqli_error($mysqli);
            }
        } else {
            echo "Insert Error: Employee ID '{$EMP_ID}' already exists.";
        }

    } else {
        echo "Insert Error: Please fill in all required fields.";
    }
}


//DELETE:
if (isset($_POST['delete'])) {

    if (!empty($_POST['emp_id'])) {
        
        $EMP_ID = mysqli_real_escape_string($mysqli, $_POST['emp_id']);

        $check_emp_sql = "SELECT EMP_ID FROM project.employee WHERE EMP_ID='$EMP_ID'";
        $check_emp = mysqli_query($mysqli, $check_emp_sql);

        if (!$check_emp) {
            echo "Database Check Error: " . mysqli_error($mysqli);
            exit;
        }

        if (mysqli_num_rows($check_emp) == 0) {
            echo "Error: Employee ID '{$EMP_ID}' not found.";
            mysqli_free_result($check_emp);
            exit;
        }
        mysqli_free_result($check_emp); 

        
        $check_prod_emp_sql = "SELECT COUNT(*) AS prod_emp_count FROM project.product_employee WHERE EMP_ID='$EMP_ID'";
        $check_prod_emp = mysqli_query($mysqli, $check_prod_emp_sql);

        if (!$check_prod_emp) {
            echo "Product-Employee Check Error: " . mysqli_error($mysqli);
            exit;
        }

        $row_prod_emp = mysqli_fetch_assoc($check_prod_emp);
        $prod_emp_count = $row_prod_emp['prod_emp_count']; 
        mysqli_free_result($check_prod_emp);
        
        
        $check_supplier_sql = "SELECT COUNT(*) AS supplier_count FROM project.supplier WHERE EMP_ID='$EMP_ID'";
        $check_supplier = mysqli_query($mysqli, $check_supplier_sql);

        if (!$check_supplier) {
            echo "Supplier Check Error: " . mysqli_error($mysqli);
            exit;
        }

        $row_supplier = mysqli_fetch_assoc($check_supplier);
        $supplier_count = $row_supplier['supplier_count']; 
        mysqli_free_result($check_supplier);

        
        if ($prod_emp_count > 0 || $supplier_count > 0) {
            
            $warning_message = "**WARNING!** Employee ID **{$EMP_ID}** cannot be deleted because they have associated records in other tables. ";
            
            if ($prod_emp_count > 0) {
                $warning_message .= "They are currently assigned to **{$prod_emp_count}** product records in the 'product_employee' table. ";
            }
            
            if ($supplier_count > 0) {
                $warning_message .= "They are linked to **{$supplier_count}** records in the 'supplier' table. ";
            }

            $warning_message .= "Please resolve these dependencies first.";
            echo $warning_message;
            
        } else {
            $delete_parent_sql = "DELETE FROM project.employee WHERE EMP_ID='$EMP_ID'";

            if (mysqli_query($mysqli, $delete_parent_sql)) {
                echo "Employee ID **{$EMP_ID}** deleted successfully.";
            } else {
                echo "Delete Execution Error: " . mysqli_error($mysqli); 
            }
        }

    } else {
        echo "Delete Error: Please provide Employee ID.";
    }
}

//UPDATE:
if (isset($_POST['update'])) {

    if (empty($_POST['emp_id'])) {
        echo "Update Error: Employee ID is required.";
    } else {

        $EMP_ID = $_POST['emp_id'];
        $EMP_FNAME = $_POST['emp_fname'];
        $EMP_LNAME = $_POST['emp_lname'];
        $EMP_GENDER = $_POST['emp_gender'];
        $EMP_CONTACTN = $_POST['emp_contactn'];
        $EMP_EMAIL = $_POST['emp_email'];
        $EMP_BANKACC = $_POST['emp_bankacc'];

        // Load original row
        $origin = "SELECT * FROM project.employee WHERE EMP_ID='$EMP_ID'";
        $result = $mysqli->query($origin);
        $row = $result->fetch_assoc();

        if (!$row) {
            echo "Error: Employee ID '{$EMP_ID}' not found.";
        } else {

            // FIELD-BY-FIELD UPDATE
            $final_FNAME   = !empty($EMP_FNAME)   ? $EMP_FNAME   : $row['EMP_FNAME'];
            $final_LNAME   = !empty($EMP_LNAME)   ? $EMP_LNAME   : $row['EMP_LNAME'];
            $final_GENDER  = !empty($EMP_GENDER)  ? $EMP_GENDER  : $row['EMP_GENDER'];
            $final_CONTACT = !empty($EMP_CONTACTN)? $EMP_CONTACTN : $row['EMP_CONTACTN'];
            $final_EMAIL   = !empty($EMP_EMAIL)   ? $EMP_EMAIL   : $row['EMP_EMAIL'];
            $final_BANKACC = !empty($EMP_BANKACC) ? $EMP_BANKACC : $row['EMP_BANKACC'];

            // UPDATE STATEMENT
            $sql = "
            UPDATE project.employee
            SET
                EMP_FNAME='$final_FNAME',
                EMP_LNAME='$final_LNAME',
                EMP_GENDER='$final_GENDER',
                EMP_CONTACTN='$final_CONTACT',
                EMP_EMAIL='$final_EMAIL',
                EMP_BANKACC='$final_BANKACC'
            WHERE EMP_ID='$EMP_ID'";

            if (mysqli_query($mysqli, $sql)) {
                echo "Employee successfully updated.";
            } else {
                echo "Update Error: " . mysqli_error($mysqli);
            }
        }
    }
}


//DISPLAY TABLE AFTER OPERATIONS:
$sql = "SELECT * FROM project.employee";
$result = $mysqli->query($sql);

if (!$result) {
    die("Query failed: " . $mysqli->error);
}
?>

<!DOCTYPE html>
<html>
<body>

<h2>Employee Table</h2>
<table border="1">
    <tr>
        <th>EMP_ID</th>
        <th>EMP_FNAME</th>
        <th>EMP_LNAME</th>
        <th>EMP_GENDER</th>
        <th>EMP_CONTACTN</th>
        <th>EMP_EMAIL</th>
        <th>EMP_BANKACC</th>
    </tr>

<?php while ($row = $result->fetch_assoc()) { ?>
    <tr>
        <td><?= $row["EMP_ID"]; ?></td>
        <td><?= $row["EMP_FNAME"]; ?></td>
        <td><?= $row["EMP_LNAME"]; ?></td>
        <td><?= $row["EMP_GENDER"]; ?></td>
        <td><?= $row["EMP_CONTACTN"]; ?></td>
        <td><?= $row["EMP_EMAIL"]; ?></td>
        <td><?= $row["EMP_BANKACC"]; ?></td>
    </tr>
<?php } ?>

</table>

<br>
<form action="/Ricery/frontend/modules/employee.html" method="POST">
    <input type="submit" value="Return">
</form>

</body>
</html>

<?php $mysqli->close(); ?>
