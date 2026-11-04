import { test, expect } from '@playwright/test';

test.describe('JUGAJUG Enterprise Web Platform — Browser E2E Test Suite', () => {

  test('1. Authentication: Login Page Renders Correctly in Headless Chrome', async ({ page }) => {
    const response = await page.goto('/login');
    expect(response?.status()).toBe(200);

    // Verify form elements exist
    await expect(page.locator('form')).toBeVisible();
    await expect(page.locator('input[type="password"]')).toBeVisible();
  });

  test('2. Authentication: Registration Page Renders Form Fields', async ({ page }) => {
    const response = await page.goto('/register');
    expect(response?.status()).toBe(200);

    await expect(page.locator('form')).toBeVisible();
    await expect(page.locator('#regPassword')).toBeVisible();
    await expect(page.locator('#regPasswordConfirm')).toBeVisible();
  });

  test('3. Administration: Dedicated Admin Login Page Renders', async ({ page }) => {
    const response = await page.goto('/admin/login');
    expect(response?.status()).toBe(200);

    await expect(page.locator('form')).toBeVisible();
    await expect(page.locator('input[type="password"]')).toBeVisible();
  });

  test('4. Social Ecosystem: Groups Discovery Page Accessible', async ({ page }) => {
    const response = await page.goto('/groups');
    // Either renders page (200) or redirects to login if unauthenticated (302)
    expect([200, 302]).toContain(response?.status());
  });

  test('5. Business Ecosystem: Business Pages Hub Accessible', async ({ page }) => {
    const response = await page.goto('/pages');
    expect([200, 302]).toContain(response?.status());
  });

  test('6. Commerce Ecosystem: Marketplace Hub Accessible', async ({ page }) => {
    const response = await page.goto('/marketplace');
    expect([200, 302]).toContain(response?.status());
  });

  test('7. Recruitment Ecosystem: Professional Jobs Board Accessible', async ({ page }) => {
    const response = await page.goto('/jobs');
    expect([200, 302]).toContain(response?.status());
  });

  test('8. Real-time Messenger Hub Accessible', async ({ page }) => {
    const response = await page.goto('/messenger');
    expect([200, 302]).toContain(response?.status());
  });

  test('9. System Health & Infrastructure Probe Responds with Valid JSON', async ({ page }) => {
    const response = await page.goto('/api/v1/health');
    expect(response?.status()).toBe(200);

    const body = await response?.json();
    expect(body.success).toBe(true);
    expect(body.data.services.database.status).toBe('healthy');
  });

  test('10. Learning & Course Marketplace API Accessible in Browser Context', async ({ page }) => {
    const response = await page.goto('/api/v1/learning/courses');
    // Sanctum auth required on courses index
    expect([200, 401]).toContain(response?.status());
  });
});
