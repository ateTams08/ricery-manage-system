<?php
require_once '../db.php';

// INSERTION:
if (isset($_POST['insert'])) {
    if (!empty($_POST['purch_id']) && 
        !empty($_POST['cust_id']) && 
        !empty($_POST['pay_refid']) && 
        !empty($_POST['purch_amt']) && 
        !empty($_POST['purch_date'])
    ) {
        
        $PURCH_ID = $_POST['purch_id'];
        $CUST_ID = $_POST['cust_id'];
        $PAY_REFID = $_POST['pay_refid'];
        $PURCH_AMT = $_POST['purch_amt'];
        $PURCH_DATE = $_POST['purch_date']; 
        
        // FKs CHECK: CUST_ID
        $cust_check_sql = "SELECT CUST_ID FROM customer WHERE CUST_ID = ?";
        $cust_stmt = $mysqli->prepare($cust_check_sql);
        
        if ($cust_stmt === false) {
             echo "Error preparing customer check: " . $mysqli->error;
             exit;
        }
        
        $cust_stmt->bind_param("s", $CUST_ID); 
        $cust_stmt->execute();
        $cust_stmt->store_result();

        if ($cust_stmt->num_rows == 0) {
            echo "Insert Error: Customer ID '{$CUST_ID}' is invalid or does not exist. Foreign Key Constraint Failed.";
            $cust_stmt->close();
            exit;
        }
        $cust_stmt->close(); 

        // FKs CHECK: PAY_REFID
        $pay_check_sql = "SELECT PAY_REFID FROM payment WHERE PAY_REFID = ?";
        $pay_stmt = $mysqli->prepare($pay_check_sql);

        if ($pay_stmt === false) {
            echo "Error preparing payment check: " . $mysqli->error;
            exit;
        }

        // Bind the PAY_REFID parameter
        $pay_stmt->bind_param("s", $PAY_REFID); 
        $pay_stmt->execute();
        $pay_stmt->store_result();

        if ($pay_stmt->num_rows == 0) {
            echo "Insert Error: Payment Reference ID '{$PAY_REFID}' is invalid or does not exist. Foreign Key Constraint Failed.";
            $pay_stmt->close();
            exit; 
        }
        $pay_stmt->close(); 

        // PK CHECK
        $pk_check_sql = "SELECT COUNT(*) FROM project.purchase WHERE PURCH_ID = ?";
        $pk_stmt = $mysqli->prepare($pk_check_sql);
        
        if ($pk_stmt === false) { /* Handle error */ }

        $pk_stmt->bind_param("s", $PURCH_ID);
        $pk_stmt->execute();
        $pk_stmt->bind_result($count);
        $pk_stmt->fetch();
        $pk_stmt->close(); 

        
        if ($count == 0) {
            $insert_sql = "INSERT INTO project.purchase (PURCH_ID, CUST_ID, PAY_REFID, PURCH_AMT, PURCH_DATE) 
                           VALUES (?, ?, ?, ?, ?)";

            $insert_stmt = $mysqli->prepare($insert_sql);
            
            if ($insert_stmt === false) {
                 echo "Error preparing insert statement: " . $mysqli->error;
                 exit;
            }

            $insert_stmt->bind_param("sssis", $PURCH_ID, $CUST_ID, $PAY_REFID, $PURCH_AMT, $PURCH_DATE); 

            if ($insert_stmt->execute()) {
                echo "Data stored in the database successfully.";
            } else {
                // This catches other potential database errors.
                echo "Insert error: " . $insert_stmt->error; 
            }

            $insert_stmt->close();
            
        } else {
            echo "Insert Error: PURCH_ID '{$PURCH_ID}' already exists.";
        }
        
    } else {
        echo "Insert Error: Please fill in the required fields and/or make sure to follow proper format.";
    }
}

// DELETE:
if (isset($_POST['delete'])) {
    if (!empty($_POST['purch_id'])) {
        $PURCH_ID = $_POST['purch_id'];

        // Check if the PROD_ID exists in the database.
        $check_sql = "SELECT COUNT(*) AS count FROM project.purchase WHERE PURCH_ID='$PURCH_ID'";
        $check_result = mysqli_query($mysqli, $check_sql);
        
        if (!$check_result) {
            echo "Database Check Error: " . mysqli_error($mysqli);
            exit;
        }

        $row = mysqli_fetch_assoc($check_result);
        $count = $row['count'];
        
        mysqli_free_result($check_result); 

        if ($count > 0) {
            
            // ID WAS FOUND: Proceed with the DELETE query
            $delete_sql = "DELETE FROM project.purchase WHERE PURCH_ID='$PURCH_ID'"; 
            
            if (mysqli_query($mysqli, $delete_sql)) {
                if ($mysqli->affected_rows > 0) {
                    echo "Data with PRODUCT ID **{$PURCH_ID}** is deleted successfully.";
                } else {
                    echo "Warning: ID was found but not deleted.";
                }
            } else {
                // Error during the DELETE execution
                echo "Delete Execution Error: " . mysqli_error($mysqli);
            }
            
        } else {
            // ID WAS NOT FOUND: Display ERROR message
            echo "Error: PURCH_ID'{$PURCH_ID}' was **not found** in the database.";
        }
        
    } else {
        echo "Delete Error: Please fill in the required PRODUCT ID field and/or make sure to follow proper format.";
    }
}


// UPDATE:
if (isset($_POST['update'])) {
    $PURCH_ID = $_POST['purch_id'];
    $CUST_ID = $_POST['cust_id'];
    $PAY_REFID = $_POST['pay_refid'];
    $PURCH_AMT = $_POST['purch_amt'];
    $PURCH_DATE = $_POST['purch_date']; 

    $origin = "SELECT * FROM project.purchase WHERE PURCH_ID='$PURCH_ID'";
    $result = $mysqli->query($origin);
    $row = $result->fetch_assoc();

    // If no row found, display ERROR message. 
    if (!$row) {
        echo "Error: Product ID '{$PURCH_ID}' was **not found** in the database.";
    } else {

        // CUST_ID (FK)
            $final_CUST_ID = $row['CUST_ID']; 
            if (!empty($CUST_ID) && $CUST_ID != $row['CUST_ID']) {
                
                $cust_check_sql = "SELECT 1 FROM customer WHERE CUST_ID = ?";
                $cust_stmt = $mysqli->prepare($cust_check_sql);
                
                if ($cust_stmt === false) {
                    $errors[] = "Database Error: Could not prepare CUST_ID validation check.";
                } else {
                    $cust_stmt->bind_param("s", $CUST_ID); 
                    $cust_stmt->execute();
                    $cust_stmt->store_result();

                    if ($cust_stmt->num_rows > 0) {
                        $final_CUST_ID = $CUST_ID;
                    } else {
                        $errors[] = "Error: New Customer ID '{$CUST_ID}' is invalid or does not exist.";
                    }
                    $cust_stmt->close();
                }
            }
        // PAY_REFID (FK)
            $final_PAY_REFID = $row['PAY_REFID']; 
            if (!empty($PAY_REFID) && $PAY_REFID != $row['PAY_REFID']) {                

                $pay_check_sql = "SELECT 1 FROM payment WHERE PAY_REFID = ?";
                $pay_stmt = $mysqli->prepare($pay_check_sql);

                if ($pay_stmt === false) {
                    $errors[] = "Database Error: Could not prepare PAY_REFID validation check.";
                } else {
                    $pay_stmt->bind_param("s", $PAY_REFID); 
                    $pay_stmt->execute();
                    $pay_stmt->store_result();

                    if ($pay_stmt->num_rows > 0) {
                        $final_PAY_REFID = $PAY_REFID; 
                    } else {
                        $errors[] = "Error: New Payment Ref ID '{$PAY_REFID}' is invalid or does not exist.";
                    }
                    $pay_stmt->close();
                }
            }

        
        // PURCH_AMT
        if (!empty($PURCH_AMT) && $PURCH_AMT != $row['PURCH_AMT']) {
            $final_PURCH_AMT = $PURCH_AMT;
        } else {
            $final_PURCH_AMT = $row['PURCH_AMT'];
        }


        // PURCH_DATE
        if (!empty($PURCH_DATE) && $PURCH_DATE != $row['PURCH_DATE']) {
            $final_PURCH_DATE = $PURCH_DATE;
        } else {
            $final_PURCH_DATE = $row['PURCH_DATE'];
        }

        // Proceed with the UPDATE
        $sql = "
        UPDATE project.purchase
        SET 
            CUST_ID='$final_CUST_ID',
            PAY_REFID= '$final_PAY_REFID',
            PURCH_AMT= $final_PURCH_AMT,
            PURCH_DATE= '$final_PURCH_DATE'
        WHERE PURCH_ID='$PURCH_ID'";

        if (mysqli_query($mysqli, $sql)) {
            echo "Data is updated in the database successfully.";
        } else {
            echo "Update error: " . mysqli_error($mysqli);
        }
    }
}


// DISPLAY THE TABLES AFTER INSERT/DELETE/UPDATE:
$sql="SELECT * FROM project.purchase";
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
    <h2>Purchase History</h2>
    <table border="1">
        <tr>
            <th>PURCH_ID</th>
            <th>CUST_ID</th>
            <th>PAY_REFID</th>
            <th>PURCH_AMT</th>
            <th>PURCH_DATE</th>
        </tr>

        <?php
        while ($row = $result->fetch_assoc()) {
        ?>
        <tr>
            <td><?php echo $row["PURCH_ID"]; ?></td>
            <td><?php echo $row["CUST_ID"]; ?></td>
            <td><?php echo $row["PAY_REFID"]; ?></td>
            <td><?php echo $row["PURCH_AMT"]; ?></td>
            <td><?php echo $row["PURCH_DATE"]; ?></td>
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