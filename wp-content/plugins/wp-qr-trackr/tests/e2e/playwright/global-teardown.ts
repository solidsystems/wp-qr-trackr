import { FullConfig } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';

async function globalTeardown(config: FullConfig) {
  // Clean up auth state if it exists
  try {
    const authFile = path.join(process.cwd(), 'playwright/.auth/user.json');
    if (fs.existsSync(authFile)) {
      fs.unlinkSync(authFile);
    }
  } catch (error) {
    console.error('Error cleaning up auth state:', error);
  }

  // Clean up screenshots older than 24 hours
  try {
    const screenshotDir = path.join(process.cwd(), 'test-results');
    if (fs.existsSync(screenshotDir)) {
      const files = fs.readdirSync(screenshotDir);
      const now = new Date().getTime();
      
      for (const file of files) {
        const filePath = path.join(screenshotDir, file);
        const stats = fs.statSync(filePath);
        const age = now - stats.mtime.getTime();
        
        // Delete files older than 24 hours
        if (age > 24 * 60 * 60 * 1000) {
          fs.unlinkSync(filePath);
        }
      }
    }
  } catch (error) {
    console.error('Error cleaning up screenshots:', error);
  }
}

export default globalTeardown; 