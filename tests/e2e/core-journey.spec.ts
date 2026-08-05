import { expect, test } from '@playwright/test'

test('core shell supports dashboard navigation, Arabic RTL, and PWA metadata', async ({ page }) => {
  await page.goto('/dashboard')
  await expect(page.getByRole('heading', { name: /Household budgeting foundation|منصة ميزانية الأسرة/ })).toBeVisible()

  await expect(page.locator('link[rel="manifest"]')).toHaveAttribute('href', '/build/manifest.webmanifest')
  const manifestResponse = await page.request.get('/build/manifest.webmanifest')
  expect(manifestResponse.ok()).toBeTruthy()
  const manifest = await manifestResponse.json()
  expect(manifest.display).toBe('standalone')
  expect(manifest.start_url).toBe('/dashboard')

  await page.getByLabel(/Language|اللغة/).selectOption('ar')
  await expect(page.locator('html')).toHaveAttribute('dir', 'rtl')
  await expect(page.getByRole('heading', { name: 'منصة ميزانية الأسرة' })).toBeVisible()

  await page.getByRole('link', { name: /الحسابات|Accounts/ }).click()
  await expect(page).toHaveURL(/\/accounts/)

  await page.getByRole('link', { name: /التقارير|Reports/ }).click()
  await expect(page).toHaveURL(/\/reports/)

  await page.getByRole('link', { name: /سجل المعاملات|Transaction history/ }).click()
  await expect(page).toHaveURL(/\/transactions/)

  await page.getByRole('link', { name: /الأمان والجلسات|Security and sessions/ }).click()
  await expect(page).toHaveURL(/\/security/)
})
