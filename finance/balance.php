<?php
include '../includes/auth.php';
include '../includes/config.php';
include_once '../includes/functions.php';

$user_id = $_SESSION['user_id'];
$selected_currency = isset($_POST['total_currency']) ? $_POST['total_currency'] : 'TZS';

// Exchange rates (Base USD) kulingana na ukurasa wa income
$rates = [
    'USD' => 1.0,        
    'TZS' => 2600.0,     
    'EURO' => 0.92,      
    'GBP' => 0.78        
];

/* 1. DYNAMIC CALCULATION FROM INCOME & EXPENSES */
// Vuta jumla ya Income kwa kila njia ya malipo (Payment Method)
// Hapa tunatafuta maneno yanayofanana na 'bank' au 'cash'/'m-pesa'/'tigo pesa' nk.
$bank_income = 0;
$cash_income = 0;

$inc_query = mysqli_query($conn, "SELECT amount, currency, payment_method FROM income");
while($row = mysqli_fetch_assoc($inc_query)) {
    $amt = (float)$row['amount'];
    $curr = $row['currency'];
    $method = strtolower($row['payment_method']);
    
    // Convert kwenda USD kwanza
    $amt_in_usd = (isset($rates[$curr]) && $rates[$curr] > 0) ? ($amt / $rates[$curr]) : $amt;
    
    // Grouping kwa Bank au Cash
    if (strpos($method, 'bank') !== false || strpos($method, 'cheque') !== false) {
        $bank_income += $amt_in_usd;
    } else {
        // Njia zingine zote (Cash, M-Pesa, Airtel Money) tunahesabu kama Cash Balance mkononi
        $cash_income += $amt_in_usd;
    }
}

// Vuta jumla ya Expenses (Expenditure) - Ila hakikisha table yako ya expense ina 'payment_method' pia
$bank_expense = 0;
$cash_expense = 0;

// Tunatumia error-suppression au check kama table ipo tayari
$exp_table_check = mysqli_query($conn, "SHOW TABLES LIKE 'expenses'");
if(mysqli_num_rows($exp_table_check) > 0) {
    $exp_query = mysqli_query($conn, "SELECT amount, currency, payment_method FROM expenses");
    while($row = mysqli_fetch_assoc($exp_query)) {
        $amt = (float)$row['amount'];
        $curr = $row['currency'];
        $method = strtolower($row['payment_method']);
        
        $amt_in_usd = (isset($rates[$curr]) && $rates[$curr] > 0) ? ($amt / $rates[$curr]) : $amt;
        
        if (strpos($method, 'bank') !== false || strpos($method, 'cheque') !== false) {
            $bank_expense += $amt_in_usd;
        } else {
            $cash_expense += $amt_in_usd;
        }
    }
}

// Mahesabu ya Mwisho (Net Balance katika USD)
$final_bank_usd = $bank_income - $bank_expense;
$final_cash_usd = $cash_income - $cash_expense;
$total_net_usd = $final_bank_usd + $final_cash_usd;

// Convert kwenda kwenye currency iliyochaguliwa na user sasa hivi
$current_rate = isset($rates[$selected_currency]) ? $rates[$selected_currency] : 1.0;

$display_bank = $final_bank_usd * $current_rate;
$display_cash = $final_cash_usd * $current_rate;
$display_total = $total_net_usd * $current_rate;


/* ACTION: MANUAL BALANCE ADJUSTMENT (Kama msimamizi anataka kurekebisha kiasi) */
if(isset($_POST['adjust_balance'])){
    $account_type = mysqli_real_escape_string($conn, $_POST['account_type']);
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);
    $currency = mysqli_real_escape_string($conn, $_POST['currency']);
    
    // Hapa unaweza kuandika kodi ya kusave log ya marekebisho kwenye table ya 'balances'
    // Kwa kuwa kwa sasa inahesabu kiotomatiki kutoka kwenye vitabu, marekebisho yanaweza kuwekwa kama "Initial Entry" kwenye income au expense.
    
    header("Location: balance.php");
    exit();
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
        margin-bottom: 25px;
    }

    h2 {
        margin: 0;
        font-size: 28px;
        font-weight: 600;
    }

    .currency-selector-wrapper {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 25px;
    }

    /* Muundo wa Kadi za Balance */
    .balance-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 25px;
        margin-bottom: 35px;
    }

    .balance-card {
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        padding: 25px;
        position: relative;
        overflow: hidden;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .balance-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    }

    /* Mapambo ya Rangi pembeni mwa kadi */
    .balance-card.bank-card { border-left: 5px solid #CFA58C; }
    .balance-card.cash-card { border-left: 5px solid #2a9d8f; }
    .balance-card.total-net-card { 
        background: rgba(207, 165, 140, 0.15); 
        border: 1px solid rgba(207, 165, 140, 0.3);
        border-left: 5px solid #e9c46a;
    }

    .card-icon {
        position: absolute;
        right: 20px;
        top: 20px;
        font-size: 40px;
        color: rgba(255, 255, 255, 0.1);
    }

    .card-title {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.6);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 10px;
        font-weight: 600;
    }

    .card-amount {
        font-size: 28px;
        font-weight: 700;
        color: #ffffff;
        margin-bottom: 15px;
    }

    .balance-card.bank-card .card-amount { color: #CFA58C; }
    .balance-card.cash-card .card-amount { color: #2a9d8f; }

    .card-status {
        font-size: 12px;
        color: rgba(255, 255, 255, 0.5);
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .btn-adjust {
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.1);
        color: #fff;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.3s;
        margin-top: 10px;
    }

    .btn-adjust:hover {
        background: #CFA58C;
        color: #0b2265;
        border-color: #CFA58C;
    }

    /* --- SIDE MODAL SYSTEM --- */
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
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(4px);
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

    .modal-header h3 { margin: 0; color: #CFA58C; }

    .btn-close-modal {
        background: none;
        border: none;
        color: #ffffff;
        font-size: 24px;
        cursor: pointer;
    }

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

    .modal-form button[type="submit"] {
        background-color: #CFA58C;
        color: #0b2265;
        padding: 12px;
        border: none;
        border-radius: 8px;
        font-weight: bold;
        font-size: 16px;
        cursor: pointer;
    }

    /* Table ya muhtasari wa hivi karibuni */
    table {
        width: 100%;
        border-collapse: collapse;
        background: rgba(255, 255, 255, 0.02);
        border-radius: 10px;
        overflow: hidden;
    }

    th, td {
        padding: 14px 18px;
        text-align: left;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    th {
        background-color: rgba(255, 255, 255, 0.1);
        color: #CFA58C;
        font-size: 13px;
        text-transform: uppercase;
    }
</style>

<div class="main-content">
    <div class="container">

        <div class="header-section">
            <h2>Financial Balance</h2>
            
            <form method="POST" id="currencyForm">
                <select name="total_currency" class="currency-select-inline" onchange="document.getElementById('currencyForm').submit();">
                    <option value="TZS" <?php echo $selected_currency == 'TZS' ? 'selected' : ''; ?>>TZS (Shilingi)</option>
                    <option value="USD" <?php echo $selected_currency == 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                    <option value="EURO" <?php echo $selected_currency == 'EURO' ? 'selected' : ''; ?>>EURO (€)</option>
                    <option value="GBP" <?php echo $selected_currency == 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                </select>
            </form>
        </div>

        <div class="balance-grid">
            
            <div class="balance-card bank-card">
                <div class="card-icon"><i class="bi bi-bank"></i></div>
                <div class="card-title">Bank Balance</div>
                <div class="card-amount">
                    <?php echo $selected_currency . ' ' . number_format($display_bank, 2); ?>
                </div>
                <div class="card-status">
                    <i class="bi bi-arrow-repeat"></i> Auto-calculated from Bank transactions
                </div>
                <button class="btn-adjust openAdjustModal" data-type="Bank">
                    <i class="bi bi-sliders"></i> Manual Adjust
                </button>
            </div>

            <div class="balance-card cash-card">
                <div class="card-icon"><i class="bi bi-wallet2"></i></div>
                <div class="card-title">Cash & Mobile Money</div>
                <div class="card-amount">
                    <?php echo $selected_currency . ' ' . number_format($display_cash, 2); ?>
                </div>
                <div class="card-status">
                    <i class="bi bi-arrow-repeat"></i> Auto-calculated from Cash/M-Pesa logs
                </div>
                <button class="btn-adjust openAdjustModal" data-type="Cash">
                    <i class="bi bi-sliders"></i> Manual Adjust
                </button>
            </div>

            <div class="balance-card total-net-card">
                <div class="card-icon"><i class="bi bi-cash-coin"></i></div>
                <div class="card-title">Total Company Net Balance</div>
                <div class="card-amount" style="color: #e9c46a;">
                    <?php echo $selected_currency . ' ' . number_format($display_total, 2); ?>
                </div>
                <div class="card-status" style="color: rgba(255,255,255,0.8);">
                    <i class="bi bi-shield-check"></i> Overall Financial Stand
                </div>
            </div>

        </div>

        <h3 style="color: #CFA58C; margin-bottom: 15px; font-weight: 500;">Account Structure Status</h3>
        <table>
            <thead>
                <tr>
                    <th>Account Category</th>
                    <th>Calculated Inflow (Revenue)</th>
                    <th>Calculated Outflow (Expenses)</th>
                    <th>Current Liquidity Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Bank Operations</strong></td>
                    <td style="color: #2a9d8f;">+ <?php echo $selected_currency . ' ' . number_format($bank_income * $current_rate, 2); ?></td>
                    <td style="color: #ff6b6b;">- <?php echo $selected_currency . ' ' . number_format($bank_expense * $current_rate, 2); ?></td>
                    <td><span style="background: rgba(207, 165, 140, 0.2); padding: 4px 10px; border-radius: 20px; font-size: 13px; color: #CFA58C;">Active</span></td>
                </tr>
                <tr>
                    <td><strong>Cash & Office Vault</strong></td>
                    <td style="color: #2a9d8f;">+ <?php echo $selected_currency . ' ' . number_format($cash_income * $current_rate, 2); ?></td>
                    <td style="color: #ff6b6b;">- <?php echo $selected_currency . ' ' . number_format($cash_expense * $current_rate, 2); ?></td>
                    <td><span style="background: rgba(42, 157, 143, 0.2); padding: 4px 10px; border-radius: 20px; font-size: 13px; color: #2a9d8f;">Stable</span></td>
                </tr>
            </tbody>
        </table>

    </div>
</div>

<div class="modal-overlay" id="modalOverlay"></div>

<div class="side-modal" id="adjustModal">
    <div class="modal-header">
        <h3>Adjust <span id="account_type_label"></span> Balance</h3>
        <button class="btn-close-modal" id="closeModalBtn">&times;</button>
    </div>
    <form method="POST" class="modal-form">
        <input type="hidden" name="account_type" id="form_account_type">
        
        <p style="font-size: 14px; color: rgba(255,255,255,0.7); line-height: 1.4;">
            Kumbuka: Mfumo unajipiga hesabu wenyewe kulingana na Income na Expenses zilizopo. Adjust hapa ikiwa unataka kuongeza au kutoa kiwango cha ufunguzi (Opening Balance Ledger).
        </p>

        <label style="font-size:14px; color:#CFA58C;">Adjustment Target Value:</label>
        <input type="number" step="0.01" name="amount" placeholder="Ingiza Kiasi" required>

        <select name="currency" required>
            <option value="TZS">TZS</option>
            <option value="USD">USD</option>
            <option value="EURO">EURO</option>
            <option value="GBP">GBP</option>
        </select>

        <button type="submit" name="adjust_balance">
            <i class="bi bi-check2-circle"></i> Confirm Adjustment
        </button>
    </form>
</div>

<script>
    const adjustModal = document.getElementById('adjustModal');
    const modalOverlay = document.getElementById('modalOverlay');
    const closeModalBtn = document.getElementById('closeModalBtn');

    // Fungua modal ya adjustment kulingana na kadi uliyobonyeza
    document.querySelectorAll('.openAdjustModal').forEach(btn => {
        btn.addEventListener('click', function() {
            const type = this.getAttribute('data-type');
            document.getElementById('account_type_label').textContent = type;
            document.getElementById('form_account_type').value = type;
            
            adjustModal.classList.add('open');
            modalOverlay.classList.add('show');
        });
    });

    if(closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
    if(modalOverlay) modalOverlay.addEventListener('click', closeModal);

    function closeModal() {
        adjustModal.classList.remove('open');
        modalOverlay.classList.remove('show');
    }
</script>

</body>
</html>