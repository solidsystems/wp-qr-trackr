// Playwright script: Automated user flow for QR Trackr plugin (dev only)
// Screenshots saved to assets/screenshots/ for documentation

const { chromium } = require('playwright');

const SITE_URL = process.env.SITE_URL || 'http://localhost:8087';

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage();

  // 1. Log in to WP admin
  console.log('Step 1: Logging in to WordPress admin...');
  await page.goto(`${SITE_URL}/wp-login.php`);
  await page.fill('#user_login', 'trackr');
  await page.fill('#user_pass', 'trackr');
  await page.click('#wp-submit');
  await page.waitForSelector('#wpadminbar');
  await page.screenshot({ path: 'wp-content/plugins/wp-qr-trackr/assets/screenshots/1-dashboard-login.png' });

  // 2. Go to QR Trackr dashboard
  console.log('Step 2: Navigating to QR Trackr dashboard...');
  await page.goto(`${SITE_URL}/wp-admin/admin.php?page=qr-trackr`);
  await page.waitForSelector('.qr-trackr-create-form');
  await page.screenshot({ path: 'wp-content/plugins/wp-qr-trackr/assets/screenshots/2-qrtrackr-dashboard.png' });

  // 3. Open the create QR code form (already visible)
  console.log('Step 3: Showing create QR code form...');
  // (Screenshot already taken above)

  // 4. Create a QR code for an external URL
  console.log('Step 4: Creating a QR code for an external URL...');
  await page.selectOption('#destination_type', 'external');
  await page.fill('#external_url', 'https://example.com');
  await page.screenshot({ path: 'wp-content/plugins/wp-qr-trackr/assets/screenshots/3-create-form-external.png' });
  await page.click('button[type=submit]');
  await page.waitForTimeout(1500); // Wait for AJAX and UI update

  // 5. Show the QR code list/table
  console.log('Step 5: Capturing QR code list/table...');
  await page.screenshot({ path: 'wp-content/plugins/wp-qr-trackr/assets/screenshots/4-qr-list.png' });

  // 6. (Optional) Go to stats page if available
  // Uncomment if you have a stats page:
  // console.log('Step 6: Capturing stats page...');
  // await page.goto(`${SITE_URL}/wp-admin/admin.php?page=qr-trackr-stats`);
  // await page.waitForSelector('.qr-trackr-stats');
  // await page.screenshot({ path: 'wp-content/plugins/wp-qr-trackr/assets/screenshots/5-stats.png' });

  console.log('User flow complete. Screenshots saved to assets/screenshots/.');
  await browser.close();
})(); 