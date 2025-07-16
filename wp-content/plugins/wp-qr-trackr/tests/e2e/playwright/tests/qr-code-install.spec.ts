import { test, expect, Page } from '@playwright/test';

const WP_ADMIN_URL = 'http://wordpress-playwright/wp-admin';
const USERNAME = 'trackr';
const PASSWORD = 'trackr';

// Utility: Login to WordPress admin
async function login(page: Page) {
  await page.goto(`${WP_ADMIN_URL}/`);
  await page.fill('#user_login', USERNAME);
  await page.fill('#user_pass', PASSWORD);
  await page.click('#wp-submit');
  await expect(page).toHaveURL(/wp-admin/);
}

test('WP QR Trackr plugin is installed and can generate a QR code', async ({ page }) => {
  // Login
  await login(page);

  // Debug output after login
  console.log('After login, current URL:', await page.url());
  console.log('Cookies:', await page.context().cookies());
  await page.screenshot({ path: 'test-results/after-login.png', fullPage: true });
  const html = await page.content();
  require('fs').writeFileSync('test-results/after-login.html', html);

  // Go to Plugins page and verify plugin is active
  await page.goto(`${WP_ADMIN_URL}/plugins.php`);
  const pluginRow = page.locator('tr[data-slug="wp-qr-trackr"]');
  await expect(pluginRow).toBeVisible();
  await expect(pluginRow.locator('.active')).toBeVisible();

  // Go to Posts and edit the first post
  await page.goto(`${WP_ADMIN_URL}/edit.php`);
  const firstPost = page.locator('tbody#the-list tr').first();
  await expect(firstPost).toBeVisible();
  await firstPost.locator('a.row-title').click();
  await expect(page).toHaveURL(/post\.php/);

  // Look for QR code generator UI (adjust selector as needed)
  const qrSection = page.locator('a[href="admin.php?page=qr-code-links"]').first();
  await expect(qrSection).toBeVisible();

  // Attempt to generate a QR code (adjust button selector as needed)
  const generateButton = page.locator('button:has-text("Generate QR Code")');
  await generateButton.click();

  // Verify QR code image or confirmation appears (adjust selector as needed)
  const qrImage = page.locator('img[src*="qr"]');
  await expect(qrImage).toBeVisible();
}); 