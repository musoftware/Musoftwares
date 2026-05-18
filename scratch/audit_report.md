# Frontend Deep Audit Results

### \Components\Chat\ChatWindow.test.jsx
- Line 7: **[Mock/Dummy/Fake]** `// Mock inertia usePage`
- Line 12: **[Mock/Dummy/Fake]** `// Mock axios`
- Line 15: **[Mock/Dummy/Fake]** `// Mock Message component`
- Line 24: **[Mock/Dummy/Fake]** `usePage.mockReturnValue({`
- Line 30: **[Mock/Dummy/Fake]** `// Mock scrollIntoView`
- Line 33: **[Mock/Dummy/Fake]** `// Mock console.error using vi.spyOn to allow restoration`
- Line 34: **[Mock/Dummy/Fake]** `consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});`
- Line 38: **[Mock/Dummy/Fake]** `vi.restoreAllMocks();`
- Line 43: **[Mock/Dummy/Fake]** `axios.get.mockRejectedValueOnce(new Error(errorMessage));`

### \Layouts\PublicLayout.tsx
- Line 324: **[Fake Link]** `<a href="#" className="text-sm hover:text-slate-950 transition-colors">Privacy P`
- Line 327: **[Fake Link]** `<a href="#" className="text-sm hover:text-slate-950 transition-colors">Terms of `
- Line 330: **[Fake Link]** `<a href="#" className="text-sm hover:text-slate-950 transition-colors">Escrow Pr`
- Line 333: **[Fake Link]** `<a href="#" className="text-sm hover:text-slate-950 transition-colors">Security `

### \Pages\Admin\Clients\Show.jsx
- Line 6: **[Mock/Dummy/Fake]** `// Implement impersonation logic later, mock for now`

### \Pages\Admin\Dashboard.jsx
- Line 52: **[Fake Link]** `<Link href="#">Review</Link>`
- Line 244: **[Fake Link]** `<Link href="#"><Settings className="w-3.5 h-3.5 mr-1.5" /> Settings</Link>`

### \Pages\Booking\Public\Show.tsx
- Line 81: **[Mock/Dummy/Fake]** `{/* Placeholder for offset days if month doesn't start on Sunday - simplified fo`

### \Pages\ERP\UpgradePreview.tsx
- Line 82: **[Mock/Dummy/Fake]** `{/* Capabilities grid with locked screen visual mockups */}`
- Line 98: **[Mock/Dummy/Fake]** `{/* Blurred Mockup Visual */}`

### \Pages\Financial\WalletTransfer\Create.jsx
- Line 388: **[Mock/Dummy/Fake]** `{/* Summary Invoice Receipt Mock */}`

### \Pages\Freelance\Contracts\Show.tsx
- Line 23: **[Mock/Dummy/Fake]** `// Mock contract data if not provided`

### \Pages\Freelance\Dashboard.tsx
- Line 44: **[Mock/Dummy/Fake]** `id: 'mock_1',`

### \Pages\Freelance\Jobs\Create.jsx
- Line 102: **[Mock/Dummy/Fake]** `{/* Mock Toolbar */}`

### \Pages\Freelance\Jobs\Show.tsx
- Line 21: **[Mock/Dummy/Fake]** `// Mock fallback if job is missing`

### \Pages\Marketplace\Browse.tsx
- Line 145: **[Mock/Dummy/Fake]** `// Mock rating`

### \Pages\Marketplace\Orders\Index.tsx
- Line 108: **[Mock/Dummy/Fake]** `// Calculate deadline mock if not present`

### \Pages\Marketplace\Orders\Show.tsx
- Line 60: **[Mock/Dummy/Fake]** `// Dummy logic for calculating commissions`

### \Pages\Marketplace\Services\Show.tsx
- Line 7: **[Mock/Dummy/Fake]** `const userBalance = auth?.user?.balance || 0; // Mock balance if not properly pa`
- Line 9: **[Mock/Dummy/Fake]** `// Default mock balance for display purposes in case it's not set`

