<?php
require_once '../db.php';

//INSERT, DELETE, UPDATE
if (isset($_POST['insert'])) {
    if (
        !empty($_POST['purch_id']) && 
        !empty($_POST['prod_id']) && 
        !empty($_POST['pur_pro_quant']) && 
        !empty($_POST['pur_pro_weight']) && 
        !empty($_POST['pur_pro_uprice'])
    ) {
        
        $PURCH_ID       = $_POST['purch_id'];
        $PROD_ID        = $_POST['prod_id'];
        $PUR_PRO_QUANT  = $_POST['pur_pro_quant'];
        $PUR_PRO_WEIGHT = $_POST['pur_pro_weight'];
        $PUR_PRO_UPRICE = $_POST['pur_pro_uprice'];
        
        $purch_fk_check_sql = "SELECT COUNT(*) FROM project.purchase WHERE PURCH_ID = ?";
        $purch_fk_stmt = $mysqli->prepare($purch_fk_check_sql);
        
        if ($purch_fk_stmt === false) {
            echo "Error preparing PURCH_ID FK check: " . $mysqli->error;
            exit;
        }

        $purch_fk_stmt->bind_param("s", $PURCH_ID);
        $purch_fk_stmt->execute();
        $purch_fk_stmt->bind_result($count);
        $purch_fk_stmt->fetch();
        $purch_fk_stmt->close(); 

        if ($count == 0) {
            echo "Insert Error: PURCH_ID '{$PURCH_ID}' does not exist in the parent 'purchase' table. Foreign Key Constraint Failed.";
            exit;
        }
        
        $prod_check_sql = "SELECT PROD_ID FROM product WHERE PROD_ID = ?"; 
        $prod_stmt = $mysqli->prepare($prod_check_sql);

        if ($prod_stmt === false) {
            echo "Error preparing PROD_ID FK check: " . $mysqli->error;
            exit;
        }

        $prod_stmt->bind_param("s", $PROD_ID); 
        $prod_stmt->execute();
        $prod_stmt->store_result();

        if ($prod_stmt->num_rows == 0) {
            echo "Insert Error: Product ID '{$PROD_ID}' is invalid or does not exist. Foreign Key Constraint Failed (Product FK Failed).";
            $prod_stmt->close();
            exit; 
        }
        $prod_stmt->close(); 
        
        $insert_sql = "INSERT INTO project.purchase_product (PURCH_ID, PROD_ID, PUR_PRO_QUANT, PUR_PRO_WEIGHT, PUR_PRO_UPRICE) 
                        VALUES (?, ?, ?, ?, ?)";

        $insert_stmt = $mysqli->prepare($insert_sql);
        
        if ($insert_stmt === false) {
             echo "Error preparing insert statement: " . $mysqli->error;
             exit;
        }

        $insert_stmt->bind_param(
            "sssis", 
            $PURCH_ID, 
            $PROD_ID,
            $PUR_PRO_QUANT,
            $PUR_PRO_WEIGHT,
            $PUR_PRO_UPRICE
        ); 

        if ($insert_stmt->execute()) {
            echo "Data stored in the database successfully.";
        } else {
            echo "Insert error: " . $insert_stmt->error; 
        }

        $insert_stmt->close();
        
    } else {
        echo "Insert Error: Please fill in the required fields and/or make sure to follow proper format.";
    }
}


// DELETE:
if (isset($_POST['delete'])) {
    if (!empty($_POST['purch_id']) && !empty($_POST['prod_id'])) { 
        
        $PURCH_ID = $_POST['purch_id'];
        $PROD_ID  = $_POST['prod_id'];
        
        // Check if the Composite Key exists
        $check_sql = "SELECT COUNT(*) AS count FROM project.purchase_product WHERE PURCH_ID='{$PURCH_ID}' AND PROD_ID='{$PROD_ID}'";
        $check_result = mysqli_query($mysqli, $check_sql);
        
        if (!$check_result) {
            echo "Database Check Error: " . mysqli_error($mysqli);
            exit;
        }

        $row = mysqli_fetch_assoc($check_result);
        $count = $row['count'];
        
        mysqli_free_result($check_result);
        
        if ($count > 0) {
            
            // Proceed with the DELETE query
            $delete_sql = "DELETE FROM project.purchase_product WHERE PURCH_ID='{$PURCH_ID}' AND PROD_ID='{$PROD_ID}'"; 
            
            if (mysqli_query($mysqli, $delete_sql)) {
                if ($mysqli->affected_rows > 0) {
                    echo "Row with PURCH_ID **{$PURCH_ID}** and PROD_ID **{$PROD_ID}** successfully deleted.";
                } else {
                    echo "Warning: Row was found but not deleted.";
                }
            } else {
                echo "Delete Execution Error: " . mysqli_error($mysqli);
            }
            
        } else {
            echo "Error: Composite Key (PURCH_ID '{$PURCH_ID}', PROD_ID '{$PROD_ID}') was **not found** in the database.";
        }
        
    } else {
        echo "Delete Error: Both PURCH_ID and PROD_ID are required for deletion.";
    }
}


// UPDATE:
if (isset($_POST['update'])) {
    
    if (empty($_POST['purch_id']) || empty($_POST['prod_id'])) {
        echo "Update Error: Both PURCH_ID and PROD_ID are required to identify the row.";
        exit;
    }

    $PURCH_ID       = $_POST['purch_id'];
    $PROD_ID        = $_POST['prod_id'];

    $update_fields = [];
    
    if (!empty($_POST['pur_pro_quant'])) {
        $update_fields[] = "PUR_PRO_QUANT = '{$_POST['pur_pro_quant']}'";
    }
    
    if (!empty($_POST['pur_pro_weight'])) {
        $update_fields[] = "PUR_PRO_WEIGHT = '{$_POST['pur_pro_weight']}'";
    }
    
    if (!empty($_POST['pur_pro_uprice'])) {
        $update_fields[] = "PUR_PRO_UPRICE = '{$_POST['pur_pro_uprice']}'";
    }

    if (empty($update_fields)) {
        echo "Update Notice: No fields (quantity, weight, price) were provided for update.";
        exit;
    }

    $sql = "
    UPDATE project.purchase_product
    SET " . implode(', ', $update_fields) . "
    WHERE PURCH_ID = '{$PURCH_ID}' AND PROD_ID = '{$PROD_ID}'";
    
    if (mysqli_query($mysqli, $sql)) {
        if (mysqli_affected_rows($mysqli) > 0) {
            echo "Row with composite key ({$PURCH_ID}, {$PROD_ID}) is updated successfully.";
        } else {
            echo "Update Notice: Row found, but no changes were made (data was already the same).";
        }
    } else {
        echo "Update Execution Error: " . mysqli_error($mysqli);
    }
}

// DISPLAY THE COMPILATION OF PURCHASES AND PRODUCTS
// THRU A DISPLAY BUTTON IN PURCHASE-PAYMENT.HTML

$sql="SELECT * FROM project.purchase_product";
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
    <h2>List of Transactions</h2>
    <table border="1">
        <tr>
            <th>PURCH_ID</th>
            <th>PROD_ID</th>
            <th>PUR_PRO_QUANT</th>
            <th>PUR_PRO_WEIGHT</th>
            <th>PUR_PRO_UPRICE</th> 
            <th>PUR_PRO_AMT</th>
        </tr>

        <?php
        while ($row = $result->fetch_assoc()) {
        ?>
        <tr>
            <td><?php echo $row["PURCH_ID"]; ?></td>
            <td><?php echo $row["PROD_ID"]; ?></td>
            <td><?php echo $row["PUR_PRO_QUANT"]; ?></td>
            <td><?php echo $row["PUR_PRO_WEIGHT"]; ?></td>
            <td><?php echo $row["PUR_PRO_UPRICE"]; ?></td>
            <td><?php echo $row["PUR_PRO_AMT"]; ?></td>
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