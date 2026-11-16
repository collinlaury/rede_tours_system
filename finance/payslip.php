<?php
include '../includes/auth.php';
include '../includes/config.php';
include_once '../includes/functions.php';

$user_id = $_SESSION['user_id'];
$message = "";

/* NOTE: Hakikisha database yako ina column ya 'description' TEXT kwenye table ya 'payslips' */

/* ACTION: ADD NEW PAYSLIP */
if(isset($_POST['add_payslip'])){
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $amount = (float)$_POST['amount'];
    $currency = mysqli_real_escape_string($conn, $_POST['currency']);
    $payslip_code = "PAY-" . strtoupper(substr(md5(time()), 0, 8));
    
    // Kushughulikia File / Photo Upload
    $attachment_name = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $target_dir = "uploads/attachments/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        
        $attachment_name = time() . '_' . basename($_FILES["attachment"]["name"]);
        move_uploaded_file($_FILES["attachment"]["tmp_name"], $target_dir . $attachment_name);
    }

    $query = "INSERT INTO payslips (payslip_code, title, description, amount, currency, attachment) 
              VALUES ('$payslip_code', '$title', '$description', '$amount', '$currency', '$attachment_name')";
    
    if(mysqli_query($conn, $query)) {
        $message = "<div class='alert alert-success' style='background: rgba(42, 157, 143, 0.2); color: #2a9d8f; border: 1px solid #2a9d8f; padding: 12px; border-radius: 8px; margin-bottom: 20px;'>Payslip imehifadhiwa kikamilifu!</div>";
    } else {
        $message = "<div class='alert alert-danger' style='background: rgba(255, 107, 107, 0.2); color: #ff6b6b; border: 1px solid #ff6b6b; padding: 12px; border-radius: 8px; margin-bottom: 20px;'>Makosa yamejitokeza wakati wa kusave.</div>";
    }
}

/* ACTION: EDIT PAYSLIP */
if(isset($_POST['edit_payslip'])){
    $id = (int)$_POST['payslip_id'];
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $amount = (float)$_POST['amount'];
    $currency = mysqli_real_escape_string($conn, $_POST['currency']);
    
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $target_dir = "uploads/attachments/";
        $attachment_name = time() . '_' . basename($_FILES["attachment"]["name"]);
        move_uploaded_file($_FILES["attachment"]["tmp_name"], $target_dir . $attachment_name);
        
        $query = "UPDATE payslips SET title='$title', description='$description', amount='$amount', currency='$currency', attachment='$attachment_name' WHERE id='$id'";
    } else {
        $query = "UPDATE payslips SET title='$title', description='$description', amount='$amount', currency='$currency' WHERE id='$id'";
    }
    
    if(mysqli_query($conn, $query)) {
        header("Location: payslip.php");
        exit();
    }
}

/* ACTION: DELETE PAYSLIP */
if(isset($_POST['delete_payslip'])){
    $id = mysqli_real_escape_string($conn, $_POST['payslip_id']);
    mysqli_query($conn, "DELETE FROM payslips WHERE id='$id'");
    header("Location: payslip.php");
    exit();
}

/* LIVE EXCHANGE RATES API (Base on USD) */
function getExchangeRates() {
    $url = "https://open.er-api.com/v6/latest/USD";
    $response = @file_get_contents($url);
    if ($response) {
        $data = json_decode($response, true);
        return $data['rates'] ?? ['USD' => 1.0, 'EUR' => 0.92, 'GBP' => 0.78, 'TZS' => 2600.0];
    }
    return ['USD' => 1.0, 'EUR' => 0.92, 'GBP' => 0.78, 'TZS' => 2600.0];
}
$rates = getExchangeRates();

/* FETCH ALL PAYSLIPS */
$sql = "SELECT * FROM payslips ORDER BY id DESC";
$data = mysqli_query($conn, $sql);

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

    /* Ukurasa wa juu - Layout Mpya */
    .controls-panel {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        gap: 20px;
        flex-wrap: wrap;
    }

    .search-wrapper {
        flex: 1;
        max-width: 400px;
    }

    .search-input {
        width: 100%;
        padding: 12px 15px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        color: #ffffff;
        font-size: 14px;
    }
    .search-input:focus { outline: none; border-color: #CFA58C; }

    .right-summary-panel {
        display: flex;
        align-items: center;
        gap: 20px;
        background: rgba(255, 255, 255, 0.06);
        padding: 10px 20px;
        border-radius: 10px;
        border-right: 4px solid #CFA58C;
    }

    .total-display-box p {
        margin: 0;
        font-size: 11px;
        color: rgba(255,255,255,0.6);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .total-display-box h3 {
        margin: 3px 0 0 0;
        font-size: 22px;
        font-weight: 700;
        color: #CFA58C;
    }

    .currency-selector-box select {
        padding: 8px 12px;
        background: #0f2b7a;
        border: 1px solid rgba(207, 165, 140, 0.5);
        color: #fff;
        border-radius: 6px;
        font-weight: bold;
        cursor: pointer;
    }

    /* Side Modal */
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
    .modal-form input:focus, .modal-form select:focus, .modal-form textarea:focus { outline: none; border-color: #CFA58C; }

    .drop-zone { border: 2px dashed rgba(207, 165, 140, 0.4); border-radius: 8px; padding: 20px; text-align: center; background: rgba(255,255,255,0.02); cursor: pointer; }

    table { width: 100%; border-collapse: collapse; margin-top: 10px; background: rgba(255, 255, 255, 0.02); border-radius: 10px; overflow: hidden; }
    th, td { padding: 14px 18px; text-align: left; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
    th { background-color: rgba(255, 255, 255, 0.1); color: #CFA58C; font-size: 13px; text-transform: uppercase; }
    tr:hover { background-color: rgba(255, 255, 255, 0.05); }

    .center-modal {
        position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0.9);
        width: 450px; background: #112a6e; border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px; padding: 25px; box-sizing: border-box; z-index: 1060; display: none; box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }
    .center-modal.open { display: block; transform: translate(-50%, -50%) scale(1); }
    
    .action-btn-group { display: flex; gap: 6px; justify-content: center; }
    .action-btn { background: none; border: none; font-size: 18px; cursor: pointer; padding: 5px 10px; border-radius: 6px; transition: 0.3s; }
    .btn-view { color: #2a9d8f; } .btn-edit { color: #e9c46a; } .btn-delete { color: #ff6b6b; }
    .action-btn:hover { background: rgba(255,255,255,0.1); }
</style>

<div class="main-content">
    <div class="container">

        <div class="header-section">
            <h2>Payslips Management</h2>
            <button class="btn-trigger-modal" id="openAddModalBtn">
                <i class="bi bi-file-earmark-plus-fill"></i> Create New Payslip
            </button>
        </div>

        <?= $message; ?>

        <div class="controls-panel">
            <div class="search-wrapper">
                <input type="text" id="liveSearchInput" placeholder="Live Search by Title, Code or Amount..." class="search-input">
            </div>
            
            <div class="right-summary-panel">
                <div class="total-display-box">
                    <p id="totalLabel">Total Sum (USD)</p>
                    <h3 id="totalDisplayValue">$0.00</h3>
                </div>
                <div class="currency-selector-box">
                    <select id="globalCurrencyToggle" onchange="updateSystemCurrency()">
                        <option value="USD" selected>USD ($)</option>
                        <option value="EUR">EURO (€)</option>
                        <option value="GBP">GBP (£)</option>
                        <option value="TZS">TZS (TSh)</option>
                    </select>
                </div>
            </div>
        </div>

        <table id="payslipTable">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Title / Maelezo</th>
                    <th style="text-align: right;">Original Amount</th>
                    <th style="text-align: center;">Currency</th>
                    <th style="text-align: right; color: #CFA58C;">Converted Amount</th>
                    <th style="text-align: center;">Attachment</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if(mysqli_num_rows($data) > 0) {
                    while($row = mysqli_fetch_assoc($data)){ 
                        $orig_amt = (float)$row['amount'];
                        $cur = ($row['currency'] == 'EURO') ? 'EUR' : $row['currency']; // kusafisha jina la euro
                ?>
                <tr class="payslip-row" 
                    data-amount="<?= $orig_amt; ?>" 
                    data-currency="<?= $cur; ?>">
                    
                    <td style="color: #CFA58C; font-weight: 600;"><?= $row['payslip_code']; ?></td>
                    <td>
                        <strong><?= htmlspecialchars($row['title']); ?></strong>
                        <?php if(!empty($row['description'])): ?>
                            <br><small style="color:rgba(255,255,255,0.5); font-size:11px;"><?= htmlspecialchars($row['description']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right; font-weight: 500;"><?= number_format($orig_amt, 2); ?></td>
                    <td style="text-align: center;"><span style="background: rgba(255,255,255,0.15); padding: 3px 8px; border-radius: 5px; font-size: 12px; font-weight: bold;"><?= $cur; ?></span></td>
                    
                    <td class="converted-amount-cell" style="text-align: right; font-weight: bold; color: #bfa080;"></td>
                    
                    <td style="text-align: center;">
                        <?php if(!empty($row['attachment'])): ?>
                            <a href="uploads/attachments/<?= $row['attachment']; ?>" target="_blank" style="color: #CFA58C; text-decoration: none;"><i class="bi bi-paperclip"></i> View</a>
                        <?php else: ?>
                            <span style="opacity: 0.3; font-size: 12px;">No File</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-btn-group">
                            <button class="action-btn btn-view" title="View Detail" onclick='openViewModal(<?= json_encode($row); ?>)'><i class="bi bi-eye"></i></button>
                            <button class="action-btn btn-edit" title="Edit" onclick='openEditModal(<?= json_encode($row); ?>)'><i class="bi bi-pencil-square"></i></button>
                            <button class="action-btn btn-delete" title="Delete" onclick="triggerDelete(<?= $row['id']; ?>, '<?= htmlspecialchars($row['payslip_code']); ?>')"><i class="bi bi-trash"></i></button>
                        </div>
                    </td>
                </tr>
                <?php 
                    } 
                } else {
                ?>
                <tr>
                    <td colspan="7" style="text-align:center; color:rgba(255,255,255,0.4); padding: 30px;">No payslips records found.</td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="modalOverlay"></div>

<div class="side-modal" id="addPayslipModal">
    <div class="modal-header">
        <h3>Create New Payslip</h3>
        <button class="btn-close-modal" onclick="closeAllModals()">&times;</button>
    </div>
    <form method="POST" enctype="multipart/form-data" class="modal-form">
        <div>
            <label style="font-size:12px; color:#CFA58C;">Title / Maelezo ya Malipo *</label>
            <input type="text" name="title" placeholder="Mfano: Salary for Guide or Bonus" required>
        </div>

        <div>
            <label style="font-size:12px; color:#CFA58C;">Description / Maelezo ya Kina (Ufafanuzi)</label>
            <textarea name="description" rows="3" placeholder="Andika maelezo ya ziada hapa kuhusu vocha au malipo haya..."></textarea>
        </div>

        <div class="form-row-2">
            <div>
                <label style="font-size:12px; color:#CFA58C;">Amount (Kiasi) *</label>
                <input type="number" step="0.01" name="amount" placeholder="0.00" required>
            </div>
            <div>
                <label style="font-size:12px; color:#CFA58C;">Currency *</label>
                <select name="currency" required>
                    <option value="USD" selected>USD ($)</option>
                    <option value="EUR">EURO (€)</option>
                    <option value="GBP">GBP (£)</option>
                    <option value="TZS">TZS (Shilingi)</option>
                </select>
            </div>
        </div>

        <div>
            <label style="font-size:12px; color:#CFA58C;">Photo / PDF Attachment</label>
            <div class="drop-zone" onclick="document.getElementById('fileInputAdd').click()">
                <i class="bi bi-cloud-arrow-up-fill" style="font-size: 24px; color: #CFA58C;"></i>
                <p style="margin: 5px 0 0 0; font-size: 13px; color: rgba(255,255,255,0.6);">Bofya au kokota faili hapa</p>
                <input type="file" name="attachment" id="fileInputAdd" style="display:none;" accept="image/*,application/pdf">
            </div>
            <small id="fileNameAdd" style="color: #CFA58C; font-size: 12px; display: block; margin-top: 5px;"></small>
        </div>

        <button type="submit" name="add_payslip" style="background:#CFA58C; color:#0b2265; padding:12px; border:none; border-radius:8px; font-weight:bold; cursor:pointer; margin-top:10px;">
            <i class="bi bi-check2-square"></i> Save Payslip Record
        </button>
    </form>
</div>

<div class="center-modal" id="viewDetailsModal">
    <h3 style="color: #CFA58C; margin-top: 0; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px;"><i class="bi bi-info-circle-fill"></i> Payslip Information Summary</h3>
    <table style="margin-top: 15px; font-size: 14px;">
        <tr><td style="color:#CFA58C; font-weight:600; width:35%;">Code:</td><td id="v_code"></td></tr>
        <tr><td style="color:#CFA58C; font-weight:600;">Title:</td><td id="v_title"></td></tr>
        <tr><td style="color:#CFA58C; font-weight:600;">Description:</td><td id="v_desc" style="white-space: pre-line; color: #ddd;"></td></tr>
        <tr><td style="color:#CFA58C; font-weight:600;">Original Value:</td><td id="v_amount" style="font-weight:bold;"></td></tr>
        <tr><td style="color:#e9c46a; font-weight:600;">Converted Live:</td><td id="v_live_conv" style="font-weight:bold; font-size: 16px;"></td></tr>
    </table>
    <button type="button" onclick="closeAllModals()" style="background:rgba(255,255,255,0.1); color:#fff; border:none; padding:10px 20px; border-radius:6px; cursor:pointer; width:100%; margin-top:20px; font-weight:bold;">Funga View</button>
</div>

<div class="side-modal" id="editPayslipModal">
    <div class="modal-header">
        <h3>Edit Payslip Record</h3>
        <button class="btn-close-modal" onclick="closeAllModals()">&times;</button>
    </div>
    <form method="POST" enctype="multipart/form-data" class="modal-form">
        <input type="hidden" name="payslip_id" id="e_id">
        <div>
            <label style="font-size:12px; color:#CFA58C;">Title / Maelezo *</label>
            <input type="text" name="title" id="e_title" required>
        </div>
        <div>
            <label style="font-size:12px; color:#CFA58C;">Description / Maelezo ya Kina</label>
            <textarea name="description" id="e_desc" rows="3"></textarea>
        </div>
        <div class="form-row-2">
            <div>
                <label style="font-size:12px; color:#CFA58C;">Amount *</label>
                <input type="number" step="0.01" name="amount" id="e_amount" required>
            </div>
            <div>
                <label style="font-size:12px; color:#CFA58C;">Currency *</label>
                <select name="currency" id="e_currency" required>
                    <option value="USD">USD ($)</option>
                    <option value="EUR">EURO (€)</option>
                    <option value="GBP">GBP (£)</option>
                    <option value="TZS">TZS (Shilingi)</option>
                </select>
            </div>
        </div>
        <div>
            <label style="font-size:12px; color:#CFA58C;">Change Attachment (Ukiacha wazi la zamani litabaki)</label>
            <input type="file" name="attachment" class="form-control" accept="image/*,application/pdf">
        </div>
        <button type="submit" name="edit_payslip" style="background:#CFA58C; color:#0b2265; padding:12px; border:none; border-radius:8px; font-weight:bold; cursor:pointer; margin-top:10px;">
            <i class="bi bi-pencil-square"></i> Save Modifications
        </button>
    </form>
</div>

<div class="center-modal" id="deletePayslipModal">
    <h3 style="color: #ff6b6b; margin-top: 0;"><i class="bi bi-exclamation-triangle-fill"></i> Delete Payslip Record?</h3>
    <p>Je, una uhakika unataka kufuta rekodi ya Payslip yenye Code <strong id="del_label" style="color:#CFA58C;"></strong>?</p>
    <form method="POST">
        <input type="hidden" name="payslip_id" id="delete_id">
        <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
            <button type="button" onclick="closeAllModals()" style="background:rgba(255,255,255,0.1); color:#fff; border:none; padding:10px 15px; border-radius:6px; cursor:pointer;">Cancel</button>
            <button type="submit" name="delete_payslip" style="background:#ff6b6b; color:#fff; border:none; padding:10px 15px; border-radius:6px; font-weight:bold; cursor:pointer;">Yes, Delete</button>
        </div>
    </form>
</div>

<script>
    // Kubeba viwango vya kubadilishia hela kutoka PHP kwenda JS array
    const exchangeRates = {
        USD: 1.0,
        EUR: <?= (float)$rates['EUR']; ?>,
        GBP: <?= (float)$rates['GBP']; ?>,
        TZS: <?= (float)$rates['TZS']; ?>
    };

    const currencySymbols = { USD: '$', EUR: '€', GBP: '£', TZS: 'TSh' };
    const modalOverlay = document.getElementById('modalOverlay');

    // Kazi kuu ya kugeuza hela zote Live kwenye jedwali na kwenye Jumla (Total Card)
    function updateSystemCurrency() {
        const targetCurrency = document.getElementById('globalCurrencyToggle').value;
        const symbol = currencySymbols[targetCurrency];
        
        // Badilisha label ya Kadi ya Juu
        document.getElementById('totalLabel').innerText = `Total Sum (${targetCurrency})`;
        
        let grandTotalInTarget = 0;
        let rows = document.querySelectorAll('.payslip-row');
        
        rows.forEach(row => {
            let amount = parseFloat(row.getAttribute('data-amount'));
            let currency = row.getAttribute('data-currency');
            if(currency === 'EURO') currency = 'EUR'; // Kusawazisha database values za zamani
            
            // 1. Geuza kwanza kwenda Base (USD)
            let amountInUSD = 0;
            if (currency === 'USD') amountInUSD = amount;
            else amountInUSD = amount / exchangeRates[currency];
            
            // 2. Kutoka USD kwenda Sarafu teule ya sasa
            let finalConverted = amountInUSD * exchangeRates[targetCurrency];
            grandTotalInTarget += finalConverted;
            
            // Weka thamani mpya kwenye cell husika ya jedwali
            let cell = row.querySelector('.converted-amount-cell');
            cell.innerText = `${symbol} ${finalConverted.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        });
        
        // Sasisha Kadi Kuu ya Jumla
        document.getElementById('totalDisplayValue').innerText = `${symbol} ${grandTotalInTarget.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    }

    // Inatekeleza mara ya kwanza wakati ukurasa unafunguka
    document.addEventListener("DOMContentLoaded", () => {
        updateSystemCurrency();
    });

    // Search bar upande wa kushoto kufanya kazi live filter
    document.getElementById('liveSearchInput').addEventListener('keyup', function() {
        let textSearch = this.value.toLowerCase();
        let rows = document.querySelectorAll('#payslipTable tbody .payslip-row');
        rows.forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(textSearch) ? '' : 'none';
        });
    });

    // Modals handling
    document.getElementById('openAddModalBtn').addEventListener('click', () => {
        document.getElementById('addPayslipModal').classList.add('open');
        modalOverlay.classList.add('show');
    });

    document.getElementById('fileInputAdd').addEventListener('change', function() {
        if(this.files.length > 0) {
            document.getElementById('fileNameAdd').innerText = "Selected: " + this.files[0].name;
        }
    });

    function openViewModal(data) {
        const targetCurrency = document.getElementById('globalCurrencyToggle').value;
        const symbol = currencySymbols[targetCurrency];
        
        let origCurrency = data.currency === 'EURO' ? 'EUR' : data.currency;
        let amount = parseFloat(data.amount);
        
        // Mahesabu ya haraka ya kibinafsi kwa view modal kufuata current currency selection
        let amtUSD = origCurrency === 'USD' ? amount : amount / exchangeRates[origCurrency];
        let convertedLiveVal = amtUSD * exchangeRates[targetCurrency];

        document.getElementById('v_code').innerText = data.payslip_code;
        document.getElementById('v_title').innerText = data.title;
        document.getElementById('v_desc').innerText = data.description ? data.description : 'Hakuna maelezo ya ziada.';
        document.getElementById('v_amount').innerText = currencySymbols[origCurrency] + " " + amount.toLocaleString('en-US', {minimumFractionDigits: 2});
        
        document.getElementById('v_live_conv').innerText = `${symbol} ${convertedLiveVal.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
        
        document.getElementById('viewDetailsModal').classList.add('open');
        modalOverlay.classList.add('show');
    }

    function openEditModal(data) {
        document.getElementById('e_id').value = data.id;
        document.getElementById('e_title').value = data.title;
        document.getElementById('e_desc').value = data.description ? data.description : '';
        document.getElementById('e_amount').value = data.amount;
        document.getElementById('e_currency').value = data.currency;
        
        document.getElementById('editPayslipModal').classList.add('open');
        modalOverlay.classList.add('show');
    }

    function triggerDelete(id, code) {
        document.getElementById('delete_id').value = id;
        document.getElementById('del_label').innerText = code;
        
        document.getElementById('deletePayslipModal').classList.add('open');
        modalOverlay.classList.add('show');
    }

    modalOverlay.addEventListener('click', closeAllModals);
    function closeAllModals() {
        document.getElementById('addPayslipModal').removeClassName ? null : document.getElementById('addPayslipModal').classList.remove('open');
        document.getElementById('editPayslipModal').classList.remove('open');
        document.getElementById('viewDetailsModal').classList.remove('open');
        document.getElementById('deletePayslipModal').classList.remove('open');
        modalOverlay.classList.remove('show');
    }
</script>