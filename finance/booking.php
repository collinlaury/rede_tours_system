<?php
include '../includes/auth.php';
include '../includes/config.php';
include '../includes/functions.php';

/* DELETE BOOKING ACTION */
if(isset($_GET['delete_id'])){
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    $delete_query = "DELETE FROM bookings WHERE id = '$delete_id'";
    if(mysqli_query($conn, $delete_query)){
        header("Location: booking.php");
        exit();
    }
}

/* ADD NEW BOOKING */
if(isset($_POST['add_booking'])){
    $client_name = mysqli_real_escape_string($conn, $_POST['client_name']);
    $tour_title = mysqli_real_escape_string($conn, $_POST['tour_title']);
    $currency = mysqli_real_escape_string($conn, $_POST['currency']); 
    $full_deposit = mysqli_real_escape_string($conn, $_POST['full_deposit']);
    $deposit = mysqli_real_escape_string($conn, $_POST['deposit']);
    $tour_date = mysqli_real_escape_string($conn, $_POST['tour_date']);

    $query = "INSERT INTO bookings (client_name, tour_title, currency, full_deposit, deposit, tour_date, status)  
              VALUES ('$client_name', '$tour_title', '$currency', '$full_deposit', '$deposit', '$tour_date', 'confirmed')";
              
    if(mysqli_query($conn, $query)){
        header("Location: booking.php");
        exit();
    }
}

/* UPDATE/EDIT BOOKING ACTION */
if(isset($_POST['edit_booking'])){
    $booking_id = mysqli_real_escape_string($conn, $_POST['booking_id']);
    $client_name = mysqli_real_escape_string($conn, $_POST['client_name']);
    $tour_title = mysqli_real_escape_string($conn, $_POST['tour_title']);
    $currency = mysqli_real_escape_string($conn, $_POST['currency']); 
    $full_deposit = mysqli_real_escape_string($conn, $_POST['full_deposit']);
    $deposit = mysqli_real_escape_string($conn, $_POST['deposit']);
    $tour_date = mysqli_real_escape_string($conn, $_POST['tour_date']);

    $update_query = "UPDATE bookings SET 
                    client_name = '$client_name',  
                    tour_title = '$tour_title',  
                    currency = '$currency',  
                    full_deposit = '$full_deposit',  
                    deposit = '$deposit',  
                    tour_date = '$tour_date'  
                    WHERE id = '$booking_id'";
                    
    if(mysqli_query($conn, $update_query)){
        header("Location: booking.php");
        exit();
    }
}

/* SETUP EXCHANGE RATES (Base: USD) */
// Unaweza kuunganisha API hapa hapo baadaye. Kwa sasa hizi ni viwango vya kuanzia.
$exchange_rates = [
    'USD' => 1.0,
    'EUR' => 0.92,
    'GBP' => 0.79,
    'TZS' => 2600.0
];

// Kazi ya kubadilisha kutoka sarafu yoyote kwenda sarafu uliyochagua
function convertCurrency($amount, $from_currency, $to_currency, $rates) {
    if ($from_currency == $to_currency) return $amount;
    // Badilisha kwenda USD kwanza (Base Currency)
    $amount_in_usd = $amount / ($rates[$from_currency] ?? 1.0);
    // Badilisha kutoka USD kwenda Target Currency
    return $amount_in_usd * ($rates[$to_currency] ?? 1.0);
}

$selected_currency = isset($_GET['filter_currency']) ? mysqli_real_escape_string($conn, $_GET['filter_currency']) : 'USD';

/* FETCH BOOKINGS WITH SEARCH (BILA KUBANA CURRENCY KWENYE SQL) */
$where_clauses = [];
if(isset($_POST['search_btn']) && !empty($_POST['search_value'])){
    $search_value = mysqli_real_escape_string($conn, $_POST['search_value']);
    $where_clauses[] = "(client_name LIKE '%$search_value%' OR tour_title LIKE '%$search_value%')";
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Tunavuta bookings zote ili ziseme "Not Found" kama zipo kwenye currency nyingine
$bookings_query = mysqli_query($conn, "SELECT * FROM bookings $where_sql ORDER BY id DESC");

// TUNAJUMUISHA SIDEBAR
include '../includes/sidebar.php'; 
?>

<link rel="stylesheet" href="../assets/css/booking.css">

<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #0b2265;
        color: #ffffff;
        margin: 0;
        padding: 0;
    }
    .main-content {
        margin-left: 260px;
        padding: 90px 30px 30px 30px;
        box-sizing: border-box;
    }
    .container {
        background: rgba(255, 255, 255, 0.05);
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        backdrop-filter: blur(10px);
    }
    .header-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding-bottom: 15px;
        margin-bottom: 20px;
    }
    h2 { margin: 0; font-size: 28px; font-weight: 600; }
    .btn-trigger-modal {
        background-color: #CFA58C;
        color: #0b2265;
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
        transition: background 0.3s;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .btn-trigger-modal:hover {
        background-color: #bfa080;
        color: #ffffff;
    }
    .dashboard-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }
    .summary-card {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 20px;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .summary-card.remaining-card { border-left: 5px solid #CFA58C; }
    .summary-card.paid-card { border-left: 5px solid #2ecc71; }
    .summary-card span {
        font-size: 13px;
        color: rgba(255, 255, 255, 0.6);
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    .summary-card h3 { margin: 0; font-size: 24px; font-weight: 700; }
    .filters-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 20px;
    }
    .search-form { display: flex; gap: 10px; width: 100%; max-width: 400px; }
    .search-input {
        flex: 1;
        padding: 10px 15px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        color: #ffffff;
        font-size: 14px;
    }
    .search-input:focus { outline: none; border-color: #CFA58C; }
    .search-btn {
        background-color: transparent;
        border: 1px solid #CFA58C;
        color: #CFA58C;
        padding: 0 15px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s;
    }
    .search-btn:hover { background-color: #CFA58C; color: #0b2265; }
    .clear-btn {
        background-color: rgba(255, 255, 255, 0.1);
        border: none;
        color: #ffffff;
        padding: 10px 15px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 14px;
        display: flex;
        align-items: center;
    }
    .currency-filter-select {
        padding: 10px 15px;
        background: #0f2b7a;
        border: 1px solid #CFA58C;
        border-radius: 8px;
        color: #ffffff;
        font-size: 14px;
        font-weight: bold;
        cursor: pointer;
    }
    .action-cell { display: flex; gap: 10px; align-items: center; }
    .btn-edit-action {
        background: rgba(207, 165, 140, 0.15);
        color: #CFA58C;
        border: 1px solid rgba(207, 165, 140, 0.3);
        padding: 6px 10px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .btn-edit-action:hover { background: #CFA58C; color: #0b2265; border-color: #CFA58C; }
    .btn-delete-action {
        background: rgba(255, 107, 107, 0.15);
        color: #ff6b6b;
        border: 1px solid rgba(255, 107, 107, 0.3);
        padding: 6px 10px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }
    .btn-delete-action:hover { background: #ff4747; color: #ffffff; border-color: #ff4747; }
    .no-data-tr { text-align: center; }
    .no-data-msg { padding: 30px !important; color: rgba(255,255,255,0.6); font-size: 16px; font-weight: 500; }
    
    /* MODAL DESIGN */
    .side-modal {
        position: fixed;
        top: 0;
        right: -450px; 
        width: 400px;
        height: 100vh;
        background: #0f2b7a; 
        box-shadow: -5px 0 25px rgba(0,0,0,0.5);
        z-index: 1050;
        transition: right 0.4s ease-in-out;
        padding: 30px;
        box-sizing: border-box;
        overflow-y: auto;
    }
    .side-modal.open { right: 0; }
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(3px);
        z-index: 1040;
        display: none;
    }
    .modal-overlay.show { display: block; }
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding-bottom: 10px;
    }
    .modal-header h3 { color: #CFA58C; margin: 0; }
    .btn-close-modal { background: none; border: none; color: #ffffff; font-size: 24px; cursor: pointer; }
    .modal-form { display: flex; flex-direction: column; gap: 15px; }
    .modal-form input, .modal-form select {
        width: 100%;
        padding: 12px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        color: #ffffff;
        font-size: 15px;
        box-sizing: border-box;
    }
    .modal-form select option { background: #0f2b7a; color: #ffffff; }
    .modal-form input:focus, .modal-form select:focus { outline: none; border-color: #CFA58C; background: rgba(255, 255, 255, 0.15); }
    .modal-form input[readonly] { background: rgba(255, 255, 255, 0.02); color: #CFA58C; border-color: rgba(207, 165, 140, 0.3); font-weight: bold; }
    .modal-form button[type="submit"] {
        background-color: #CFA58C;
        color: #0b2265;
        padding: 12px;
        border: none;
        border-radius: 8px;
        font-weight: bold;
        font-size: 16px;
        cursor: pointer;
        margin-top: 10px;
    }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; background: rgba(255, 255, 255, 0.02); border-radius: 10px; overflow: hidden; }
    th, td { padding: 14px 18px; text-align: left; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
    th { background-color: rgba(255, 255, 255, 0.1); color: #CFA58C; font-weight: 600; text-transform: uppercase; font-size: 13px; }
    tr:hover { background-color: rgba(255, 255, 255, 0.05); }
</style>

<div class="main-content">
    <div class="container">
        
        <div class="header-section">
            <h2>Booking Management</h2>
            <button class="btn-trigger-modal" id="openModalBtn">
                <i class="bi bi-plus-circle-fill"></i> Add New Booking
            </button>
        </div>

        <?php
        // Kutengeneza array ya kuhifadhi rows ili tuzipitie mara mbili (Kwenye Totals na Kwenye Table Body)
        $rows_buffer = [];
        $total_paid = 0;
        $total_remaining = 0;

        if (mysqli_num_rows($bookings_query) > 0) {
            while ($row = mysqli_fetch_assoc($bookings_query)) {
                // Kubadili maadili ya sasa kwenda kwenye sarafu iliyochaguliwa (Dynamically converted)
                $converted_full = convertCurrency($row['full_deposit'], $row['currency'], $selected_currency, $exchange_rates);
                $converted_deposit = convertCurrency($row['deposit'], $row['currency'], $selected_currency, $exchange_rates);
                $converted_remaining = $converted_full - $converted_deposit;

                // Jumlisha kwenye muhtasari wa juu
                $total_paid += $converted_deposit;
                $total_remaining += $converted_remaining;

                // Hifadhi data zilizobadilishwa tayari kwa ajili ya kuonyeshwa kwenye jedwali
                $row['display_full'] = $converted_full;
                $row['display_deposit'] = $converted_deposit;
                $row['display_remaining'] = $converted_remaining;
                $rows_buffer[] = $row;
            }
        }
        ?>

        <div class="dashboard-summary">
            <div class="summary-card paid-card">
                <span>Total Amount Paid (Deposit)</span>
                <h3 style="color: #2ecc71;"><?php echo number_format($total_paid, 2) . ' ' . $selected_currency; ?></h3>
            </div>
            <div class="summary-card remaining-card">
                <span>Total Remaining Balance</span>
                <h3 style="color: #CFA58C;"><?php echo number_format($total_remaining, 2) . ' ' . $selected_currency; ?></h3>
            </div>
        </div>

        <div class="filters-wrapper">
            <div class="search-wrapper">
                <form method="POST" class="search-form">
                    <input 
                        type="text" 
                        name="search_value" 
                        placeholder="Search by Client Name or Tour..." 
                        class="search-input"
                        value="<?php echo isset($_POST['search_value']) ? htmlspecialchars($_POST['search_value']) : ''; ?>">
                    <button type="submit" name="search_btn" class="search-btn">
                        <i class="bi bi-search"></i>
                    </button>
                    <?php if(isset($_POST['search_btn'])){ ?>
                        <a href="booking.php?filter_currency=<?php echo $selected_currency; ?>" class="clear-btn">Clear</a>
                    <?php } ?>
                </form>
            </div>

            <div>
                <form method="GET" id="currencyFilterForm">
                    <label style="font-size: 13px; font-weight:600; color: rgba(255,255,255,0.7); margin-right: 8px;">View Currency:</label>
                    <select name="filter_currency" class="currency-filter-select" onchange="document.getElementById('currencyFilterForm').submit();">
                        <option value="USD" <?php echo $selected_currency == 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                        <option value="GBP" <?php echo $selected_currency == 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                        <option value="EUR" <?php echo $selected_currency == 'EUR' ? 'selected' : ''; ?>>EURO (€)</option>
                        <option value="TZS" <?php echo $selected_currency == 'TZS' ? 'selected' : ''; ?>>TZS (Shilingi)</option>
                    </select>
                </form>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Client Name</th>
                    <th>Tour Title</th>
                    <th>Full Amount</th>
                    <th>Paid Deposit</th>
                    <th>Remaining</th>
                    <th>Tour Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if(!empty($rows_buffer)) {
                    foreach($rows_buffer as $row){ 
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['tour_title']); ?></td>
                    <td><?php echo number_format($row['display_full'], 2) . ' ' . htmlspecialchars($selected_currency); ?></td>
                    <td><?php echo number_format($row['display_deposit'], 2) . ' ' . htmlspecialchars($selected_currency); ?></td>
                    <td style="color: #CFA58C; font-weight: 600;">
                        <?php echo number_format($row['display_remaining'], 2) . ' ' . htmlspecialchars($selected_currency); ?>
                    </td>
                    <td><?php echo $row['tour_date']; ?></td>
                    <td>
                        <div class="action-cell">
                            <button type="button" class="btn-edit-action btn-trigger-edit" 
                                    data-id="<?php echo $row['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($row['client_name']); ?>"
                                    data-title="<?php echo htmlspecialchars($row['tour_title']); ?>"
                                    data-currency="<?php echo htmlspecialchars($row['currency']); ?>"
                                    data-full="<?php echo $row['full_deposit']; ?>"
                                    data-deposit="<?php echo $row['deposit']; ?>"
                                    data-date="<?php echo $row['tour_date']; ?>">
                                <i class="bi bi-pencil-square"></i>
                            </button>

                            <a href="booking.php?delete_id=<?php echo $row['id']; ?>" class="btn-delete-action" onclick="return confirm('Are you sure you want to delete this booking?');">
                                <i class="bi bi-trash3-fill"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php 
                    } 
                } else { 
                ?>
                <tr class="no-data-tr">
                    <td colspan="7" class="no-data-msg">No booking data found in the system.</td>
                </tr>
                <?php } ?>
            </tbody>
        </table>

    </div>
</div>

<div class="modal-overlay" id="modalOverlay"></div>

<div class="side-modal" id="bookingModal">
    <div class="modal-header">
        <h3>Add New Booking</h3>
        <button class="btn-close-modal" id="closeModalBtn">&times;</button>
    </div>
    
    <form method="POST" class="modal-form">
        <input type="text" name="client_name" placeholder="Client Name" required>
        <input type="text" name="tour_title" placeholder="Title of Tour" required>

        <div>
            <label style="font-size: 13px; color: rgba(255,255,255,0.7); display:block; margin-bottom: 5px;">Select Currency:</label>
            <select name="currency" required>
                <option value="USD">USD ($)</option>
                <option value="GBP">GBP (£)</option>
                <option value="EUR">EURO (€)</option>
                <option value="TZS">TZS (Shilingi)</option>
            </select>
        </div>

        <input type="number" step="0.01" name="full_deposit" id="full_deposit" placeholder="Full Tour Amount (Total Cost)" required>
        <input type="number" step="0.01" name="deposit" id="deposit" placeholder="Advance Deposit Paid" required>

        <div>
            <label style="font-size: 13px; color: #CFA58C; display:block; margin-bottom: 5px;">Remaining Balance (Auto-calculated):</label>
            <input type="number" id="remain_deposit" placeholder="0.00" readonly>
        </div>

        <div>
            <label style="font-size: 13px; color: rgba(255,255,255,0.7); display:block; margin-bottom: 5px;">Date of Tour Activity:</label>
            <input type="date" name="tour_date" required>
        </div>

        <button type="submit" name="add_booking">
            <i class="bi bi-save"></i> Save Booking
        </button>
    </form>
</div>

<div class="side-modal" id="editBookingModal">
    <div class="modal-header">
        <h3>Edit Booking</h3>
        <button class="btn-close-modal" id="closeEditModalBtn">&times;</button>
    </div>
    
    <form method="POST" class="modal-form">
        <input type="hidden" name="booking_id" id="edit_id">

        <input type="text" name="client_name" id="edit_name" placeholder="Client Name" required>
        <input type="text" name="tour_title" id="edit_title" placeholder="Title of Tour" required>

        <div>
            <label style="font-size: 13px; color: rgba(255,255,255,0.7); display:block; margin-bottom: 5px;">Select Currency:</label>
            <select name="currency" id="edit_currency" required>
                <option value="USD">USD ($)</option>
                <option value="GBP">GBP (£)</option>
                <option value="EUR">EURO (€)</option>
                <option value="TZS">TZS (Shilingi)</option>
            </select>
        </div>

        <input type="number" step="0.01" name="full_deposit" id="edit_full_deposit" placeholder="Full Tour Amount" required>
        <input type="number" step="0.01" name="deposit" id="edit_deposit" placeholder="Advance Deposit Paid" required>

        <div>
            <label style="font-size: 13px; color: #CFA58C; display:block; margin-bottom: 5px;">Remaining Balance (Auto-calculated):</label>
            <input type="number" id="edit_remain_deposit" placeholder="0.00" readonly>
        </div>

        <div>
            <label style="font-size: 13px; color: rgba(255,255,255,0.7); display:block; margin-bottom: 5px;">Date of Tour Activity:</label>
            <input type="date" name="tour_date" id="edit_date" required>
        </div>

        <button type="submit" name="edit_booking">
            <i class="bi bi-check-circle"></i> Update Booking
        </button>
    </form>
</div>

<script>
    const openModalBtn = document.getElementById('openModalBtn');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const closeEditModalBtn = document.getElementById('closeEditModalBtn');
    
    const bookingModal = document.getElementById('bookingModal');
    const editBookingModal = document.getElementById('editBookingModal');
    const modalOverlay = document.getElementById('modalOverlay');

    const fullDepositInput = document.getElementById('full_deposit');
    const depositInput = document.getElementById('deposit');
    const remainDepositInput = document.getElementById('remain_deposit');

    const editId = document.getElementById('edit_id');
    const editName = document.getElementById('edit_name');
    const editTitle = document.getElementById('edit_title');
    const editCurrency = document.getElementById('edit_currency');
    const editFullDeposit = document.getElementById('edit_full_deposit');
    const editDeposit = document.getElementById('edit_deposit');
    const editRemainDeposit = document.getElementById('edit_remain_deposit');
    const editDate = document.getElementById('edit_date');

    if(openModalBtn) {
        openModalBtn.addEventListener('click', () => {
            bookingModal.classList.add('open');
            modalOverlay.classList.add('show');
        });
    }

    if(closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
    if(closeEditModalBtn) closeEditModalBtn.addEventListener('click', closeModal);
    if(modalOverlay) modalOverlay.addEventListener('click', closeModal);

    function closeModal() {
        // FIXED BUG HERE: changed `.remove('open')` to `.classList.remove('open')`
        bookingModal.classList.remove('open');
        editBookingModal.classList.remove('open');
        modalOverlay.classList.remove('show');
    }

    document.addEventListener('click', function(e) {
        const targetBtn = e.target.closest('.btn-trigger-edit');
        if (targetBtn) {
            e.preventDefault();
            
            editId.value = targetBtn.getAttribute('data-id') || '';
            editName.value = targetBtn.getAttribute('data-name') || '';
            editTitle.value = targetBtn.getAttribute('data-title') || '';
            editCurrency.value = targetBtn.getAttribute('data-currency') || 'USD';
            editFullDeposit.value = targetBtn.getAttribute('data-full') || '0';
            editDeposit.value = targetBtn.getAttribute('data-deposit') || '0';
            editDate.value = targetBtn.getAttribute('data-date') || '';
            
            calculateEditRemaining();

            editBookingModal.classList.add('open');
            modalOverlay.classList.add('show');
        }
    });

    function calculateRemaining() {
        const fullAmount = parseFloat(fullDepositInput.value) || 0;
        const paidAmount = parseFloat(depositInput.value) || 0;
        remainDepositInput.value = (fullAmount - paidAmount).toFixed(2);
    }
    if(fullDepositInput) fullDepositInput.addEventListener('input', calculateRemaining);
    if(depositInput) depositInput.addEventListener('input', calculateRemaining);

    function calculateEditRemaining() {
        const fullAmount = parseFloat(editFullDeposit.value) || 0;
        const paidAmount = parseFloat(editDeposit.value) || 0;
        editRemainDeposit.value = (fullAmount - paidAmount).toFixed(2);
    }
    if(editFullDeposit) editFullDeposit.addEventListener('input', calculateEditRemaining);
    if(editDeposit) editDeposit.addEventListener('input', calculateEditRemaining);
</script>

</body>
</html>