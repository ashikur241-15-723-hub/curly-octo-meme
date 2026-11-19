<?php
declare(strict_types=1);
$user = current_user();
?>
<?php if ($user): ?>
        </main>
        <footer class="app-footer print-hide">
            <span>&copy; <?= date('Y') ?> <?= e((string) app_config('app.name', 'ExpenseTrack')) ?> · Personal finance in BDT (৳)</span>
        </footer>
    </div>
</div>
<?php else: ?>
    </main>
</div>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.2/dist/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.4/dist/jspdf.plugin.autotable.min.js"></script>
<script src="<?= e(base_path('/assets/js/app.js')) ?>"></script>
<?php if (!empty($extraScripts)) {
    echo $extraScripts;
} ?>
</body>
</html>
