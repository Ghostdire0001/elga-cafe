<?php
$page_title = 'Generate Table QR Codes';
require_once '../includes/config.php';
require_once '../includes/translations.php';
require_once '../includes/theme.php';
require_once '../includes/language.php';

$current_lang = getCurrentTheme();
$site_url = 'https://elgacafe.onrender.com';

// Number of tables (you can change this)
$number_of_tables = 20;

require_once 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Table QR Codes</h1>
    <button onclick="window.print()" class="btn-success">
        <i class="fas fa-print"></i> Print All
    </button>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
    <?php for ($table = 1; $table <= $number_of_tables; $table++): 
        $table_code = 'TABLE_' . str_pad($table, 2, '0', STR_PAD_LEFT);
        $qr_url = $site_url . '/?table=' . $table_code;
    ?>
    <div class="bg-white rounded-lg shadow p-4 text-center" style="background-color: var(--card-bg);">
        <h3 class="font-bold text-lg mb-2">Table <?php echo $table; ?></h3>
        <div class="mb-3 flex justify-center">
            <img src="https://quickchart.io/qr?text=<?php echo urlencode($qr_url); ?>&size=200&margin=2&color=F97316" 
                 alt="QR Code Table <?php echo $table; ?>"
                 class="border rounded-lg"
                 style="max-width: 150px; width: 100%;">
        </div>
        <div class="text-xs text-gray-500 break-all mt-2">
            <?php echo $qr_url; ?>
        </div>
        <div class="mt-3 flex gap-2 justify-center">
            <a href="https://quickchart.io/qr?text=<?php echo urlencode($qr_url); ?>&size=500&margin=2&color=F97316" 
               target="_blank" class="btn-primary text-sm">
                <i class="fas fa-download"></i> Download
            </a>
        </div>
    </div>
    <?php endfor; ?>
</div>

<div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4" style="background-color: #fef3c7;">
    <h3 class="font-bold mb-2">📋 Instructions:</h3>
    <ol class="list-decimal list-inside space-y-1 text-sm">
        <li>Print each QR code (or download and print)</li>
        <li>Cut and laminate each QR code</li>
        <li>Place one QR code on each table</li>
        <li>Customers scan the QR code at their table</li>
        <li>The menu will automatically know which table they're at</li>
        <li>Orders will be saved with the table number</li>
    </ol>
</div>

<style>
    @media print {
        .sidebar, .menu-toggle, .page-header button, .mt-6, .no-print {
            display: none !important;
        }
        .main-content {
            margin-left: 0 !important;
            padding: 0 !important;
        }
        .grid {
            display: grid !important;
        }
    }
</style>

<?php require_once 'includes/footer.php'; ?>
