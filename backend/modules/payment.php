<?php
require_once '../db.php';

// INSERTION:
if (isset($_POST['insert'])) {
    // Check if all required fields are answered, then insert.
    if (!empty($_POST['pay_refid']) && 
        !empty($_POST['pay_mode']) && 
        !empty($_POST['pay_amt'])
    ) {
        
        $PAY_REFID = $_POST['pay_refid'];
        $PAY_MODE = $_POST['pay_mode'];
        $PAY_AMT = $_POST['pay_amt'];

        // Check if the PROD_ID exists in the database.
        $check_sql = "SELECT COUNT(*) AS count FROM project.payment WHERE PAY_REFID='$PAY_REFID'";
        $check_result = mysqli_query($mysqli, $check_sql);
        
        if (!$check_result) {
            echo "Database Check Error: " . mysqli_error($mysqli);
            exit;
        }

        $row = mysqli_fetch_assoc($check_result);
        $count = $row['count'];
        
        mysqli_free_result($check_result); 

        // PK DOES NOT EXIST YET: Proceed with Insertion. 
        if ($count == 0) {
            $sql = "INSERT INTO project.payment (PAY_REFID, PAY_MODE, PAY_AMT) 
            VALUES ('$PAY_REFID', '$PAY_MODE', '$PAY_AMT')";

            if (mysqli_query($mysqli, $sql)) {
                echo "Data stored in the database successfully.";
            } else {
                echo "Insert error: " . mysqli_error($mysqli);
            }
        // PK ALREADY EXISTS.
        } else {
            echo "Insert Error: PAY_REFID '{$PAY_REFID}' already exists.";
        }
    } else {
        echo "Insert Error: Please fill in the required fields and/or make sure to follow proper format.";
    }
}

// DELETE:
if (isset($_POST['delete'])) {
    if (!empty($_POST['pay_refid'])) {
        
        $PAY_REFID = mysqli_real_escape_string($mysqli, $_POST['pay_refid']);

        $check_sql = "SELECT COUNT(*) AS count FROM project.payment WHERE PAY_REFID='$PAY_REFID'";
        $check_result = mysqli_query($mysqli, $check_sql);
        
        if (!$check_result) {
            echo "Database Check Error: " . mysqli_error($mysqli);
            exit;
        }

        $row = mysqli_fetch_assoc($check_result);
        $count = $row['count'];
        mysqli_free_result($check_result); 

        if ($count > 0) {
            
            $check_purchase_sql = "SELECT COUNT(*) AS purchase_count FROM project.purchase WHERE PAY_REFID='$PAY_REFID'";
            $check_purchase_result = mysqli_query($mysqli, $check_purchase_sql);

            if (!$check_purchase_result) {
                echo "Purchase Record Check Error: " . mysqli_error($mysqli);
                exit;
            }

            $purchase_row = mysqli_fetch_assoc($check_purchase_result);
            $purchase_count = $purchase_row['purchase_count']; 
            mysqli_free_result($check_purchase_result);
            
            if ($purchase_count > 0) {
                echo "**WARNING!** Payment ID **{$PAY_REFID}** cannot be deleted because it is linked to **{$purchase_count}** existing records in the 'purchase' table. Please remove the associated purchases first.";
            } else {
                $delete_sql = "DELETE FROM project.payment WHERE PAY_REFID='$PAY_REFID'"; 
                
                if (mysqli_query($mysqli, $delete_sql)) {
                    if ($mysqli->affected_rows > 0) {
                        echo "Data with PAY_REFID **{$PAY_REFID}** is deleted successfully.";
                    } else {
                        echo "Warning: ID was found but not deleted.";
                    }
                } else {
                    echo "Delete Execution Error: " . mysqli_error($mysqli);
                }
            }
            
        } else {
            echo "Error: PAY_REFID '{$PAY_REFID}' was **not found** in the database.";
        }
        
    } else {
        echo "Delete Error: Please provide the required PAY_REFID.";
    }
}



// UPDATE:
if (isset($_POST['update'])) {
    // PAY_REFID Row to Update
    $PAY_REFID = $_POST['pay_refid'];
    $PAY_MODE = $_POST['pay_mode'];
    $PAY_AMT = $_POST['pay_amt'];

    $origin = "SELECT * FROM project.payment WHERE PAY_REFID='$PAY_REFID'";
    $result = $mysqli->query($origin);
    $row = $result->fetch_assoc();

    // If no row found, display ERROR message. 
    if (!$row) {
        echo "Error: PAY_REFID '{$PAY_REFID}' was **not found** in the database.";
    } else {

        // PAY_MODE
        if (!empty($PAY_MODE) && $PAY_MODE != $row['PAY_MODE']) {
            $final_PAY_MODE = $PAY_MODE;
        } else {
            $final_PAY_MODE = $row['PAY_MODE'];
        }

        // PAY_AMT
        if (!empty($PAY_AMT) && $PAY_AMT != $row['PAY_AMT']) {
            $final_PAY_AMT = $PAY_AMT;
        } else {
            $final_PAY_AMT = $row['PAY_AMT'];
        }

        // Proceed with the UPDATE
        $sql = "
        UPDATE project.payment
        SET 
            PAY_MODE='$final_PAY_MODE',
            PAY_AMT= '$final_PAY_AMT'
        WHERE PAY_REFID='$PAY_REFID'";

        if (mysqli_query($mysqli, $sql)) {
            echo "Data is updated in the database successfully.";
        } else {
            echo "Update error: " . mysqli_error($mysqli);
        }
    }
}


// DISPLAY THE TABLES AFTER INSERT/DELETE/UPDATE:
$sql="SELECT * FROM project.payment";
$result= $mysqli -> query($sql);

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
    <h2>Payment History</h2>
    <table border="1">
        <tr>
            <th>PAY_REFID</th>
            <th>PAY_MODE</th>
            <th>PAY_AMT</th>
        </tr>

        <?php
        while ($row = $result->fetch_assoc()) {
        ?>
        <tr>
            <td><?php echo $row["PAY_REFID"]; ?></td>
            <td><?php echo $row["PAY_MODE"]; ?></td>
            <td><?php echo $row["PAY_AMT"]; ?></td>
        </tr>

        <?php
        }
        $mysqli -> close();
        ?>

    </table><br>

    <form action="/Ricery/frontend/modules/transaction.html" method="POST">
        <input type="submit" value="Return">
    </form>

</body>
</html>