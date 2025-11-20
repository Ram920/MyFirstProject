<?php
session_start();

// --- Session Timeout (15 minutes) ---
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
    session_unset(); session_destroy();
    header("Location: index.php"); // Force redirect to login page
    exit;
}

require_once __DIR__ . '/../db_connect.php';
require_once 'config.php'; // Include configuration

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

// If we've reached here, the user is logged in. Now we can update their activity time.
$_SESSION['last_activity'] = time();

$enquiries = $conn->query("SELECT * FROM enquiries ORDER BY submission_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Enquiries</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid mt-5">
    <a href="index.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>View Enquiries</h2>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>
    <a href="export_enquiries.php" class="btn btn-success mb-3">Export to Excel (CSV)</a>

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Name</th>
                    <th>Company</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Products</th>
                    <th>Type</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $enquiries->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo date('d M Y, H:i', strtotime($row['submission_date'])); ?></td>
                        <td>
                            <select class="form-control status-change" data-id="<?php echo $row['id']; ?>">
                                <option value="New" <?php if($row['status'] == 'New') echo 'selected'; ?>>New</option>
                                <option value="Contacted" <?php if($row['status'] == 'Contacted') echo 'selected'; ?>>Contacted</option>
                                <option value="Quoted" <?php if($row['status'] == 'Quoted') echo 'selected'; ?>>Quoted</option>
                                <option value="Closed" <?php if($row['status'] == 'Closed') echo 'selected'; ?>>Closed</option>
                            </select>
                        </td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['company_name']); ?></td>
                        <td><a href="mailto:<?php echo htmlspecialchars($row['email']); ?>"><?php echo htmlspecialchars($row['email']); ?></a></td>
                        <td><a href="tel:<?php echo htmlspecialchars($row['phone']); ?>"><?php echo htmlspecialchars($row['phone']); ?></a></td>
                        <td><?php echo htmlspecialchars($row['products_inquired']); ?></td>
                        <td><?php echo htmlspecialchars($row['inquiry_type']); ?></td>
                        <td><button class="btn btn-info btn-sm" data-toggle="modal" data-target="#detailsModal-<?php echo $row['id']; ?>">View</button></td>
                    </tr>

                    <!-- Details Modal -->
                    <div class="modal fade" id="detailsModal-<?php echo $row['id']; ?>" tabindex="-1" role="dialog">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Enquiry #<?php echo $row['id']; ?> Details</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                </div>
                                <div class="modal-body">
                                    <p><strong>Delivery Location:</strong> <?php echo htmlspecialchars($row['delivery_location']); ?></p>
                                    <p><strong>Quantity:</strong> <?php echo htmlspecialchars($row['quantity']); ?></p>
                                    <p><strong>Customization:</strong><br><?php echo nl2br(htmlspecialchars($row['customization_req'])); ?></p>
                                    <p><strong>Additional Req:</strong><br><?php echo nl2br(htmlspecialchars($row['additional_req'])); ?></p>
                                    <?php if ($row['drawing_file']): ?>
                                        <p><strong>Drawing:</strong> <a href="../uploads/<?php echo htmlspecialchars($row['drawing_file']); ?>" target="_blank">View/Download Drawing</a></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="../assets/vendor/jquery/jquery.min.js"></script>
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function(){
    $('.status-change').on('change', function(){
        var enquiryId = $(this).data('id');
        var newStatus = $(this).val();
        
        $.ajax({
            url: 'update_enquiry_status.php',
            type: 'POST',
            data: {
                id: enquiryId,
                status: newStatus
            },
            success: function(response){
                // You can add a success message here if you want
                console.log('Status updated successfully');
            },
            error: function(){
                alert('Error updating status.');
            }
        });
    });
});
</script>
</body>
</html>
<?php $conn->close(); ?>
