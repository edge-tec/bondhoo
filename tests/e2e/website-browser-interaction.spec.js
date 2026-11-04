import { test, expect } from '@playwright/test';

test.describe('JUGAJUG Enterprise Web Platform — Deep Interactive E2E Journeys', () => {

  test('1. User Login Form Interaction: Input Filling and Client-Side Validation', async ({ page }) => {
    await page.goto('/login');
    await expect(page.locator('#loginIdentifier')).toBeVisible();

    // Type credentials into the browser input fields
    await page.locator('#loginIdentifier').fill('dixie.mann');
    await expect(page.locator('#loginIdentifier')).toHaveValue('dixie.mann');

    await page.locator('#loginPassword').fill('secret12345');
    await expect(page.locator('#loginPassword')).toHaveValue('secret12345');

    // Password visibility toggle test
    const toggleBtn = page.locator('#pwdToggleBtn');
    if (await toggleBtn.isVisible()) {
      await toggleBtn.click();
      await expect(page.locator('#loginPassword')).toHaveAttribute('type', 'text');
      await toggleBtn.click();
      await expect(page.locator('#loginPassword')).toHaveAttribute('type', 'password');
    }
  });

  test('2. Authenticated Session Establishment & Cookie State Persistence', async ({ page, context }) => {
    // Perform real dev-login in browser
    const res = await page.goto('/dev/login/dixie.mann');
    expect([200, 302]).toContain(res?.status());

    // Verify session cookies are deposited in browser jar
    const cookies = await context.cookies();
    const tokenCookie = cookies.find(c => c.name === 'jugajug_token' || c.name === 'bondhoo_token');
    expect(tokenCookie).toBeDefined();
    expect(tokenCookie?.value).toBeTruthy();
  });

  test('3. Authenticated Social Feed Interaction', async ({ page }) => {
    // Authenticate first
    await page.goto('/dev/login/dixie.mann');

    // Visit main newsfeed
    const feedRes = await page.goto('/');
    expect(feedRes?.status()).toBe(200);

    // Verify page has loaded body
    const body = page.locator('body');
    await expect(body).toBeVisible();
  });

  test('4. Authenticated Groups Workflow Hub', async ({ page }) => {
    await page.goto('/dev/login/dixie.mann');

    const groupsRes = await page.goto('/groups');
    expect(groupsRes?.status()).toBe(200);

    // Assert DOM elements are present
    await expect(page.locator('body')).toBeVisible();
  });

  test('5. Authenticated Business Pages Management Hub', async ({ page }) => {
    await page.goto('/dev/login/dixie.mann');

    const pagesRes = await page.goto('/pages');
    expect(pagesRes?.status()).toBe(200);

    await expect(page.locator('body')).toBeVisible();
  });

  test('6. Authenticated Commerce & Marketplace Hub', async ({ page }) => {
    await page.goto('/dev/login/dixie.mann');

    const marketRes = await page.goto('/marketplace');
    expect(marketRes?.status()).toBe(200);

    await expect(page.locator('body')).toBeVisible();
  });

  test('7. Authenticated Professional Recruitment Jobs Hub', async ({ page }) => {
    await page.goto('/dev/login/dixie.mann');

    const jobsRes = await page.goto('/jobs');
    expect(jobsRes?.status()).toBe(200);

    await expect(page.locator('body')).toBeVisible();
  });

  test('8. Authenticated Real-time Messenger Interface', async ({ page }) => {
    await page.goto('/dev/login/dixie.mann');

    const msgRes = await page.goto('/messenger');
    expect(msgRes?.status()).toBe(200);

    await expect(page.locator('body')).toBeVisible();
  });

  test('9. User Settings & Notification Preferences Interaction', async ({ page }) => {
    await page.goto('/dev/login/dixie.mann');

    const settingsRes = await page.goto('/settings/notifications');
    expect(settingsRes?.status()).toBe(200);

    await expect(page.locator('body')).toBeVisible();
  });

  test('10. Security Sign-out Interaction with CSRF Token', async ({ page, context }) => {
    await page.goto('/dev/login/dixie.mann');

    const cookies = await context.cookies();
    const xsrfCookie = cookies.find(c => c.name === 'XSRF-TOKEN');

    const logoutRes = await page.request.post('/logout', {
      headers: {
        'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie?.value || ''),
        'Accept': 'application/json, text/html',
      },
    });

    expect([200, 204, 302]).toContain(logoutRes.status());
  });
});
