import { expect, test } from '@playwright/test'

test('core shell supports login, dashboard navigation, Arabic RTL, and PWA metadata', async ({ page }) => {
  await page.goto('/dashboard')
  await expect(page).toHaveURL(/\/login/)
  await expect(page.getByRole('heading', { name: /Log in|تسجيل الدخول/ })).toBeVisible()

  await page.getByRole('button', { name: /Log in|تسجيل الدخول/ }).click()
  await expect(page).toHaveURL(/\/dashboard/)
  await expect(page.getByRole('heading', { name: /Household budgeting foundation|منصة ميزانية الأسرة/ })).toBeVisible()

  await expect(page.getByRole('heading', { name: /Split payment/ })).toBeVisible()
  await expect(page.getByRole('button', { name: /Add source/ })).toBeVisible()
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

  await page.getByRole('link', { name: /الفئات|Categories/ }).click()
  await expect(page).toHaveURL(/\/categories/)

  await page.getByRole('link', { name: /الإيصالات|Receipts/ }).click()
  await expect(page).toHaveURL(/\/receipts/)

  await page.getByRole('link', { name: /التقارير|Reports/ }).click()
  await expect(page).toHaveURL(/\/reports/)

  await page.getByRole('link', { name: /الإشعارات|Notifications/ }).click()
  await expect(page).toHaveURL(/\/notifications/)

  await page.getByRole('link', { name: /المزامنة دون اتصال|Offline sync/ }).click()
  await expect(page).toHaveURL(/\/offline-sync/)

  await page.getByRole('link', { name: /سجل المعاملات|Transaction history/ }).click()
  await expect(page).toHaveURL(/\/transactions/)

  await page.getByRole('link', { name: /الإعدادات|Settings/ }).click()
  await expect(page).toHaveURL(/\/settings/)

  await page.getByRole('navigation').getByRole('link', { name: /الأمان والجلسات|Security and sessions/ }).click()
  await expect(page).toHaveURL(/\/security/)
})

test('split payment can be created, edited, and partially refunded through the live API', async ({ request }, testInfo) => {
  test.skip(testInfo.project.name !== 'chromium', 'The API financial journey only needs one browser project.')
  const login = await request.post('/api/auth/login', {
    data: { email: 'owner@example.com', password: 'password', device_name: 'playwright split payment' }
  })
  expect(login.ok(), await login.text()).toBeTruthy()
  const { token } = await login.json()
  const headers = { Authorization: `Bearer ${token}`, Accept: 'application/json' }

  const me = await request.get('/api/auth/me', { headers })
  expect(me.ok()).toBeTruthy()
  const profile = await me.json()
  const householdId = profile.household.id
  const firstAccount = profile.household.accounts[0]

  const accountsResponse = await request.get(`/api/households/${householdId}/accounts`, { headers })
  expect(accountsResponse.ok()).toBeTruthy()
  const accountsPayload = await accountsResponse.json()
  const accountType = accountsPayload.account_types[0]
  const currency = accountsPayload.currencies.find((item: { id: number }) => item.id === firstAccount.currency_id) ?? accountsPayload.currencies[0]

  const secondAccountResponse = await request.post(`/api/households/${householdId}/accounts`, {
    headers,
    data: {
      account_type_id: accountType.id,
      currency_id: currency.id,
      name: `E2E Split Source ${Date.now()}`,
      opening_balance_minor: 0,
      is_shared: true,
      is_active: true
    }
  })
  expect(secondAccountResponse.ok()).toBeTruthy()
  const secondAccount = (await secondAccountResponse.json()).data

  const splitResponse = await request.post(`/api/households/${householdId}/transactions/split-expense`, {
    headers,
    data: {
      currency_id: currency.id,
      amount_minor: 10000,
      base_amount_minor: 10000,
      description: 'E2E split payment',
      transaction_date: '2026-08-10',
      payment_legs: [
        { account_id: firstAccount.id, amount_minor: 4000, base_amount_minor: 4000 },
        { account_id: secondAccount.id, amount_minor: 6000, base_amount_minor: 6000 }
      ]
    }
  })
  expect(splitResponse.ok(), await splitResponse.text()).toBeTruthy()
  const split = (await splitResponse.json()).data
  expect(split.payment_legs).toHaveLength(2)

  const editResponse = await request.put(`/api/households/${householdId}/transactions/${split.id}/payment-legs`, {
    headers,
    data: {
      version: split.version,
      payment_legs: [
        { account_id: firstAccount.id, amount_minor: 2500, base_amount_minor: 2500 },
        { account_id: secondAccount.id, amount_minor: 7500, base_amount_minor: 7500 }
      ]
    }
  })
  expect(editResponse.ok(), await editResponse.text()).toBeTruthy()
  const edited = (await editResponse.json()).data
  expect(edited.version).toBe(split.version + 1)
  expect(edited.payment_legs.reduce((sum: number, leg: { base_amount_minor: number }) => sum + leg.base_amount_minor, 0)).toBe(10000)

  const refundResponse = await request.post(`/api/households/${householdId}/transactions/${split.id}/refunds`, {
    headers,
    data: {
      account_id: firstAccount.id,
      amount_minor: 3000,
      transaction_date: '2026-08-11',
      description: 'E2E partial refund'
    }
  })
  expect(refundResponse.ok(), await refundResponse.text()).toBeTruthy()
  const refund = (await refundResponse.json()).data
  expect(refund.type).toBe('refund')
  expect(refund.amount_minor).toBe(3000)
})
