<?php
require_once '../db.php';

// INSERTION:
if (isset($_POST['insert'])) {
    // Check if all required fields are answered, then insert.
    if (!empty($_POST['prod_id']) && 
        !empty($_POST['prod_brand']) && 
        !empty($_POST['prod_type']) && 
        !empty($_POST['prod_weight']) && 
        !empty($_POST['prod_quant']) && 
        !empty($_POST['prod_uprice'])
    ) {
        
        $PROD_ID = $_POST['prod_id'];
        $PROD_BRAND = $_POST['prod_brand'];
        $PROD_TYPE = $_POST['prod_type'];
        $PROD_WEIGHT = $_POST['prod_weight'];
        $PROD_QUANT = $_POST['prod_quant'];
        $PROD_UPRICE = $_POST['prod_uprice'];

        // Check if the PROD_ID exists in the database.
        $check_sql = "SELECT COUNT(*) AS count FROM project.product WHERE PROD_ID='$PROD_ID'";
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
            $sql = "INSERT INTO project.product (PROD_ID, PROD_BRAND, PROD_TYPE, PROD_WEIGHT, PROD_QUANT, PROD_UPRICE) 
            VALUES ('$PROD_ID', '$PROD_BRAND', '$PROD_TYPE', '$PROD_WEIGHT', '$PROD_QUANT', '$PROD_UPRICE')";

            if (mysqli_query($mysqli, $sql)) {
                echo "Data stored in the database successfully.";
            } else {
                echo "Insert error: " . mysqli_error($mysqli);
            }
        // PK ALREADY EXISTS.
        } else {
            echo "Insert Error: PROD_ID '{$PROD_ID}' already exists.";
        }
    } else {
        echo "Insert Error: Please fill in the required fields and/or make sure to follow proper format.";
    }
}

// DELETE:
if (isset($_POST['delete'])) {
    if (!empty($_POST['prod_id'])) {
        
        $PROD_ID = mysqli_real_escape_string($mysqli, $_POST['prod_id']);

        $check_sql = "SELECT COUNT(*) AS count FROM project.product WHERE PROD_ID='$PROD_ID'";
        $check_result = mysqli_query($mysqli, $check_sql);
        
        if (!$check_result) {
            echo "Database Check Error: " . mysqli_error($mysqli);
            exit;
        }

        $row = mysqli_fetch_assoc($check_result);
        $count = $row['count'];
        mysqli_free_result($check_result); 

        if ($count > 0) {
            
            $check_child_sql = "SELECT COUNT(*) AS child_count FROM project.product_employee WHERE PROD_ID='$PROD_ID'";
            $check_child_result = mysqli_query($mysqli, $check_child_sql);

            if (!$check_child_result) {
                echo "Child Record Check Error: " . mysqli_error($mysqli);
                exit;
            }

            $child_row = mysqli_fetch_assoc($check_child_result);
            $child_count = $child_row['child_count']; 
            mysqli_free_result($check_child_result);
            
            if ($child_count > 0) {
                echo "**WARNING!** Product ID **{$PROD_ID}** cannot be deleted because it is currently linked to **{$child_count}** employee assignments in the 'product_employee' table. Please remove the product from those assignments first.";
            } else {
                $delete_sql = "DELETE FROM project.product WHERE PROD_ID='$PROD_ID'"; 
                
                if (mysqli_query($mysqli, $delete_sql)) {
                    if ($mysqli->affected_rows > 0) {
                        echo "Data with PRODUCT ID **{$PROD_ID}** is deleted successfully.";
                    } else {
                        echo "Warning: ID was found but not deleted.";
                    }
                } else {
                    echo "Delete Execution Error: " . mysqli_error($mysqli);
                }
            }
            
        } else {
            echo "Error: Product ID '{$PROD_ID}' was **not found** in the database.";
        }
        
    } else {
        echo "Delete Error: Please fill in the required PRODUCT ID field and/or make sure to follow proper format.";
    }
}



// UPDATE:
if (isset($_POST['update'])) {
    // PROD_ID Row to Update
    $PROD_ID = $_POST['prod_id'];
    $PROD_BRAND = $_POST['prod_brand'];
    $PROD_TYPE = $_POST['prod_type'];
    $PROD_WEIGHT = $_POST['prod_weight'];
    $PROD_QUANT = $_POST['prod_quant'];
    $PROD_UPRICE = $_POST['prod_uprice'];

    $origin = "SELECT * FROM project.product WHERE PROD_ID='$PROD_ID'";
    $result = $mysqli->query($origin);
    $row = $result->fetch_assoc();

    // If no row found, display ERROR message. 
    if (!$row) {
        echo "Error: Product ID '{$PROD_ID}' was **not found** in the database.";
    } else {

        // PROD_BRAND
        if (!empty($PROD_BRAND) && $PROD_BRAND != $row['PROD_BRAND']) {
            $final_PROD_BRAND = $PROD_BRAND;
        } else {
            $final_PROD_BRAND = $row['PROD_BRAND'];
        }

        // PROD_TYPE
        if (!empty($PROD_TYPE) && $PROD_TYPE != $row['PROD_TYPE']) {
            $final_PROD_TYPE = $PROD_TYPE;
        } else {
            $final_PROD_TYPE = $row['PROD_TYPE'];
        }

        // PROD_WEIGHT
        if (!empty($PROD_WEIGHT) && $PROD_WEIGHT != $row['PROD_WEIGHT']) {
            $final_PROD_WEIGHT = $PROD_WEIGHT;
        } else {
            $final_PROD_WEIGHT = $row['PROD_WEIGHT'];
        }

        // PROD_QUANT
        if (!empty($PROD_QUANT) && $PROD_QUANT != $row['PROD_QUANT']) {
            $final_PROD_QUANT = $PROD_QUANT;
        } else {
            $final_PROD_QUANT = $row['PROD_QUANT'];
        }

        // PROD_UPRICE
        if (!empty($PROD_UPRICE) && $PROD_UPRICE != $row['PROD_UPRICE']) {
            $final_PROD_UPRICE = $PROD_UPRICE;
        } else {
            $final_PROD_UPRICE = $row['PROD_UPRICE'];
        }

        // Proceed with the UPDATE
        $sql = "
        UPDATE project.product
        SET 
            PROD_BRAND='$final_PROD_BRAND',
            PROD_TYPE= '$final_PROD_TYPE',
            PROD_WEIGHT= $final_PROD_WEIGHT,
            PROD_QUANT= $final_PROD_QUANT,
            PROD_UPRICE= $final_PROD_UPRICE
        WHERE PROD_ID='$PROD_ID'";

        if (mysqli_query($mysqli, $sql)) {
            echo "Data is updated in the database successfully.";
        } else {
            echo "Update error: " . mysqli_error($mysqli);
        }
    }
}


// DISPLAY THE TABLES AFTER INSERT/DELETE/UPDATE:
$sql="SELECT * FROM project.product";
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
    <h2>Product Inventory</h2>
    <table border="1">
        <tr>
            <th>PROD_ID</th>
            <th>PROD_BRAND</th>
            <th>PROD_TYPE</th>
            <th>PROD_WEIGHT</th>
            <th>PROD_QUANT</th>
            <th>PROD_UPRICE</th>
        </tr>

        <?php
        while ($row = $result->fetch_assoc()) {
        ?>
        <tr>
            <td><?php echo $row["PROD_ID"]; ?></td>
            <td><?php echo $row["PROD_BRAND"]; ?></td>
            <td><?php echo $row["PROD_TYPE"]; ?></td>
            <td><?php echo $row["PROD_WEIGHT"]; ?></td>
            <td><?php echo $row["PROD_QUANT"]; ?></td>
            <td><?php echo $row["PROD_UPRICE"]; ?></td>
        </tr>

        <?php
        }
        $mysqli -> close();
        ?>

    </table><br>

    <form action="/Ricery/frontend/modules/product.html" method="POST">
        <input type="submit" value="Return">
    </form>

</body>
</html>