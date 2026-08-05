import { expect, test } from '@playwright/test'

test('core shell supports dashboard navigation and RTL locale', async ({ page }) => {
  await page.goto('/dashboard')
  await expect(page.getByRole('heading', { name: /Household budgeting foundation|منصة ميزانية الأسرة/ })).toBeVisible()

  await page.getByLabel(/Language|اللغة/).selectOption('ar')
  await expect(page.locator('html')).toHaveAttribute('dir', 'rtl')

  await page.getByRole('link', { name: /سجل المعاملات|Transaction history/ }).click()
  await expect(page).toHaveURL(/\/transactions/)

  await page.getByRole('link', { name: /الأمان والجلسات|Security and sessions/ }).click()
  await expect(page).toHaveURL(/\/security/)
})
