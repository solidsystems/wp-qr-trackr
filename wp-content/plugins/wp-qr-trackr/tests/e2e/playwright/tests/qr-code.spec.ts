import { test, expect, Page } from '@playwright/test';

// Add TinyMCE type declarations
declare global {
  interface Window {
    tinyMCE?: {
      get: (id: string) => {
        setContent: (content: string) => void;
      } | null;
    };
  }
}

const BASE_URL = 'http://localhost:8080';

// Helper: Wait for network idle with timeout
async function waitForNetworkIdle(page: Page, timeout = 15000) {
  try {
    await page.waitForLoadState('networkidle', { timeout });
    return true;
  } catch (error) {
    console.error('Network did not become idle:', error);
    return false;
  }
}

// Helper: Login to WordPress
async function login(page: Page) {
  console.log('Attempting login...');
  
  try {
    await page.goto(`${BASE_URL}/wp-login.php`, {
      waitUntil: 'networkidle',
      timeout: 15000
    });
    
    await page.waitForSelector('#loginform', { timeout: 15000 });
    
    console.log('Login form found, filling credentials...');
    await page.fill('#user_login', 'trackr');
    await page.fill('#user_pass', 'trackr');
    
    console.log('Submitting login form...');
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle', timeout: 15000 }),
      page.click('#wp-submit')
    ]);
    
    console.log('Waiting for admin bar...');
    await page.waitForSelector('#wpadminbar', { timeout: 15000 });
    console.log('Login successful.');
    
    return true;
  } catch (error) {
    console.error('Login failed:', error);
    await page.screenshot({ 
      path: `test-results/login-error-${Date.now()}.png`,
      fullPage: true 
    });
    throw error;
  }
}

// Helper: Verify page load with better error handling
async function verifyPageLoad(page: Page, url: string) {
  console.log(`Verifying page load for: ${url}`);
  
  try {
    const response = await page.goto(`${BASE_URL}${url}`, {
      waitUntil: 'domcontentloaded',
      timeout: 15000
    });
    // Explicitly wait for editor or admin body to be visible
    await page.waitForSelector('.edit-post-layout, #wpbody-content', { state: 'attached', timeout: 15000 });
    await page.screenshot({ path: 'test-results/post-new-after-nav.png', fullPage: true });

    if (!response?.ok()) {
      throw new Error(`Failed to load ${url}: ${response?.status()}`);
    }

    // Wait for critical selectors with better error messages
    await Promise.race([
      page.waitForSelector('body', { 
        state: 'visible', 
        timeout: 15000 
      }).catch(() => { throw new Error('Body element not found'); }),
      
      page.waitForSelector('#wpadminbar', { 
        state: 'visible', 
        timeout: 15000 
      }).catch(() => { throw new Error('Admin bar not found - possible login issue'); }),
      
      page.waitForSelector('#wpbody', { 
        state: 'visible', 
        timeout: 15000 
      }).catch(() => { throw new Error('WordPress body not found - possible page load issue'); })
    ]);

    await waitForNetworkIdle(page);
    return response;
  } catch (error) {
    console.error('Page load verification failed:', error);
    await page.screenshot({ 
      path: `test-results/page-load-error-${Date.now()}.png`,
      fullPage: true 
    });
    throw error;
  }
}

// Helper: Detect which editor is loaded
async function detectEditor(page: Page): Promise<'block' | 'classic'> {
  console.log('Detecting which editor is loaded...');
  
  try {
    // Take a screenshot for debugging
    await page.screenshot({
      path: 'test-results/editor-detection.png',
      fullPage: true
    });
    
    // Check for block editor elements
    const hasBlockEditor = await Promise.race([
      page.waitForSelector('.block-editor-writing-flow', { timeout: 5000 })
        .then(() => true)
        .catch(() => false),
      page.waitForSelector('.edit-post-visual-editor', { timeout: 5000 })
        .then(() => true)
        .catch(() => false),
      page.waitForSelector('.wp-block-post-content', { timeout: 5000 })
        .then(() => true)
        .catch(() => false)
    ]);
    
    if (hasBlockEditor) {
      console.log('Block editor detected.');
      return 'block';
    }
    
    // Check for classic editor elements
    const hasClassicEditor = await Promise.race([
      page.waitForSelector('#wp-content-wrap', { timeout: 5000 })
        .then(() => true)
        .catch(() => false),
      page.waitForSelector('#wp-content-editor-container', { timeout: 5000 })
        .then(() => true)
        .catch(() => false)
    ]);
    
    if (hasClassicEditor) {
      console.log('Classic editor detected.');
      return 'classic';
    }
    
    // If neither is found, assume classic editor (more common in basic setups)
    console.log('No editor detected, defaulting to classic editor.');
    return 'classic';
  } catch (error) {
    console.error('Error detecting editor:', error);
    console.log('Defaulting to classic editor.');
    return 'classic';
  }
}

// Helper: Fill content in TinyMCE editor
async function fillTinyMCEContent(page: Page, content: string) {
  console.log('Filling TinyMCE content...');
  
  try {
    // Wait for TinyMCE to be ready
    await page.waitForSelector('#wp-content-wrap:not(.html-active)', { 
      timeout: 90000,
      state: 'visible'
    });
    
    // Switch to Visual mode if in Text mode
    const isTextMode = await page.$('#content-html.active');
    if (isTextMode) {
      await page.click('#content-tmce');
      await page.waitForTimeout(1000); // Wait for editor switch
    }
    
    // Focus the editor
    await page.click('#wp-content-wrap');
    
    // Clear existing content using TinyMCE API
    await page.evaluate(() => {
      const editor = window.tinyMCE?.get('content');
      if (editor) {
        editor.setContent('');
      }
    });
    
    // Type the content
    await page.keyboard.type(content);
    
    console.log('Content filled successfully.');
  } catch (error) {
    console.error('Error filling TinyMCE content:', error);
    throw error;
  }
}

// Helper: Generate QR code for a post
async function generateQRCode(page: Page, postId: string, postTitle: string) {
  console.log('Generating QR code...');
  
  try {
    // Navigate to QR code generation page
    await verifyPageLoad(page, `/wp-admin/admin.php?page=qrc-add-new`);
    
    // Take screenshot for debugging
    await page.screenshot({
      path: 'test-results/qr-add-new-page.png',
      fullPage: true
    });
    
    // Select post type
    await page.selectOption('#destination_type', 'post');
    
    // Wait for post selector to be visible
    await page.waitForSelector('#post-selector', { state: 'visible' });
    
    // Enter post title in search
    await page.fill('#post_search', postTitle);
    
    // Wait for search results
    await page.waitForSelector('#post_search_results .post-result-item', {
      state: 'visible',
      timeout: 90000
    });
    
    // Take screenshot of search results
    await page.screenshot({
      path: 'test-results/search-results.png',
      fullPage: true
    });
    
    // Click the first search result
    await page.click('#post_search_results .post-result-item');
    
    // Wait for selected post info to be visible
    await page.waitForSelector('#selected_post_info', {
      state: 'visible',
      timeout: 90000
    });
    
    // Wait for destination URL to be set
    await page.waitForFunction(() => {
      const input = document.querySelector('#destination_url') as HTMLInputElement;
      return input && input.value && input.value.length > 0;
    }, { timeout: 90000 });
    
    // Take screenshot of selected post
    await page.screenshot({
      path: 'test-results/post-selected.png',
      fullPage: true
    });
    
    // Fill in common name
    await page.fill('#common_name', `Test QR Code for ${postTitle}`);
    
    // Fill in referral code
    await page.fill('#referral_code', `test-${postId}`);
    
    // Take screenshot before submitting
    await page.screenshot({
      path: 'test-results/before-submit.png',
      fullPage: true
    });
    
    // Get the nonce value
    const nonce = await page.$eval('#qrc_nonce', (el: HTMLInputElement) => el.value);
    
    // Submit the form with nonce
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle', timeout: 90000 }),
      page.click('input[type="submit"]')
    ]);
    
    // Take screenshot after form submission
    await page.screenshot({
      path: 'test-results/after-submit.png',
      fullPage: true
    });

    // Wait for redirect to list page
    await page.waitForURL('**/wp-admin/admin.php?page=qrc-links&message=created', {
      timeout: 90000,
      waitUntil: 'networkidle'
    });
    
    // Take screenshot after redirect
    await page.screenshot({
      path: 'test-results/qr-list-page.png',
      fullPage: true
    });
    
    // Verify QR code exists in list
    await page.waitForSelector('.qr-code-preview img', {
      state: 'visible',
      timeout: 90000
    });
    
    console.log('QR code generated successfully.');
  } catch (error) {
    console.error('Error generating QR code:', error);
    await page.screenshot({
      path: `test-results/qr-generation-error-${Date.now()}.png`,
      fullPage: true
    });
    throw error;
  }
}

// Test: Basic WordPress site accessibility
test('WordPress site is accessible and login works', async ({ page }) => {
  await login(page);
  const response = await verifyPageLoad(page, '/wp-admin');
  expect(response?.ok()).toBeTruthy();
  
  const title = await page.title();
  expect(title).toContain('Dashboard');
});

// Test: QR Code generation in post editor
test('Can generate QR code in post editor', async ({ page }) => {
  test.setTimeout(180000); // 3 minutes timeout
  
  try {
    // First ensure we're logged in
    await login(page);
    
    console.log('Navigating to new post page...');
    await verifyPageLoad(page, '/wp-admin/post-new.php');

    // Dismiss the block editor welcome modal if present
    const welcomeModal = await page.$('text=Welcome to the editor');
    if (welcomeModal) {
      const closeButton = await page.$('button:has-text("Close")');
      if (closeButton) {
        await closeButton.click();
        await page.waitForSelector('text=Welcome to the editor', { state: 'detached', timeout: 5000 });
      }
    }
    
    // Add debug screenshot
    await page.screenshot({ 
      path: 'test-results/new-post-page-initial.png',
      fullPage: true 
    });
    
    // Detect which editor is loaded
    const editorType = await detectEditor(page);
    
    // Fill in the title and content based on editor type
    const postTitle = 'QR Code Test Post';
    console.log('Filling post title...');
    if (editorType === 'block') {
      // Block editor
      await page.click('[role="textbox"][aria-label="Add title"]');
      await page.keyboard.type(postTitle);
      
      // Add a paragraph block
      await page.keyboard.press('Enter');
      await page.keyboard.type('This is a test post for QR code generation.');
      
      // Click publish
      await page.click('button.editor-post-publish-button__button');
      
      // If it's the first time publishing, we need to confirm
      const prePublishCheck = await page.$('.editor-post-publish-panel__header');
      if (prePublishCheck) {
        await page.click('.editor-post-publish-button');
      }
      
      // Wait for publish confirmation
      await page.waitForSelector('.components-snackbar', {
        timeout: 90000,
        state: 'visible'
      });
    } else {
      // Classic editor
      await page.waitForSelector('#title', { timeout: 90000 });
      await page.fill('#title', postTitle);
      
      // Fill in content using TinyMCE
      await fillTinyMCEContent(page, 'This is a test post for QR code generation.');
      
      // Click publish
      await page.click('#publish');
      
      // Wait for publish confirmation
      await page.waitForSelector('#message.updated', {
        timeout: 90000,
        state: 'visible'
      });
    }
    
    // Add debug screenshot
    await page.screenshot({ 
      path: 'test-results/post-published.png',
      fullPage: true 
    });
    
    // Get post ID from URL
    const url = page.url();
    const postId = url.match(/post=(\d+)/)?.[1] || url.match(/\/?p=(\d+)/)?.[1];
    
    if (!postId) {
      throw new Error('Failed to get post ID from URL: ' + url);
    }
    
    console.log(`Post ID: ${postId}`);
    
    // Generate QR code for the post
    await generateQRCode(page, postId, postTitle);
    
    // Navigate to QR code list page
    console.log('Navigating to QR code list page...');
    await verifyPageLoad(page, '/wp-admin/admin.php?page=qrc-links');
    
    // Verify QR code exists
    console.log('Checking for QR code...');
    await page.waitForSelector('.qr-code-preview img', {
      state: 'visible',
      timeout: 90000
    });
    
    // Take final screenshot
    await page.screenshot({
      path: 'test-results/qr-code-generated.png',
      fullPage: true
    });
    
    // Verify QR code image exists
    const qrImage = await page.$('.qr-code-preview img');
    expect(qrImage).toBeTruthy();
    
  } catch (error) {
    console.error('Test failed:', error);
    await page.screenshot({
      path: `test-results/test-failure-${Date.now()}.png`,
      fullPage: true
    });
    throw error;
  }
}); 