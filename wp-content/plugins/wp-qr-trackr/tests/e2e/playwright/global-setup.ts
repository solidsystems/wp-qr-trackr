import { chromium, FullConfig, Page } from '@playwright/test';

const BASE_URL = 'http://wordpress-playwright';

async function waitForWordPress(page: Page): Promise<boolean> {
  let retries = 10;
  while (retries > 0) {
    try {
      await page.goto(`${BASE_URL}/wp-login.php`, {
        waitUntil: 'networkidle',
        timeout: 90000
      });
      
      // Check if we got the login page
      const loginForm = await page.waitForSelector('#loginform', {
        timeout: 30000,
        state: 'visible'
      });
      
      if (loginForm) {
        console.log('WordPress is ready.');
        return true;
      }
    } catch (error: unknown) {
      console.log(`Waiting for WordPress to be ready... (${retries} retries left)`);
      await new Promise(resolve => setTimeout(resolve, 10000)); // Wait 10 seconds
      retries--;
      
      if (retries === 0) {
        throw new Error('WordPress failed to become ready: ' + (error instanceof Error ? error.message : String(error)));
      }
    }
  }
  return false;
}

async function globalSetup(config: FullConfig) {
  const browser = await chromium.launch({
    args: [
      '--disable-dev-shm-usage',
      '--no-sandbox',
      '--disable-setuid-sandbox'
    ]
  });
  
  const context = await browser.newContext({
    ignoreHTTPSErrors: true,
    viewport: { width: 1280, height: 720 }
  });
  
  const page = await context.newPage();

  try {
    // Wait for WordPress to be ready
    await waitForWordPress(page);
    
    console.log('Attempting login...');
    
    // Fill in login credentials
    await page.fill('#user_login', 'trackr');
    await page.fill('#user_pass', 'trackr');
    
    // Click login button and wait for navigation
    await Promise.all([
      page.waitForNavigation({ 
        waitUntil: 'networkidle',
        timeout: 90000
      }),
      page.click('#wp-submit')
    ]);

    // Verify login success
    const adminBar = await page.waitForSelector('#wpadminbar', {
      timeout: 90000,
      state: 'visible'
    });
    
    if (!adminBar) {
      throw new Error('Login failed - admin bar not found');
    }

    console.log('Login successful, saving auth state...');
    
    // Save signed-in state
    await context.storageState({ 
      path: 'playwright/.auth/user.json'
    });
    
    console.log('Global setup completed successfully.');
    
  } catch (error: unknown) {
    console.error('Global setup failed:', error instanceof Error ? error.message : String(error));
    
    // Take error screenshot
    await page.screenshot({ 
      path: 'test-results/global-setup-error.png',
      fullPage: true 
    });
    
    throw error;
  } finally {
    await context.close();
    await browser.close();
  }
}

export default globalSetup; 