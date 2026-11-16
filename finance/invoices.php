<?php
include '../includes/auth.php';
include '../includes/config.php';
include_once '../includes/functions.php';

$user_id = $_SESSION['user_id'];

/* ACTION: ADD PURCHASE INVOICE */
if(isset($_POST['add_invoice'])){
    updateBalance($conn);

    $invoice_number = mysqli_real_escape_string($conn, $_POST['invoice_number']);
    $supplier_name = mysqli_real_escape_string($conn, $_POST['supplier_name']);
    $invoice_date = mysqli_real_escape_string($conn, $_POST['invoice_date']);
    $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);
    $currency = mysqli_real_escape_string($conn, $_POST['currency']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $payment_status = mysqli_real_escape_string($conn, $_POST['payment_status']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);

    // 1. Ingiza kwenye Table Kuu
    $query_main = "INSERT INTO purchase_invoices (invoice_number, supplier_name, invoice_date, due_date, currency, payment_method, payment_status, notes, created_by) 
                   VALUES ('$invoice_number', '$supplier_name', '$invoice_date', '$due_date', '$currency', '$payment_method', '$payment_status', '$notes', '$user_id')";
    
    if(mysqli_query($conn, $query_main)) {
        $invoice_id = mysqli_insert_id($conn); // Pata ID ya Invoice iliyoingia hivi karibuni

        // 2. Kamata Items zilizotumwa kutoka kwenye Dynamic Form Rows
        $items = $_POST['item_name'];
        $quantities = $_POST['quantity'];
        $prices = $_POST['unit_price'];

        for ($i = 0; $i < count($items); $i++) {
            $item_name = mysqli_real_escape_string($conn, $items[$i]);
            $qty = (int)$quantities[$i];
            $unit_p = (float)$prices[$i];
            $total_p = $qty * $unit_p;

            if(!empty($item_name)) {
                mysqli_query($conn, "INSERT INTO purchase_invoice_items (invoice_id, item_name, quantity, unit_price, total_price) 
                                     VALUES ('$invoice_id', '$item_name', '$qty', '$unit_p', '$total_p')");
            }
        }
    }

    header("Location: purchase_invoice.php");
    exit();
}

/* ACTION: DELETE INVOICE (Imeboreshwa kufuta na vitu vya ndani vizuri) */
if(isset($_POST['delete_invoice'])){
    $invoice_id = mysqli_real_escape_string($conn, $_POST['invoice_id']);
    
    // Futa kwanza item zilizofungamana na hii invoice kuzuia data baki (orphan rows)
    mysqli_query($conn, "DELETE FROM purchase_invoice_items WHERE invoice_id='$invoice_id'");
    
    // Futa invoice yenyewe
    mysqli_query($conn, "DELETE FROM purchase_invoices WHERE id='$invoice_id' AND created_by='$user_id'");
    
    header("Location: purchase_invoice.php");
    exit();
}

/* SEARCH & FETCH INVOICES (Ulinzi wa user_id umejumuishwa) */
$search_query = "WHERE pi.created_by = '$user_id'";
if(isset($_POST['search_btn']) && !empty($_POST['search_value'])){
    $search_value = mysqli_real_escape_string($conn, $_POST['search_value']);
    $search_query .= " AND (pi.invoice_number LIKE '%$search_value%' OR pi.supplier_name LIKE '%$search_value%')";
}

// Vuta invoice za mtumiaji husika pamoja na jumla ya mahesabu ya vitu vya ndani
$sql = "SELECT pi.*, COALESCE(SUM(pii.total_price), 0) AS grand_total 
        FROM purchase_invoices pi
        LEFT JOIN purchase_invoice_items pii ON pi.id = pii.invoice_id
        $search_query 
        GROUP BY pi.id 
        ORDER BY pi.id DESC";
$data = mysqli_query($conn, $sql);


/* MULTI-CURRENCY TOTAL AUTOMATIC CALCULATION FOR INVOICES */
$selected_currency = isset($_POST['total_currency']) ? $_POST['total_currency'] : 'TZS';

// Viwango vya kubadilishia fedha (Exchange Rates)
$rates = [
    'USD' => 1.0,        
    'TZS' => 2600.0,     
    'EURO' => 0.92,      
    'GBP' => 0.78        
];

$total_invoices_converted = 0;

// Kupiga hesabu ya jumla ya kiasi cha invoice kulingana na chuja (filter) ya huyu user tu
$calc_sql = "SELECT pi.currency, COALESCE(SUM(pii.total_price), 0) AS inv_grand_total 
             FROM purchase_invoices pi
             LEFT JOIN purchase_invoice_items pii ON pi.id = pii.invoice_id
             $search_query 
             GROUP BY pi.id";
$calc_query = mysqli_query($conn, $calc_sql);

while($calc_row = mysqli_fetch_assoc($calc_query)) {
    $row_amount = (float)$calc_row['inv_grand_total'];
    $row_currency = $calc_row['currency'];
    
    // 1. Convert row amount kwenda USD kwanza (kama base)
    if(isset($rates[$row_currency]) && $rates[$row_currency] > 0) {
        $amount_in_usd = $row_amount / $rates[$row_currency];
    } else {
        $amount_in_usd = $row_amount;
    }
    
    // 2. Convert kutoka USD kwenda kwenye Currency aliyochagua mtumiaji
    if(isset($rates[$selected_currency])) {
        $converted_amount = $amount_in_usd * $rates[$selected_currency];
    } else {
        $converted_amount = $amount_in_usd;
    }
    
    $total_invoices_converted += $converted_amount;
}

include '../includes/sidebar.php'; 
?>

<link rel="stylesheet" href="../assets/css/debts.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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
        display: flex;
        align-items: center;
        gap: 8px;
        transition: 0.3s;
    }

    .btn-trigger-modal:hover { background-color: #bfa080; color: #ffffff; }

    .top-controls-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 25px;
    }

    .search-form { display: flex; gap: 10px; width: 100%; max-width: 420px; }
    .search-input {
        flex: 1; padding: 10px 15px; background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #ffffff;
        font-size: 14px;
    }
    .search-input:focus { outline: none; border-color: #CFA58C; }
    .search-btn {
        background: transparent; border: 1px solid #CFA58C; color: #CFA58C;
        padding: 0 15px; border-radius: 8px; cursor: pointer; transition: 0.3s;
    }
    .search-btn:hover { background: #CFA58C; color: #0b2265; }
    .clear-btn {
        background-color: rgba(255, 255, 255, 0.1); color: #fff; padding: 10px 15px;
        border-radius: 8px; text-decoration: none; font-size: 14px; display: flex; align-items: center;
    }

    .total-invoice-card {
        background: rgba(207, 165, 140, 0.12);
        border: 1px solid rgba(207, 165, 140, 0.25);
        border-radius: 12px;
        padding: 12px 20px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .total-invoice-card .label-area {
        font-size: 13px;
        color: rgba(255, 255, 255, 0.7);
        text-transform: uppercase;
        font-weight: 500;
        margin-bottom: 2px;
    }

    .total-invoice-card .amount-area {
        font-size: 20px;
        font-weight: 700;
        color: #CFA58C;
    }

    .currency-select-inline {
        background: #0f2b7a;
        color: #CFA58C;
        border: 1px solid #CFA58C;
        padding: 6px 10px;
        border-radius: 6px;
        font-weight: bold;
        cursor: pointer;
        font-size: 14px;
        outline: none;
    }

    .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
    .status-paid { background: rgba(42, 157, 143, 0.2); color: #2a9d8f; border: 1px solid #2a9d8f; }
    .status-partial { background: rgba(233, 196, 106, 0.2); color: #e9c46a; border: 1px solid #e9c46a; }
    .status-unpaid { background: rgba(255, 107, 107, 0.2); color: #ff6b6b; border: 1px solid #ff6b6b; }

    .side-modal {
        position: fixed; top: 0; right: -650px; width: 600px; height: 100vh;
        background: #0f2b7a; box-shadow: -5px 0 25px rgba(0,0,0,0.5); z-index: 1050;
        transition: right 0.4s ease-in-out; padding: 30px; box-sizing: border-box; overflow-y: auto;
    }
    .side-modal.open { right: 0; }
    .modal-overlay {
        position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
        background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(4px); z-index: 1040; display: none;
    }
    .modal-overlay.show { display: block; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding-bottom: 10px; }
    .modal-header h3 { margin: 0; color: #CFA58C; }
    .btn-close-modal { background: none; border: none; color: #ffffff; font-size: 24px; cursor: pointer; }

    .modal-form { display: flex; flex-direction: column; gap: 15px; }
    .form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .modal-form input, .modal-form select, .modal-form textarea {
        width: 100%; padding: 12px; background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #ffffff; box-sizing: border-box;
    }
    .modal-form input:focus, .modal-form select:focus { outline: none; border-color: #CFA58C; }

    .items-container-title { margin-top: 15px; color: #CFA58C; font-size: 16px; font-weight: 600; }
    .item-row { display: grid; grid-template-columns: 2fr 1fr 1fr 40px; gap: 10px; align-items: center; margin-bottom: 8px; }
    .btn-add-row { background: #2a9d8f; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 13px; align-self: flex-start; }
    .btn-remove-row { background: #ff6b6b; color: white; border: none; height: 40px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; }

    table { width: 100%; border-collapse: collapse; margin-top: 10px; background: rgba(255, 255, 255, 0.02); border-radius: 10px; overflow: hidden; }
    th, td { padding: 14px 18px; text-align: left; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
    th { background-color: rgba(255, 255, 255, 0.1); color: #CFA58C; font-size: 13px; text-transform: uppercase; }
    tr:hover { background-color: rgba(255, 255, 255, 0.05); }

    .center-modal {
        position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0.9);
        width: 380px; background: #112a6e; border: 1px solid rgba(255,0,0,0.3);
        border-radius: 12px; padding: 25px; box-sizing: border-box; z-index: 1060; display: none; box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }
    .center-modal.open { display: block; transform: translate(-50%, -50%) scale(1); }
</style>

<div class="main-content">
    <div class="container">

        <div class="header-section">
            <h2>Purchase Invoices</h2>
            <button class="btn-trigger-modal" id="openModalBtn">
                <i class="bi bi-file-earmark-plus-fill"></i> Create Purchase Invoice
            </button>
        </div>

        <div class="top-controls-wrapper">
            <form method="POST" class="search-form">
                <input type="text" name="search_value" placeholder="Search Invoice No or Supplier..." class="search-input"
                       value="<?php echo isset($_POST['search_value']) ? htmlspecialchars($_POST['search_value']) : ''; ?>">
                <button type="submit" name="search_btn" class="search-btn"><i class="bi bi-search"></i></button>
                <?php if(isset($_POST['search_btn'])){ echo '<a href="purchase_invoice.php" class="clear-btn">Clear</a>'; } ?>
            </form>

            <div class="total-invoice-card">
                <div>
                    <div class="label-area">Total Purchase Amount:</div>
                    <div class="amount-area"><?php echo number_format($total_invoices_converted, 2); ?></div>
                </div>
                <form method="POST" id="currencyForm">
                    <input type="hidden" name="search_value" value="<?php echo isset($_POST['search_value']) ? htmlspecialchars($_POST['search_value']) : ''; ?>">
                    <?php if(isset($_POST['search_btn'])) { echo '<input type="hidden" name="search_btn" value="1">'; } ?>
                    
                    <select name="total_currency" class="currency-select-inline" onchange="document.getElementById('currencyForm').submit();">
                        <option value="TZS" <?php echo $selected_currency == 'TZS' ? 'selected' : ''; ?>>TZS (Shilingi)</option>
                        <option value="USD" <?php echo $selected_currency == 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                        <option value="EURO" <?php echo $selected_currency == 'EURO' ? 'selected' : ''; ?>>EURO (€)</option>
                        <option value="GBP" <?php echo $selected_currency == 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                    </select>
                </form>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Invoice No</th>
                    <th>Supplier Name</th>
                    <th>Invoice Date</th>
                    <th>Grand Total</th>
                    <th>Payment Method</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if(mysqli_num_rows($data) > 0) {
                    while($row = mysqli_fetch_assoc($data)){ 
                        $status = $row['payment_status'];
                        $badge_class = ($status == 'Paid') ? 'status-paid' : (($status == 'Partially Paid') ? 'status-partial' : 'status-unpaid');
                ?>
                <tr>
                    <td style="color: #CFA58C; font-weight: 600;"><?php echo htmlspecialchars($row['invoice_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                    <td><?php echo $row['invoice_date']; ?></td>
                    <td style="font-weight: bold;"><?php echo $row['currency'] . ' ' . number_format($row['grand_total'], 2); ?></td>
                    <td><i class="bi bi-wallet2"></i> <?php echo htmlspecialchars($row['payment_method']); ?></td>
                    <td><span class="status-badge <?php echo $badge_class; ?>"><?php echo $status; ?></span></td>
                    <td>
                        <div class="action-btn-group">
                            <button class="action-btn btn-delete deleteInvoiceBtn" data-id="<?php echo $row['id']; ?>" data-num="<?php echo htmlspecialchars($row['invoice_number']); ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php 
                    } 
                } else {
                ?>
                <tr>
                    <td colspan="7" style="text-align:center; color:rgba(255,255,255,0.4); padding: 30px;">No purchase invoices found.</td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="modalOverlay"></div>

<div class="side-modal" id="invoiceModal">
    <div class="modal-header">
        <h3>Create Purchase Invoice</h3>
        <button class="btn-close-modal" id="closeModalBtn">&times;</button>
    </div>
    <form method="POST" class="modal-form">
        <div class="form-row-2">
            <div>
                <label style="font-size:12px; color:#CFA58C;">Invoice Number *</label>
                <input type="text" name="invoice_number" placeholder="e.g. INV-2026-001" required>
            </div>
            <div>
                <label style="font-size:12px; color:#CFA58C;">Supplier Name *</label>
                <input type="text" name="supplier_name" placeholder="Supplier / Vendor" required>
            </div>
        </div>

        <div class="form-row-2">
            <div>
                <label style="font-size:12px; color:#CFA58C;">Invoice Date *</label>
                <input type="date" name="invoice_date" required>
            </div>
            <div>
                <label style="font-size:12px; color:#CFA58C;">Due Date</label>
                <input type="date" name="due_date">
            </div>
        </div>

        <div class="form-row-2">
            <div>
                <label style="font-size:12px; color:#CFA58C;">Currency *</label>
                <select name="currency" required>
                    <option value="TZS">TZS (Shilingi)</option>
                    <option value="USD">USD ($)</option>
                    <option value="EURO">EURO (€)</option>
                    <option value="GBP">GBP (£)</option>
                </select>
            </div>
            <div>
                <label style="font-size:12px; color:#CFA58C;">Payment Method *</label>
                <input type="text" name="payment_method" placeholder="e.g. Bank Transfer, Cash, M-Pesa" required>
            </div>
        </div>

        <div>
            <label style="font-size:12px; color:#CFA58C;">Payment Status *</label>
            <select name="payment_status" required>
                <option value="Unpaid">Unpaid (Haijalipwa)</option>
                <option value="Partially Paid">Partially Paid (Imelipwa Kiasi)</option>
                <option value="Paid">Paid (Imelipwa Kamili)</option>
            </select>
        </div>

        <div class="items-container-title"><i class="bi bi-box-seam"></i> Invoice Items (Bidhaa/Vitu vilivyomo)</div>
        <div id="dynamicItemsWrapper">
            <div class="item-row">
                <input type="text" name="item_name[]" placeholder="Item Name / Service" required>
                <input type="number" name="quantity[]" placeholder="Qty" min="1" value="1" required>
                <input type="number" step="0.01" name="unit_price[]" placeholder="Unit Price" required>
                <div></div>
            </div>
        </div>
        <button type="button" class="btn-add-row" id="addItemRowBtn"><i class="bi bi-plus"></i> Add Item Row</button>

        <div>
            <label style="font-size:12px; color:#CFA58C;">Notes / Description</label>
            <textarea name="notes" placeholder="Maelezo ya ziada kuhusu invoice hii..." rows="2"></textarea>
        </div>

        <button type="submit" name="add_invoice" style="background:#CFA58C; color:#0b2265; padding:12px; border:none; border-radius:8px; font-weight:bold; cursor:pointer;">
            <i class="bi bi-check2-square"></i> Save Purchase Invoice
        </button>
    </form>
</div>

<div class="center-modal" id="deleteModal">
    <h3 style="color: #ff6b6b; margin-top: 0;"><i class="bi bi-exclamation-triangle-fill"></i> Delete Invoice?</h3>
    <p>Are you sure you want to delete Invoice <strong id="del_inv_label" style="color:#CFA58C;"></strong>? Hii itafuta na vitu vyote vilivyomo ndani yake.</p>
    <form method="POST">
        <input type="hidden" name="invoice_id" id="delete_invoice_id">
        <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
            <button type="button" class="btn-cancel" id="closeDeleteModalBtn" style="background:rgba(255,255,255,0.1); color:#fff; border:none; padding:10px 15px; border-radius:6px; cursor:pointer;">Cancel</button>
            <button type="submit" name="delete_invoice" style="background:#ff6b6b; color:#fff; border:none; padding:10px 15px; border-radius:6px; font-weight:bold; cursor:pointer;">Yes, Delete</button>
        </div>
    </form>
</div>

<script>
    const openModalBtn = document.getElementById('openModalBtn');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const invoiceModal = document.getElementById('invoiceModal');
    const modalOverlay = document.getElementById('modalOverlay');
    const deleteModal = document.getElementById('deleteModal');
    const closeDeleteModalBtn = document.getElementById('closeDeleteModalBtn');

    if(openModalBtn) {
        openModalBtn.addEventListener('click', () => {
            invoiceModal.classList.add('open');
            modalOverlay.classList.add('show');
        });
    }

    const addItemRowBtn = document.getElementById('addItemRowBtn');
    const dynamicItemsWrapper = document.getElementById('dynamicItemsWrapper');

    if(addItemRowBtn) {
        addItemRowBtn.addEventListener('click', () => {
            const row = document.createElement('div');
            row.className = 'item-row';
            row.innerHTML = `
                <input type="text" name="item_name[]" placeholder="Item Name / Service" required>
                <input type="number" name="quantity[]" placeholder="Qty" min="1" value="1" required>
                <input type="number" step="0.01" name="unit_price[]" placeholder="Unit Price" required>
                <button type="button" class="btn-remove-row"><i class="bi bi-trash"></i></button>
            `;
            dynamicItemsWrapper.appendChild(row);

            row.querySelector('.btn-remove-row').addEventListener('click', () => {
                row.remove();
            });
        });
    }

    document.querySelectorAll('.deleteInvoiceBtn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('delete_invoice_id').value = this.getAttribute('data-id');
            document.getElementById('del_inv_label').textContent = this.getAttribute('data-num');
            deleteModal.classList.add('open');
            modalOverlay.classList.add('show');
        });
    });

    if(closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
    if(closeDeleteModalBtn) closeDeleteModalBtn.addEventListener('click', closeModal);
    if(modalOverlay) modalOverlay.addEventListener('click', closeModal);

    function closeModal() {
        invoiceModal.classList.remove('open');
        deleteModal.classList.remove('open');
        modalOverlay.classList.remove('show');
    }
</script>
</body>
</html>