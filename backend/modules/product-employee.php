<?php
require_once '../db.php';

// INSERT
if (isset($_POST['insert'])) {
    if (
        !empty($_POST['prod_id']) && 
        !empty($_POST['emp_id'])
    ) {
        
        $PROD_ID  = $_POST['prod_id'];
        $EMP_ID   = $_POST['emp_id'];

        $is_valid_fk = true; 


        $prod_check_sql = "SELECT PROD_ID FROM project.product WHERE PROD_ID = ?"; 
        $prod_stmt = $mysqli->prepare($prod_check_sql);

        if ($prod_stmt === false) {
            echo "Error preparing PROD_ID FK check: " . $mysqli->error;
            exit;
        }

        $prod_stmt->bind_param("s", $PROD_ID);
        $prod_stmt->execute();
        $prod_stmt->store_result();

        if ($prod_stmt->num_rows == 0) {
            echo "Insert Error: Product ID '{$PROD_ID}' is invalid or does not exist. (Product FK Failed).";
            $is_valid_fk = false;
        }
        $prod_stmt->close();
        
        
        $emp_check_sql = "SELECT EMP_ID FROM project.employee WHERE EMP_ID = ?"; 
        $emp_stmt = $mysqli->prepare($emp_check_sql);

        if ($emp_stmt === false) {
            echo "Error preparing EMP_ID FK check: " . $mysqli->error;
            exit;
        }

        $emp_stmt->bind_param("s", $EMP_ID);
        $emp_stmt->execute();
        $emp_stmt->store_result();

        if ($emp_stmt->num_rows == 0) {
            echo "Insert Error: Employee ID '{$EMP_ID}' is invalid or does not exist. (Employee FK Failed).";
            $is_valid_fk = false;
        }
        $emp_stmt->close();


        if ($is_valid_fk) {
            
            $insert_sql = "INSERT INTO project.product_employee (PROD_ID, EMP_ID) VALUES (?, ?)"; 
            
            $insert_stmt = $mysqli->prepare($insert_sql);
            
            if ($insert_stmt === false) {
                 echo "Error preparing insert statement: " . $mysqli->error;
                 exit;
            }

            $insert_stmt->bind_param(
                "ss", 
                $PROD_ID, 
                $EMP_ID
            ); 

            try {
                if ($insert_stmt->execute()) { // This is likely line 73
                    echo "Data stored in the database successfully.";
                } 
            } catch (mysqli_sql_exception $e) {
                
                // Check the error code from the caught exception
                if ($e->getCode() == 1062) {
                    // Handle the specific duplicate key error
                    echo "Insert Error: The link between Product ID ({$PROD_ID}) and Employee ID ({$EMP_ID}) already exists. This is a duplicate entry.";
                } else {
                    // Handle all other database errors (e.g., connection, syntax)
                    echo "Insert error: An unexpected database error occurred (" . $e->getMessage() . ").";
                }
            }

            $insert_stmt->close();
        
    } else {
        echo "Insert Error: Please fill in the required fields (Product ID and Employee ID).";
    }
    }
}

// DELETE
if (isset($_POST['delete'])) {
    if (
        !empty($_POST['prod_id']) && 
        !empty($_POST['emp_id'])
    ) {
        
        $PROD_ID = $_POST['prod_id'];
        $EMP_ID  = $_POST['emp_id'];

        $delete_sql = "DELETE FROM project.product_employee WHERE PROD_ID = ? AND EMP_ID = ?"; 
        
        $delete_stmt = $mysqli->prepare($delete_sql);
        
        if ($delete_stmt === false) {
             echo "Error preparing delete statement: " . $mysqli->error;
             exit;
        }

        $delete_stmt->bind_param(
            "ss", 
            $PROD_ID, 
            $EMP_ID
        ); 

        try {
            if ($delete_stmt->execute()) {
                
                if ($delete_stmt->affected_rows > 0) {
                    echo "Record for Product ID ({$PROD_ID}) and Employee ID ({$EMP_ID}) deleted successfully.";
                } else {
                    echo "Delete Warning: No record found with Product ID ({$PROD_ID}) and Employee ID ({$EMP_ID}).";
                }
            } else {
                echo "Delete error: " . $delete_stmt->error; 
            }
        } catch (mysqli_sql_exception $e) {
            echo "An unexpected database error occurred during deletion: " . $e->getMessage();
        }

        $delete_stmt->close(); 
        
    } else {
        echo "Delete Error: Both Product ID and Employee ID are required for deletion.";
    }
}

// DISPLAY THE COMPILATION OF PRODUCTS AND EMPLOYEE, TO SEE WHOS IN CHARGE
// THRU A DISPLAY BUTTON IN PRODUCT.HTML

$sql="SELECT * FROM project.product_employee";
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
    <h2>Product-Employee Assignment</h2>
    <table border="1">
        <tr>
            <th>PROD_ID</th>
            <th>EMP_ID</th>
        </tr>

        <?php
        while ($row = $result->fetch_assoc()) {
        ?>
        <tr>
            <td><?php echo $row["PROD_ID"]; ?></td>
            <td><?php echo $row["EMP_ID"]; ?></td>
        </tr>

        <?php
        }
        $mysqli -> close();
        ?>

    </table><br>

    <form id="employeeReturnForm" action="/Ricery/frontend/modules/product.html" method="POST" style="display: none;">
        <input type="submit" value="Return">
    </form>

    <form id="adminReturnForm" action="/Ricery/frontend/modules/product-employee.html" method="POST" style="display: none;">
        <input type="submit" value="Edit">
    </form>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const userRole = localStorage.getItem('userRole');
        
        const adminForm = document.getElementById('adminReturnForm');
        const employeeForm = document.getElementById('employeeReturnForm');

        if (userRole === 'admin') {
            adminForm.style.display = 'block';
        } else if (userRole === 'employee') {
            employeeForm.style.display = 'block';
        } else {
            console.error('User role not found. Cannot determine return path.');
        }
    });
</script>


</body>
</html>