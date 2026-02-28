Invoice Print & PDF Export

Overview
--------
This adds A4 print-ready layout and a PDF export endpoint for invoices.

Files added/modified
- Modified: `invoices/view_invoice.php` (added Download PDF button)
- Modified: `invoices/add_invoice.php` (robust NULL case_id insert)
- Added: `invoices/print_invoice.php` (print-ready HTML)
- Added: `invoices/print_pdf.php` (PDF generator using Dompdf if available)
- Added migration: `migrations/2026_02_08_add_invoice_item_subtotal_and_fix_fk.sql`

Database migration
------------------
1. Backup your database before running any migration.
2. Run the SQL script located at `migrations/2026_02_08_add_invoice_item_subtotal_and_fix_fk.sql`.

On MySQL (cli):

```bash
mysql -u root -p iss < "C:/Program Files/Ampps/www/system/migrations/2026_02_08_add_invoice_item_subtotal_and_fix_fk.sql"
```

If your MySQL user/password or database differs, adjust accordingly.

Install Dompdf (optional, recommended for server-side PDF generation)
----------------------------------------------------------------------
Dompdf is a PHP library that can convert HTML to PDF.

If your project uses Composer (recommended):

```bash
cd "C:/Program Files/Ampps/www/system"
composer require dompdf/dompdf
```

If Composer is not used, you can install Dompdf and its dependencies manually, or use a system-level tool like `wkhtmltopdf` and alter `print_pdf.php` to call it.

Usage
-----
- View invoice: `invoices/view_invoice.php?id=123`
- Print invoice (browser): use the Print button on the view page.
- Generate/Download PDF (server-side): `invoices/print_pdf.php?id=123`.

Notes & Troubleshooting
-----------------------
- If PDF does not trigger download, ensure `vendor/autoload.php` exists (Composer installed Dompdf).
- If Dompdf is not available, `print_pdf.php` will show the print HTML so you can manually Save as PDF.
- Verify `images/logo.png` is accessible by the invoices scripts (relative paths correct).
- Test printing in Chrome & Acrobat to ensure 1cm margins and no cut-off.

Testing
-------
1. Create a sample invoice with multiple items.
2. Open `view_invoice.php?id=<id>` and click Print — verify the printed output fits A4 with 1cm margins.
3. Click Download PDF — if Dompdf installed, the PDF should be generated and downloaded.
4. Check invoice items show correct line totals (quantity × unit_price). If any differences occur, confirm DB data.

Rollback
--------
If you need to rollback the migration, you can restore the DB backup taken prior to running the migration.

Contact
-------
If you want me to run the migration and install Dompdf (composer), I can: run the migration and install dependencies, then validate PDF generation and report the results.
